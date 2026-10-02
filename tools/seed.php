<?php
/**
 * Seeds a fresh WordPress install with the parish site structure and draft
 * content in English and Russian. Safe to re-run: existing pages are kept.
 *
 * Run locally:   automatically by blueprint.json (npm run dev)
 * Run on a host: wp eval-file tools/seed.php   (after activating Polylang,
 *                the Parish Cathedral theme and the Parish Core plugin)
 *
 * Russian texts are a first draft and should be checked by a native speaker.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'PLL' ) || ! function_exists( 'pll_set_post_language' ) ) {
	echo "Polylang must be active before seeding.\n";
	return;
}

/* -------------------------------------------------------------------------
 * Block markup helpers (keeps the content below readable and valid).
 * ---------------------------------------------------------------------- */

function sd_h( string $text, int $level = 2, string $class = '' ): string {
	$attrs = array();
	if ( 2 !== $level ) {
		$attrs['level'] = $level;
	}
	if ( $class ) {
		$attrs['className'] = $class;
	}
	$json = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';
	$cls  = trim( 'wp-block-heading ' . $class );
	return "<!-- wp:heading{$json} -->\n<h{$level} class=\"{$cls}\">{$text}</h{$level}>\n<!-- /wp:heading -->\n\n";
}

function sd_title( string $text ): string {
	return sd_h( $text, 2, 'is-style-parish-section-title' );
}

function sd_p( string $html, string $class = '' ): string {
	if ( $class ) {
		return '<!-- wp:paragraph {"className":"' . $class . "\"} -->\n<p class=\"{$class}\">{$html}</p>\n<!-- /wp:paragraph -->\n\n";
	}
	return "<!-- wp:paragraph -->\n<p>{$html}</p>\n<!-- /wp:paragraph -->\n\n";
}

function sd_list( array $items, bool $timetable = false ): string {
	$out = '';
	foreach ( $items as $item ) {
		if ( is_array( $item ) ) {
			$item = "{$item[0]} <strong>{$item[1]}</strong>";
		}
		$out .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->\n\n";
	}
	if ( $timetable ) {
		return "<!-- wp:list {\"className\":\"is-style-parish-timetable\"} -->\n<ul class=\"wp-block-list is-style-parish-timetable\">{$out}</ul>\n<!-- /wp:list -->\n\n";
	}
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">{$out}</ul>\n<!-- /wp:list -->\n\n";
}

function sd_card( string $inner, string $extra_class = '' ): string {
	$cls = trim( 'is-style-parish-card ' . $extra_class );
	return "<!-- wp:group {\"className\":\"{$cls}\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group {$cls}\">{$inner}</div>\n<!-- /wp:group -->\n\n";
}

function sd_button( string $label, string $url ): string {
	return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"{$url}\">{$label}</a></div>\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->\n\n";
}

function sd_columns( array $columns ): string {
	$out = '';
	foreach ( $columns as $col ) {
		[ $width, $inner ] = is_array( $col ) ? $col : array( '', $col );
		if ( $width ) {
			$out .= "<!-- wp:column {\"width\":\"{$width}\"} -->\n<div class=\"wp-block-column\" style=\"flex-basis:{$width}\">{$inner}</div>\n<!-- /wp:column -->\n\n";
		} else {
			$out .= "<!-- wp:column -->\n<div class=\"wp-block-column\">{$inner}</div>\n<!-- /wp:column -->\n\n";
		}
	}
	return "<!-- wp:columns -->\n<div class=\"wp-block-columns\">{$out}</div>\n<!-- /wp:columns -->\n\n";
}

/**
 * A news category in one language (Polylang), created on first use.
 */
function sd_category( string $slug, string $name, string $lang ): int {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) {
		$made = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		if ( is_wp_error( $made ) ) {
			return 0;
		}
		$term = get_term( $made['term_id'], 'category' );
	}
	if ( function_exists( 'pll_set_term_language' ) ) {
		pll_set_term_language( $term->term_id, $lang );
	}
	return (int) $term->term_id;
}

/**
 * The Youth Group news categories, [ 'en' => id, 'ru' => id ], linked as
 * translations so the language switch moves between them.
 */
function sd_youth_categories(): array {
	$ids = array(
		'en' => sd_category( 'youth-group', 'Youth Group', 'en' ),
		'ru' => sd_category( 'molodezhnaya-gruppa', 'Молодёжная группа', 'ru' ),
	);
	if ( function_exists( 'pll_save_term_translations' ) ) {
		pll_save_term_translations( array_filter( $ids ) );
	}
	return $ids;
}

/**
 * A feed of news posts from one category: photo, date, title and summary,
 * newest first, with "older" / "newer" links. Posting news in that category
 * adds it here; nothing on this page needs editing.
 */
function sd_feed( int $category_id, string $lang ): string {
	$t     = 'ru' === $lang
		? array( 'Пока нет новостей.', 'Новее', 'Старее' )
		: array( 'No updates yet.', 'Newer', 'Older' );
	$query = array(
		'queryId'   => 2,
		'query'     => array(
			'perPage'  => 6,
			'pages'    => 0,
			'offset'   => 0,
			'postType' => 'post',
			'order'    => 'desc',
			'orderBy'  => 'date',
			'inherit'  => false,
			'taxQuery' => array( 'category' => array( $category_id ) ),
		),
		'className' => 'parish-feed',
	);
	return '<!-- wp:query ' . wp_json_encode( $query, JSON_UNESCAPED_UNICODE ) . " -->\n"
		. "<div class=\"wp-block-query parish-feed\"><!-- wp:post-template {\"layout\":{\"type\":\"grid\",\"columnCount\":2}} -->\n"
		. "<!-- wp:group {\"className\":\"parish-feed__item\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group parish-feed__item\">"
		. "<!-- wp:post-featured-image {\"isLink\":true,\"aspectRatio\":\"3/2\"} /-->\n\n"
		. "<!-- wp:post-date /-->\n\n"
		. "<!-- wp:post-title {\"isLink\":true,\"level\":3} /-->\n\n"
		. "<!-- wp:post-excerpt {\"excerptLength\":30} /--></div>\n<!-- /wp:group -->\n"
		. "<!-- /wp:post-template -->\n\n"
		. "<!-- wp:query-pagination {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"space-between\"}} -->\n"
		. "<!-- wp:query-pagination-previous {\"label\":\"{$t[1]}\"} /-->\n<!-- wp:query-pagination-next {\"label\":\"{$t[2]}\"} /-->\n"
		. "<!-- /wp:query-pagination -->\n\n"
		. "<!-- wp:query-no-results -->\n<!-- wp:paragraph -->\n<p>{$t[0]}</p>\n<!-- /wp:paragraph -->\n<!-- /wp:query-no-results --></div>\n"
		. "<!-- /wp:query -->\n\n";
}

function sd_calendar( string $view = 'MONTH', int $height = 600 ): string {
	return '<!-- wp:parish/service-calendar {"view":"' . $view . '","height":' . $height . "} /-->\n\n";
}

function sd_html( string $html ): string {
	return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->\n\n";
}

function sd_separator(): string {
	return "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n";
}

function sd_latest_news( int $count ): string {
	return '<!-- wp:query {"queryId":2,"query":{"perPage":' . $count . ',"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:group {"className":"parish-news-item","layout":{"type":"default"}} -->
<div class="wp-block-group parish-news-item"><!-- wp:post-date /-->

<!-- wp:post-title {"isLink":true,"level":3,"fontSize":"large"} /-->

<!-- wp:post-excerpt {"excerptLength":24} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->' . "\n\n";
}

/**
 * Import an image file into the Media Library (once, keyed by $slug) and
 * return its attachment ID, or 0 if the file is missing or fails to upload.
 */
function sd_import_image( string $path, string $slug, string $alt ): int {
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$existing = get_posts(
		array(
			'name'        => $slug,
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
		)
	);
	if ( $existing ) {
		return $existing[0]->ID;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$upload = wp_upload_bits( basename( $path ), null, file_get_contents( $path ) );
	if ( $upload['error'] ) {
		echo 'Image error: ' . esc_html( $upload['error'] ) . "\n";
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_title'     => $alt,
			'post_name'      => $slug,
			'post_mime_type' => 'image/jpeg',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return $id;
}

/**
 * Import a photo from tools/clergy/ into the Media Library (once) and return
 * an image block for it. Returns '' if the file is missing.
 */
function sd_clergy_photo( string $file, string $alt ): string {
	$id = sd_import_image( __DIR__ . '/clergy/' . $file, 'clergy-' . pathinfo( $file, PATHINFO_FILENAME ), $alt );
	if ( ! $id ) {
		return '';
	}
	$url = esc_url( wp_get_attachment_url( $id ) );
	$alt = esc_attr( $alt );
	return "<!-- wp:image {\"id\":{$id},\"sizeSlug\":\"full\",\"linkDestination\":\"none\",\"className\":\"parish-person__photo\"} -->\n<figure class=\"wp-block-image size-full parish-person__photo\"><img src=\"{$url}\" alt=\"{$alt}\" class=\"wp-image-{$id}\"/></figure>\n<!-- /wp:image -->\n\n";
}

function sd_person( string $role, string $name, string $details, string $photo = '' ): string {
	return sd_card(
		( $photo ? sd_clergy_photo( $photo, wp_strip_all_tags( $name ) ) : '' )
		. sd_p( $role, 'parish-person__role' ) . sd_h( $name ) . sd_p( $details ),
		'parish-person'
	);
}

const SD_CREDIT_SB = array( 'Protodeacon Sergei Baranoff', 'протодиакон Сергий Баранов' );

/**
 * Photos of the Cathedral and parish life in tools/photos/, taken from the
 * parish pages on orthodox-europe.org and from Protodeacon Sergei's albums.
 * Key => [ file, English alt, Russian alt, optional [ English credit, Russian credit ] ].
 */
function sd_photos(): array {
	return array(
		'hero-interior'        => array( 'hero-interior.jpg', 'Frescoes and the great chandelier inside the Cathedral', 'Росписи и паникадило в соборе' ),
		'interior-iconostasis' => array( 'interior-iconostasis.jpg', 'Sunlight falling on the iconostasis and frescoes', 'Солнечный свет на иконостасе и росписях собора' ),
		'dome-fresco'          => array( 'dome-fresco.jpg', 'Christ Pantocrator in the central dome', 'Христос Вседержитель в центральном куполе' ),
		'exterior-dome'        => array( 'exterior-dome.jpg', 'The blue dome of the Cathedral against the sky', 'Синий купол собора на фоне неба' ),
		'exterior-side'        => array( 'exterior-side.jpg', 'The Cathedral from the churchyard', 'Собор со стороны церковного двора' ),
		'icon-flowers'         => array( 'icon-flowers.jpg', 'An icon of the Mother of God decorated with flowers', 'Икона Божией Матери, украшенная цветами' ),
		'vigil-candles'        => array( 'vigil-candles.jpg', 'The faithful holding candles at a service', 'Молящиеся со свечами за богослужением' ),
		'hierarchical-liturgy' => array( 'hierarchical-liturgy.jpg', 'A hierarchical service in the Cathedral', 'Архиерейское богослужение в соборе' ),
		'clergy-group'         => array( 'clergy-group.jpg', 'The Bishop and clergy of the Cathedral', 'Владыка и духовенство собора' ),
		'willow-blessing'      => array( 'willow-blessing.jpg', 'A priest blesses a parishioner holding willow branches', 'Священник благословляет прихожанку с вербой' ),
		'holy-water-blessing'  => array( 'holy-water-blessing.jpg', 'The faithful blessed with holy water at a feast', 'Окропление святой водой на празднике' ),
		'candle'               => array( 'candle.jpg', 'Lighting a candle', 'Возжжение свечи' ),
		'parish-group'         => array( 'parish-group.jpg', 'Parishioners in front of the iconostasis', 'Прихожане у иконостаса' ),
		'youth-group'          => array( 'youth-group.jpg', 'The Youth Group in the Parish House', 'Молодёжная группа в Приходском доме' ),
		'sisterhood'           => array( 'sisterhood.jpg', 'Sisters preparing flowers for a feast', 'Сёстры готовят цветы к празднику' ),
		'sunday-school'        => array( 'sunday-school.jpg', 'Sunday School children with the Bishop', 'Дети воскресной школы с владыкой' ),
		'choir-music'          => array( 'choir-music.jpg', 'Choir music in Church Slavonic', 'Ноты хора на церковнославянском' ),
		'food-bank-team'       => array( 'food-bank-team.jpg', 'Food Bank volunteers outside the Cathedral', 'Волонтёры продуктового банка у собора' ),
		'book-cover'           => array( 'book-cover.jpg', 'Cover of the book “The London Cathedral”', 'Обложка книги «The London Cathedral»' ),
		// Photos by Protodeacon Sergei Baranoff (4th element: photo credit, English and Russian).
		'sb-nave-iconostasis'  => array( 'sb-nave-iconostasis.jpg', 'The nave, chandelier and iconostasis of the Cathedral', 'Храм, паникадило и иконостас собора', SD_CREDIT_SB ),
		'sb-morning-light'     => array( 'sb-morning-light.jpg', 'Morning light falling through the Cathedral during the Liturgy', 'Утренний свет в соборе во время Литургии', SD_CREDIT_SB ),
		'sb-feast-congregation' => array( 'sb-feast-congregation.jpg', 'The faithful at a feast-day service', 'Молящиеся на праздничном богослужении', SD_CREDIT_SB ),
		'sb-chandelier-vigil'  => array( 'sb-chandelier-vigil.jpg', 'The All-night Vigil under the great chandelier', 'Всенощное бдение под паникадилом', SD_CREDIT_SB ),
		'sb-pascha-crowd'      => array( 'sb-pascha-crowd.jpg', 'The Cathedral full of worshippers at a great feast', 'Собор, полный молящихся, на великий праздник', SD_CREDIT_SB ),
		'sb-door-garland'      => array( 'sb-door-garland.jpg', 'The Cathedral door decorated with a garland of flowers', 'Двери собора, украшенные цветочной гирляндой', SD_CREDIT_SB ),
		'sb-procession'        => array( 'sb-procession.jpg', 'A procession of the Cross around the Cathedral', 'Крестный ход вокруг собора', SD_CREDIT_SB ),
		'sb-clergy-feast'      => array( 'sb-clergy-feast.jpg', 'The Bishop, clergy and servers on the patronal feast', 'Владыка, духовенство и алтарники в престольный праздник', SD_CREDIT_SB ),
		'sb-bishop-blessing'   => array( 'sb-bishop-blessing.jpg', 'The Bishop blessing the people with the dikirion and trikirion', 'Архиерейское благословение дикирием и трикирием', SD_CREDIT_SB ),
		'sb-icon-lampada'      => array( 'sb-icon-lampada.jpg', 'An icon of the Mother of God with a burning lampada', 'Икона Божией Матери с горящей лампадой', SD_CREDIT_SB ),
		'sb-icon-kiot'         => array( 'sb-icon-kiot.jpg', 'The Kazan icon of the Mother of God in its carved case', 'Казанская икона Божией Матери в резном киоте', SD_CREDIT_SB ),
		'sb-shroud-flowers'    => array( 'sb-shroud-flowers.jpg', 'The Holy Shroud decorated with flowers', 'Плащаница, украшенная цветами', SD_CREDIT_SB ),
		'sb-theophany-water'   => array( 'sb-theophany-water.jpg', 'The Great Blessing of Water at Theophany', 'Великое освящение воды на Богоявление', SD_CREDIT_SB ),
		'sb-feast-red'         => array( 'sb-feast-red.jpg', 'A festal service with the clergy in red vestments', 'Праздничное богослужение, духовенство в красных облачениях', SD_CREDIT_SB ),
		'sb-vigil-crowd'       => array( 'sb-vigil-crowd.jpg', 'The faithful at the evening service', 'Молящиеся на вечернем богослужении', SD_CREDIT_SB ),
	);
}

/**
 * Attachment ID for a photo from sd_photos(), importing it on first use.
 * $lang 'ru' imports a separate copy with Russian alt text, for featured images
 * (a page banner takes its alt text from the attachment, not the page).
 */
function sd_photo_id( string $key, string $lang = 'en' ): int {
	[ $file, $alt, $alt_ru ] = sd_photos()[ $key ];
	$credit = sd_photos()[ $key ][3] ?? array();
	if ( 'ru' === $lang ) {
		$id = sd_import_image( __DIR__ . '/photos/' . $file, 'photo-' . $key . '-ru', $alt_ru );
	} else {
		$id = sd_import_image( __DIR__ . '/photos/' . $file, 'photo-' . $key, $alt );
	}
	if ( $id ) {
		// Both language copies share a source key, so a credit edited on one
		// is copied to the other (see parish-core photo-credit.php).
		update_post_meta( $id, 'parish_photo_source', $key );
		if ( $credit ) {
			update_post_meta( $id, 'parish_photo_credit', $credit[0] );
			update_post_meta( $id, 'parish_photo_credit_ru', $credit[1] );
		}
	}
	return $id;
}

/**
 * Image block for a photo from sd_photos(), with alt text in the page's language.
 */
function sd_photo( string $key, string $lang, string $class = '', string $size = 'large' ): string {
	$id = sd_photo_id( $key );
	if ( ! $id ) {
		return '';
	}
	$url   = esc_url( wp_get_attachment_image_url( $id, $size ) ?: wp_get_attachment_url( $id ) );
	$alt   = esc_attr( sd_photos()[ $key ][ 'ru' === $lang ? 2 : 1 ] );
	$attrs = array( 'id' => $id, 'sizeSlug' => $size, 'linkDestination' => 'none' );
	if ( $class ) {
		$attrs['className'] = $class;
	}
	$cls = trim( "wp-block-image size-{$size} {$class}" );
	return '<!-- wp:image ' . wp_json_encode( $attrs ) . " -->\n<figure class=\"{$cls}\"><img src=\"{$url}\" alt=\"{$alt}\" class=\"wp-image-{$id}\"/></figure>\n<!-- /wp:image -->\n\n";
}

/**
 * Gallery block of photos from sd_photos(). Photos open larger when clicked
 * (lightbox is switched on for images in theme.json).
 */
function sd_gallery( array $keys, string $lang, int $columns = 3, string $class = '' ): string {
	$images = '';
	foreach ( $keys as $key ) {
		$images .= sd_photo( $key, $lang );
	}
	$attrs = array( 'columns' => $columns, 'linkTo' => 'none' );
	if ( $class ) {
		$attrs['className'] = $class;
	}
	$cls = trim( "wp-block-gallery has-nested-images columns-{$columns} is-cropped {$class}" );
	return '<!-- wp:gallery ' . wp_json_encode( $attrs ) . " -->\n<figure class=\"{$cls}\">{$images}</figure>\n<!-- /wp:gallery -->\n\n";
}

/**
 * Full-width photo banner at the top of the home page (a Cover block, so the
 * photo and text can be changed in the editor).
 */
function sd_hero( string $lang ): string {
	$id = sd_photo_id( 'sb-nave-iconostasis' );
	if ( ! $id ) {
		return '';
	}
	$url = esc_url( wp_get_attachment_url( $id ) );
	$t   = 'ru' === $lang
		? array( 'Русская Православная Церковь Заграницей', 'Добро пожаловать в Лондонский православный собор', 'Приход в Лондоне с 1725 года · Чизик, Западный Лондон', 'Расписание богослужений', 'Как нас найти' )
		: array( 'Russian Orthodox Church Outside Russia', 'Welcome to the London Orthodox Cathedral', 'A parish in London since 1725 · Chiswick, West London', 'Service times', 'Plan your visit' );

	$inner = sd_p( $t[0], 'parish-hero__eyebrow' )
		. "<!-- wp:heading {\"level\":1} -->\n<h1 class=\"wp-block-heading\">{$t[1]}</h1>\n<!-- /wp:heading -->\n\n"
		. sd_p( $t[2], 'parish-hero__lead' )
		. "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"{{url:services}}\">{$t[3]}</a></div>\n<!-- /wp:button -->\n\n"
		. "<!-- wp:button {\"className\":\"is-style-outline\"} -->\n<div class=\"wp-block-button is-style-outline\"><a class=\"wp-block-button__link wp-element-button\" href=\"{{url:visit}}\">{$t[4]}</a></div>\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->\n\n";

	return '<!-- wp:cover {"url":"' . $url . '","id":' . $id . ',"dimRatio":70,"overlayColor":"ink","isUserOverlayColor":true,"minHeight":460,"align":"full","className":"parish-hero","layout":{"type":"constrained","contentSize":"1140px"}} -->' . "\n"
		. '<div class="wp-block-cover alignfull parish-hero" style="min-height:460px"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim-70 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-' . $id . '" alt="" src="' . $url . '" data-object-fit="cover"/><div class="wp-block-cover__inner-container">'
		. $inner
		. "</div></div>\n<!-- /wp:cover -->\n\n";
}

/**
 * Embedded Google Map of the Cathedral.
 */
function sd_map( string $title, int $height = 380, string $class = '' ): string {
	$cls = trim( "parish-embed {$class}" );
	return sd_html( '<div class="' . $cls . '"><iframe src="https://www.google.com/maps?q=Russian+Orthodox+Cathedral,+57+Harvard+Road,+London+W4+4ED&amp;output=embed" height="' . $height . '" loading="lazy" title="' . $title . '"></iframe></div>' );
}

/**
 * A phone number that can be tapped to call.
 */function sd_tel( string $number ): string {
	return '<a href="tel:' . preg_replace( '/[^\d+]/', '', $number ) . '">' . $number . '</a>';
}

/**
 * "Regular services" card, shared by Home and Services so the times are
 * readable even when the Google Calendar is slow or blocked.
 */
function sd_regular_services( string $lang, bool $link = true, string $class = '' ): string {
	if ( 'ru' === $lang ) {
		return sd_card(
			sd_h( 'Регулярные богослужения' )
			. sd_list(
				array(
					array( 'Всенощное бдение, суббота', '17:00' ),
					array( 'Литургия, воскресенье', '10:00' ),
					array( 'Литургия, будни и суббота', '9:00' ),
					array( 'Акафист, пятница', '18:15' ),
				),
				true
			)
			. ( $link ? sd_p( '<a href="{{url:services}}">Полное расписание</a>', 'parish-more' ) : '' ),
			$class
		);
	}
	return sd_card(
		sd_h( 'Regular services' )
		. sd_list(
			array(
				array( 'Saturday Vigil', '5.00 pm' ),
				array( 'Sunday Liturgy', '10.00 am' ),
				array( 'Weekday / Saturday Liturgy', '9.00 am' ),
				array( 'Friday Akathist', '6.15 pm' ),
			),
			true
		)
		. ( $link ? sd_p( '<a href="{{url:services}}">Full schedule</a>', 'parish-more' ) : '' ),
		$class
	);
}

/* -------------------------------------------------------------------------
 * 1. Languages (English default, Russian second).
 * ---------------------------------------------------------------------- */

function sd_languages(): void {
	$model = PLL()->model;
	$have  = wp_list_pluck( $model->get_languages_list(), 'slug' );
	$defs  = array(
		array( 'name' => 'English', 'slug' => 'en', 'locale' => 'en_GB', 'rtl' => false, 'term_group' => 0, 'flag' => 'gb' ),
		array( 'name' => 'Русский', 'slug' => 'ru', 'locale' => 'ru_RU', 'rtl' => false, 'term_group' => 1, 'flag' => 'ru' ),
	);
	foreach ( $defs as $def ) {
		if ( in_array( $def['slug'], $have, true ) ) {
			continue;
		}
		if ( isset( $model->languages ) && method_exists( $model->languages, 'add' ) ) {
			$result = $model->languages->add( $def ); // Polylang 3.7+.
		} else {
			$result = $model->add_language( $def ); // Older Polylang.
		}
		if ( is_wp_error( $result ) ) {
			echo 'Language error: ' . esc_html( $result->get_error_message() ) . "\n";
		}
	}
	if ( method_exists( $model, 'clean_languages_cache' ) ) {
		$model->clean_languages_cache();
	}

	// WordPress's own translations, so dates ("15 июля 2026") and built-in labels
	// ("Результаты поиска") appear in Russian. Needs internet access; skipped if offline.
	require_once ABSPATH . 'wp-admin/includes/admin.php';
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	foreach ( array( 'en_GB', 'ru_RU' ) as $locale ) {
		try {
			$ok = wp_download_language_pack( $locale );
		} catch ( Throwable $e ) {
			$ok = false;
		}
		if ( ! $ok ) {
			echo 'Could not download the ' . esc_html( $locale ) . " language pack.\n";
		}
	}
}

/**
 * Site title and tagline in Russian (Polylang → Languages → Strings translations),
 * used in the browser tab and when links are shared.
 */
function sd_translate_strings(): void {
	$ru = PLL()->model->get_language( 'ru' );
	if ( ! $ru || ! class_exists( 'PLL_MO' ) ) {
		return;
	}
	$mo = new PLL_MO();
	$mo->import_from_db( $ru );
	$mo->add_entry( $mo->make_entry( get_option( 'blogname' ), 'Лондонский православный собор' ) );
	$mo->add_entry( $mo->make_entry( get_option( 'blogdescription' ), 'Собор Рождества Пресвятой Богородицы и Святых Царственных Мучеников (РПЦЗ)' ) );
	$mo->export_to_db( $ru );
}

/* -------------------------------------------------------------------------
 * 2. Pages. Content may contain {{url:<english-slug>}} placeholders,
 *    resolved to the same-language page URL once all pages exist.
 * ---------------------------------------------------------------------- */

function sd_pages(): array {
	$pages = array();

	// Opens the Maps app on phones, so visitors get directions from where they are.
	$maps_url = 'https://maps.app.goo.gl/6hcgWaNTynMqAhcx6';

	/* ---- Home ---- */
	$pages['home'] = array(
		'en' => array(
			'slug'    => 'home',
			'title'   => 'Home',
			'content' => sd_hero( 'en' )
			. sd_columns(
				array(
					array(
						'64%',
						sd_regular_services( 'en', true, 'parish-mobile-only' )
						. sd_title( 'About the Cathedral' )
						. sd_p( 'The Cathedral of the Nativity of the Mother of God and the Holy Royal Martyrs is the cathedral of the Diocese of Great Britain and Western Europe of the Russian Orthodox Church Outside Russia (ROCOR). Founded in 1725, it is one of the oldest Orthodox parishes in the British Isles. Today it worships in a new cathedral in Chiswick, West London, built in the Pskov style and consecrated in 2018.' )
						. sd_p( 'The Divine Liturgy is normally celebrated four times a week, with evening services several times a week. Services are in Church Slavonic and English. <a href="{{url:visit}}">New to Orthodoxy? Read our guidance for visitors.</a>' )
						. sd_title( 'Upcoming services' )
						. sd_calendar( 'MONTH', 600 )
						. sd_p( '<a href="{{url:services}}">Full schedule of services →</a><br><a href="{{url:services/readings}}">Today’s Scripture readings →</a>', 'parish-more' )
						. sd_title( 'Latest news' )
						. sd_latest_news( 4 )
						. sd_p( '<a href="{{url:news}}">All news →</a>', 'parish-more' ),
					),
					array(
						'36%',
						sd_regular_services( 'en', true, 'parish-desktop-only' )
						. sd_card(
							sd_h( 'Cathedral address' )
							. sd_p( '57 Harvard Road<br>London W4 4ED' )
							. sd_map( 'Map showing the Cathedral, 57 Harvard Road, London W4 4ED', 220, 'parish-card-map' )
							. sd_p( '<a href="' . $maps_url . '">Open in Google Maps</a><br><a href="{{url:visit}}">Directions and visiting</a>', 'parish-more' )
						)
						. sd_card(
							sd_h( 'Church shop' )
							. sd_list(
								array(
									array( 'Saturday', '9.00 – 11.30 am' ),
									array( 'Sunday', '9.00 am – 2.00 pm' ),
								),
								true
							)
						)
						. sd_card(
							sd_h( 'Main contacts' )
							. sd_p( '<strong>Archpriest Vitaly Serapinas</strong><br>' . sd_tel( '+44 7935 700 721' ) )
							. sd_p( '<strong>Archpriest Yaroslav Hudymenko</strong><br>' . sd_tel( '+44 7563 407 991' ) )
							. sd_p( '<strong>Hieromonk Theodore</strong><br>' . sd_tel( '+44 7534 694 534' ) )
							. sd_p( '<a href="{{url:clergy}}">All clergy</a>', 'parish-more' )
						)
						. sd_card(
							sd_h( 'Support the Cathedral' )
							. sd_p( 'The Cathedral is funded entirely by its parishioners. UK taxpayers can add 25% to every gift through Gift Aid.' )
							. sd_button( 'Donate', '{{url:donate}}' )
						),
					),
				)
			)
			. sd_title( 'The Cathedral' )
			. sd_gallery( array( 'interior-iconostasis', 'dome-fresco', 'exterior-dome', 'icon-flowers' ), 'en', 4, 'parish-photo-strip' )
			. sd_p( '<a href="{{url:parish-life/gallery}}">More photos →</a>', 'parish-more' )
			. sd_title( 'Parish life' )
			. sd_columns(
				array(
					sd_card( sd_photo( 'sisterhood', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( 'Sisterhood', 3 ) . sd_p( 'Visiting the sick, helping the poor, baking prosphora and caring for the vestments.' ) . sd_p( '<a href="{{url:parish-life/sisterhood}}">Sisterhood of St Xenia →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'sunday-school', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( 'Sunday School', 3 ) . sd_p( 'Classes in the Faith for children of the parish during the school year.' ) . sd_p( '<a href="{{url:parish-life/sunday-school}}">Sunday School →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'youth-group', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( 'Youth Group', 3 ) . sd_p( 'Evening talks, pilgrimages, service projects and film nights for ages 14–30.' ) . sd_p( '<a href="{{url:parish-life/youth-group}}">Youth Group →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'choir-music', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( 'Choir', 3 ) . sd_p( 'A mixed choir open to all who wish to sing. Rehearsals on Sundays at 9.00 am.' ) . sd_p( '<a href="{{url:parish-life/choir}}">Choir →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'food-bank-team', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( 'Food Bank', 3 ) . sd_p( 'Free food every two weeks for families in need, run from the Cathedral House.' ) . sd_p( '<a href="{{url:parish-life/food-bank}}">Food Bank →</a>', 'parish-more' ) ),
				)
			),
		),
		'ru' => array(
			'slug'    => 'glavnaya',
			'title'   => 'Главная',
			'content' => sd_hero( 'ru' )
			. sd_columns(
				array(
					array(
						'64%',
						sd_regular_services( 'ru', true, 'parish-mobile-only' )
						. sd_title( 'О соборе' )
						. sd_p( 'Собор Рождества Пресвятой Богородицы и Святых Царственных Мучеников — кафедральный собор Епархии Великобритании и Западной Европы Русской Православной Церкви Заграницей (РПЦЗ). Приход, основанный в 1725 году, — один из старейших православных приходов на Британских островах. Сегодня богослужения совершаются в новом соборе в Чизике, на западе Лондона, построенном в псковском стиле и освящённом в 2018 году.' )
						. sd_p( 'Божественная литургия, как правило, совершается четыре раза в неделю, вечерние богослужения — несколько раз в неделю. Службы идут на церковнославянском и английском языках. <a href="{{url:visit}}">Впервые в православном храме? Прочитайте наши советы для посетителей.</a>' )
						. sd_title( 'Ближайшие богослужения' )
						. sd_calendar( 'MONTH', 600 )
						. sd_p( '<a href="{{url:services}}">Полное расписание богослужений →</a><br><a href="{{url:services/readings}}">Чтения дня →</a>', 'parish-more' )
						. sd_title( 'Последние новости' )
						. sd_latest_news( 4 )
						. sd_p( '<a href="{{url:news}}">Все новости →</a>', 'parish-more' ),
					),
					array(
						'36%',
						sd_regular_services( 'ru', true, 'parish-desktop-only' )
						. sd_card(
							sd_h( 'Адрес собора' )
							. sd_p( '57 Harvard Road<br>London W4 4ED' )
							. sd_map( 'Карта: собор, 57 Harvard Road, London W4 4ED', 220, 'parish-card-map' )
							. sd_p( '<a href="' . $maps_url . '">Открыть в Google Картах</a><br><a href="{{url:visit}}">Как добраться</a>', 'parish-more' )
						)
						. sd_card(
							sd_h( 'Церковная лавка' )
							. sd_list(
								array(
									array( 'Суббота', '9:00 – 11:30' ),
									array( 'Воскресенье', '9:00 – 14:00' ),
								),
								true
							)
						)
						. sd_card(
							sd_h( 'Основные контакты' )
							. sd_p( '<strong>Протоиерей Виталий Серапинас</strong><br>' . sd_tel( '+44 7935 700 721' ) )
							. sd_p( '<strong>Протоиерей Ярослав Гудименко</strong><br>' . sd_tel( '+44 7563 407 991' ) )
							. sd_p( '<strong>Иеромонах Феодор</strong><br>' . sd_tel( '+44 7534 694 534' ) )
							. sd_p( '<a href="{{url:clergy}}">Всё духовенство</a>', 'parish-more' )
						)
						. sd_card(
							sd_h( 'Поддержать собор' )
							. sd_p( 'Собор существует исключительно на пожертвования прихожан. Налогоплательщики Великобритании могут увеличить каждое пожертвование на 25% через Gift Aid.' )
							. sd_button( 'Пожертвовать', '{{url:donate}}' )
						),
					),
				)
			)
			. sd_title( 'Собор' )
			. sd_gallery( array( 'interior-iconostasis', 'dome-fresco', 'exterior-dome', 'icon-flowers' ), 'ru', 4, 'parish-photo-strip' )
			. sd_p( '<a href="{{url:parish-life/gallery}}">Больше фотографий →</a>', 'parish-more' )
			. sd_title( 'Приходская жизнь' )
			. sd_columns(
				array(
					sd_card( sd_photo( 'sisterhood', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( 'Сестричество', 3 ) . sd_p( 'Посещение больных, помощь нуждающимся, выпечка просфор и забота о церковных облачениях.' ) . sd_p( '<a href="{{url:parish-life/sisterhood}}">Сестричество св. Ксении →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'sunday-school', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( 'Воскресная школа', 3 ) . sd_p( 'Занятия по основам веры для детей прихода в течение учебного года.' ) . sd_p( '<a href="{{url:parish-life/sunday-school}}">Воскресная школа →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'youth-group', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( 'Молодёжная группа', 3 ) . sd_p( 'Вечерние беседы, паломничества, волонтёрство и киновечера для молодёжи 14–30 лет.' ) . sd_p( '<a href="{{url:parish-life/youth-group}}">Молодёжная группа →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'choir-music', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( 'Хор', 3 ) . sd_p( 'Смешанный хор, открытый для всех желающих петь. Спевки по воскресеньям в 9:00.' ) . sd_p( '<a href="{{url:parish-life/choir}}">Хор →</a>', 'parish-more' ) ),
					sd_card( sd_photo( 'food-bank-team', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( 'Продуктовый банк', 3 ) . sd_p( 'Раз в две недели — бесплатные продукты для нуждающихся семей в Приходском доме.' ) . sd_p( '<a href="{{url:parish-life/food-bank}}">Продуктовый банк →</a>', 'parish-more' ) ),
				)
			),
		),
	);

	/* ---- Services ---- */
	$pages['services'] = array(
		'en' => array(
			'slug'    => 'services',
			'title'   => 'Schedule of Services',
			'content' => sd_p( 'Divine Services normally follow a pattern of four Liturgies each week, with additional evening services. The Divine Liturgy is always celebrated on Saturday and Sunday mornings, and there is always a Vigil on Saturday evening. Apart from the summer, the Akathist to the Theotokos is served on Friday evenings. Weekday Liturgies depend on the commemorations of the week.' )
				. sd_columns(
					array(
						sd_regular_services( 'en', false ),
						sd_card(
							sd_h( 'The services' )
							. sd_p( '<strong>Vigil</strong>: the Saturday evening service that prepares for Sunday, joining Vespers and Matins.' )
							. sd_p( '<strong>Divine Liturgy</strong>: the main service of the Church, at which Holy Communion is given.' )
							. sd_p( '<strong>Akathist</strong>: a hymn of praise sung standing. On Fridays it is sung to the Mother of God.' )
						),
					)
				)
				. sd_calendar( 'MONTH', 700 )
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Confession', 3 )
							. sd_p( 'Confessions are heard on Saturdays and during the Saturday evening Vigil. On Sunday mornings a limited number are heard before the Liturgy, mainly for the elderly and those travelling from afar. Confessions are never heard after the Liturgy has begun.' )
						),
						sd_card(
							sd_h( 'Before you visit', 3 )
							. sd_p( 'Please read our <a href="{{url:visit}}">guidance on visiting the Cathedral</a>. It covers dress, receiving the Sacraments, and coming with small children.' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Language', 3 )
							. sd_p( 'Services are in Church Slavonic and English. Readings and sermons are always in both. Service books can be borrowed or bought at the kiosk.' )
						),
						sd_card(
							sd_h( 'Children at services', 3 )
							. sd_p( 'Children of all ages are welcome. If they need more room, the lower church is open with the service relayed by speakers. To feed a baby, ask at the kiosk for a private room in the Church House.' )
						),
						sd_card(
							sd_h( 'Meals after the Liturgy', 3 )
							. sd_p( 'On most Sundays (not in summer) the Sisterhood serves a meal in the Hall after the Liturgy. Everyone is welcome, especially visitors. We begin with a prayer once the clergy arrive.' )
						),
					)
				),
		),
		'ru' => array(
			'slug'    => 'bogosluzheniya',
			'title'   => 'Расписание богослужений',
			'content' => sd_p( 'Как правило, Божественная литургия совершается четыре раза в неделю, также совершаются вечерние богослужения. Литургия всегда служится в субботу и воскресенье утром, а в субботу вечером — всенощное бдение. Кроме летнего времени, по пятницам вечером читается акафист Божией Матери. Литургии в будние дни зависят от памятей седмицы.' )
				. sd_columns(
					array(
						sd_regular_services( 'ru', false ),
						sd_card(
							sd_h( 'О богослужениях' )
							. sd_p( '<strong>Всенощное бдение</strong> — субботнее вечернее богослужение накануне воскресенья: вечерня и утреня.' )
							. sd_p( '<strong>Божественная литургия</strong> — главное богослужение Церкви, за которым совершается Причащение.' )
							. sd_p( '<strong>Акафист</strong> — хвалебное песнопение, которое поют стоя. По пятницам поётся акафист Божией Матери.' )
						),
					)
				)
				. sd_calendar( 'MONTH', 700 )
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Исповедь', 3 )
							. sd_p( 'Исповедь совершается по субботам и во время субботнего всенощного бдения. В воскресенье утром перед литургией исповедуют ограниченное число людей, в основном пожилых и приехавших издалека. После начала литургии исповедь не совершается.' )
						),
						sd_card(
							sd_h( 'Перед посещением', 3 )
							. sd_p( 'Пожалуйста, ознакомьтесь с <a href="{{url:visit}}">советами для посетителей собора</a>: об одежде, о подготовке к Таинствам и о посещении храма с маленькими детьми.' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Язык богослужений', 3 )
							. sd_p( 'Богослужения совершаются на церковнославянском и английском языках. Чтения и проповеди всегда звучат на обоих. Богослужебные книги можно взять или купить в церковной лавке.' )
						),
						sd_card(
							sd_h( 'Дети на службе', 3 )
							. sd_p( 'Детям любого возраста мы рады. Если ребёнку нужно больше простора, открыт нижний храм, куда транслируется служба. Покормить малыша можно в отдельной комнате Приходского дома — спросите в лавке.' )
						),
						sd_card(
							sd_h( 'Трапеза после литургии', 3 )
							. sd_p( 'Почти каждое воскресенье (кроме лета) сестричество накрывает трапезу в зале после литургии. Приглашаются все, особенно гости. Трапеза начинается с молитвы после прихода духовенства.' )
						),
					)
				),
		),
	);

	/* ---- Daily readings: filled in live by [parish_readings] (parish-core/includes/readings.php) ---- */
	$pages['services/readings'] = array(
		'parent' => 'services',
		'en'     => array(
			'slug'    => 'daily-readings',
			'title'   => 'Daily Readings',
			'content' => sd_p( 'The Epistle and Gospel appointed for each day, with the saints and feasts of the day, according to the Church calendar (Old Style) kept by the Cathedral.' )
				. "<!-- wp:shortcode -->\n[parish_readings]\n<!-- /wp:shortcode -->\n\n",
		),
		'ru'     => array(
			'slug'    => 'chteniya-dnya',
			'title'   => 'Чтения дня',
			'content' => sd_p( 'Апостольские и евангельские чтения на каждый день по церковному календарю (по старому стилю), которому следует собор.' )
				. "<!-- wp:shortcode -->\n[parish_readings]\n<!-- /wp:shortcode -->\n\n",
		),
	);

	/* ---- Sacraments (draft text: to be checked by the clergy) ---- */
	$pages['sacraments'] = array(
		'en' => array(
			'slug'    => 'sacraments',
			'title'   => 'Sacraments',
			'content' => sd_p( 'The Holy Mysteries (Sacraments) and other services of the Church are celebrated at the Cathedral for its parishioners and for all Orthodox Christians. Please speak to a priest before arranging any of them.' )
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Baptism' )
							. sd_p( 'To arrange a baptism, for a child or an adult, please contact Archpriest Vitaly Serapinas on ' . sd_tel( '+44 7935 700 721' ) . '.' )
							. sd_p( 'At least one godparent should be an Orthodox Christian. Adults are baptised after a period of preparation with a priest.' )
						),
						sd_card(
							sd_h( 'Weddings' )
							. sd_p( 'Please contact Archpriest Vitaly Serapinas on ' . sd_tel( '+44 7935 700 721' ) . ' well in advance to discuss preparation and a date.' )
							. sd_p( 'Weddings are not celebrated during the fasting seasons or on some other days of the Church year.' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Confession' )
							. sd_p( 'Confessions are heard on Saturdays and during the Saturday evening Vigil, and a limited number before the Sunday Liturgy. See the <a href="{{url:services}}">schedule of services</a>.' )
						),
						sd_card(
							sd_h( 'Holy Communion' )
							. sd_p( 'Holy Communion is for Orthodox Christians who have prepared for it, including Confession, normally the day before. See <a href="{{url:visit}}">guidance for visitors</a>.' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Funerals and memorial services' )
							. sd_p( 'In the event of a death, please contact one of the <a href="{{url:clergy}}">clergy</a> as soon as possible to arrange the funeral. Memorial services (panikhidas) for the departed can also be arranged with the clergy.' )
						),
						sd_card(
							sd_h( 'Other services' )
							. sd_p( 'Blessings of homes and cars, prayer services (molebens) and visits to the sick at home or in hospital can be arranged with the <a href="{{url:clergy}}">clergy</a>.' )
						),
					)
				),
		),
		'ru' => array(
			'slug'    => 'tainstva',
			'title'   => 'Таинства и требы',
			'content' => sd_p( 'Таинства и требы совершаются в соборе для прихожан и для всех православных христиан. Пожалуйста, предварительно поговорите со священником.' )
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Крещение' )
							. sd_p( 'Чтобы договориться о крещении ребёнка или взрослого, обращайтесь к протоиерею Виталию Серапинасу: ' . sd_tel( '+44 7935 700 721' ) . '.' )
							. sd_p( 'Хотя бы один из восприемников должен быть православным христианином. Взрослые принимают крещение после подготовки со священником.' )
						),
						sd_card(
							sd_h( 'Венчание' )
							. sd_p( 'Пожалуйста, заранее обратитесь к протоиерею Виталию Серапинасу (' . sd_tel( '+44 7935 700 721' ) . '), чтобы обсудить подготовку и дату.' )
							. sd_p( 'Венчание не совершается во время постов и в некоторые другие дни церковного года.' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Исповедь' )
							. sd_p( 'Исповедь совершается по субботам и во время субботнего всенощного бдения, а также, для ограниченного числа людей, перед воскресной литургией. См. <a href="{{url:services}}">расписание богослужений</a>.' )
						),
						sd_card(
							sd_h( 'Причастие' )
							. sd_p( 'К Святому Причастию приступают православные христиане, подготовившиеся к нему, в том числе исповедью, обычно накануне. См. <a href="{{url:visit}}">советы для посетителей</a>.' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Отпевание и панихиды' )
							. sd_p( 'В случае кончины близкого человека, пожалуйста, как можно скорее обратитесь к одному из <a href="{{url:clergy}}">священников</a>, чтобы договориться об отпевании. Панихиду по усопшим также можно заказать у духовенства.' )
						),
						sd_card(
							sd_h( 'Другие требы' )
							. sd_p( 'Освящение дома и автомобиля, молебны, посещение больных на дому или в больнице — по договорённости с <a href="{{url:clergy}}">духовенством</a>.' )
						),
					)
				),
		),
	);

	/* ---- News (posts page; content comes from news posts) ---- */
	$pages['news'] = array(
		'en' => array( 'slug' => 'news', 'title' => 'News', 'content' => '' ),
		'ru' => array( 'slug' => 'novosti', 'title' => 'Новости', 'content' => '' ),
	);

	/* ---- Parish life (parent) + group pages ---- */
	$pages['parish-life'] = array(
		'en' => array(
			'slug'    => 'parish-life',
			'title'   => 'Parish Life',
			'content' => sd_p( 'The Cathedral is a living parish community. Alongside the Divine Services, parishioners serve one another and the wider community through the groups below. Everyone is welcome to take part: please speak to the group leader, or to one of the clergy.' )
				. sd_columns(
					array(
						sd_card( sd_photo( 'sisterhood', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/sisterhood}}">Sisterhood</a>', 2, 'parish-subhead' ) . sd_p( 'The Sisterhood of St Xenia brings together the women of the Cathedral to support its life.' ) ),
						sd_card( sd_photo( 'youth-group', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/youth-group}}">Youth Group</a>', 2, 'parish-subhead' ) . sd_p( 'For young people aged 14–30: talks, pilgrimages, service projects and social evenings.' ) ),
						sd_card( sd_photo( 'sunday-school', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/sunday-school}}">Sunday School</a>', 2, 'parish-subhead' ) . sd_p( 'Classes in the Faith for children of the parish during the school year.' ) ),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_photo( 'choir-music', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/choir}}">Choir</a>', 2, 'parish-subhead' ) . sd_p( 'All who wish to sing to the glory of God are welcome to join.' ) ),
						sd_card( sd_photo( 'food-bank-team', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/food-bank}}">Food Bank</a>', 2, 'parish-subhead' ) . sd_p( 'Free food for families in need, every two weeks.' ) ),
						sd_card( sd_photo( 'hierarchical-liturgy', 'en', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/gallery}}">Photo gallery</a>', 2, 'parish-subhead' ) . sd_p( 'Feasts, services and parish events in pictures.' ) ),
					)
				),
		),
		'ru' => array(
			'slug'    => 'prihodskaya-zhizn',
			'title'   => 'Приходская жизнь',
			'content' => sd_p( 'Собор — это живая приходская община. Помимо богослужений, прихожане служат друг другу и окружающим через приходские группы. Участвовать может каждый: обратитесь к руководителю группы или к одному из священников.' )
				. sd_columns(
					array(
						sd_card( sd_photo( 'sisterhood', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/sisterhood}}">Сестричество</a>', 2, 'parish-subhead' ) . sd_p( 'Сестричество во имя св. блж. Ксении объединяет женщин прихода для служения собору.' ) ),
						sd_card( sd_photo( 'youth-group', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/youth-group}}">Молодёжная группа</a>', 2, 'parish-subhead' ) . sd_p( 'Для молодёжи 14–30 лет: беседы, паломничества, волонтёрство и встречи.' ) ),
						sd_card( sd_photo( 'sunday-school', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/sunday-school}}">Воскресная школа</a>', 2, 'parish-subhead' ) . sd_p( 'Занятия по основам веры для детей прихода в течение учебного года.' ) ),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_photo( 'choir-music', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/choir}}">Хор</a>', 2, 'parish-subhead' ) . sd_p( 'Приглашаем всех, кто желает петь во славу Божию.' ) ),
						sd_card( sd_photo( 'food-bank-team', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/food-bank}}">Продуктовый банк</a>', 2, 'parish-subhead' ) . sd_p( 'Бесплатные продукты для нуждающихся семей раз в две недели.' ) ),
						sd_card( sd_photo( 'hierarchical-liturgy', 'ru', 'parish-card__photo', 'medium_large' ) . sd_h( '<a href="{{url:parish-life/gallery}}">Фотогалерея</a>', 2, 'parish-subhead' ) . sd_p( 'Праздники, богослужения и приходская жизнь в фотографиях.' ) ),
					)
				),
		),
	);

	$group = function ( string $intro, string $what_h, array $what, string $when_h, string $when, string $contact_h, string $contact ): string {
		return sd_columns(
			array(
				array( '64%', sd_p( $intro ) . sd_h( $what_h, 2, 'parish-subhead' ) . sd_list( $what ) ),
				array( '36%', sd_card( sd_h( $when_h, 2, 'parish-subhead' ) . sd_p( $when ) ) . sd_card( sd_h( $contact_h, 2, 'parish-subhead' ) . sd_p( $contact ) ) ),
			)
		);
	};

	$pages['parish-life/sisterhood'] = array(
		'parent' => 'parish-life',
		'en'     => array(
			'slug'    => 'sisterhood',
			'title'   => 'Sisterhood of St Xenia',
			'content' => $group(
				'The Sisterhood of St Xenia brings together the women of the Cathedral to support its life through prayer and practical service.',
				'What we do',
				array( 'Visiting the sick and the elderly', 'Helping those in need', 'Baking prosphora', 'Caring for the sacred vestments', 'Preparing meals for feast days and Sundays' ),
				'When we meet',
				'Details to be added by the Head Sister.',
				'Contact',
				'Head Sister: <em>name to be added</em><br><a href="{{url:clergy}}">Contact the Cathedral clergy</a>'
			),
		),
		'ru'     => array(
			'slug'    => 'sestrichestvo',
			'title'   => 'Сестричество св. Ксении',
			'content' => $group(
				'Сестричество во имя святой блаженной Ксении Петербургской объединяет женщин прихода для молитвы и практического служения собору.',
				'Чем мы занимаемся',
				array( 'Посещаем больных и пожилых', 'Помогаем нуждающимся', 'Печём просфоры', 'Заботимся о церковных облачениях', 'Готовим трапезы на праздники и по воскресеньям' ),
				'Когда мы встречаемся',
				'Информацию добавит старшая сестра.',
				'Контакты',
				'Старшая сестра: <em>имя будет добавлено</em><br><a href="{{url:clergy}}">Связаться с духовенством собора</a>'
			),
		),
	);

	// Youth Group: like the old site, a feed of the group's news ("Latest
	// youth updates") instead of contact and upcoming-events boxes. Posting
	// news in the "Youth Group" category adds it to the feed.
	$youth_cats = sd_youth_categories();
	$pages['parish-life/youth-group'] = array(
		'parent' => 'parish-life',
		'en'     => array(
			'slug'    => 'youth-group',
			'title'   => 'Youth Group',
			'content' => sd_p( 'The Youth Group supports and coordinates the activities of young people in the parish. It welcomes everyone aged roughly 14 to 30.' )
				. sd_h( 'What we do', 2, 'parish-subhead' )
				. sd_list( array( 'Evening talks and discussions', 'Youth pilgrimages', 'Service projects', 'Film nights and social events', 'Helping with parish activities' ) )
				. sd_h( 'Latest youth updates', 2, 'parish-subhead' )
				. sd_feed( $youth_cats['en'], 'en' ),
		),
		'ru'     => array(
			'slug'    => 'molodezhnaya-gruppa',
			'title'   => 'Молодёжная группа',
			'content' => sd_p( 'Молодёжная группа объединяет и организует деятельность молодёжи прихода. Приглашаем всех в возрасте примерно от 14 до 30 лет.' )
				. sd_h( 'Чем мы занимаемся', 2, 'parish-subhead' )
				. sd_list( array( 'Вечерние беседы и обсуждения', 'Молодёжные паломничества', 'Волонтёрские проекты', 'Киновечера и встречи', 'Помощь в приходских делах' ) )
				. sd_h( 'Новости молодёжной группы', 2, 'parish-subhead' )
				. sd_feed( $youth_cats['ru'], 'ru' ),
		),
	);

	$pages['parish-life/sunday-school'] = array(
		'parent' => 'parish-life',
		'en'     => array(
			'slug'    => 'sunday-school',
			'title'   => 'Sunday School',
			'content' => $group(
				'The parish Sunday School teaches the Orthodox Faith to the children of the parish during the school year.',
				'What we offer',
				array( 'Classes for different age groups', 'Preparation for feasts and parish celebrations', 'Church singing and crafts' ),
				'Term dates',
				'Classes resume in September. Details to be added by the Sunday School.',
				'Contact',
				'Head teacher: <em>name to be added</em><br><a href="{{url:clergy}}">Contact the Cathedral clergy</a>'
			),
		),
		'ru'     => array(
			'slug'    => 'voskresnaya-shkola',
			'title'   => 'Воскресная школа',
			'content' => $group(
				'Церковно-приходская воскресная школа знакомит детей прихода с православной верой в течение учебного года.',
				'Что мы предлагаем',
				array( 'Занятия для разных возрастных групп', 'Подготовка к праздникам и приходским торжествам', 'Церковное пение и творчество' ),
				'Расписание',
				'Занятия возобновятся в сентябре. Подробности добавит воскресная школа.',
				'Контакты',
				'Директор: <em>имя будет добавлено</em><br><a href="{{url:clergy}}">Связаться с духовенством собора</a>'
			),
		),
	);

	$pages['parish-life/choir'] = array(
		'parent' => 'parish-life',
		'en'     => array(
			'slug'    => 'choir',
			'title'   => 'Choir',
			'content' => $group(
				'The services are sung by a mixed choir of men and women: any of the faithful who wish to sing to the glory of God and who attend rehearsals regularly. We especially need new singers, including for the Saturday evening Vigil.',
				'Joining the choir',
				array( 'No audition needed, just a willingness to learn', 'Rehearsals every Sunday from 9.00 am in the Parish House', 'Music in Church Slavonic and English' ),
				'Rehearsals',
				'Sundays, 9.00 am<br>Parish House',
				'Contact',
				'Choir Director<br><a href="{{url:clergy}}">Contact the Cathedral clergy</a>'
			),
		),
		'ru'     => array(
			'slug'    => 'hor',
			'title'   => 'Хор',
			'content' => $group(
				'Богослужения поёт смешанный хор, в котором может петь каждый верующий, желающий славить Бога и регулярно посещающий спевки. Особенно нужны новые певчие, в том числе на субботнее всенощное бдение.',
				'Как присоединиться',
				array( 'Прослушивание не требуется — достаточно желания учиться', 'Спевки каждое воскресенье с 9:00 в Приходском доме', 'Песнопения на церковнославянском и английском' ),
				'Спевки',
				'Воскресенье, 9:00<br>Приходской дом',
				'Контакты',
				'Регент хора<br><a href="{{url:clergy}}">Связаться с духовенством собора</a>'
			),
		),
	);

	$pages['parish-life/food-bank'] = array(
		'parent' => 'parish-life',
		'en'     => array(
			'slug'    => 'food-bank',
			'title'   => 'Food Bank',
			'content' => $group(
				'The Cathedral runs an active food bank giving free food to families in need: refugees from the war in Ukraine, parishioners and visitors in difficult circumstances, and those hit by the cost-of-living crisis.',
				'How it works',
				array( 'Open every two weeks at the Cathedral House', 'Free of charge, no questions asked', 'Donations of non-perishable food are welcome' ),
				'Opening times',
				'Every other week. Next dates to be added by the Food Bank team.',
				'Contact',
				'Food Bank team<br><a href="{{url:clergy}}">Contact the Cathedral clergy</a>'
			),
		),
		'ru'     => array(
			'slug'    => 'produktovyj-bank',
			'title'   => 'Продуктовый банк',
			'content' => $group(
				'При соборе действует продуктовый банк, бесплатно помогающий продуктами нуждающимся семьям: беженцам от войны на Украине, прихожанам и гостям в трудных обстоятельствах, а также пострадавшим от роста стоимости жизни.',
				'Как это работает',
				array( 'Раз в две недели в Приходском доме', 'Бесплатно, без лишних вопросов', 'Мы с благодарностью принимаем продукты длительного хранения' ),
				'Часы работы',
				'Раз в две недели. Ближайшие даты добавит команда продуктового банка.',
				'Контакты',
				'Команда продуктового банка<br><a href="{{url:clergy}}">Связаться с духовенством собора</a>'
			),
		),
	);

	/*
	 * ---- Photo gallery ----
	 * Starts with photos from the old parish pages. Editors add more by dropping
	 * photos into the gallery block.
	 */
	$gallery_photos = array( 'sb-nave-iconostasis', 'sb-morning-light', 'sb-chandelier-vigil', 'sb-pascha-crowd', 'sb-procession', 'sb-clergy-feast', 'sb-bishop-blessing', 'sb-theophany-water', 'sb-feast-red', 'sb-icon-lampada', 'sb-icon-kiot', 'sb-shroud-flowers', 'sb-door-garland', 'sb-feast-congregation', 'sb-vigil-crowd', 'interior-iconostasis', 'dome-fresco', 'exterior-dome', 'exterior-side', 'icon-flowers', 'vigil-candles', 'hierarchical-liturgy', 'clergy-group', 'willow-blessing', 'holy-water-blessing', 'candle', 'parish-group', 'sisterhood', 'youth-group', 'food-bank-team' );
	$gallery        = fn( string $lang ) => sd_gallery( $gallery_photos, $lang, 3, 'parish-photo-grid' );

	$pages['parish-life/gallery'] = array(
		'parent' => 'parish-life',
		'en'     => array(
			'slug'    => 'gallery',
			'title'   => 'Photo gallery',
			'content' => sd_p( 'Photographs from feasts, pilgrimages and parish events. Click a photo to see it larger.' )
				. $gallery( 'en' )
				. sd_card(
					sd_h( 'About the photos', 2, 'parish-subhead' )
					. sd_p( 'Photography is not permitted during services, so these photos are taken before or after them, or at parish events. If you would like a photo of yourself or your child removed, please <a href="{{url:visit}}">let us know</a>.' )
				),
		),
		'ru'     => array(
			'slug'    => 'fotogalereya',
			'title'   => 'Фотогалерея',
			'content' => sd_p( 'Фотографии с праздников, паломничеств и приходских событий. Нажмите на фото, чтобы увеличить его.' )
				. $gallery( 'ru' )
				. sd_card(
					sd_h( 'О фотографиях', 2, 'parish-subhead' )
					. sd_p( 'Фотосъёмка во время богослужений запрещена, поэтому снимки сделаны до или после служб либо на приходских мероприятиях. Если вы хотите, чтобы ваше фото или фото вашего ребёнка было удалено, пожалуйста, <a href="{{url:visit}}">сообщите нам</a>.' )
				),
		),
	);

	/* ---- Clergy ---- */
	$pages['clergy'] = array(
		'en' => array(
			'slug'    => 'clergy',
			'title'   => 'Clergy',
			'content' => sd_columns(
				array(
					sd_person( 'Cathedral Rector', 'His Grace Bishop Irenei of London and Western Europe', 'The Cathedral is the See of the Ruling Bishop of the Diocese of Great Britain and Western Europe.', 'irenei.jpg' ),
					sd_person( 'Ecclesiarch', 'Archpriest Vitaly Serapinas', 'Baptisms, weddings and other services<br>' . sd_tel( '+44 7935 700 721' ), 'vitaly-serapinas.jpg' ),
					sd_person( 'Cathedral Priest', 'Archpriest Yaroslav Hudymenko', sd_tel( '+44 7563 407 991' ), 'yaroslav-hudymenko.jpg' ),
				)
			)
			. sd_columns(
				array(
					sd_person( 'Priest', 'Hieromonk Theodore', sd_tel( '+44 7534 694 534' ) ),
					sd_person( 'Retired Priest', 'Archpriest Peter Baulk', sd_tel( '+44 7714 324 482' ), 'peter-baulk.jpg' ),
					sd_person( 'Cathedral Deacon', 'Deacon Andrei Borisas', sd_tel( '+44 7876 474 358' ), 'andrei-borisas.jpg' ),
				)
			)
			. sd_columns(
				array(
					sd_person( 'Visiting Deacon', 'Deacon Sergei Baranov', '' ),
					'',
					'',
				)
			),
		),
		'ru' => array(
			'slug'    => 'duhovenstvo',
			'title'   => 'Духовенство',
			'content' => sd_columns(
				array(
					sd_person( 'Настоятель собора', 'Преосвященнейший Ириней, епископ Лондонский и Западноевропейский', 'Собор является кафедрой правящего архиерея Епархии Великобритании и Западной Европы.', 'irenei.jpg' ),
					sd_person( 'Ключарь', 'Протоиерей Виталий Серапинас', 'Крещения, венчания и другие требы<br>' . sd_tel( '+44 7935 700 721' ), 'vitaly-serapinas.jpg' ),
					sd_person( 'Клирик собора', 'Протоиерей Ярослав Гудименко', sd_tel( '+44 7563 407 991' ), 'yaroslav-hudymenko.jpg' ),
				)
			)
			. sd_columns(
				array(
					sd_person( 'Священник', 'Иеромонах Феодор', sd_tel( '+44 7534 694 534' ) ),
					sd_person( 'Заштатный священник', 'Протоиерей Петр Болк', sd_tel( '+44 7714 324 482' ), 'peter-baulk.jpg' ),
					sd_person( 'Диакон собора', 'Диакон Андрей Борисас', sd_tel( '+44 7876 474 358' ), 'andrei-borisas.jpg' ),
				)
			)
			. sd_columns(
				array(
					sd_person( 'Приезжий диакон', 'Диакон Сергий Баранов', '' ),
					'',
					'',
				)
			),
		),
	);

	/* ---- Visit & contact ---- */
	$map = fn( string $title ) => sd_map( $title );

	$pages['visit'] = array(
		'en' => array(
			'slug'    => 'visit',
			'title'   => 'Visit Us',
			'content' => sd_columns(
				array(
					array(
						'64%',
						sd_p( 'Everyone is welcome at the Cathedral. We keep the traditional liturgical and pastoral practice of the Orthodox Church, including in dress and conduct in church. Because this may be unfamiliar, here is some short guidance.' )
						. sd_h( 'Dress', 2, 'parish-subhead' )
						. sd_p( 'Please wear formal church clothes. Men wear long trousers and a shirt that covers the arms. Women wear a dress or long skirt and cover their heads. Headscarves can be borrowed at the kiosk. Open-toed shoes and sportswear are not appropriate.' )
						. sd_h( 'In the church', 2, 'parish-subhead' )
						. sd_p( 'Men stand on the right and women on the left. A few seats at the back are kept for the frail and elderly. Only clergy and authorised staff may go onto the solea in front of the iconostasis.' )
						. sd_h( 'Receiving the Holy Mysteries', 2, 'parish-subhead' )
						. sd_p( 'Holy Communion is for Orthodox Christians who have prepared for it, including Confession, normally the day before. All visitors are welcome to receive the blessed bread at the end of the Liturgy and to venerate the Cross.' )
						. sd_h( 'Photography and phones', 2, 'parish-subhead' )
						. sd_p( 'Photography during services is not permitted. Please switch your phone off or to silent before entering the church.' )
						. sd_h( 'Getting here', 2, 'parish-subhead' )
						. sd_p( 'The Cathedral is on Harvard Road in Chiswick, West London. The entrance is the arched wooden door under the icon of the Mother of God. Rail and Underground stations are within walking distance, and several bus routes stop nearby.' )
						. sd_photo( 'sb-door-garland', 'en', 'parish-door-photo' )
						. $map( 'Map showing the Cathedral, 57 Harvard Road, London W4 4ED' ),
					),
					array(
						'36%',
						sd_card( sd_h( 'Address', 2, 'parish-subhead' ) . sd_p( 'Cathedral of the Nativity of the Mother of God<br>57 Harvard Road<br>London W4 4ED' ) . sd_p( '<a href="' . $maps_url . '">Open in Google Maps</a>' ) )
						. sd_card( sd_h( 'Contact', 2, 'parish-subhead' ) . sd_p( 'For baptisms, weddings and other services, please contact Archpriest Vitaly Serapinas on ' . sd_tel( '+44 7935 700 721' ) . '. <a href="{{url:sacraments}}">More about the Sacraments</a>' ) . sd_p( 'For general enquiries or use of the Parish House, please contact the Churchwarden.' ) . sd_p( '<a href="{{url:clergy}}">All clergy contacts</a>' ) )
						. sd_card( sd_h( 'Opening times', 2, 'parish-subhead' ) . sd_p( 'The Cathedral is open during all services on the <a href="{{url:services}}">schedule</a>, and at other times by appointment.' ) ),
					),
				)
			),
		),
		'ru' => array(
			'slug'    => 'kak-nas-najti',
			'title'   => 'Как нас найти',
			'content' => sd_columns(
				array(
					array(
						'64%',
						sd_p( 'Мы рады всем, кто приходит в собор. Мы сохраняем традиционную богослужебную и пастырскую практику Православной Церкви, в том числе в одежде и поведении в храме. Ниже — краткие советы для тех, кому это непривычно.' )
						. sd_h( 'Одежда', 2, 'parish-subhead' )
						. sd_p( 'Просим приходить в подобающей для храма одежде. Мужчинам — в длинных брюках и рубашке с рукавами. Женщинам — в платье или длинной юбке и с покрытой головой. Платок можно взять в церковной лавке. Открытая обувь и спортивная одежда неуместны.' )
						. sd_h( 'В храме', 2, 'parish-subhead' )
						. sd_p( 'Мужчины стоят справа, женщины — слева. Несколько мест в конце храма предназначены для немощных и пожилых. На солею перед иконостасом могут подниматься только священнослужители и уполномоченные сотрудники.' )
						. sd_h( 'Участие в Таинствах', 2, 'parish-subhead' )
						. sd_p( 'К Святому Причастию приступают православные христиане, подготовившиеся к нему, в том числе исповедью, обычно накануне. Все гости могут получить благословлённый хлеб в конце литургии и приложиться ко Кресту.' )
						. sd_h( 'Фотосъёмка и телефоны', 2, 'parish-subhead' )
						. sd_p( 'Фото- и видеосъёмка во время богослужений запрещена. Пожалуйста, выключите телефон или переведите его в беззвучный режим до входа в храм.' )
						. sd_h( 'Как добраться', 2, 'parish-subhead' )
						. sd_p( 'Собор находится на Harvard Road в Чизике, на западе Лондона. Вход — арочная деревянная дверь под иконой Божией Матери. В пешей доступности — железнодорожные станции и станции метро, рядом останавливаются несколько автобусных маршрутов.' )
						. sd_photo( 'sb-door-garland', 'ru', 'parish-door-photo' )
						. $map( 'Карта: собор, 57 Harvard Road, London W4 4ED' ),
					),
					array(
						'36%',
						sd_card( sd_h( 'Адрес', 2, 'parish-subhead' ) . sd_p( 'Собор Рождества Пресвятой Богородицы<br>57 Harvard Road<br>London W4 4ED' ) . sd_p( '<a href="' . $maps_url . '">Открыть в Google Картах</a>' ) )
						. sd_card( sd_h( 'Контакты', 2, 'parish-subhead' ) . sd_p( 'По вопросам крещения, венчания и других треб обращайтесь к протоиерею Виталию Серапинасу: ' . sd_tel( '+44 7935 700 721' ) . '. <a href="{{url:sacraments}}">Подробнее о таинствах и требах</a>' ) . sd_p( 'По общим вопросам и использованию Приходского дома обращайтесь к старосте.' ) . sd_p( '<a href="{{url:clergy}}">Все контакты духовенства</a>' ) )
						. sd_card( sd_h( 'Часы работы', 2, 'parish-subhead' ) . sd_p( 'Собор открыт во время всех богослужений по <a href="{{url:services}}">расписанию</a>, а в другое время — по договорённости.' ) ),
					),
				)
			),
		),
	);

	/* ---- Donate (requirement 4: Gift Aid) ---- */
	$bank = '<strong>Account name:</strong> Russian Orthodox Church Abroad (London)<br><strong>Bank:</strong> HSBC<br><strong>Sort code:</strong> 40-11-60<br><strong>Account number:</strong> 40464899<br><strong>IBAN:</strong> GB44 HBUK 4011 6040 4648 99';
	$bank_ru = '<strong>Получатель:</strong> Russian Orthodox Church Abroad (London)<br><strong>Банк:</strong> HSBC<br><strong>Sort code:</strong> 40-11-60<br><strong>Номер счёта:</strong> 40464899<br><strong>IBAN:</strong> GB44 HBUK 4011 6040 4648 99';

	$pages['donate'] = array(
		'en' => array(
			'slug'    => 'donate',
			'title'   => 'Support the Cathedral',
			'content' => sd_p( 'Our Cathedral is funded only by its parishioners and private donations. We receive no funding from any state, public or patriarchal body. Your gifts support our clergy, the upkeep of the building, the choir, youth work and missionary work, and everyday costs such as heating and lighting.' )
				. sd_p( 'We ask every regular parishioner to consider a <strong>monthly standing order</strong>, even a small one. Regular giving helps the parish plan and budget.' )
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Give online', 2, 'parish-subhead' )
							. sd_p( 'Donate securely by card, Apple Pay or Google Pay, once or monthly. If you are a UK taxpayer, tick the Gift Aid box and we can claim an extra 25p for every £1 you give, at no cost to you.' )
							. sd_html( '<div class="parish-donation-form" style="padding:1rem;border:1px dashed var(--wp--preset--color--border);border-radius:4px;color:var(--wp--preset--color--muted)">[ Online donation form with Gift Aid will appear here. See docs/PROPOSAL.md, "Donations and Gift Aid". ]</div>' )
						),
						sd_card(
							sd_h( 'Gift Aid: add 25% at no cost', 2, 'parish-subhead' )
							. sd_p( 'If you pay UK Income Tax or Capital Gains Tax, a Gift Aid declaration lets the Cathedral reclaim the basic-rate tax on your gifts from HMRC. £10 becomes £12.50.' )
							. sd_p( 'You only need to declare once. The declaration covers past (up to 4 years) and future gifts to the Cathedral. You can make it online when you donate, or on a paper form from the kiosk.' )
							. sd_p( '<em>Please tell us if you stop paying enough UK tax, or change your name or address.</em>' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_h( 'Bank transfer or standing order', 2, 'parish-subhead' ) . sd_p( $bank ) . sd_p( 'Transfers are welcome from any account, including abroad.' ) ),
						sd_card( sd_h( 'By cheque', 2, 'parish-subhead' ) . sd_p( 'Please make cheques payable to <strong>London Russian Orthodox Church</strong> and hand them in at the kiosk, or post them to 57 Harvard Road, London W4 4ED.' ) ),
						sd_card( sd_h( 'Support the Sunday School', 2, 'parish-subhead' ) . sd_p( 'Sort code: 40-11-60<br>Account number: 40495042<br>Reference: Donation' ) ),
					)
				),
		),
		'ru' => array(
			'slug'    => 'pozhertvovaniya',
			'title'   => 'Поддержать собор',
			'content' => sd_p( 'Наш собор существует только на средства прихожан и частные пожертвования. Мы не получаем финансирования ни от государственных, ни от общественных, ни от патриархийных организаций. Ваши пожертвования поддерживают духовенство, содержание здания, хор, работу с молодёжью и миссионерские труды, а также ежедневные расходы на отопление и освещение.' )
				. sd_p( 'Мы просим каждого постоянного прихожанина рассмотреть возможность <strong>ежемесячного регулярного платежа</strong> (standing order), даже небольшого. Регулярные пожертвования помогают приходу планировать бюджет.' )
				. sd_columns(
					array(
						sd_card(
							sd_h( 'Пожертвовать онлайн', 2, 'parish-subhead' )
							. sd_p( 'Безопасное пожертвование картой, Apple Pay или Google Pay, разово или ежемесячно. Если вы платите налоги в Великобритании, отметьте Gift Aid, и мы сможем получить дополнительно 25 пенсов с каждого фунта без каких-либо затрат с вашей стороны.' )
							. sd_html( '<div class="parish-donation-form" style="padding:1rem;border:1px dashed var(--wp--preset--color--border);border-radius:4px;color:var(--wp--preset--color--muted)">[ Здесь будет форма онлайн-пожертвования с Gift Aid. ]</div>' )
						),
						sd_card(
							sd_h( 'Gift Aid: +25% без дополнительных затрат', 2, 'parish-subhead' )
							. sd_p( 'Если вы платите подоходный налог или налог на прирост капитала в Великобритании, декларация Gift Aid позволяет собору вернуть от HMRC налог по базовой ставке с ваших пожертвований: £10 превращаются в £12,50.' )
							. sd_p( 'Декларацию достаточно подписать один раз. Она распространяется на прошлые (до 4 лет) и будущие пожертвования собору. Её можно оформить онлайн при пожертвовании или на бумажном бланке в церковной лавке.' )
							. sd_p( '<em>Пожалуйста, сообщите нам, если вы перестанете платить достаточно налогов в Великобритании или смените имя или адрес.</em>' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_h( 'Банковский перевод или standing order', 2, 'parish-subhead' ) . sd_p( $bank_ru ) . sd_p( 'Переводы принимаются с любых счетов, в том числе из-за рубежа.' ) ),
						sd_card( sd_h( 'Чеком', 2, 'parish-subhead' ) . sd_p( 'Чеки выписываются на имя <strong>London Russian Orthodox Church</strong>. Их можно передать в церковную лавку или отправить по почте: 57 Harvard Road, London W4 4ED.' ) ),
						sd_card( sd_h( 'Поддержать воскресную школу', 2, 'parish-subhead' ) . sd_p( 'Sort code: 40-11-60<br>Номер счёта: 40495042<br>Назначение: Donation' ) ),
					)
				),
		),
	);

	return $pages;
}

/**
 * Create (or find) one page in one language.
 */
function sd_upsert_page( array $def, string $lang, int $parent_id = 0 ): int {
	$existing = get_posts(
		array(
			'name'        => $def['slug'],
			'post_type'   => 'page',
			'post_status' => 'any',
			'numberposts' => 1,
			'lang'        => '',
		)
	);
	if ( $existing ) {
		return $existing[0]->ID;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => $def['status'] ?? 'publish',
			'post_title'   => $def['title'],
			'post_name'    => $def['slug'],
			'post_content' => '',
			'post_parent'  => $parent_id,
			'post_author'  => 1,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		echo 'Page error: ' . esc_html( $id->get_error_message() ) . "\n";
		return 0;
	}
	pll_set_post_language( $id, $lang );
	update_post_meta( $id, '_parish_seed_content', wp_slash( $def['content'] ) );
	return $id;
}

/* -------------------------------------------------------------------------
 * 3. News posts.
 * ---------------------------------------------------------------------- */

function sd_news(): void {
	$posts = array(
		// Youth Group news (shown in the feed on the Youth Group page). Examples
		// from the old youth page, to show how the feed looks.
		array(
			'date'     => '2024-01-10 12:00:00',
			'category' => 'youth',
			'photo'    => 'youth-group',
			'en'       => array( 'Youth pilgrimage to Walsingham', sd_p( 'From 29 December to 1 January the Youth Group made a pilgrimage to Walsingham. We visited the holy sites of the area, and at midnight on 1 January an Akathist to the Mother of God was served.' ) ),
			'ru'       => array( 'Паломничество молодёжи в Уолсингем', sd_p( 'С 29 декабря по 1 января молодёжная группа совершила паломничество в Уолсингем. Мы посетили святые места округи, а в полночь на 1 января был отслужен акафист Божией Матери.' ) ),
		),
		array(
			'date'     => '2023-11-25 12:00:00',
			'category' => 'youth',
			'en'       => array( 'Iconography workshop', sd_p( 'On Thursday evening the Youth Group held an iconography workshop with an experienced iconographer from the parish. We watched a short film on the stages of writing an icon, then talked about the meaning of each layer.' ) ),
			'ru'       => array( 'Мастер-класс по иконописи', sd_p( 'В четверг вечером молодёжная группа провела мастер-класс по иконописи с опытным иконописцем нашего прихода. Мы посмотрели короткий фильм об этапах написания иконы и поговорили о смысле каждого слоя.' ) ),
		),
		array(
			'date' => '2026-07-15 12:00:00',
			'en'   => array( 'Sunday School resumes in September', sd_p( 'The parish Sunday School will resume its classes in September. Parents who would like to enrol their children are asked to speak to the Sunday School teachers after the Sunday Liturgy.' ) ),
			'ru'   => array( 'Воскресная школа возобновит работу в сентябре', sd_p( 'Церковно-приходская воскресная школа возобновит занятия в сентябре. Родителей, желающих записать детей, просим обращаться к преподавателям воскресной школы после воскресной литургии.' ) ),
		),
		array(
			'date' => '2026-06-06 12:00:00',
			'en'   => array( 'Reminder: Annual Parish Assembly this Sunday, 7 June', sd_p( 'The Bishop encourages everyone who is able to attend the Annual Parish Assembly, which will begin straight after the Sunday Divine Liturgy in the Parish House.' ) ),
			'ru'   => array( 'Напоминание: годовое приходское собрание в это воскресенье, 7 июня', sd_p( 'Владыка призывает всех, кто имеет возможность, принять участие в годовом приходском собрании, которое начнётся сразу после воскресной Божественной литургии в Приходском доме.' ) ),
		),
		array(
			'date' => '2026-05-05 12:00:00',
			'en'   => array( 'Please renew your parish membership by 4 June', sd_p( 'Our next Annual Parish Meeting will be held on 7 June. To vote at the meeting, please renew your parish membership by 4 June. Forms are available at the kiosk.' ) ),
			'ru'   => array( 'Просим продлить приходское членство до 4 июня', sd_p( 'Годовое приходское собрание состоится 7 июня. Чтобы голосовать на собрании, просим продлить приходское членство до 4 июня. Бланки можно взять в церковной лавке.' ) ),
		),
		array(
			'date'  => '2026-03-01 12:00:00',
			'photo' => 'book-cover',
			'en'   => array( 'New book on the architecture and iconography of the Cathedral', sd_p( 'The Cathedral is pleased to announce a new illustrated book on the Cathedral: its architecture, iconostasis and frescoes, with essays on its history and on Christianity in the British Isles.' ) . sd_p( 'The book is on sale at the Cathedral kiosk (hardback, 96 pages, £20, or £15 each for five or more). Copies can also be ordered by post.' ) ),
			'ru'   => array( 'Вышла новая книга об архитектуре и иконографии собора', sd_p( 'Собор рад сообщить о выходе иллюстрированной книги о соборе: его архитектуре, иконостасе и росписях, с очерками о его истории и о христианстве на Британских островах.' ) . sd_p( 'Книгу можно приобрести в церковной лавке (твёрдый переплёт, 96 страниц, £20, при покупке от пяти экземпляров — £15 за экземпляр). Также возможен заказ по почте.' ) ),
		),
	);

	foreach ( $posts as $item ) {
		$ids = array();
		foreach ( array( 'en', 'ru' ) as $lang ) {
			[ $title, $content ] = $item[ $lang ];
			$found               = get_posts(
				array(
					'title'       => $title,
					'post_type'   => 'post',
					'numberposts' => 1,
					'lang'        => '',
				)
			);
			if ( $found ) {
				$ids[ $lang ] = $found[0]->ID;
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => $title,
					'post_content'  => $content,
					'post_date'     => $item['date'],
					'post_date_gmt' => get_gmt_from_date( $item['date'] ),
					'post_author'   => 1,
				)
			);
			pll_set_post_language( $id, $lang );
			if ( isset( $item['photo'] ) ) {
				set_post_thumbnail( $id, sd_photo_id( $item['photo'], $lang ) );
			}
			if ( 'youth' === ( $item['category'] ?? '' ) ) {
				wp_set_post_categories( $id, array( sd_youth_categories()[ $lang ] ) );
			}
			$ids[ $lang ] = $id;
		}
		pll_save_post_translations( $ids );
	}

	// Remove WordPress's default "Hello world!" post and sample page.
	foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
		$p = get_page_by_path( $slug, OBJECT, $type );
		if ( $p ) {
			wp_delete_post( $p->ID, true );
		}
	}
}

/* -------------------------------------------------------------------------
 * 4. Menus per language.
 * ---------------------------------------------------------------------- */

function sd_menus( array $ids ): void {
	$structure = array( 'home', 'services', 'sacraments', 'news', 'parish-life', 'clergy', 'visit', 'donate' );
	$children  = array(
		'services'    => array( 'services/readings' ),
		'parish-life' => array( 'parish-life/sisterhood', 'parish-life/youth-group', 'parish-life/sunday-school', 'parish-life/choir', 'parish-life/food-bank', 'parish-life/gallery' ),
	);
	$labels    = array(
		'en' => array( 'home' => 'Home', 'donate' => 'Donate', 'services' => 'Services', 'visit' => 'Visit & Contact' ),
		'ru' => array( 'home' => 'Главная', 'donate' => 'Пожертвования', 'services' => 'Богослужения', 'sacraments' => 'Таинства', 'visit' => 'Контакты' ),
	);

	$locations = array();
	foreach ( array( 'en', 'ru' ) as $lang ) {
		$name = 'Main menu (' . strtoupper( $lang ) . ')';
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu ) {
			$locations[ $lang ] = $menu->term_id;
			continue;
		}
		$menu_id = wp_create_nav_menu( $name );
		foreach ( $structure as $key ) {
			$page_id = $ids[ $key ][ $lang ] ?? 0;
			if ( ! $page_id ) {
				continue;
			}
			$item_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $labels[ $lang ][ $key ] ?? get_the_title( $page_id ),
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
			if ( isset( $children[ $key ] ) ) {
				foreach ( $children[ $key ] as $child ) {
					if ( empty( $ids[ $child ][ $lang ] ) ) {
						continue;
					}
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'     => get_the_title( $ids[ $child ][ $lang ] ),
							'menu-item-object'    => 'page',
							'menu-item-object-id' => $ids[ $child ][ $lang ],
							'menu-item-type'      => 'post_type',
							'menu-item-parent-id' => $item_id,
							'menu-item-status'    => 'publish',
						)
					);
				}
			}
		}
		$locations[ $lang ] = $menu_id;
	}

	// Polylang stores per-language menu locations in its own options.
	// Polylang 3.7+ keeps options in an object that is saved on shutdown,
	// so write through it rather than update_option() (which it would overwrite).
	$pll_options = PLL()->options;
	if ( $pll_options instanceof ArrayAccess ) {
		$nav_menus = $pll_options['nav_menus'] ?: array();
		$nav_menus[ get_stylesheet() ]['primary'] = $locations;
		$pll_options['nav_menus'] = $nav_menus;
		if ( method_exists( $pll_options, 'save' ) ) {
			$pll_options->save();
		}
	} else {
		$options = get_option( 'polylang' );
		$options['nav_menus'][ get_stylesheet() ]['primary'] = $locations;
		update_option( 'polylang', $options );
	}
	set_theme_mod( 'nav_menu_locations', array( 'primary' => $locations['en'] ) );
}

/* -------------------------------------------------------------------------
 * 5. Demo group leader (shows requirement 6 working).
 * ---------------------------------------------------------------------- */

function sd_demo_leader( array $ids ): void {
	if ( username_exists( 'headsister' ) ) {
		return;
	}
	$user_id = wp_insert_user(
		array(
			'user_login'   => 'headsister',
			'user_pass'    => 'headsister',
			'user_email'   => 'headsister@example.org',
			'display_name' => 'Head Sister (demo)',
			'role'         => 'group_leader',
		)
	);
	if ( ! is_wp_error( $user_id ) ) {
		update_user_meta( $user_id, 'parish_group_pages', array( $ids['parish-life/sisterhood']['en'] ) );
	}
}

/**
 * Demo news editor (the news volunteer). She writes in Russian first, so the
 * admin's language filter is set to Russian: Polylang then starts new posts in Russian.
 */
function sd_demo_editor(): void {
	if ( username_exists( 'newseditor' ) ) {
		return;
	}
	$user_id = wp_insert_user(
		array(
			'user_login'   => 'newseditor',
			'user_pass'    => 'newseditor',
			'user_email'   => 'newseditor@example.org',
			'display_name' => 'News editor (demo)',
			'role'         => 'editor',
		)
	);
	if ( ! is_wp_error( $user_id ) ) {
		update_user_meta( $user_id, 'pll_filter_content', 'ru' );
	}
}

/* -------------------------------------------------------------------------
 * Run.
 * ---------------------------------------------------------------------- */

update_option( 'blogname', 'London Orthodox Cathedral' );
update_option( 'blogdescription', 'Cathedral of the Nativity of the Mother of God and the Holy Royal Martyrs (ROCOR)' );
update_option( 'timezone_string', 'Europe/London' );
update_option( 'date_format', 'j F Y' );
update_option( 'start_of_week', 1 );
update_option( 'default_comment_status', 'closed' );
update_option( 'permalink_structure', '/%postname%/' );
if ( ! get_option( 'parish_calendar_id' ) ) {
	// The Cathedral's existing public Google Calendars of services (as on the old site).
	update_option( 'parish_calendar_id', 'cpdlaqq6537kqb4mvedbmssmi8@group.calendar.google.com' );
}
if ( ! get_option( 'parish_calendar_id_ru' ) ) {
	update_option( 'parish_calendar_id_ru', 'russcc@googlemail.com' );
}

sd_languages();
sd_translate_strings();

$sd_defs = sd_pages();
$sd_ids  = array();
foreach ( $sd_defs as $key => $def ) {
	$parent = isset( $def['parent'] ) ? $sd_ids[ $def['parent'] ] : array();
	foreach ( array( 'en', 'ru' ) as $lang ) {
		$sd_ids[ $key ][ $lang ] = sd_upsert_page( $def[ $lang ] + array( 'status' => $def['status'] ?? 'publish' ), $lang, (int) ( $parent[ $lang ] ?? 0 ) );
	}
	pll_save_post_translations( array_filter( $sd_ids[ $key ] ) );
}

// Banner photo under each page title (the page's featured image).
$sd_banners = array(
	'services'                  => 'sb-morning-light',
	'sacraments'                => 'sb-bishop-blessing',
	'parish-life'               => 'parish-group',
	'parish-life/sisterhood'    => 'icon-flowers',
	'parish-life/youth-group'   => 'youth-group',
	'parish-life/sunday-school' => 'sunday-school',
	'parish-life/choir'         => 'choir-music',
	'parish-life/food-bank'     => 'food-bank-team',
	'clergy'                    => 'sb-clergy-feast',
	'visit'                     => 'exterior-side',
	'donate'                    => 'sb-vigil-crowd',
);
foreach ( $sd_banners as $key => $photo ) {
	foreach ( $sd_ids[ $key ] ?? array() as $lang => $id ) {
		if ( $id && ! has_post_thumbnail( $id ) ) {
			set_post_thumbnail( $id, sd_photo_id( $photo, $lang ) );
		}
	}
}

// Resolve {{url:key}} placeholders now that every page has a permalink.
foreach ( $sd_ids as $key => $by_lang ) {
	foreach ( $by_lang as $lang => $id ) {
		$raw = get_post_meta( $id, '_parish_seed_content', true );
		if ( '' === $raw ) {
			continue;
		}
		$content = preg_replace_callback(
			'/\{\{url:([a-z0-9\/-]+)\}\}/',
			function ( $m ) use ( $sd_ids, $lang ) {
				$target = $sd_ids[ $m[1] ][ $lang ] ?? 0;
				return $target ? wp_make_link_relative( get_permalink( $target ) ) : '#';
			},
			$raw
		);
		wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $content ) ) );
		delete_post_meta( $id, '_parish_seed_content' );
	}
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $sd_ids['home']['en'] );
update_option( 'page_for_posts', $sd_ids['news']['en'] );

sd_news();
sd_menus( $sd_ids );
if ( ! defined( 'PARISH_SEED_SKIP_DEMO_USERS' ) ) { // Demo logins have weak passwords: local only.
	sd_demo_leader( $sd_ids );
	sd_demo_editor();
}

update_option( 'parish_flush_rewrite', 1 ); // Flushed by Parish Core on the next page load.
echo "Parish site seeded.\n";
