<?php
/**
 * Plugin Name: Daiktuva Article CTA
 * Description: CTA straipsniuose: kompaktiška juosta po 3-ios pastraipos + blokas straipsnio pabaigoje (kviečia įdėti skelbimą / žiūrėti skelbimus). GA4 įvykis article_cta_click.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_acta_urls() {
	return array(
		'new'  => esc_url( function_exists( 'dk_add_listing_url' ) ? dk_add_listing_url() : home_url( '/vendor-onboarding/' ) ),
		'shop' => esc_url( home_url( '/shop/' ) ),
	);
}

function dk_acta_css() {
	return '<style id="dk-acta-css">
.dk-acta-box{background:#eaf2fd;border:1px solid #c7dcf7;border-radius:12px;padding:24px 22px;margin:36px 0 8px;text-align:center}
.dk-acta-box h3{margin:0 0 8px;color:#0f4c81;font-size:1.35em}
.dk-acta-box p{margin:0 0 16px;color:#334155}
.dk-acta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.dk-acta-btn{display:inline-block;padding:12px 22px;border-radius:8px;font-weight:600;text-decoration:none !important;line-height:1.2}
.dk-acta-btn--main{background:#2563eb;color:#fff !important}
.dk-acta-btn--main:hover{background:#0f4c81;color:#fff !important}
.dk-acta-btn--ghost{background:#fff;color:#2563eb !important;border:2px solid #2563eb}
.dk-acta-btn--ghost:hover{background:#eaf2fd}
.dk-acta-inline{background:#f8fafc;border-left:4px solid #2563eb;border-radius:0 8px 8px 0;padding:12px 16px;margin:24px 0;color:#334155}
.dk-acta-inline a{color:#2563eb;font-weight:600;text-decoration:none}
.dk-acta-inline a:hover{text-decoration:underline}
</style>';
}

function dk_acta_inline_html() {
	$u = dk_acta_urls();
	return <<<HTML
<div class="dk-acta-inline">💡 <strong>Žinojote?</strong> Daiktuvoje skelbimą įdėsite nemokamai ir be komisinių — <a href="{$u['new']}" onclick="window.gtag&&gtag('event','article_cta_click',{cta_pos:'inline'})">įdėti skelbimą »</a></div>
HTML;
}

function dk_acta_box_html() {
	$u   = dk_acta_urls();
	$css = dk_acta_css();
	return <<<HTML
{$css}
<div class="dk-acta-box">
<h3>Turite nereikalingų daiktų?</h3>
<p>Įdėkite skelbimą per porą minučių — nemokamai, be komisinių. Pirkėjas paskambins tiesiogiai jums.</p>
<div class="dk-acta-btns">
<a class="dk-acta-btn dk-acta-btn--main" href="{$u['new']}" onclick="window.gtag&&gtag('event','article_cta_click',{cta_pos:'box'})">+ Įdėti skelbimą</a>
<a class="dk-acta-btn dk-acta-btn--ghost" href="{$u['shop']}" onclick="window.gtag&&gtag('event','article_cta_click',{cta_pos:'box_shop'})">Žiūrėti skelbimus</a>
</div>
</div>
HTML;
}

add_filter( 'the_content', function ( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	// Kompaktiškas CTA po 3-ios pastraipos (tik pakankamai ilguose straipsniuose)
	$parts = explode( '</p>', $content );
	if ( count( $parts ) > 6 ) {
		$out  = '';
		$last = count( $parts ) - 1;
		foreach ( $parts as $i => $chunk ) {
			$out .= $chunk;
			if ( $i < $last ) {
				$out .= '</p>';
			}
			if ( 2 === $i ) {
				$out .= dk_acta_inline_html();
			}
		}
		$content = $out;
	}

	return $content . dk_acta_box_html();
}, 30 );
