<?php
/**
 * Daiktuva: AI crawler policy in robots.txt.
 *
 * Policy (chosen 2026-06-10): ALLOW AI search / citation / grounding crawlers so AI
 * assistants can read and recommend Daiktuva to users; DISALLOW training-only crawlers.
 *
 * NOTE: this only takes effect once Cloudflare's managed robots.txt ("AI Audit > Manage
 * robots.txt") is turned OFF — otherwise Cloudflare serves its own block list instead of
 * WordPress's. robots.txt is advisory; the hard edge enforcement is the Cloudflare WAF.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'robots_txt', function ( $output, $public ) {
	if ( ! $public ) {
		return $output; // site marked non-public; leave as-is
	}

	$blocked = array(
		'GPTBot',              // OpenAI training
		'CCBot',               // Common Crawl (feeds many trainers)
		'Bytespider',          // ByteDance
		'Amazonbot',
		'Applebot-Extended',   // Apple training (Applebot search stays allowed)
		'meta-externalagent',  // Meta training
		'FacebookBot',
		'Diffbot',
		'Omgilibot',
		'ImagesiftBot',
		'PetalBot',
		'DataForSeoBot',
	);

	$allowed = array(
		'OAI-SearchBot',  // ChatGPT search results
		'ChatGPT-User',   // ChatGPT user-initiated browsing
		'PerplexityBot',  // Perplexity index
		'Perplexity-User',// Perplexity user-initiated
		'ClaudeBot',      // Anthropic / Claude
		'Claude-User',    // Claude user-initiated
		'anthropic-ai',
		'Google-Extended',// Gemini grounding & Google AI Overviews
		'Applebot',       // Apple/Siri search
	);

	$lines = array( '# Daiktuva AI crawler policy: allow AI search & citation, block training-only bots.' );

	foreach ( $blocked as $ua ) {
		$lines[] = "User-agent: {$ua}";
		$lines[] = 'Disallow: /';
		$lines[] = '';
	}
	foreach ( $allowed as $ua ) {
		$lines[] = "User-agent: {$ua}";
		$lines[] = 'Allow: /';
		$lines[] = '';
	}

	// Prepend our policy before WordPress's default "User-agent: *" section.
	// 2026-08-18: kanoninis sitemap — Rank Math (sitemap eilute i robots.txt
	// prideda pats Rank Math; core wp-sitemap isjungtas).
	return implode( "\n", $lines ) . "\n" . $output;
}, 10, 2 );
