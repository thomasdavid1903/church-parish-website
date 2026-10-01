<?php
/**
 * Block editor: make a post's language hard to get wrong. Polylang's Language
 * box is the last panel in the sidebar, so the pre-publish check shows the
 * language, and a warning (with a one-click fix) appears when a Russian title
 * is set to English.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'enqueue_block_editor_assets',
	function () {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return;
		}
		wp_enqueue_script(
			'parish-editor-language',
			plugins_url( 'assets/editor-language.js', PARISH_CORE_DIR . '/parish-core.php' ),
			array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-data', 'wp-notices' ),
			(string) filemtime( PARISH_CORE_DIR . '/assets/editor-language.js' ),
			true
		);
	}
);
