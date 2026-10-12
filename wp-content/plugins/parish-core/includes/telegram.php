<?php
/**
 * Telegram → News: posts in the parish Telegram channel become news posts.
 *
 * A bot that is an admin of the channel receives each post. The site either
 * asks Telegram for new posts every 5 minutes (works anywhere, including the
 * local preview) or, once switched on in Settings → Parish, Telegram pushes
 * them instantly to /wp-json/parish/v1/telegram (live site only, needs HTTPS).
 *
 * Bilingual posts are split into an English and a Russian post, linked as
 * translations. See docs/TELEGRAM-NEWS-PLAN.md.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_OPTION_TG_TOKEN    = 'parish_tg_token';
const PARISH_OPTION_TG_CHANNEL  = 'parish_tg_channel';
const PARISH_OPTION_TG_FORWARDS = 'parish_tg_forwards';
const PARISH_TG_KEY             = '_parish_tg_key';
const PARISH_TG_TIME            = '_parish_tg_time';
const PARISH_TG_HASH            = '_parish_tg_hash';

/**
 * Call the Telegram Bot API. Returns the result, or WP_Error.
 */
function parish_tg_api( string $method, array $args = array() ) {
	$token = (string) get_option( PARISH_OPTION_TG_TOKEN );
	if ( '' === $token ) {
		return new WP_Error( 'parish_tg', 'No bot token has been saved.' );
	}
	$res = wp_remote_post(
		"https://api.telegram.org/bot{$token}/{$method}",
		array(
			'timeout' => 20,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $args ? $args : new stdClass() ),
		)
	);
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'parish_tg', 'Could not reach Telegram.' );
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $body['ok'] ) ) {
		return new WP_Error( 'parish_tg', 'Telegram said: ' . ( $body['description'] ?? 'unknown error' ) );
	}
	return $body['result'];
}

/* ------------------------------------------------------------------------
 * Receiving posts
 * --------------------------------------------------------------------- */

/**
 * Ask Telegram for posts since the last check. Returns the number imported.
 */
function parish_tg_poll() {
	$updates = parish_tg_api(
		'getUpdates',
		array(
			'offset'          => (int) get_option( 'parish_tg_offset', 0 ),
			'timeout'         => 0,
			'allowed_updates' => array( 'channel_post', 'edited_channel_post' ),
		)
	);
	if ( is_wp_error( $updates ) ) {
		update_option( 'parish_tg_last', array( time(), $updates->get_error_message() ), false );
		return $updates;
	}
	$count = 0;
	foreach ( $updates as $update ) {
		update_option( 'parish_tg_offset', (int) $update['update_id'] + 1, false );
		$count += parish_tg_handle_update( $update ) ? 1 : 0;
	}
	update_option( 'parish_tg_last', array( time(), sprintf( '%d new post(s) imported.', $count ) ), false );
	return $count;
}

function parish_tg_handle_update( array $update ): bool {
	$edited  = isset( $update['edited_channel_post'] );
	$message = $update['channel_post'] ?? $update['edited_channel_post'] ?? null;
	if ( ! is_array( $message ) ) {
		return false;
	}
	// Only the channel named in Settings, if one is named.
	$want = ltrim( (string) get_option( PARISH_OPTION_TG_CHANNEL ), '@' );
	$chat = $message['chat'] ?? array();
	if ( '' !== $want && 0 !== strcasecmp( $want, (string) ( $chat['username'] ?? '' ) ) && (string) ( $chat['id'] ?? '' ) !== $want ) {
		return false;
	}
	return parish_tg_import( $message, $edited );
}

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['parish_5min'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => 'Every 5 minutes',
		);
		return $schedules;
	}
);

add_action(
	'init',
	function () {
		$polling = get_option( PARISH_OPTION_TG_TOKEN ) && ! get_option( 'parish_tg_webhook' );
		$next    = wp_next_scheduled( 'parish_tg_poll' );
		if ( $polling && ! $next ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'parish_5min', 'parish_tg_poll' );
		} elseif ( ! $polling && $next ) {
			wp_clear_scheduled_hook( 'parish_tg_poll' );
		}
	}
);
add_action( 'parish_tg_poll', 'parish_tg_poll' );

// Instant delivery: Telegram posts here, signed with our secret.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'parish/v1',
			'/telegram',
			array(
				'methods'             => 'POST',
				'permission_callback' => function ( WP_REST_Request $req ) {
					$secret = (string) get_option( 'parish_tg_secret' );
					return '' !== $secret && hash_equals( $secret, (string) $req->get_header( 'X-Telegram-Bot-Api-Secret-Token' ) );
				},
				'callback'            => function ( WP_REST_Request $req ) {
					parish_tg_handle_update( (array) $req->get_json_params() );
					return array( 'ok' => true );
				},
			)
		);
	}
);

/* ------------------------------------------------------------------------
 * Turning a Telegram message into news posts
 * --------------------------------------------------------------------- */

/**
 * Import one channel message. Returns true if a post was created or changed.
 */
function parish_tg_import( array $m, bool $edited = false ): bool {
	$chat_id  = (string) ( $m['chat']['id'] ?? '0' );
	$key      = $chat_id . ':' . (int) $m['message_id'];
	$existing = parish_tg_find( PARISH_TG_KEY, $key );
	if ( $existing && ! $edited ) {
		return false;
	}

	// Forwards: never re-import our own posts; others only if switched on.
	if ( ! empty( $m['forward_origin'] ) ) {
		if ( (string) ( $m['forward_origin']['chat']['id'] ?? '' ) === $chat_id || ! get_option( PARISH_OPTION_TG_FORWARDS ) ) {
			return false;
		}
	}

	$text     = (string) ( $m['text'] ?? $m['caption'] ?? '' );
	$entities = $m['entities'] ?? $m['caption_entities'] ?? array();
	if ( false !== stripos( $text, '#nosite' ) ) {
		return false;
	}

	// A photo on its own (e.g. the rest of an album) joins the post just before it.
	if ( '' === trim( $text ) ) {
		if ( empty( $m['photo'] ) || $edited ) {
			return false;
		}
		$recent = parish_tg_recent( $m, 10 * MINUTE_IN_SECONDS );
		if ( ! $recent ) {
			return false;
		}
		parish_tg_add_photo( $recent, $m );
		return true;
	}

	// The same text posted again (e.g. a reminder) isn't a new news item.
	$hash = md5( preg_replace( '/\s+/u', ' ', mb_strtolower( trim( $text ) ) ) );
	if ( ! $existing && parish_tg_find( PARISH_TG_HASH, $hash, '90 days ago' ) ) {
		return false;
	}

	// Sort each paragraph into English, Russian, or both (links, phone numbers).
	$blocks = array(
		'en' => array(),
		'ru' => array(),
	);
	$plain  = $blocks;
	$own    = $blocks;
	foreach ( preg_split( '/\n\s*\n/u', parish_tg_to_html( $text, $entities ) ) as $para ) {
		$para = trim( $para );
		if ( '' === $para ) {
			continue;
		}
		$lang  = parish_tg_language( $para );
		$block = "<!-- wp:paragraph -->\n<p>" . make_clickable( nl2br( $para, false ) ) . "</p>\n<!-- /wp:paragraph -->";
		foreach ( array( 'en', 'ru' ) as $l ) {
			if ( $lang === $l || 'both' === $lang ) {
				$blocks[ $l ][] = $block;
				$plain[ $l ][]  = html_entity_decode( wp_strip_all_tags( $para ), ENT_QUOTES );
				$own[ $l ]      = $own[ $l ] || $lang === $l;
			}
		}
	}
	$langs = array_keys( array_filter( $own ) );
	if ( ! $langs ) {
		return false;
	}

	// One language only, just after a post in the other language: they're translations
	// (the channel sometimes posts the English and Russian a minute apart).
	$group = $existing;
	if ( ! $existing && 1 === count( $langs ) ) {
		$recent = parish_tg_recent( $m, 15 * MINUTE_IN_SECONDS );
		if ( $recent && 1 === count( $recent ) && ! isset( $recent[ $langs[0] ] ) ) {
			$group = $recent;
		}
	}

	$username = (string) ( $m['chat']['username'] ?? '' );
	$link     = $username ? 'https://t.me/' . rawurlencode( $username ) . '/' . (int) $m['message_id'] : '';
	$has_film = ! empty( $m['video'] ) || ! empty( $m['animation'] );
	$date_gmt = gmdate( 'Y-m-d H:i:s', (int) $m['date'] );
	$created  = array();

	foreach ( $langs as $lang ) {
		$ru      = 'ru' === $lang;
		$content = $blocks[ $lang ];
		if ( $has_film && $link ) {
			$content[] = "<!-- wp:paragraph -->\n<p><a href=\"" . esc_url( $link ) . '">' . ( $ru ? 'Смотреть видео в Telegram' : 'Watch the video on Telegram' ) . "</a></p>\n<!-- /wp:paragraph -->";
		}
		if ( $link ) {
			$content[] = "<!-- wp:paragraph {\"className\":\"parish-telegram-source\"} -->\n<p class=\"parish-telegram-source\"><a href=\"" . esc_url( $link ) . '">' . ( $ru ? 'Опубликовано в Telegram' : 'Originally posted on Telegram' ) . "</a></p>\n<!-- /wp:paragraph -->";
		}
		$post = array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => parish_tg_title( $plain[ $lang ] ),
			'post_content' => implode( "\n\n", $content ),
		);
		if ( isset( $existing[ $lang ] ) ) {
			$post['ID'] = $existing[ $lang ];
			$id         = wp_update_post( wp_slash( $post ) );
		} else {
			$post['post_date_gmt'] = $date_gmt;
			$post['post_date']     = get_date_from_gmt( $date_gmt );
			$post['post_author']   = parish_tg_author();
			$id                    = wp_insert_post( wp_slash( $post ) );
		}
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $id, $lang );
		}
		update_post_meta( $id, PARISH_TG_KEY, $key );
		update_post_meta( $id, PARISH_TG_TIME, (int) $m['date'] );
		update_post_meta( $id, PARISH_TG_HASH, $hash );
		$created[ $lang ] = $id;
	}
	if ( ! $created ) {
		return false;
	}
	if ( function_exists( 'pll_save_post_translations' ) ) {
		pll_save_post_translations( array_merge( $group, $created ) );
	}
	if ( ! empty( $m['photo'] ) && ! $edited ) {
		parish_tg_add_photo( $created, $m );
	}
	return true;
}

/**
 * Posts with this meta value, keyed by language.
 */
function parish_tg_find( string $meta, string $value, string $since = '' ): array {
	$args = array(
		'post_type'   => 'post',
		'post_status' => 'any',
		'meta_key'    => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'  => $value, // phpcs:ignore WordPress.DB.SlowDBQuery
		'fields'      => 'ids',
		'numberposts' => -1,
		'lang'        => '',
	);
	if ( $since ) {
		$args['date_query'] = array( array( 'after' => $since ) );
	}
	$out = array();
	foreach ( get_posts( $args ) as $id ) {
		$lang         = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $id ) : '';
		$out[ $lang ?: 'en' ] = $id;
	}
	return $out;
}

/**
 * The most recent Telegram post (with its translations) from up to $window
 * seconds before this message.
 */
function parish_tg_recent( array $m, int $window ): array {
	$ids = get_posts(
		array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => PARISH_TG_TIME,
					'value'   => array( (int) $m['date'] - $window, (int) $m['date'] ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			),
			'meta_key'    => PARISH_TG_TIME, // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'     => 'meta_value_num',
			'order'       => 'DESC',
			'fields'      => 'ids',
			'numberposts' => 1,
			'lang'        => '',
		)
	);
	if ( ! $ids ) {
		return array();
	}
	if ( function_exists( 'pll_get_post_translations' ) ) {
		return array_map( 'intval', pll_get_post_translations( $ids[0] ) ) ?: array( 'en' => $ids[0] );
	}
	return array( 'en' => $ids[0] );
}

/**
 * Which language a paragraph is in: en, ru, both (shared lines such as
 * links, numbers or "Полный отчетъ | Full report"), or fr (left out).
 */
function parish_tg_language( string $html ): string {
	$text = preg_replace( '#https?://\S+|@\w+|\S+@\S+#u', '', html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES ) );
	$cyr  = preg_match_all( '/\p{Cyrillic}/u', $text );
	$lat  = preg_match_all( '/\p{Latin}/u', $text );
	if ( ! $cyr && preg_match( '/[éèêàçùûôî]/u', $text ) && preg_match( '/\b(les|des|une|est|dans|sont|du)\b/iu', $text ) ) {
		return 'fr';
	}
	if ( $cyr + $lat < 3 || ( $cyr && $lat && min( $cyr, $lat ) / max( $cyr, $lat ) > 0.3 ) ) {
		return 'both';
	}
	return $cyr > $lat ? 'ru' : 'en';
}

/**
 * A title from the first real sentence, skipping greetings like
 * "Dear brothers and sisters!".
 */
function parish_tg_title( array $paragraphs ): string {
	foreach ( preg_split( '/\n+/u', implode( "\n", $paragraphs ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || preg_match( '/^(dear|дорог)[^.!?]{0,40}[!,.]?$/iu', $line ) ) {
			continue;
		}
		// A sentence ends at . ! ? followed by a capital letter (so "2026 г. состоится" isn't cut).
		if ( preg_match( '/^(.{20,}?[.!?])(?=\s+[\p{Lu}\d«"]|$)/u', $line, $match ) ) {
			$line = $match[1];
		}
		$line = rtrim( $line, " .!:;," );
		if ( mb_strlen( $line ) > 80 ) {
			$line = rtrim( mb_substr( $line, 0, mb_strrpos( mb_substr( $line, 0, 80 ), ' ' ) ?: 80 ), ' ,;:–-' ) . '…';
		}
		return $line;
	}
	return 'News';
}

/**
 * Telegram text plus its formatting "entities" as HTML. Entity offsets count
 * UTF-16 code units, so the text is walked in UTF-16.
 */
function parish_tg_to_html( string $text, array $entities ): string {
	$u16   = mb_convert_encoding( $text, 'UTF-16LE', 'UTF-8' );
	$len   = intdiv( strlen( $u16 ), 2 );
	$part  = fn( int $from, int $to ) => mb_convert_encoding( substr( $u16, 2 * $from, 2 * ( $to - $from ) ), 'UTF-8', 'UTF-16LE' );
	$open  = array();
	$close = array();
	usort( $entities, fn( $a, $b ) => $a['offset'] <=> $b['offset'] ?: $b['length'] <=> $a['length'] );
	foreach ( $entities as $e ) {
		$from = (int) $e['offset'];
		$to   = $from + (int) $e['length'];
		$tags = array(
			'bold'          => 'strong',
			'italic'        => 'em',
			'underline'     => 'u',
			'strikethrough' => 's',
			'code'          => 'code',
		);
		$href = '';
		switch ( $e['type'] ) {
			case 'text_link':
				$href = $e['url'];
				break;
			case 'url':
				$href = $part( $from, $to );
				$href = preg_match( '#^https?://#i', $href ) ? $href : 'https://' . $href;
				break;
			case 'mention':
				$href = 'https://t.me/' . ltrim( $part( $from, $to ), '@' );
				break;
			case 'email':
				$href = 'mailto:' . $part( $from, $to );
				break;
		}
		if ( $href ) {
			$tag  = 'a';
			$head = '<a href="' . esc_url( $href ) . '">';
		} elseif ( isset( $tags[ $e['type'] ] ) ) {
			$tag  = $tags[ $e['type'] ];
			$head = "<{$tag}>";
		} else {
			continue;
		}
		$open[ $from ][] = $head;
		$close[ $to ] = $close[ $to ] ?? array();
		array_unshift( $close[ $to ], "</{$tag}>" ); // Inner tags close first.
	}
	$points = array_unique( array_merge( array( 0, $len ), array_keys( $open ), array_keys( $close ) ) );
	sort( $points );
	$html = '';
	foreach ( $points as $i => $p ) {
		$html .= implode( '', $close[ $p ] ?? array() ) . implode( '', $open[ $p ] ?? array() );
		if ( isset( $points[ $i + 1 ] ) ) {
			$html .= esc_html( $part( $p, $points[ $i + 1 ] ) );
		}
	}
	return $html;
}

/**
 * Download the message's photo into the Media Library: the featured image if
 * the post has none yet, otherwise added to the end of the post.
 */
function parish_tg_add_photo( array $ids, array $m ): void {
	$sizes = $m['photo'];
	$file  = parish_tg_api( 'getFile', array( 'file_id' => end( $sizes )['file_id'] ) );
	if ( is_wp_error( $file ) || empty( $file['file_path'] ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	// Not download_url(): its "safe URL" check can reject api.telegram.org on
	// some hosts (it does on Playground), and the photo is then silently lost.
	$res = wp_remote_get( 'https://api.telegram.org/file/bot' . get_option( PARISH_OPTION_TG_TOKEN ) . '/' . $file['file_path'], array( 'timeout' => 60 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return;
	}
	$tmp = wp_tempnam( 'telegram-' . (int) $m['message_id'] );
	file_put_contents( $tmp, wp_remote_retrieve_body( $res ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	$attachment = media_handle_sideload(
		array(
			'name'     => 'telegram-' . (int) $m['message_id'] . '.jpg',
			'tmp_name' => $tmp,
		),
		(int) reset( $ids )
	);
	if ( is_wp_error( $attachment ) ) {
		wp_delete_file( $tmp );
		return;
	}
	foreach ( $ids as $id ) {
		if ( ! has_post_thumbnail( $id ) ) {
			set_post_thumbnail( $id, $attachment );
			continue;
		}
		$src = wp_get_attachment_image_url( $attachment, 'large' );
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $id,
					'post_content' => get_post_field( 'post_content', $id ) . "\n\n<!-- wp:image {\"id\":{$attachment},\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"" . esc_url( $src ) . "\" alt=\"\" class=\"wp-image-{$attachment}\"/></figure>\n<!-- /wp:image -->",
				)
			)
		);
	}
}

function parish_tg_author(): int {
	$admins = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		)
	);
	return (int) ( $admins[0] ?? 1 );
}

/* ------------------------------------------------------------------------
 * Settings → Parish
 * --------------------------------------------------------------------- */

add_action(
	'admin_init',
	function () {
		add_settings_section(
			'parish_telegram',
			__( 'Telegram → News', 'parish-core' ),
			function () {
				echo '<p>' . esc_html__( 'Posts in the parish Telegram channel are added to News automatically. Create a bot with @BotFather, add it to the channel as an administrator, then paste its token here.', 'parish-core' ) . '</p>';
			},
			'parish'
		);

		register_setting(
			'parish',
			PARISH_OPTION_TG_TOKEN,
			array(
				'type'              => 'string',
				'default'           => '',
				// An empty box keeps the saved token, so it never has to be shown again.
				'sanitize_callback' => fn( $v ) => '' === trim( (string) $v ) ? (string) get_option( PARISH_OPTION_TG_TOKEN ) : sanitize_text_field( $v ),
			)
		);
		add_settings_field(
			PARISH_OPTION_TG_TOKEN,
			__( 'Bot token', 'parish-core' ),
			function () {
				printf(
					'<input type="password" class="regular-text code" name="%s" value="" autocomplete="off" placeholder="%s">',
					esc_attr( PARISH_OPTION_TG_TOKEN ),
					esc_attr( get_option( PARISH_OPTION_TG_TOKEN ) ? __( 'Saved. Leave empty to keep it.', 'parish-core' ) : '123456:ABC…' )
				);
			},
			'parish',
			'parish_telegram'
		);

		register_setting(
			'parish',
			PARISH_OPTION_TG_CHANNEL,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => fn( $v ) => sanitize_text_field( preg_replace( '#^(https?://)?t\.me/(s/)?#i', '', trim( (string) $v ) ) ),
			)
		);
		add_settings_field(
			PARISH_OPTION_TG_CHANNEL,
			__( 'Channel', 'parish-core' ),
			function () {
				printf(
					'<input type="text" class="regular-text code" name="%s" value="%s" placeholder="ROCORinLondon"><p class="description">%s</p>',
					esc_attr( PARISH_OPTION_TG_CHANNEL ),
					esc_attr( get_option( PARISH_OPTION_TG_CHANNEL ) ),
					esc_html__( 'Only posts from this channel are imported. The name after t.me/, or the channel link.', 'parish-core' )
				);
			},
			'parish',
			'parish_telegram'
		);

		register_setting(
			'parish',
			PARISH_OPTION_TG_FORWARDS,
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => fn( $v ) => ! empty( $v ),
			)
		);
		add_settings_field(
			PARISH_OPTION_TG_FORWARDS,
			__( 'Forwarded posts', 'parish-core' ),
			function () {
				printf(
					'<label><input type="checkbox" name="%s" value="1" %s> %s</label>',
					esc_attr( PARISH_OPTION_TG_FORWARDS ),
					checked( get_option( PARISH_OPTION_TG_FORWARDS ), true, false ),
					esc_html__( 'Also import posts forwarded from other channels (e.g. ROCOR Europe)', 'parish-core' )
				);
			},
			'parish',
			'parish_telegram'
		);
	}
);

// Status, "Check now", instant delivery and the test box, below the settings form.
add_action(
	'parish_settings_after_form',
	function () {
		$last    = get_option( 'parish_tg_last' );
		$webhook = (bool) get_option( 'parish_tg_webhook' );
		$notice  = sanitize_text_field( wp_unslash( $_GET['parish_tg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$action  = admin_url( 'admin-post.php' );
		?>
		<hr>
		<h2 id="telegram"><?php esc_html_e( 'Telegram status', 'parish-core' ); ?></h2>
		<?php if ( $notice ) : ?>
			<div class="notice notice-info inline"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>
		<p>
			<?php
			if ( ! get_option( PARISH_OPTION_TG_TOKEN ) ) {
				esc_html_e( 'Not connected: no bot token saved.', 'parish-core' );
			} elseif ( $webhook ) {
				esc_html_e( 'Connected. Telegram sends new posts to the site instantly.', 'parish-core' );
			} else {
				esc_html_e( 'Connected. The site checks Telegram for new posts every 5 minutes.', 'parish-core' );
			}
			if ( is_array( $last ) ) {
				/* translators: 1: time ago, 2: result */
				echo ' ' . esc_html( sprintf( __( 'Last check %1$s ago: %2$s', 'parish-core' ), human_time_diff( $last[0] ), $last[1] ) );
			}
			?>
		</p>
		<?php if ( get_option( PARISH_OPTION_TG_TOKEN ) ) : ?>
			<form method="post" action="<?php echo esc_url( $action ); ?>" style="display:inline-block;margin-right:8px">
				<?php wp_nonce_field( 'parish_tg' ); ?>
				<input type="hidden" name="action" value="parish_tg">
				<input type="hidden" name="do" value="<?php echo $webhook ? 'poll_mode' : 'check'; ?>">
				<?php submit_button( $webhook ? __( 'Switch back to checking every 5 minutes', 'parish-core' ) : __( 'Check Telegram now', 'parish-core' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php if ( ! $webhook ) : ?>
				<form method="post" action="<?php echo esc_url( $action ); ?>" style="display:inline-block">
					<?php wp_nonce_field( 'parish_tg' ); ?>
					<input type="hidden" name="action" value="parish_tg">
					<input type="hidden" name="do" value="webhook">
					<?php submit_button( __( 'Switch on instant delivery (live site only)', 'parish-core' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Try a post', 'parish-core' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Paste the text of a Telegram post to see the news post it would make. It is published like a real one, so delete it afterwards.', 'parish-core' ); ?></p>
		<form method="post" action="<?php echo esc_url( $action ); ?>">
			<?php wp_nonce_field( 'parish_tg' ); ?>
			<input type="hidden" name="action" value="parish_tg">
			<input type="hidden" name="do" value="test">
			<textarea name="text" rows="8" class="large-text"></textarea>
			<?php submit_button( __( 'Import as a test', 'parish-core' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}
);

add_action(
	'admin_post_parish_tg',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'parish_tg' );
		$do = sanitize_key( $_POST['do'] ?? '' );
		$msg = '';
		switch ( $do ) {
			case 'check':
				$n   = parish_tg_poll();
				$msg = is_wp_error( $n ) ? $n->get_error_message() : sprintf( '%d new post(s) imported.', $n );
				break;
			case 'webhook':
				$secret = wp_generate_password( 32, false );
				update_option( 'parish_tg_secret', $secret, false );
				$r = parish_tg_api(
					'setWebhook',
					array(
						'url'             => rest_url( 'parish/v1/telegram' ),
						'secret_token'    => $secret,
						'allowed_updates' => array( 'channel_post', 'edited_channel_post' ),
					)
				);
				if ( is_wp_error( $r ) ) {
					$msg = $r->get_error_message();
				} else {
					update_option( 'parish_tg_webhook', 1, false );
					$msg = 'Instant delivery is on.';
				}
				break;
			case 'poll_mode':
				parish_tg_api( 'deleteWebhook' );
				update_option( 'parish_tg_webhook', 0, false );
				$msg = 'The site now checks Telegram every 5 minutes.';
				break;
			case 'test':
				$text = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) );
				$ok   = '' !== trim( $text ) && parish_tg_import(
					array(
						'message_id' => wp_rand( 1000000, 9999999 ),
						'date'       => time(),
						'chat'       => array( 'id' => 'test' ),
						'text'       => $text,
					)
				);
				$msg = $ok ? 'Imported. See Posts.' : 'Nothing imported (empty, #nosite, or the same text was imported recently).';
				break;
		}
		wp_safe_redirect( add_query_arg( 'parish_tg', rawurlencode( $msg ), admin_url( 'options-general.php?page=parish' ) ) . '#telegram' );
		exit;
	}
);
