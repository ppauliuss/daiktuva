<?php
/**
 * Plugin Name: Daiktuva Seller Identity
 * Description: Viešas pardavėjo vardas be asmens duomenų + procedūrinis
 *   unikalus avataras (gamtos/daiktų glifas ant spalvoto fono) kiekvienam
 *   vartotojui. 2026-08-21.
 *
 * 1) VARDAS: dk_public_seller_name() — store_name rodomas TIK jei jis nėra
 *    tiesiog login'as ar display_name (auto-sugeneruotas registracijos metu);
 *    kitaip viešai rodoma „Privatus pardavėjas".
 * 2) AVATARAS: kiekvienam vartotojui priskiriamas atsitiktinis seed
 *    (`dk_avatar_seed` meta); glifas (22 gamtos/daiktų motyvai) + fono spalva
 *    (8 firminės paletės) parenkami deterministiškai. SVG paduodamas per
 *    REST `/wp-json/daiktuva/v1/avatar/<seed>` (image/svg+xml, ilgas cache) —
 *    NE data: URI (WP esc_url() numeta data: — dokan šablonuose avataras
 *    būdavo tuščias) ir NE /?query (Cache Enabler ignoruoja query string —
 *    grąžindavo homepage HTML). Kabinama ant WP `get_avatar_url` ir
 *    Dokan `dokan_get_avatar_url`.
 * 3) KEITIMAS (be praradimo): „Mano paskyroje" — „Maišyti" / žodžių „Kurti"
 *    sugeneruoja KANDIDATĄ, rodomą šalia dabartinio („Dabartinis" vs „Naujas");
 *    pritaikoma tik paspaudus „Pasilikti šį" (nonce), „Palikti dabartinį"
 *    nieko nekeičia. Kandidatas perduodamas URL parametrais dk_cand_seed/g/p —
 *    generavimas nieko nesaugo. Žodžių raktažodžių atpažinimas:
 *    dk_avatar_glyph_keywords()/dk_avatar_color_keywords() (~40 kamienų, LT
 *    kirčiavimas numetamas), atpažinta saugoma `dk_avatar_glyph`/`dk_avatar_pal`
 *    meta (URL ?g=&p=), neatpažinti žodžiai — deterministinė sėkla.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------- viešas vardas --- */

function dk_public_seller_name( int $uid ): string {
	$name = '';
	if ( function_exists( 'dokan_get_store_info' ) ) {
		$store = dokan_get_store_info( $uid );
		if ( is_array( $store ) && ! empty( $store['store_name'] ) ) {
			$name = trim( (string) $store['store_name'] );
		}
	}
	if ( '' !== $name ) {
		$u     = get_userdata( $uid );
		$login = $u ? (string) $u->user_login : '';
		$dn    = $u ? (string) $u->display_name : '';
		$auto  = ( '' !== $login && 0 === strcasecmp( $name, $login ) )
			|| ( '' !== $dn && 0 === strcasecmp( $name, $dn ) );
		if ( ! $auto ) {
			return $name; // žmogaus įvestas parduotuvės/verslo pavadinimas — rodomas
		}
	}
	return 'Privatus pardavėjas';
}

/* ------------------------------------------------------------ avataras --- */

/** Fono/priešfono spalvų poros (švelnus fonas + tamsesnis glifas). */
function dk_avatar_palettes(): array {
	return array(
		array( '#dbeafe', '#1d4ed8' ), // mėlyna
		array( '#ffedd5', '#c2410c' ), // oranžinė
		array( '#d1fae5', '#047857' ), // žalia
		array( '#fce7f3', '#be185d' ), // rožinė
		array( '#ede9fe', '#6d28d9' ), // violetinė
		array( '#fef9c3', '#a16207' ), // geltona
		array( '#cffafe', '#0e7490' ), // turkio
		array( '#fee2e2', '#b91c1c' ), // raudona
	);
}

/**
 * Glifai (gamta + daiktai), viewBox 150, centras ~75,78.
 * {c} = glifo spalva, {b} = fono spalva (išpjovoms: akys, ratai).
 */
function dk_avatar_glyphs(): array {
	return array(
		// lapas
		'<path fill="{c}" d="M75 34c26 12 32 46 6 66-8 6-16 8-24 6 22-10 30-30 32-52-8 22-18 36-34 46-10-18-4-52 20-66z"/>',
		// pušis
		'<path fill="{c}" d="M75 30l22 30h-12l18 26h-18v22h-20v-22H47l18-26H53z"/>',
		// kalnai + saulė
		'<circle cx="102" cy="48" r="13" fill="{c}"/><path fill="{c}" d="M28 108l30-42 20 26 14-16 30 32z"/>',
		// gėlė
		'<g fill="{c}"><circle cx="75" cy="48" r="14"/><circle cx="98" cy="66" r="14"/><circle cx="89" cy="95" r="14"/><circle cx="61" cy="95" r="14"/><circle cx="52" cy="66" r="14"/><rect x="71" y="88" width="8" height="26" rx="4"/></g><circle cx="75" cy="72" r="11" fill="{b}"/>',
		// grybas
		'<path fill="{c}" d="M35 78c0-24 18-40 40-40s40 16 40 40z"/><path fill="{c}" d="M62 78h26l-4 30c-1 6-7 9-9 9s-8-3-9-9z"/>',
		// paukštis
		'<g fill="{c}"><ellipse cx="72" cy="82" rx="24" ry="20"/><circle cx="94" cy="56" r="13"/><path d="M105 52l16 5-16 6z"/><path d="M50 76l-18-10 4 18z"/></g><circle cx="97" cy="53" r="3" fill="{b}"/>',
		// katė
		'<g fill="{c}"><path d="M52 52l-6-20 20 10zM98 52l6-20-20 10z"/><circle cx="75" cy="80" r="30"/></g><g fill="{b}"><circle cx="64" cy="76" r="4"/><circle cx="86" cy="76" r="4"/></g>',
		// šuo
		'<g fill="{c}"><ellipse cx="50" cy="66" rx="11" ry="20"/><ellipse cx="100" cy="66" rx="11" ry="20"/><circle cx="75" cy="80" r="28"/><ellipse cx="75" cy="94" rx="12" ry="9"/></g><circle cx="75" cy="88" r="4" fill="{b}"/>',
		// žuvis
		'<g fill="{c}"><ellipse cx="72" cy="76" rx="30" ry="20"/><path d="M100 76l22-14v28z"/></g><circle cx="58" cy="70" r="4" fill="{b}"/>',
		// drugelis
		'<g fill="{c}"><ellipse cx="52" cy="64" rx="18" ry="22" transform="rotate(-18 52 64)"/><ellipse cx="98" cy="64" rx="18" ry="22" transform="rotate(18 98 64)"/><ellipse cx="56" cy="98" rx="12" ry="14" transform="rotate(-14 56 98)"/><ellipse cx="94" cy="98" rx="12" ry="14" transform="rotate(14 94 98)"/><rect x="71" y="52" width="8" height="56" rx="4"/></g>',
		// obuolys
		'<g fill="{c}"><circle cx="75" cy="84" r="26"/><rect x="72" y="48" width="6" height="16" rx="3"/><ellipse cx="90" cy="50" rx="12" ry="6" transform="rotate(-24 90 50)"/></g>',
		// namelis
		'<g fill="{c}"><path d="M75 34l38 32H37z"/><rect x="47" y="66" width="56" height="42" rx="4"/></g><rect x="67" y="84" width="16" height="24" rx="2" fill="{b}"/>',
		// dviratis
		'<g fill="none" stroke="{c}" stroke-width="7"><circle cx="48" cy="94" r="18"/><circle cx="104" cy="94" r="18"/><path d="M48 94l18-32h24l14 32M66 62h-8"/></g>',
		// fotoaparatas
		'<g fill="{c}"><rect x="36" y="58" width="78" height="50" rx="9"/><path d="M62 58l6-12h14l6 12z"/></g><circle cx="75" cy="83" r="14" fill="{b}"/>',
		// lemputė
		'<g fill="{c}"><path d="M75 32c-18 0-28 12-28 26 0 11 6 17 11 23 4 5 5 9 5 13h24c0-4 1-8 5-13 5-6 11-12 11-23 0-14-10-26-28-26z"/><rect x="63" y="98" width="24" height="8" rx="3"/><rect x="66" y="110" width="18" height="7" rx="3"/></g>',
		// knyga
		'<g fill="{c}"><path d="M75 52c-8-8-20-10-32-8v58c12-2 24 0 32 8 8-8 20-10 32-8V44c-12-2-24 0-32 8z"/><rect x="71" y="50" width="8" height="58"/></g>',
		// puodelis
		'<g fill="{c}"><path d="M46 56h52v26c0 14-12 26-26 26s-26-12-26-26z"/></g><path d="M98 62h10a12 12 0 010 24h-8" fill="none" stroke="{c}" stroke-width="7"/>',
		// gitara
		'<g fill="{c}"><circle cx="64" cy="90" r="20"/><circle cx="84" cy="74" r="16"/><rect x="88" y="30" width="10" height="48" rx="4" transform="rotate(28 93 54)"/></g><circle cx="70" cy="82" r="6" fill="{b}"/>',
		// automobilis
		'<g fill="{c}"><path d="M52 66l8-18c2-4 6-6 10-6h14c4 0 8 2 10 6l8 18z"/><rect x="36" y="66" width="80" height="28" rx="10"/></g><g fill="{b}"><circle cx="58" cy="96" r="10"/><circle cx="94" cy="96" r="10"/></g>',
		// apvalus medis
		'<g fill="{c}"><circle cx="75" cy="58" r="26"/><circle cx="52" cy="74" r="16"/><circle cx="98" cy="74" r="16"/><rect x="70" y="84" width="10" height="28" rx="4"/></g>',
		// mėnulis + žvaigždė
		'<path fill="{c}" d="M92 34a34 34 0 100 82 40 40 0 01-24-41c3-13 12-23 24-41z" transform="translate(6 0)"/><path fill="{c}" d="M102 44l4 9 9 1-7 6 2 9-8-5-8 5 2-9-7-6 9-1z"/>',
		// bangos
		'<g fill="none" stroke="{c}" stroke-width="8" stroke-linecap="round"><path d="M32 62c10-12 22-12 32 0s22 12 32 0 16-9 24-4"/><path d="M32 88c10-12 22-12 32 0s22 12 32 0 16-9 24-4"/></g>',
	);
}

function dk_avatar_render( int $g, int $p ): string {
	$pal    = dk_avatar_palettes();
	$glyphs = dk_avatar_glyphs();
	list( $bg, $fg ) = $pal[ $p % count( $pal ) ];
	$glyph = $glyphs[ $g % count( $glyphs ) ];
	$glyph = str_replace( array( '{c}', '{b}' ), array( $fg, $bg ), $glyph );
	// Glifas padidinamas 1.35x (centro atžvilgiu) — kad 17-20px dydyje
	// kortelėse skaitytųsi forma, ne tik spalva.
	return '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150">'
		. '<rect width="150" height="150" rx="24" fill="' . $bg . '"/>'
		. '<g transform="translate(75 78) scale(1.35) translate(-75 -78)">' . $glyph . '</g></svg>';
}

function dk_avatar_svg( int $seed ): string {
	$hex = md5( 'dk-avatar-' . $seed );
	return dk_avatar_render(
		hexdec( substr( $hex, 4, 4 ) ),
		hexdec( substr( $hex, 0, 4 ) )
	);
}

/* ----------------------------------- žodžių atpažinimas (nemokamai, vietoje) --- */

function dk_avatar_norm( string $s ): string {
	$s = mb_strtolower( trim( $s ) );
	$s = strtr( $s, array(
		'ą' => 'a', 'č' => 'c', 'ę' => 'e', 'ė' => 'e', 'į' => 'i',
		'š' => 's', 'ų' => 'u', 'ū' => 'u', 'ž' => 'z',
	) );
	$s = preg_replace( '/[^a-z0-9]+/u', ' ', (string) $s );
	return trim( (string) preg_replace( '/\s+/', ' ', $s ) );
}

/** Raktažodžių kamienai (be galūnių) -> glifo indeksas dk_avatar_glyphs(). */
function dk_avatar_glyph_keywords(): array {
	return array(
		0  => array( 'lap' ),                                    // lapas
		1  => array( 'pusis', 'pusait', 'egle', 'eglut' ),       // pušis
		2  => array( 'kaln', 'kalnel' ),                         // kalnai
		3  => array( 'gel', 'tulp', 'rozyt', 'ramun', 'vijok' ), // gėlė
		4  => array( 'gryb', 'baravyk' ),                        // grybas
		5  => array( 'pauks', 'baland', 'varn', 'zyl', 'gand' ), // paukštis
		6  => array( 'kat', 'kačiuk' ),                          // katė
		7  => array( 'suo', 'sun', 'skalik', 'bobik' ),          // šuo
		8  => array( 'zuv', 'karos', 'lydek', 'eser', 'las' ),   // žuvis
		9  => array( 'drug' ),                                   // drugelis
		10 => array( 'obuol', 'obsel' ),                         // obuolys
		11 => array( 'nam', 'bust', 'trobel', 'namuk' ),         // namas
		12 => array( 'dvirat', 'ratuk' ),                        // dviratis
		13 => array( 'foto', 'kamer', 'fotik' ),                 // fotoaparatas
		14 => array( 'lemput', 'lemp', 'svies' ),                // lemputė
		15 => array( 'knyg', 'skaitym' ),                        // knyga
		16 => array( 'puod', 'taur', 'kavos', 'arbat' ),         // puodelis
		17 => array( 'gitar', 'muzik' ),                         // gitara
		18 => array( 'automob', 'masin', 'auto', 'bugi' ),       // automobilis
		19 => array( 'medis', 'medz', 'giri', 'azuol', 'berz' ), // medis
		20 => array( 'menul', 'zvaigzd', 'nakt', 'menes' ),      // mėnulis
		21 => array( 'bang', 'jur', 'vand', 'ezer', 'upe' ),     // bangos
	);
}

/** Spalvų kamienai -> paletės indeksas dk_avatar_palettes(). */
function dk_avatar_color_keywords(): array {
	return array(
		0 => array( 'melyn', 'zydra', 'zydri', 'dangu', 'melsv' ),
		1 => array( 'oranzin', 'oranz', 'rud' ),
		2 => array( 'zali', 'zal' ),
		3 => array( 'rozin', 'roz', 'roziu' ),
		4 => array( 'violet', 'purpur' ),
		5 => array( 'gelton', 'gelt' ),
		6 => array( 'turki', 'juros' ),
		7 => array( 'raudon', 'raud' ),
	);
}

/** Žodžiai -> ['g'=>?int, 'p'=>?int] (null jei neatpažinta). */
function dk_avatar_parse_words( string $words ): array {
	$out = array( 'g' => null, 'p' => null );
	foreach ( explode( ' ', dk_avatar_norm( $words ) ) as $tok ) {
		if ( '' === $tok ) {
			continue;
		}
		foreach ( dk_avatar_glyph_keywords() as $g => $stems ) {
			foreach ( $stems as $st ) {
				if ( null === $out['g'] && 0 === strpos( $tok, $st ) ) {
					$out['g'] = (int) $g;
				}
			}
		}
		foreach ( dk_avatar_color_keywords() as $p => $stems ) {
			foreach ( $stems as $st ) {
				if ( null === $out['p'] && 0 === strpos( $tok, $st ) ) {
					$out['p'] = (int) $p;
				}
			}
		}
	}
	return $out;
}

function dk_avatar_seed( int $uid ): int {
	$seed = (int) get_user_meta( $uid, 'dk_avatar_seed', true );
	if ( $seed <= 0 ) {
		$seed = random_int( 1, PHP_INT_MAX );
		update_user_meta( $uid, 'dk_avatar_seed', $seed );
	}
	return $seed;
}

/** Viešas avataro URL (HTTPS, esc_url-saugus, cache'inamas).
 *  SVARBU: paduodama per REST (wp-json), NE per /?dk_avatar= — Cache Enabler
 *  pradinio puslapio cache'ą atiduoda IGNORUODAMAS query string, todėl
 *  /?dk_avatar= po homepage uzkache'inimo grąžindavo HTML, ne SVG. */
function dk_avatar_url_for_seed( int $seed ): string {
	return home_url( '/wp-json/daiktuva/v1/avatar/' . $seed );
}

function dk_avatar_url_for_user( int $uid ): string {
	$url = dk_avatar_url_for_seed( dk_avatar_seed( $uid ) );
	$g   = get_user_meta( $uid, 'dk_avatar_glyph', true );
	$p   = get_user_meta( $uid, 'dk_avatar_pal', true );
	if ( '' !== $g && false !== $g ) {
		$url = add_query_arg( 'g', (int) $g, $url );
	}
	if ( '' !== $p && false !== $p ) {
		$url = add_query_arg( 'p', (int) $p, $url );
	}
	return $url;
}

/** SVG endpoint'as: /wp-json/daiktuva/v1/avatar/<seed> (viešas, necahce'inamas CE). */
add_action( 'rest_api_init', static function () {
	register_rest_route( 'daiktuva/v1', '/avatar/(?P<seed>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => static function ( WP_REST_Request $req ) {
			return new WP_REST_Response( array( 'seed' => (int) $req['seed'] ), 200 );
		},
	) );
} );

add_filter( 'rest_pre_serve_request', static function ( $served, $result, $request ) {
	if ( 0 !== strpos( (string) $request->get_route(), '/daiktuva/v1/avatar' ) ) {
		return $served;
	}
	$seed  = (int) $request->get_param( 'seed' );
	$g     = $request->get_param( 'g' );
	$p     = $request->get_param( 'p' );
	status_header( 200 );
	header( 'Content-Type: image/svg+xml; charset=utf-8' );
	header( 'Cache-Control: public, max-age=31536000, immutable' );
	header( 'X-Robots-Tag: noindex' );
	if ( null !== $g || null !== $p ) {
		// Žodžių variantas: glifas/paletė paimti iš URL, ko trūksta — iš seed.
		$hex = md5( 'dk-avatar-' . $seed );
		echo dk_avatar_render(
			null !== $g ? (int) $g : hexdec( substr( $hex, 4, 4 ) ),
			null !== $p ? (int) $p : hexdec( substr( $hex, 0, 4 ) )
		);
	} else {
		echo dk_avatar_svg( $seed );
	}
	return true;
}, 10, 3 );

/** WP core avatarai (komentarai, profiliai ir kt.). */
add_filter( 'get_avatar_url', static function ( $url, $id_or_email ) {
	$uid = 0;
	if ( is_numeric( $id_or_email ) ) {
		$uid = (int) $id_or_email;
	} elseif ( $id_or_email instanceof WP_User ) {
		$uid = (int) $id_or_email->ID;
	} elseif ( $id_or_email instanceof WP_Comment && $id_or_email->user_id ) {
		$uid = (int) $id_or_email->user_id;
	} elseif ( is_string( $id_or_email ) ) {
		$u = get_user_by( 'email', $id_or_email );
		if ( $u ) {
			$uid = (int) $u->ID;
		}
	}
	return $uid ? dk_avatar_url_for_user( $uid ) : $url;
}, 20, 2 );

/** Dokan pardavėjo avataras (kortelės, parduotuvės puslapis). Prio 20 — po
 *  daiktuva-store-listing initials fallback'o: procedūrinis tampa numatytuoju. */
add_filter( 'dokan_get_avatar_url', static function ( $url, $vendor ) {
	$uid = 0;
	if ( is_object( $vendor ) && method_exists( $vendor, 'get_id' ) ) {
		$uid = (int) $vendor->get_id();
	} elseif ( is_numeric( $vendor ) ) {
		$uid = (int) $vendor;
	}
	return $uid ? dk_avatar_url_for_user( $uid ) : $url;
}, 20, 2 );

/** Registracijos metu — seed iš karto (kad nebūtų generuojama tingiai). */
add_action( 'user_register', static function ( $user_id ) {
	if ( ! get_user_meta( $user_id, 'dk_avatar_seed', true ) ) {
		update_user_meta( $user_id, 'dk_avatar_seed', random_int( 1, PHP_INT_MAX ) );
	}
}, 30 );

/* --------------------------------------- keitimas „Mano paskyroje" --- */

/** Kandidato avataro URL (seed + neprivalomi glifo/paleties override'ai). */
function dk_avatar_url_for_choice( int $seed, ?int $g, ?int $p ): string {
	$url = dk_avatar_url_for_seed( $seed );
	if ( null !== $g ) {
		$url = add_query_arg( 'g', $g, $url );
	}
	if ( null !== $p ) {
		$url = add_query_arg( 'p', $p, $url );
	}
	return $url;
}

add_action( 'template_redirect', static function () {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$uid  = get_current_user_id();
	$base = wc_get_page_permalink( 'myaccount' );

	// 1) Kandidato generavimas — NIEKO nesaugo, tik redirect su cand parametrais.
	if ( ! empty( $_GET['dk_avatar_shuffle'] ) || isset( $_GET['dk_avatar_words'] ) ) {
		$words = isset( $_GET['dk_avatar_words'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['dk_avatar_words'] ) ) ) : '';
		if ( '' !== $words ) {
			$found = dk_avatar_parse_words( $words );
			$args  = array( 'dk_cand_seed' => hexdec( substr( md5( 'dk-words-' . dk_avatar_norm( $words ) ), 0, 12 ) ) );
			if ( null !== $found['g'] ) {
				$args['dk_cand_g'] = $found['g'];
			}
			if ( null !== $found['p'] ) {
				$args['dk_cand_p'] = $found['p'];
			}
		} elseif ( ! empty( $_GET['dk_avatar_shuffle'] ) ) {
			$args = array( 'dk_cand_seed' => random_int( 1, PHP_INT_MAX ) );
		} else {
			return; // tuščias žodžių submit — ignoruojam
		}
		wp_safe_redirect( add_query_arg( $args, $base ) );
		exit;
	}

	// 2) Kandidato pritaikymas (nonce) — tik čia keičiamas vartotojo avataras.
	if ( ! empty( $_GET['dk_avatar_apply'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'dk_avatar_apply_' . $uid ) ) {
			wp_die( 'Neteisinga nuoroda.' );
		}
		$seed = absint( $_GET['dk_cand_seed'] ?? 0 );
		if ( $seed ) {
			update_user_meta( $uid, 'dk_avatar_seed', $seed );
		}
		if ( isset( $_GET['dk_cand_g'] ) ) {
			update_user_meta( $uid, 'dk_avatar_glyph', absint( $_GET['dk_cand_g'] ) );
		} else {
			delete_user_meta( $uid, 'dk_avatar_glyph' );
		}
		if ( isset( $_GET['dk_cand_p'] ) ) {
			update_user_meta( $uid, 'dk_avatar_pal', absint( $_GET['dk_cand_p'] ) );
		} else {
			delete_user_meta( $uid, 'dk_avatar_pal' );
		}
		wp_safe_redirect( add_query_arg( 'dk_avatar_set', '1', $base ) );
		exit;
	}
}, 6 );

add_action( 'woocommerce_account_content', static function () {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return;
	}
	if ( ! empty( $_GET['dk_avatar_set'] ) ) {
		echo '<div class="woocommerce-message" style="font-size:16px;">✅ Avataras pakeistas.</div>';
	}
	$nonce  = wp_create_nonce( 'dk_avatar_apply_' . $uid );
	$base   = wc_get_page_permalink( 'myaccount' );
	$cururl = dk_avatar_url_for_user( $uid );

	$cand_seed = absint( $_GET['dk_cand_seed'] ?? 0 );
	$cand_g    = isset( $_GET['dk_cand_g'] ) ? absint( $_GET['dk_cand_g'] ) : null;
	$cand_p    = isset( $_GET['dk_cand_p'] ) ? absint( $_GET['dk_cand_p'] ) : null;

	echo '<div class="dk-avatar-pick" style="background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:14px 16px;margin:14px 0;">'
		. '<strong style="display:block;margin-bottom:4px;font-size:16px;">Tavo avataras</strong>'
		. '<p style="margin:0 0 10px;color:#7a828e;font-size:13.5px;">Viešai rodomas tik šis avataras ir miestas — tavo vardas ar el. paštas niekur nepublikuojami. Spausk „Maišyti", kol rasi patinkantį, arba įrašyk kelis žodžius — sistema atpažins gyvūnus, gamtą, daiktus ir spalvas (pvz. „mėlynas katinas", „žalias miškas", „raudona mašina"). Dabartinis avataras nepakeičiamas, kol nepaspausi „Pasilikti šį".</p>';

	if ( $cand_seed ) {
		// Kandidatas šalia dabartinio — nieko neprarandama, kol nepatvirtinta.
		$candurl = dk_avatar_url_for_choice( $cand_seed, $cand_g, $cand_p );
		$apply   = array( 'dk_avatar_apply' => '1', 'dk_cand_seed' => $cand_seed, '_wpnonce' => $nonce );
		if ( null !== $cand_g ) {
			$apply['dk_cand_g'] = $cand_g;
		}
		if ( null !== $cand_p ) {
			$apply['dk_cand_p'] = $cand_p;
		}
		$shuffle = add_query_arg( 'dk_avatar_shuffle', '1', $base );
		echo '<div style="display:flex;gap:18px;flex-wrap:wrap;align-items:center;">'
			. '<div style="text-align:center;"><img src="' . esc_url( $cururl ) . '" alt="" width="72" height="72" style="border-radius:14px;display:block;margin:0 auto 4px;"><span style="font-size:12.5px;color:#7a828e;">Dabartinis</span></div>'
			. '<div style="text-align:center;"><img src="' . esc_url( $candurl ) . '" alt="" width="72" height="72" style="border-radius:14px;display:block;margin:0 auto 4px;outline:3px solid #2563eb;"><span style="font-size:12.5px;color:#0F4C81;font-weight:700;">Naujas</span></div>'
			. '<div style="display:flex;flex-direction:column;gap:8px;min-width:200px;">'
			. '<a href="' . esc_url( add_query_arg( $apply, $base ) ) . '" style="display:inline-block;text-align:center;background:#0F4C81;color:#fff;font-weight:700;padding:10px 18px;border-radius:9px;text-decoration:none;">Pasilikti šį</a>'
			. '<a href="' . esc_url( $shuffle ) . '" style="display:inline-block;text-align:center;background:#fff;color:#0F4C81;border:1px solid #0F4C81;font-weight:700;padding:9px 14px;border-radius:9px;text-decoration:none;">Maišyti dar</a>'
			. '<a href="' . esc_url( $base ) . '" style="text-align:center;color:#7a828e;font-size:13px;">Palikti dabartinį</a>'
			. '</div></div>';
	} else {
		$shuffle = add_query_arg( 'dk_avatar_shuffle', '1', $base );
		echo '<div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">'
			. '<img src="' . esc_url( $cururl ) . '" alt="" width="72" height="72" style="border-radius:14px;">'
			. '<div style="display:flex;flex-direction:column;gap:8px;min-width:220px;">'
			. '<a href="' . esc_url( $shuffle ) . '" style="display:inline-block;text-align:center;background:#0F4C81;color:#fff;font-weight:700;padding:10px 18px;border-radius:9px;text-decoration:none;">Maišyti</a>'
			. '<form method="get" action="' . esc_url( $base ) . '" style="display:flex;gap:6px;">'
			. '<input type="text" name="dk_avatar_words" maxlength="60" placeholder="pvz.: mėlynas katinas" style="flex:1;padding:9px 12px;border:1px solid #cfd8e3;border-radius:9px;font-size:14px;">'
			. '<button type="submit" style="background:#fff;color:#0F4C81;border:1px solid #0F4C81;font-weight:700;padding:9px 14px;border-radius:9px;cursor:pointer;">Kurti</button>'
			. '</form>'
			. '</div></div>';
	}
	echo '</div>';
}, 6 );
