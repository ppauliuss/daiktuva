<?php
/**
 * Daiktuva: auto FAQPage schema from a post's "Dažniausiai užduodami klausimai" section.
 *
 * Scans single-post content for an <h2> FAQ heading followed by <h3>question</h3> +
 * <p>answer</p> pairs, and emits FAQPage JSON-LD. Lets the DUK blocks in our guides
 * surface directly in Google rich results and AI answers. No content duplication —
 * it just describes what's already on the page.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post = get_post();
	if ( ! $post || '' === trim( (string) $post->post_content ) ) {
		return;
	}

	$html = $post->post_content;
	if ( false === mb_stripos( $html, 'užduodami' ) && false === mb_stripos( $html, 'DUK' ) ) {
		return;
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="UTF-8"?><div>' . $html . '</div>' );
	libxml_clear_errors();

	// Find the FAQ <h2>.
	$faq_h2 = null;
	foreach ( $dom->getElementsByTagName( 'h2' ) as $h2 ) {
		$t = trim( $h2->textContent );
		if ( false !== mb_stripos( $t, 'užduodami klausimai' ) || 'DUK' === mb_strtoupper( $t ) ) {
			$faq_h2 = $h2;
			break;
		}
	}
	if ( ! $faq_h2 ) {
		return;
	}

	$faqs = array();
	$q = null;
	$a = '';
	for ( $node = $faq_h2->nextElementSibling; $node; $node = $node->nextElementSibling ) {
		$tag = strtolower( $node->nodeName );
		if ( 'h2' === $tag ) {
			break; // next section
		}
		if ( 'h3' === $tag ) {
			if ( null !== $q && '' !== trim( $a ) ) {
				$faqs[] = array( $q, trim( $a ) );
			}
			$q = trim( $node->textContent );
			$a = '';
			continue;
		}
		if ( 'p' === $tag ) {
			$txt = trim( $node->textContent );
			// Stop at a trailing "Daugiau/Susiję/Norite" CTA paragraph.
			if ( preg_match( '/^(Daugiau|Susiję|Norite)/u', $txt ) ) {
				break;
			}
			if ( null !== $q && '' !== $txt ) {
				$a .= ( '' === $a ? '' : ' ' ) . $txt;
			}
		}
		// Any other element (aside, figure, ul, div, ...) is ignored, not appended.
	}
	if ( null !== $q && '' !== trim( $a ) ) {
		$faqs[] = array( $q, trim( $a ) );
	}

	if ( count( $faqs ) < 2 ) {
		return;
	}

	$entities = array();
	foreach ( $faqs as $qa ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $qa[0],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $qa[1],
			),
		);
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( $post ) . '#faq',
		'mainEntity' => $entities,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 31 );
