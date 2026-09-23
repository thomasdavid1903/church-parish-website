<?php
/**
 * Parish Cathedral theme setup.
 *
 * The theme is a block theme; header and footer are rendered by PHP (via
 * shortcodes placed in the template parts) so that they can switch language
 * with Polylang and use classic per-language menus, which Polylang supports
 * well without any paid add-ons.
 *
 * @package parish-cathedral
 */

defined( 'ABSPATH' ) || exit;

define( 'PARISH_THEME_VERSION', '0.1.0' );

require_once __DIR__ . '/inc/i18n.php';
require_once __DIR__ . '/inc/header-footer.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'style.css' );

		// Classic menu locations: Polylang lets you assign a different menu per language.
		// Registering these also re-enables Appearance → Menus, which is easier for editors.
		register_nav_menus(
			array(
				'primary' => __( 'Main menu', 'parish-cathedral' ),
				'footer'  => __( 'Footer links', 'parish-cathedral' ),
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'parish-cathedral', get_stylesheet_uri(), array(), PARISH_THEME_VERSION );
		wp_enqueue_script(
			'parish-cathedral-nav',
			get_theme_file_uri( 'assets/js/nav.js' ),
			array(),
			PARISH_THEME_VERSION,
			array( 'strategy' => 'defer', 'in_footer' => true )
		);
	}
);

/**
 * Block styles that editors can pick from the sidebar ("Styles" panel),
 * so they never need to touch CSS.
 */
add_action(
	'init',
	function () {
		register_block_style(
			'core/group',
			array(
				'name'  => 'parish-card',
				'label' => __( 'Info card', 'parish-cathedral' ),
			)
		);
		register_block_style(
			'core/list',
			array(
				'name'  => 'parish-timetable',
				'label' => __( 'Timetable', 'parish-cathedral' ),
			)
		);
		register_block_style(
			'core/heading',
			array(
				'name'  => 'parish-section-title',
				'label' => __( 'Section title', 'parish-cathedral' ),
			)
		);

		register_block_pattern_category( 'parish', array( 'label' => __( 'Parish', 'parish-cathedral' ) ) );
	}
);

/**
 * Keep the admin simple for non-technical editors: hide the comment UI
 * (the parish site does not take comments).
 */
add_action(
	'init',
	function () {
		remove_post_type_support( 'post', 'comments' );
		remove_post_type_support( 'page', 'comments' );
	},
	100
);
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_action(
	'admin_menu',
	function () {
		remove_menu_page( 'edit-comments.php' );
	}
);
