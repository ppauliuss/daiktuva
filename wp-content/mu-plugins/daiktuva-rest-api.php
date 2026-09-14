<?php
/**
 * Plugin Name: Daiktuva REST API
 * Description: Public read-only listings feed plus an authenticated status endpoint for Daiktuva.
 * Version: 0.1.0
 *
 * Routes (namespace daiktuva/v1):
 *   GET  /listings                 - paginated catalog feed, no phone numbers.
 *   GET  /listings/<id>            - single listing, includes the seller phone.
 *   POST /listings/<id>/status     - set active|reserved|sold, requires edit_post on the product.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Daiktuva_Rest_Api
{
    private const REST_NAMESPACE = 'daiktuva/v1';
    private const STATUSES = ['active', 'reserved', 'sold'];
    private const DEFAULT_PHONE_OPTION = 'daiktuva_default_phone';

    public static function boot(): void
    {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/listings', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'get_listings'],
            'permission_callback' => '__return_true',
            'args' => [
                'status' => [
                    'type' => 'string',
                    'enum' => self::STATUSES,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'category' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_title',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'search' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 50,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/listings/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'get_listing'],
            'permission_callback' => '__return_true',
            'args' => ['id' => self::id_arg()],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/listings/(?P<id>\d+)/status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_status'],
            'permission_callback' => [self::class, 'can_edit_listing'],
            'args' => [
                'id' => self::id_arg(),
                'status' => [
                    'type' => 'string',
                    'required' => true,
                    'enum' => self::STATUSES,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
            ],
        ]);
    }

    public static function get_listings(WP_REST_Request $request): WP_REST_Response
    {
        $per_page = (int) $request->get_param('per_page');
        $page = (int) $request->get_param('page');

        // WC_Product_Query silently drops meta_query, so the status filter needs WP_Query.
        $query = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
        ];

        $status = (string) $request->get_param('status');
        if ($status !== '') {
            // Legacy listings were saved before the meta existed; treat them as active.
            $query['meta_query'] = $status === 'active'
                ? [
                    'relation' => 'OR',
                    ['key' => '_daiktuva_status', 'value' => 'active'],
                    ['key' => '_daiktuva_status', 'compare' => 'NOT EXISTS'],
                    ['key' => '_daiktuva_status', 'value' => '', 'compare' => '='],
                ]
                : [['key' => '_daiktuva_status', 'value' => $status]];
        }

        $category = (string) $request->get_param('category');
        if ($category !== '') {
            $query['tax_query'] = [[
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category,
            ]];
        }

        $search = (string) $request->get_param('search');
        if ($search !== '') {
            $query['s'] = $search;
        }

        $results = new WP_Query($query);

        $items = [];
        foreach ($results->posts as $post) {
            $product = wc_get_product($post);

            if ($product instanceof WC_Product) {
                $items[] = self::prepare_listing($product, false);
            }
        }

        $response = new WP_REST_Response($items);
        $response->header('X-WP-Total', (string) $results->found_posts);
        $response->header('X-WP-TotalPages', (string) $results->max_num_pages);

        return $response;
    }

    public static function get_listing(WP_REST_Request $request)
    {
        $product = self::find_product((int) $request->get_param('id'));

        if ($product instanceof WP_Error) {
            return $product;
        }

        return new WP_REST_Response(self::prepare_listing($product, true));
    }

    public static function update_status(WP_REST_Request $request)
    {
        $product = self::find_product((int) $request->get_param('id'));

        if ($product instanceof WP_Error) {
            return $product;
        }

        $product->update_meta_data('_daiktuva_status', (string) $request->get_param('status'));
        $product->save();

        self::purge_cache($product->get_id());

        return new WP_REST_Response(self::prepare_listing($product, true));
    }

    public static function can_edit_listing(WP_REST_Request $request): bool
    {
        return current_user_can('edit_post', (int) $request->get_param('id'));
    }

    private static function id_arg(): array
    {
        return [
            'type' => 'integer',
            'required' => true,
            'sanitize_callback' => 'absint',
            'validate_callback' => static fn ($value): bool => absint($value) > 0,
        ];
    }

    /**
     * @return WC_Product|WP_Error
     */
    private static function find_product(int $id)
    {
        $product = wc_get_product($id);

        if (!$product instanceof WC_Product || $product->get_status() !== 'publish') {
            return new WP_Error(
                'daiktuva_listing_not_found',
                __('Skelbimas nerastas.', 'daiktuva'),
                ['status' => 404]
            );
        }

        return $product;
    }

    private static function prepare_listing(WC_Product $product, bool $with_phone): array
    {
        $id = $product->get_id();
        $status = (string) get_post_meta($id, '_daiktuva_status', true);

        if (!in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        $data = [
            'id' => $id,
            'title' => $product->get_name(),
            'permalink' => (string) get_permalink($id),
            'price' => $product->get_price() === '' ? null : (float) $product->get_price(),
            'currency' => get_woocommerce_currency(),
            'status' => $status,
            'status_label' => self::status_label($status),
            'categories' => wp_list_pluck(
                wp_get_post_terms($id, 'product_cat', ['fields' => 'all']) ?: [],
                'slug'
            ),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: null,
            'date_created' => $product->get_date_created()?->date(DATE_ATOM),
        ];

        // The phone is public on the product page, but a bulk feed of numbers is a scraping target.
        if ($with_phone) {
            $data['phone'] = self::listing_phone($id);
        }

        return $data;
    }

    private static function listing_phone(int $product_id): string
    {
        $phone = trim((string) get_post_meta($product_id, '_daiktuva_phone', true));

        if ($phone !== '') {
            return $phone;
        }

        return trim((string) get_option(self::DEFAULT_PHONE_OPTION, ''));
    }

    private static function status_label(string $status): string
    {
        return match ($status) {
            'reserved' => __('Rezervuota', 'daiktuva'),
            'sold' => __('Parduota', 'daiktuva'),
            default => __('Aktyvi', 'daiktuva'),
        };
    }

    /* The catalog is served from Cache Enabler; a stale badge would outlive the change. */
    private static function purge_cache(int $product_id): void
    {
        if (class_exists('Cache_Enabler')) {
            Cache_Enabler::clear_page_cache_by_post_id($product_id);
        }
    }
}

add_action('plugins_loaded', static function (): void {
    if (class_exists('WooCommerce')) {
        Daiktuva_Rest_Api::boot();
    }
});
