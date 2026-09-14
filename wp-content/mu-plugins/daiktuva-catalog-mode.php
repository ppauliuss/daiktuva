<?php
/**
 * Plugin Name: Daiktuva Catalog Mode
 * Description: Turns WooCommerce into a phone-first listings catalog for Daiktuva.
 * Version: 0.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Daiktuva_Catalog_Mode
{
    private const DEFAULT_PHONE_OPTION = 'daiktuva_default_phone';

    public static function boot(): void
    {
        add_filter('woocommerce_is_purchasable', '__return_false');
        add_filter('woocommerce_product_add_to_cart_text', fn () => __('Skambinti', 'daiktuva'));
        add_filter('woocommerce_loop_add_to_cart_link', [self::class, 'loop_contact_button'], 20, 2);
        add_action('woocommerce_single_product_summary', [self::class, 'single_contact_panel'], 31);
        add_action('woocommerce_after_shop_loop_item_title', [self::class, 'loop_status_badge'], 6);
        add_action('woocommerce_product_options_general_product_data', [self::class, 'product_fields']);
        add_action('woocommerce_admin_process_product_object', [self::class, 'save_product_fields']);
        add_action('template_redirect', [self::class, 'block_checkout_pages']);
        add_filter('woocommerce_get_catalog_ordering_args', [self::class, 'catalog_ordering_args'], 99);
        add_action('admin_menu', [self::class, 'settings_page']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('wp_head', [self::class, 'styles']);
    }

    public static function loop_contact_button(string $html, WC_Product $product): string
    {
        if ('sold' === (string) get_post_meta($product->get_id(), '_daiktuva_status', true)) {
            return '<span class="button daiktuva-disabled" aria-disabled="true">' . esc_html__('Parduota', 'daiktuva') . '</span>';
        }

        $phone = self::product_phone($product->get_id());

        if ($phone === '') {
            return sprintf(
                '<a class="button daiktuva-contact-button" href="%s">%s</a>',
                esc_url(home_url('/kontaktai/')),
                esc_html__('Teirautis', 'daiktuva')
            );
        }

        return self::call_button_html($phone, esc_html__('Skambinti', 'daiktuva'));
    }

    /**
     * Katalogas: numatytoji tvarka = vėliausiai atnaujinti pirmiausia (2026-08-20).
     * Woo default rikiuoja pagal menu_order/date (paskelbimo datą) — pardavėjas
     * papildęs ar redagavęs skelbimą turi iššokti į viršų. Keičiama TIK
     * numatytoji tvarka; lankytojo pasirinkta (kaina, įvertinimas) lieka.
     */
    public static function catalog_ordering_args(array $args): array
    {
        $orderby_value = isset($_GET['orderby'])
            ? wc_clean(wp_unslash((string) $_GET['orderby']))
            : get_option('woocommerce_default_catalog_orderby', 'menu_order');

        if (in_array($orderby_value, ['', 'menu_order', 'date'], true)) {
            $args['orderby'] = 'modified';
            $args['order']   = 'DESC';
        }

        return $args;
    }

    public static function single_contact_panel(): void
    {
        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $phone = self::product_phone($product->get_id());
        $raw_status = (string) get_post_meta($product->get_id(), '_daiktuva_status', true);
        $status = self::status_label($raw_status);
        if ($raw_status === 'sold') {
            $status .= self::sold_speed_suffix($product->get_id());
        }

        echo '<div class="daiktuva-contact-panel">';
        echo '<div class="daiktuva-status daiktuva-status-' . esc_attr($raw_status ?: 'active') . '">' . esc_html($status) . '</div>';

        if ($raw_status === 'sold') {
            echo '<p class="daiktuva-sold-note">' . esc_html__('Šis skelbimas jau parduotas. Peržiūrėkite panašius aktyvius skelbimus žemiau.', 'daiktuva') . '</p>';
            echo '</div>';
            return;
        }

        if ($phone !== '') {
            echo self::call_button_html($phone, esc_html__('Skambinti pardavejui', 'daiktuva'));
            echo self::whatsapp_button_html(
                $phone,
                $product->get_name(),
                (string) get_permalink($product->get_id()),
                esc_html__('Susisiekti per WhatsApp', 'daiktuva')
            );
            echo '<div class="daiktuva-phone">' . esc_html($phone) . '</div>';
        } else {
            echo '<a class="button daiktuva-contact-button" href="' . esc_url(home_url('/kontaktai/')) . '">' . esc_html__('Teirautis per kontaktus', 'daiktuva') . '</a>';
            echo '<div class="daiktuva-phone">' . esc_html__('Telefono numeris dar nepridetas.', 'daiktuva') . '</div>';
        }

        echo '</div>';
    }

    public static function loop_status_badge(): void
    {
        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $status = (string) get_post_meta($product->get_id(), '_daiktuva_status', true);

        if ($status === '' || $status === 'active') {
            return;
        }

        $label = self::status_label($status);
        if ($status === 'sold') {
            $label .= self::sold_speed_suffix($product->get_id());
        }

        echo '<div class="daiktuva-listing-status is-' . esc_attr($status) . '">' . esc_html($label) . '</div>';
    }

    public static function product_fields(): void
    {
        woocommerce_wp_text_input([
            'id' => '_daiktuva_phone',
            'label' => __('Pardavejo telefonas', 'daiktuva'),
            'placeholder' => '+370...',
            'desc_tip' => true,
            'description' => __('Jei palikta tuscia, naudojamas bendras Daiktuva telefonas.', 'daiktuva'),
        ]);

        woocommerce_wp_select([
            'id' => '_daiktuva_status',
            'label' => __('Prekes busena', 'daiktuva'),
            'options' => [
                'active' => __('Aktyvi', 'daiktuva'),
                'reserved' => __('Rezervuota', 'daiktuva'),
                'sold' => __('Parduota', 'daiktuva'),
            ],
        ]);
    }

    public static function save_product_fields(WC_Product $product): void
    {
        $phone = isset($_POST['_daiktuva_phone']) ? sanitize_text_field(wp_unslash($_POST['_daiktuva_phone'])) : '';
        $status = isset($_POST['_daiktuva_status']) ? sanitize_key(wp_unslash($_POST['_daiktuva_status'])) : 'active';

        if (!in_array($status, ['active', 'reserved', 'sold'], true)) {
            $status = 'active';
        }

        $product->update_meta_data('_daiktuva_phone', $phone);
        $product->update_meta_data('_daiktuva_status', $status);
    }

    public static function block_checkout_pages(): void
    {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        if ((function_exists('is_cart') && is_cart()) || (function_exists('is_checkout') && is_checkout())) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }

    public static function settings_page(): void
    {
        add_options_page(
            __('Daiktuva', 'daiktuva'),
            __('Daiktuva', 'daiktuva'),
            'manage_options',
            'daiktuva-settings',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting('daiktuva_settings', self::DEFAULT_PHONE_OPTION, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
    }

    public static function render_settings_page(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Daiktuva nustatymai', 'daiktuva') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('daiktuva_settings');
        echo '<table class="form-table" role="presentation"><tr>';
        echo '<th scope="row"><label for="' . esc_attr(self::DEFAULT_PHONE_OPTION) . '">' . esc_html__('Bendras telefonas', 'daiktuva') . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="' . esc_attr(self::DEFAULT_PHONE_OPTION) . '" name="' . esc_attr(self::DEFAULT_PHONE_OPTION) . '" value="' . esc_attr((string) get_option(self::DEFAULT_PHONE_OPTION, '')) . '" placeholder="+370..." /></td>';
        echo '</tr></table>';
        submit_button();
        echo '</form></div>';
    }

    public static function styles(): void
    {
        echo '<style>
            .daiktuva-contact-panel{border:1px solid #d7dde5;padding:16px;margin:18px 0;background:#f7f8f9}
            .daiktuva-status,.daiktuva-listing-status{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0;color:#0F4C81;margin:8px 0}
            .daiktuva-status-sold{color:#16a34a}
            .daiktuva-status-reserved{color:#d97706}
            .daiktuva-sold-note{margin:8px 0 0;color:#475569;line-height:1.5}
            /* Kortelės statuso ženkliukas = overlay ant nuotraukos kampo */
            ul.products li.product,.wc-block-grid__product,li.product{position:relative}
            .daiktuva-listing-status{position:absolute;top:10px;left:10px;z-index:6;margin:0;padding:5px 10px;border-radius:9px;font-size:12px;font-weight:800;letter-spacing:.01em;text-transform:none;color:#fff;background:#0F4C81;box-shadow:0 2px 7px rgba(0,0,0,.28);max-width:calc(100% - 20px)}
            .daiktuva-listing-status.is-sold{background:#16a34a}
            .daiktuva-listing-status.is-reserved{background:#d97706}
            .daiktuva-phone{margin-top:10px;font-weight:700}
            .daiktuva-call-button,.daiktuva-contact-button{background:#0F4C81!important;color:#fff!important;border-color:#0F4C81!important}
            .daiktuva-call-button:hover,.daiktuva-contact-button:hover{background:#0B3760!important;border-color:#0B3760!important}
            .daiktuva-disabled{background:#e2e8f0!important;border-color:#e2e8f0!important;color:#475569!important;cursor:default;pointer-events:none}
            .daiktuva-whatsapp-button{background:#25D366!important;color:#fff!important;border-color:#25D366!important;margin-left:8px}
            .daiktuva-whatsapp-button:hover{background:#1DA851!important;border-color:#1DA851!important}
        </style>';
    }

    private static function product_phone(int $product_id): string
    {
        $phone = trim((string) get_post_meta($product_id, '_daiktuva_phone', true));

        if ($phone !== '') {
            return $phone;
        }

        return trim((string) get_option(self::DEFAULT_PHONE_OPTION, ''));
    }

    private static function phone_href(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone) ?: $phone;
    }

    private static function whatsapp_number(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        // Lithuanian local mobile "8XXXXXXXX" -> "3706XXXXXXX".
        if (strlen($digits) === 9 && $digits[0] === '8') {
            $digits = '370' . substr($digits, 1);
        }
        return $digits;
    }

    private static function whatsapp_button_html(string $phone, string $text, string $url, string $label): string
    {
        $number = self::whatsapp_number($phone);
        if ($number === '') {
            return '';
        }
        $message = trim($text . ' - ' . $url);
        $href = 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
        return sprintf(
            '<a class="button daiktuva-whatsapp-button" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url($href),
            $label
        );
    }

    private static function call_button_html(string $phone, string $label): string
    {
        return sprintf(
            '<a class="button daiktuva-call-button" href="%s">%s</a>',
            esc_url('tel:' . self::phone_href($phone)),
            $label
        );
    }

    private static function status_label(string $status): string
    {
        return match ($status) {
            'reserved' => __('Rezervuota', 'daiktuva'),
            'sold' => __('Parduota', 'daiktuva'),
            default => __('Aktyvi', 'daiktuva'),
        };
    }

    /**
     * „per X" priesaga parduotam skelbimui (kaip greitai pardavė) — socialinis irodymas.
     * Rodoma tik greitiems pardavimams (<= 14 d.), kad neatrodytu prastai.
     */
    public static function sold_speed_suffix(int $product_id): string
    {
        $sold_at = (int) get_post_meta($product_id, '_dk_sold_at', true);
        if ($sold_at <= 0) {
            return '';
        }
        // Greičio override (naudoja demo „parduota" rotatorius); kitaip skaičiuojam publish -> sold.
        $override = (int) get_post_meta($product_id, '_dk_sold_speed', true);
        if ($override > 0) {
            $secs = $override;
        } else {
            $published = (int) get_post_time('U', true, $product_id);
            if ($published <= 0 || $sold_at <= $published) {
                return '';
            }
            $secs = $sold_at - $published;
        }
        if ($secs > 14 * DAY_IN_SECONDS) {
            return '';
        }
        if ($secs < HOUR_IN_SECONDS) {
            $n = max(1, (int) round($secs / MINUTE_IN_SECONDS));
            return sprintf(' per %d min.', $n);
        }
        if ($secs < DAY_IN_SECONDS) {
            $n = max(1, (int) round($secs / HOUR_IN_SECONDS));
            return sprintf(' per %d val.', $n);
        }
        $n = max(1, (int) round($secs / DAY_IN_SECONDS));
        return sprintf(' per %d d.', $n);
    }
}

add_action('plugins_loaded', static function (): void {
    if (class_exists('WooCommerce')) {
        Daiktuva_Catalog_Mode::boot();
    }
});
