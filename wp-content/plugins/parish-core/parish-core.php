<?php
/**
 * Plugin Name: Parish Core
 * Description: Parish-specific features: group leader accounts restricted to their own pages, a Google Calendar "Service schedule" block, and parish settings.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * Text Domain: parish-core
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

define( 'PARISH_CORE_VERSION', '0.1.0' );
define( 'PARISH_CORE_DIR', __DIR__ );

require_once __DIR__ . '/includes/group-leaders.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/calendar.php';

register_activation_hook( __FILE__, 'parish_core_add_roles' );

/**
 * One-off rewrite flush requested by tools/seed.php. It must happen in a normal
 * request, after Polylang has registered its /ru/ language rules.
 */
add_action(
	'wp_loaded',
	function () {
		if ( get_option( 'parish_flush_rewrite' ) ) {
			delete_option( 'parish_flush_rewrite' );
			flush_rewrite_rules();
		}
	}
);
