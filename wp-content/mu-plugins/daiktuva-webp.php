<?php
/**
 * Daiktuva: WebP tiekimas per <picture> (2026-07-05).
 * webp-gen sugeneravo image.jpg.webp visiems uploads (−45% svorio). Vietoj Cache Enabler Accept-metodo
 * (nesuderinamas su Cloudflare HTML kešu + rizika parodyti webp senai naršyklei) naudojam
 * <picture><source webp> + <img> fallback: VIENAS HTML visiems, naršyklė renkasi formatą, senos mato
 * originalą. Saugu — jei .webp nėra, <img> lieka nepaliestas. Perrašo TIK /wp-content/uploads/.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'template_redirect', static function () {
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'dk_webp_rewrite_buffer' );
}, 99 ); // PO custom fullcache (prio 10) — kad webp transformacija būtų INNERMOST ir pakliūtų į kešą

/** URL -> webp URL jei .webp failas realiai egzistuoja diske, kitaip null. */
function dk_webp_url_to_webp( $url ) {
	$clean = strtok( $url, '?' );
	if ( strpos( $clean, '/wp-content/uploads/' ) === false || ! preg_match( '#\.(jpe?g|png)$#i', $clean ) ) {
		return null;
	}
	$path = str_replace( content_url(), WP_CONTENT_DIR, $clean );
	if ( $path === $clean || ! is_file( $path . '.webp' ) ) {
		return null;
	}
	return $clean . '.webp';
}

/* Naujiems įkeltiems paveikslėliams AUTOMATIŠKAI sugeneruoti .webp (kad ir nauji skelbimai būtų webp). */
add_filter( 'wp_generate_attachment_metadata', static function ( $metadata, $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( $file ) {
		dk_webp_make( $file );
		$dir = dirname( $file );
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $sz ) {
				if ( ! empty( $sz['file'] ) ) {
					dk_webp_make( $dir . '/' . $sz['file'] );
				}
			}
		}
	}
	return $metadata;
}, 20, 2 );

function dk_webp_make( $path ) {
	if ( ! is_file( $path ) || ! preg_match( '#\.(jpe?g|png)$#i', $path ) || ! function_exists( 'imagewebp' ) ) {
		return;
	}
	$webp = $path . '.webp';
	if ( is_file( $webp ) && filemtime( $webp ) >= filemtime( $path ) ) {
		return;
	}
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	$img = ( $ext === 'png' ) ? @imagecreatefrompng( $path ) : @imagecreatefromjpeg( $path );
	if ( ! $img ) {
		return;
	}
	if ( $ext === 'png' ) {
		imagepalettetotruecolor( $img );
		imagealphablending( $img, true );
		imagesavealpha( $img, true );
	}
	@imagewebp( $img, $webp, 82 );
	imagedestroy( $img );
}

function dk_webp_rewrite_buffer( $html ) {
	if ( ! is_string( $html ) || stripos( $html, '<img' ) === false ) {
		return $html;
	}
	return preg_replace_callback( '#<img\b[^>]*>#i', static function ( $m ) {
		$img = $m[0];
		if ( strpos( $img, 'data-dkwebp' ) !== false ) {
			return $img; // jau apdorotas
		}
		if ( ! preg_match( '#\bsrc=(["\'])(.*?)\1#i', $img, $s ) ) {
			return $img;
		}
		$webp_src = dk_webp_url_to_webp( $s[2] );
		if ( ! $webp_src ) {
			return $img;
		}
		// srcset -> webp (visiems URL; jei bent vieno .webp nėra — atsisakom srcset, lieka vien src)
		$source_srcset = $webp_src;
		if ( preg_match( '#\bsrcset=(["\'])(.*?)\1#i', $img, $ss ) ) {
			$parts      = array_map( 'trim', explode( ',', $ss[2] ) );
			$webp_parts = array();
			$ok         = true;
			foreach ( $parts as $part ) {
				if ( $part === '' ) { continue; }
				$seg = preg_split( '#\s+#', $part, 2 );
				$w   = dk_webp_url_to_webp( $seg[0] );
				if ( ! $w ) { $ok = false; break; }
				$webp_parts[] = $w . ( isset( $seg[1] ) ? ' ' . $seg[1] : '' );
			}
			if ( $ok && $webp_parts ) {
				$source_srcset = implode( ', ', $webp_parts );
			}
		}
		$sizes = '';
		if ( preg_match( '#\bsizes=(["\'])(.*?)\1#i', $img, $sz ) ) {
			$sizes = ' sizes="' . esc_attr( $sz[2] ) . '"';
		}
		$img_marked = preg_replace( '#<img\b#i', '<img data-dkwebp="1"', $img, 1 );
		return '<picture><source type="image/webp" srcset="' . esc_attr( $source_srcset ) . '"' . $sizes . '>' . $img_marked . '</picture>';
	}, $html );
}
