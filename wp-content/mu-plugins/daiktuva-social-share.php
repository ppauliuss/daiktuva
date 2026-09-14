<?php
/**
 * Daiktuva social sharing buttons for posts and listings.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', function () {
	?>
	<style>
		.dk-share {
			border-top: 1px solid #e5e7eb;
			margin-top: 28px;
			padding-top: 18px;
		}
		.dk-share__title {
			color: #111827;
			font-size: 15px;
			font-weight: 700;
			margin: 0 0 10px;
		}
		.dk-share__buttons {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}
		.dk-share__button {
			align-items: center;
			background: #f3f4f6;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			color: #111827;
			display: inline-flex;
			font-size: 14px;
			font-weight: 700;
			line-height: 1;
			min-height: 38px;
			padding: 0 12px;
			text-decoration: none;
		}
		.dk-share__button:hover,
		.dk-share__button:focus {
			background: #e5e7eb;
			color: #111827;
			text-decoration: none;
		}
		.dk-share__button--facebook {
			background: #1877f2;
			border-color: #1877f2;
			color: #fff;
		}
		.dk-share__button--facebook:hover,
		.dk-share__button--facebook:focus {
			background: #166fe5;
			color: #fff;
		}
		.dk-share__button--whatsapp {
			background: #16a34a;
			border-color: #16a34a;
			color: #fff;
		}
		.dk-share__button--whatsapp:hover,
		.dk-share__button--whatsapp:focus {
			background: #15803d;
			color: #fff;
		}
	.dk-share__button--messenger{background:#a334fa;border-color:#a334fa;color:#fff;}
@media(hover:hover) and (pointer:fine){.dk-share__button--messenger{display:none;}}
</style>
	<script>
		document.addEventListener('click', function (event) {
			var button = event.target.closest('[data-dk-copy-link]');
			if (!button || !navigator.clipboard) {
				return;
			}

			event.preventDefault();
			navigator.clipboard.writeText(button.getAttribute('data-dk-copy-link')).then(function () {
				var original = button.textContent;
				button.textContent = 'Nukopijuota';
				window.setTimeout(function () {
					button.textContent = original;
				}, 1800);
			});
		});
	</script>
	<?php
} );

add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( array( 'post', 'product' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$url = get_permalink();
	$title = get_the_title();
	$encoded_url = rawurlencode( $url );
	$encoded_text = rawurlencode( $title . ' - ' . $url );

	$share = sprintf(
		'<aside class="dk-share" aria-label="Dalintis"><p class="dk-share__title">Dalintis</p><div class="dk-share__buttons"><a class="dk-share__button dk-share__button--facebook" href="https://www.facebook.com/sharer/sharer.php?u=%1$s" target="_blank" rel="noopener noreferrer">Facebook</a><a class="dk-share__button dk-share__button--messenger" href="fb-messenger://share/?link=%1$s">Messenger</a><a class="dk-share__button dk-share__button--whatsapp" href="https://api.whatsapp.com/send?text=%2$s" target="_blank" rel="noopener noreferrer">WhatsApp</a><a class="dk-share__button" href="mailto:?subject=%3$s&body=%2$s">El. paštu</a><a class="dk-share__button" href="%4$s" data-dk-copy-link="%4$s">Kopijuoti nuorodą</a></div></aside>',
		$encoded_url,
		$encoded_text,
		rawurlencode( $title ),
		esc_url( $url )
	);

	return $content . $share;
}, 20 );
