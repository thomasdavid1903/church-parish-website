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
 * Browser-tab icon (the Orthodox cross) until an admin sets a Site Icon
 * under Appearance → Editor → Site Identity.
 */
add_action(
	'wp_head',
	function () {
		if ( ! has_site_icon() ) {
			printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_theme_file_uri( 'assets/icon.svg' ) ) );
		}
	}
);

/**
 * Description and Open Graph tags, so links shared on WhatsApp or Facebook
 * show a proper title, summary and (for news with a photo) an image.
 * Skipped if an SEO plugin is installed, as it does this itself.
 */
add_action(
	'wp_head',
	function () {
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) ) {
			return;
		}
		$site  = parish_t( 'London Orthodox Cathedral', 'Лондонский православный собор' );
		$title = is_front_page() ? $site : wp_get_document_title();
		$desc  = parish_t(
			'Russian Orthodox Cathedral of the Nativity of the Mother of God and the Holy Royal Martyrs (ROCOR), 57 Harvard Road, Chiswick, London W4 4ED. Services, news and parish life.',
			'Русский православный собор Рождества Пресвятой Богородицы и Святых Царственных Мучеников (РПЦЗ), 57 Harvard Road, Чизик, Лондон W4 4ED. Богослужения, новости и приходская жизнь.'
		);
		$image = '';
		if ( is_singular() && ! is_front_page() ) {
			$post = get_queried_object();
			if ( has_excerpt( $post ) || 'post' === $post->post_type ) {
				$desc = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 30 );
			}
			$image = (string) get_the_post_thumbnail_url( $post, 'large' );
		}
		$tags = array(
			'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
			'og:site_name'   => $site,
			'og:title'       => $title,
			'og:description' => $desc,
			'og:url'         => is_singular() ? get_permalink() : '',
			'og:locale'      => 'ru' === parish_lang() ? 'ru_RU' : 'en_GB',
			'og:image'       => $image,
		);
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		foreach ( array_filter( $tags ) as $property => $content ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
		}
	}
);

/**
 * Hide menu links to pages that aren't published yet (e.g. the photo gallery,
 * which starts as a draft). Publishing the page makes its link appear.
 */
add_filter(
	'wp_nav_menu_objects',
	function ( array $items ): array {
		return array_filter(
			$items,
			fn( $item ) => 'post_type' !== $item->type || 'publish' === get_post_status( (int) $item->object_id )
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
