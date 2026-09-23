<?php
/**
 * Tiny bilingual helpers for fixed theme strings (header/footer labels).
 *
 * Page and news content is translated by editors in Polylang; these helpers
 * only cover the handful of strings baked into the theme, so we avoid a
 * .po/.mo build step.
 *
 * @package parish-cathedral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Current language slug ("en" or "ru").
 */
function parish_lang(): string {
	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language( 'slug' );
		if ( $lang ) {
			return $lang;
		}
	}
	return str_starts_with( determine_locale(), 'ru' ) ? 'ru' : 'en';
}

/**
 * Pick the English or Russian variant of a fixed string.
 */
function parish_t( string $en, string $ru ): string {
	return 'ru' === parish_lang() ? $ru : $en;
}

/**
 * URL of a page in the current language, given the English page's slug.
 * Falls back to the English page (or home) if no translation exists.
 */
function parish_page_url( string $en_slug ): string {
	$page = get_page_by_path( $en_slug );
	if ( ! $page ) {
		return home_url( '/' );
	}
	$id = $page->ID;
	if ( function_exists( 'pll_get_post' ) ) {
		$id = pll_get_post( $page->ID ) ?: $page->ID;
	}
	return get_permalink( $id );
}

/**
 * [parish_heading en="News" ru="Новости"] — a language-aware H1 for templates.
 */
add_shortcode(
	'parish_heading',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'en' => '', 'ru' => '' ), $atts );
		return '<h1 class="wp-block-heading">' . esc_html( parish_t( $atts['en'], $atts['ru'] ?: $atts['en'] ) ) . '</h1>';
	}
);
