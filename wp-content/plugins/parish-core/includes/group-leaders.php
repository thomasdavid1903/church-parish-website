<?php
/**
 * Group leader accounts (requirement 6).
 *
 * A "Group leader" (e.g. the head sister, the youth leader) can log in and
 * edit ONLY the page(s) an administrator has assigned to them, in both
 * languages. They cannot publish new pages, delete pages, touch the news,
 * menus, settings or other users.
 *
 * Assignment: Users → (the leader) → "Parish group pages".
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_LEADER_ROLE = 'group_leader';
const PARISH_LEADER_META = 'parish_group_pages';

/**
 * Create the role. Runs on activation and self-heals on init.
 */
function parish_core_add_roles(): void {
	if ( get_role( PARISH_LEADER_ROLE ) ) {
		return;
	}
	add_role(
		PARISH_LEADER_ROLE,
		__( 'Group leader', 'parish-core' ),
		array(
			'read'                  => true,
			'upload_files'          => true,
			// Needed to open existing pages in the editor at all; narrowed down
			// to the assigned pages by parish_leader_map_meta_cap() below.
			'edit_pages'            => true,
			'edit_others_pages'     => true,
			'edit_published_pages'  => true,
		)
	);
}
add_action( 'init', 'parish_core_add_roles' );

function parish_is_leader( ?int $user_id = null ): bool {
	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	return $user && in_array( PARISH_LEADER_ROLE, (array) $user->roles, true )
		&& ! user_can( $user, 'manage_options' );
}

/**
 * Page IDs a leader may edit: the assigned pages plus their translations.
 *
 * @return int[]
 */
function parish_leader_pages( int $user_id ): array {
	$ids = array_map( 'intval', (array) get_user_meta( $user_id, PARISH_LEADER_META, true ) );
	$all = $ids;
	if ( function_exists( 'pll_get_post_translations' ) ) {
		foreach ( $ids as $id ) {
			$all = array_merge( $all, array_map( 'intval', pll_get_post_translations( $id ) ) );
		}
	}
	return array_values( array_unique( array_filter( $all ) ) );
}

/**
 * The actual restriction: deny edit/delete/publish on any page not assigned.
 */
add_filter(
	'map_meta_cap',
	function ( array $caps, string $cap, int $user_id, array $args ) {
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'edit_page', 'delete_page' ), true ) ) {
			return $caps;
		}
		if ( empty( $args[0] ) || ! parish_is_leader( $user_id ) ) {
			return $caps;
		}
		$post = get_post( (int) $args[0] );
		if ( ! $post ) {
			return $caps;
		}
		if ( 'page' === $post->post_type ) {
			if ( 'delete_post' === $cap || 'delete_page' === $cap ) {
				return array( 'do_not_allow' );
			}
			if ( ! in_array( $post->ID, parish_leader_pages( $user_id ), true ) ) {
				return array( 'do_not_allow' );
			}
		}
		return $caps;
	},
	10,
	4
);

/**
 * Leaders shouldn't create new pages (keeps the site structure tidy).
 */
add_action(
	'load-post-new.php',
	function () {
		if ( parish_is_leader() && 'page' === ( $_GET['post_type'] ?? 'post' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_die( esc_html__( 'Group leaders can edit their own page but not create new ones. Please ask a site administrator.', 'parish-core' ) );
		}
	}
);

/**
 * Only show the leader's own pages in Pages → All Pages.
 */
add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'page' !== $q->get( 'post_type' ) || ! parish_is_leader() ) {
			return;
		}
		$q->set( 'post__in', parish_leader_pages( get_current_user_id() ) ?: array( 0 ) );
		$q->set( 'lang', '' ); // Show both languages (Polylang).
	}
);

/**
 * Trim the admin down for leaders: no "Add New Page", friendly dashboard.
 */
add_action(
	'admin_menu',
	function () {
		if ( ! parish_is_leader() ) {
			return;
		}
		remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );
		remove_menu_page( 'tools.php' );
	},
	999
);

add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! parish_is_leader() ) {
			return;
		}
		wp_add_dashboard_widget(
			'parish_leader_pages',
			__( 'Your group pages', 'parish-core' ),
			function () {
				$pages = parish_leader_pages( get_current_user_id() );
				if ( ! $pages ) {
					echo '<p>' . esc_html__( 'No pages have been assigned to you yet. Please ask a site administrator.', 'parish-core' ) . '</p>';
					return;
				}
				echo '<p>' . esc_html__( 'Click a page to update it. Changes appear on the website as soon as you press "Save".', 'parish-core' ) . '</p><ul>';
				foreach ( $pages as $id ) {
					$lang = function_exists( 'pll_get_post_language' ) ? strtoupper( (string) pll_get_post_language( $id ) ) : '';
					printf(
						'<li><a class="button button-primary" style="margin-bottom:6px" href="%s">%s %s</a> <a href="%s" target="_blank">%s</a></li>',
						esc_url( get_edit_post_link( $id ) ),
						esc_html__( 'Edit', 'parish-core' ),
						esc_html( get_the_title( $id ) . ( $lang ? " ($lang)" : '' ) ),
						esc_url( get_permalink( $id ) ),
						esc_html__( 'View', 'parish-core' )
					);
				}
				echo '</ul>';
			}
		);
	}
);

/**
 * Users → Edit user: choose which pages this leader looks after.
 */
function parish_leader_profile_fields( WP_User $user ): void {
	if ( ! current_user_can( 'edit_users' ) || ! in_array( PARISH_LEADER_ROLE, (array) $user->roles, true ) ) {
		return;
	}
	$assigned = array_map( 'intval', (array) get_user_meta( $user->ID, PARISH_LEADER_META, true ) );
	$args     = array(
		'post_type'   => 'page',
		'post_status' => array( 'publish', 'draft', 'private' ),
		'numberposts' => -1,
		'orderby'     => 'menu_order title',
		'order'       => 'ASC',
	);
	if ( function_exists( 'pll_default_language' ) ) {
		$args['lang'] = pll_default_language(); // Translations are included automatically.
	}
	$pages = get_posts( $args );
	?>
	<h2><?php esc_html_e( 'Parish group pages', 'parish-core' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Can edit', 'parish-core' ); ?></th>
			<td>
				<?php wp_nonce_field( 'parish_leader_pages', 'parish_leader_pages_nonce' ); ?>
				<fieldset>
					<?php foreach ( $pages as $page ) : ?>
						<label style="display:block;margin-bottom:4px">
							<input type="checkbox" name="<?php echo esc_attr( PARISH_LEADER_META ); ?>[]" value="<?php echo esc_attr( $page->ID ); ?>" <?php checked( in_array( $page->ID, $assigned, true ) ); ?>>
							<?php echo esc_html( ( $page->post_parent ? '— ' : '' ) . $page->post_title ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description"><?php esc_html_e( 'The leader can edit these pages and their Russian/English translations. Nothing else.', 'parish-core' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'edit_user_profile', 'parish_leader_profile_fields' );

add_action(
	'edit_user_profile_update',
	function ( int $user_id ) {
		if ( ! current_user_can( 'edit_users' )
			|| ! isset( $_POST['parish_leader_pages_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['parish_leader_pages_nonce'] ), 'parish_leader_pages' ) ) {
			return;
		}
		$ids = array_map( 'absint', (array) ( $_POST[ PARISH_LEADER_META ] ?? array() ) );
		update_user_meta( $user_id, PARISH_LEADER_META, array_values( array_filter( $ids ) ) );
	}
);
