<?php
/**
 * Plugin Name: Parish Setup (one-time)
 * Description: Loads the parish pages, news, menus and photos onto a live site. Run it once from Tools → Parish Setup, then delete this plugin.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 *
 * Built by tools/build-zips.ps1, which bundles tools/seed.php and its photos.
 *
 * @package parish-setup
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_management_page( 'Parish Setup', 'Parish Setup', 'manage_options', 'parish-setup', 'parish_setup_page' );
	}
);

function parish_setup_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>Parish Setup</h1>';

	if ( isset( $_POST['parish_setup_run'] ) && check_admin_referer( 'parish_setup_run' ) ) {
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 600 ); // Importing the photos takes a while.
		}
		define( 'PARISH_SEED_SKIP_DEMO_USERS', true );
		ob_start();
		require __DIR__ . '/seed/seed.php';
		$log = trim( (string) ob_get_clean() );
		echo '<div class="notice notice-success"><p><strong>Done.</strong> <a href="' . esc_url( home_url( '/' ) ) . '">View the site</a>, then delete this plugin under Plugins.</p></div>';
		echo '<pre style="background:#fff;padding:1em;max-height:20em;overflow:auto">' . esc_html( $log ) . '</pre>';
	} else {
		echo '<p>Creates the parish pages (English and Russian), news, menus, clergy and photos. Existing pages with the same address are kept, so it is safe to run again.</p>';
		echo '<p>Polylang, the Parish Cathedral theme and the Parish Core plugin must be active first.</p>';
		echo '<form method="post">';
		wp_nonce_field( 'parish_setup_run' );
		submit_button( 'Load parish content', 'primary', 'parish_setup_run' );
		echo '</form>';
	}
	echo '</div>';
}
