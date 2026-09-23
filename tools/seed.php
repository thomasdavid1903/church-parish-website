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

function sd_calendar( string $view = 'AGENDA', int $height = 600 ): string {
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

function sd_person( string $role, string $name, string $details ): string {
	return sd_card(
		sd_p( $role, 'parish-person__role' ) . sd_h( $name, 3 ) . sd_p( $details ),
		'parish-person'
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
}

/* -------------------------------------------------------------------------
 * 2. Pages. Content may contain {{url:<english-slug>}} placeholders,
 *    resolved to the same-language page URL once all pages exist.
 * ---------------------------------------------------------------------- */

function sd_pages(): array {
	$pages = array();

	/* ---- Home ---- */
	$pages['home'] = array(
		'en' => array(
			'slug'    => 'home',
			'title'   => 'Home',
			'content' => sd_columns(
				array(
					array(
						'64%',
						sd_title( 'Welcome' )
						. sd_p( 'The Cathedral of the Nativity of the Mother of God and the Holy Royal Martyrs is the cathedral of the Diocese of Great Britain and Western Europe of the Russian Orthodox Church Outside Russia (ROCOR). Founded in 1725, it is one of the oldest Orthodox parishes in the British Isles. Today it worships in a new cathedral in Chiswick, West London, built in the Pskov style and consecrated in 2018.' )
						. sd_p( 'The Divine Liturgy is celebrated four to five times a week, with evening services several times a week. Services are in Church Slavonic and English. <a href="{{url:visit}}">New to Orthodoxy? Read our guidance for visitors.</a>' )
						. sd_title( 'Upcoming services' )
						. sd_calendar( 'AGENDA', 420 )
						. sd_p( '<a href="{{url:services}}">Full schedule of services →</a>' )
						. sd_title( 'Latest news' )
						. sd_latest_news( 4 )
						. sd_p( '<a href="{{url:news}}">All news →</a>' ),
					),
					array(
						'36%',
						sd_card(
							sd_h( 'Regular services', 3 )
							. sd_list(
								array(
									array( 'Saturday Vigil', '5.00 pm' ),
									array( 'Sunday Liturgy', '10.00 am' ),
									array( 'Weekday / Saturday Liturgy', '9.00 am' ),
									array( 'Friday Akathist', '6.15 pm' ),
								),
								true
							)
							. sd_p( '<a href="{{url:services}}">Full schedule</a>' )
						)
						. sd_card(
							sd_h( 'Cathedral address', 3 )
							. sd_p( '57 Harvard Road<br>London W4 4ED' )
							. sd_p( '<a href="{{url:visit}}">Map and directions</a>' )
						)
						. sd_card(
							sd_h( 'Church shop', 3 )
							. sd_list(
								array(
									array( 'Saturday', '9.00 – 11.30 am' ),
									array( 'Sunday', '9.00 am – 2.00 pm' ),
								),
								true
							)
						)
						. sd_card(
							sd_h( 'Main contacts', 3 )
							. sd_p( '<strong>Archpriest Vitaly Serapinas</strong><br>+44 7935 700 721' )
							. sd_p( '<strong>Archpriest Yaroslav Hudymenko</strong><br>+44 7563 407 991' )
							. sd_p( '<strong>Hieromonk Theodore</strong><br>+44 7534 694 534' )
							. sd_p( '<a href="{{url:clergy}}">All clergy</a>' )
						)
						. sd_card(
							sd_h( 'Support the Cathedral', 3 )
							. sd_p( 'The Cathedral is funded entirely by its parishioners. UK taxpayers can add 25% to every gift through Gift Aid.' )
							. sd_button( 'Donate', '{{url:donate}}' )
						),
					),
				)
			)
			. sd_title( 'Parish life' )
			. sd_columns(
				array(
					sd_card( sd_h( 'Sisterhood', 3 ) . sd_p( 'Visiting the sick, helping the poor, baking prosphora and caring for the vestments.' ) . sd_p( '<a href="{{url:parish-life/sisterhood}}">Sisterhood of St Xenia →</a>' ) ),
					sd_card( sd_h( 'Youth Group', 3 ) . sd_p( 'Evening talks, pilgrimages, service projects and film nights for ages 14–30.' ) . sd_p( '<a href="{{url:parish-life/youth-group}}">Youth Group →</a>' ) ),
					sd_card( sd_h( 'Choir', 3 ) . sd_p( 'A mixed choir open to all who wish to sing. Rehearsals on Sundays at 9.00 am.' ) . sd_p( '<a href="{{url:parish-life/choir}}">Choir →</a>' ) ),
					sd_card( sd_h( 'Food Bank', 3 ) . sd_p( 'Free food every two weeks for families in need, run from the Cathedral House.' ) . sd_p( '<a href="{{url:parish-life/food-bank}}">Food Bank →</a>' ) ),
				)
			),
		),
		'ru' => array(
			'slug'    => 'glavnaya',
			'title'   => 'Главная',
			'content' => sd_columns(
				array(
					array(
						'64%',
						sd_title( 'Добро пожаловать' )
						. sd_p( 'Собор Рождества Пресвятой Богородицы и Святых Царственных Мучеников — кафедральный собор Епархии Великобритании и Западной Европы Русской Православной Церкви Заграницей (РПЦЗ). Приход, основанный в 1725 году, — один из старейших православных приходов на Британских островах. Сегодня богослужения совершаются в новом соборе в Чизике, на западе Лондона, построенном в псковском стиле и освящённом в 2018 году.' )
						. sd_p( 'Божественная литургия совершается четыре-пять раз в неделю, вечерние богослужения — несколько раз в неделю. Службы идут на церковнославянском и английском языках. <a href="{{url:visit}}">Впервые в православном храме? Прочитайте наши советы для посетителей.</a>' )
						. sd_title( 'Ближайшие богослужения' )
						. sd_calendar( 'AGENDA', 420 )
						. sd_p( '<a href="{{url:services}}">Полное расписание богослужений →</a>' )
						. sd_title( 'Последние новости' )
						. sd_latest_news( 4 )
						. sd_p( '<a href="{{url:news}}">Все новости →</a>' ),
					),
					array(
						'36%',
						sd_card(
							sd_h( 'Регулярные богослужения', 3 )
							. sd_list(
								array(
									array( 'Всенощное бдение, суббота', '17:00' ),
									array( 'Литургия, воскресенье', '10:00' ),
									array( 'Литургия, будни и суббота', '9:00' ),
									array( 'Акафист, пятница', '18:15' ),
								),
								true
							)
							. sd_p( '<a href="{{url:services}}">Полное расписание</a>' )
						)
						. sd_card(
							sd_h( 'Адрес собора', 3 )
							. sd_p( '57 Harvard Road<br>London W4 4ED' )
							. sd_p( '<a href="{{url:visit}}">Карта и как добраться</a>' )
						)
						. sd_card(
							sd_h( 'Церковная лавка', 3 )
							. sd_list(
								array(
									array( 'Суббота', '9:00 – 11:30' ),
									array( 'Воскресенье', '9:00 – 14:00' ),
								),
								true
							)
						)
						. sd_card(
							sd_h( 'Основные контакты', 3 )
							. sd_p( '<strong>Протоиерей Виталий Серапинас</strong><br>+44 7935 700 721' )
							. sd_p( '<strong>Протоиерей Ярослав Гудименко</strong><br>+44 7563 407 991' )
							. sd_p( '<strong>Иеромонах Феодор</strong><br>+44 7534 694 534' )
							. sd_p( '<a href="{{url:clergy}}">Всё духовенство</a>' )
						)
						. sd_card(
							sd_h( 'Поддержать собор', 3 )
							. sd_p( 'Собор существует исключительно на пожертвования прихожан. Налогоплательщики Великобритании могут увеличить каждое пожертвование на 25% через Gift Aid.' )
							. sd_button( 'Пожертвовать', '{{url:donate}}' )
						),
					),
				)
			)
			. sd_title( 'Приходская жизнь' )
			. sd_columns(
				array(
					sd_card( sd_h( 'Сестричество', 3 ) . sd_p( 'Посещение больных, помощь нуждающимся, выпечка просфор и забота о церковных облачениях.' ) . sd_p( '<a href="{{url:parish-life/sisterhood}}">Сестричество св. Ксении →</a>' ) ),
					sd_card( sd_h( 'Молодёжная группа', 3 ) . sd_p( 'Вечерние беседы, паломничества, волонтёрство и киновечера для молодёжи 14–30 лет.' ) . sd_p( '<a href="{{url:parish-life/youth-group}}">Молодёжная группа →</a>' ) ),
					sd_card( sd_h( 'Хор', 3 ) . sd_p( 'Смешанный хор, открытый для всех желающих петь. Спевки по воскресеньям в 9:00.' ) . sd_p( '<a href="{{url:parish-life/choir}}">Хор →</a>' ) ),
					sd_card( sd_h( 'Продуктовый банк', 3 ) . sd_p( 'Раз в две недели — бесплатные продукты для нуждающихся семей в Приходском доме.' ) . sd_p( '<a href="{{url:parish-life/food-bank}}">Продуктовый банк →</a>' ) ),
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
				. sd_calendar( 'AGENDA', 700 )
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
				),
		),
		'ru' => array(
			'slug'    => 'bogosluzheniya',
			'title'   => 'Расписание богослужений',
			'content' => sd_p( 'Как правило, Божественная литургия совершается четыре раза в неделю, также совершаются вечерние богослужения. Литургия всегда служится в субботу и воскресенье утром, а в субботу вечером — всенощное бдение. Кроме летнего времени, по пятницам вечером читается акафист Божией Матери. Литургии в будние дни зависят от памятей седмицы.' )
				. sd_calendar( 'AGENDA', 700 )
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
						sd_card( sd_h( '<a href="{{url:parish-life/sisterhood}}">Sisterhood</a>', 3 ) . sd_p( 'The Sisterhood of St Xenia brings together the women of the Cathedral to support its life.' ) ),
						sd_card( sd_h( '<a href="{{url:parish-life/youth-group}}">Youth Group</a>', 3 ) . sd_p( 'For young people aged 14–30: talks, pilgrimages, service projects and social evenings.' ) ),
						sd_card( sd_h( '<a href="{{url:parish-life/sunday-school}}">Sunday School</a>', 3 ) . sd_p( 'Classes in the Faith for children of the parish during the school year.' ) ),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_h( '<a href="{{url:parish-life/choir}}">Choir</a>', 3 ) . sd_p( 'All who wish to sing to the glory of God are welcome to join.' ) ),
						sd_card( sd_h( '<a href="{{url:parish-life/food-bank}}">Food Bank</a>', 3 ) . sd_p( 'Free food for families in need, every two weeks.' ) ),
						'',
					)
				),
		),
		'ru' => array(
			'slug'    => 'prihodskaya-zhizn',
			'title'   => 'Приходская жизнь',
			'content' => sd_p( 'Собор — это живая приходская община. Помимо богослужений, прихожане служат друг другу и окружающим через приходские группы. Участвовать может каждый: обратитесь к руководителю группы или к одному из священников.' )
				. sd_columns(
					array(
						sd_card( sd_h( '<a href="{{url:parish-life/sisterhood}}">Сестричество</a>', 3 ) . sd_p( 'Сестричество во имя св. блж. Ксении объединяет женщин прихода для служения собору.' ) ),
						sd_card( sd_h( '<a href="{{url:parish-life/youth-group}}">Молодёжная группа</a>', 3 ) . sd_p( 'Для молодёжи 14–30 лет: беседы, паломничества, волонтёрство и встречи.' ) ),
						sd_card( sd_h( '<a href="{{url:parish-life/sunday-school}}">Воскресная школа</a>', 3 ) . sd_p( 'Занятия по основам веры для детей прихода в течение учебного года.' ) ),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_h( '<a href="{{url:parish-life/choir}}">Хор</a>', 3 ) . sd_p( 'Приглашаем всех, кто желает петь во славу Божию.' ) ),
						sd_card( sd_h( '<a href="{{url:parish-life/food-bank}}">Продуктовый банк</a>', 3 ) . sd_p( 'Бесплатные продукты для нуждающихся семей раз в две недели.' ) ),
						'',
					)
				),
		),
	);

	$group = function ( string $intro, string $what_h, array $what, string $when_h, string $when, string $contact_h, string $contact ): string {
		return sd_columns(
			array(
				array( '64%', sd_p( $intro ) . sd_h( $what_h, 3 ) . sd_list( $what ) ),
				array( '36%', sd_card( sd_h( $when_h, 3 ) . sd_p( $when ) ) . sd_card( sd_h( $contact_h, 3 ) . sd_p( $contact ) ) ),
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
				'Head Sister: <em>name to be added</em><br><a href="{{url:visit}}">Send a message</a>'
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
				'Старшая сестра: <em>имя будет добавлено</em><br><a href="{{url:visit}}">Написать сообщение</a>'
			),
		),
	);

	$pages['parish-life/youth-group'] = array(
		'parent' => 'parish-life',
		'en'     => array(
			'slug'    => 'youth-group',
			'title'   => 'Youth Group',
			'content' => $group(
				'The Youth Group supports and coordinates the activities of young people in the parish. It welcomes everyone aged roughly 14 to 30.',
				'What we do',
				array( 'Evening talks and discussions', 'Youth pilgrimages', 'Service projects', 'Film nights and social events', 'Helping with parish activities' ),
				'Upcoming events',
				'Details to be added by the Youth Leader.',
				'Contact',
				'Youth Leader: <em>name to be added</em><br><a href="{{url:visit}}">Send a message</a>'
			),
		),
		'ru'     => array(
			'slug'    => 'molodezhnaya-gruppa',
			'title'   => 'Молодёжная группа',
			'content' => $group(
				'Молодёжная группа объединяет и организует деятельность молодёжи прихода. Приглашаем всех в возрасте примерно от 14 до 30 лет.',
				'Чем мы занимаемся',
				array( 'Вечерние беседы и обсуждения', 'Молодёжные паломничества', 'Волонтёрские проекты', 'Киновечера и встречи', 'Помощь в приходских делах' ),
				'Ближайшие встречи',
				'Информацию добавит руководитель молодёжной группы.',
				'Контакты',
				'Руководитель: <em>имя будет добавлено</em><br><a href="{{url:visit}}">Написать сообщение</a>'
			),
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
				'Head teacher: <em>name to be added</em><br><a href="{{url:visit}}">Send a message</a>'
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
				'Директор: <em>имя будет добавлено</em><br><a href="{{url:visit}}">Написать сообщение</a>'
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
				'Choir Director<br><a href="{{url:visit}}">Send a message</a>'
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
				'Регент хора<br><a href="{{url:visit}}">Написать сообщение</a>'
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
				'Food Bank team<br><a href="{{url:visit}}">Send a message</a>'
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
				'Команда продуктового банка<br><a href="{{url:visit}}">Написать сообщение</a>'
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
					sd_person( 'Cathedral Rector', 'His Grace Bishop Irenei of London and Western Europe', 'The Cathedral is the See of the Ruling Bishop of the Diocese of Great Britain and Western Europe.' ),
					sd_person( 'Ecclesiarch', 'Archpriest Vitaly Serapinas', 'Baptisms, weddings and other services<br>+44 7935 700 721' ),
					sd_person( 'Cathedral Priest', 'Archpriest Yaroslav Hudymenko', '+44 7563 407 991' ),
				)
			)
			. sd_columns(
				array(
					sd_person( 'Priest', 'Hieromonk Theodore', '+44 7534 694 534' ),
					sd_person( 'Retired Priest', 'Archpriest Peter Baulk', '+44 771 432 4482' ),
					sd_person( 'Cathedral Deacon', 'Deacon Andrei Borisas', '+44 7876 474358' ),
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
					sd_person( 'Настоятель собора', 'Преосвященнейший Ириней, епископ Лондонский и Западноевропейский', 'Собор является кафедрой правящего архиерея Епархии Великобритании и Западной Европы.' ),
					sd_person( 'Ключарь', 'Протоиерей Виталий Серапинас', 'Крещения, венчания и другие требы<br>+44 7935 700 721' ),
					sd_person( 'Клирик собора', 'Протоиерей Ярослав Гудименко', '+44 7563 407 991' ),
				)
			)
			. sd_columns(
				array(
					sd_person( 'Священник', 'Иеромонах Феодор', '+44 7534 694 534' ),
					sd_person( 'Заштатный священник', 'Протоиерей Петр Болк', '+44 771 432 4482' ),
					sd_person( 'Диакон собора', 'Диакон Андрей Борисас', '+44 7876 474358' ),
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
	$map = sd_html( '<div class="parish-embed"><iframe src="https://www.google.com/maps?q=Russian+Orthodox+Cathedral,+57+Harvard+Road,+London+W4+4ED&amp;output=embed" height="380" loading="lazy" title="Map"></iframe></div>' );

	$pages['visit'] = array(
		'en' => array(
			'slug'    => 'visit',
			'title'   => 'Visit Us',
			'content' => sd_columns(
				array(
					array(
						'64%',
						sd_p( 'Everyone is welcome at the Cathedral. We keep the traditional liturgical and pastoral practice of the Orthodox Church, including in dress and conduct in church. Because this may be unfamiliar, here is some short guidance.' )
						. sd_h( 'Dress', 3 )
						. sd_p( 'Please wear formal church clothes. Men wear long trousers and a shirt that covers the arms. Women wear a dress or long skirt and cover their heads. Headscarves can be borrowed at the kiosk. Open-toed shoes and sportswear are not appropriate.' )
						. sd_h( 'In the church', 3 )
						. sd_p( 'Men stand on the right and women on the left. A few seats at the back are kept for the frail and elderly. Only clergy and authorised staff may go onto the solea in front of the iconostasis.' )
						. sd_h( 'Receiving the Holy Mysteries', 3 )
						. sd_p( 'Holy Communion is for Orthodox Christians who have prepared for it, including Confession, normally the day before. All visitors are welcome to receive the blessed bread at the end of the Liturgy and to venerate the Cross.' )
						. sd_h( 'Photography and phones', 3 )
						. sd_p( 'Photography during services is not permitted. Please switch your phone off or to silent before entering the church.' )
						. sd_h( 'Getting here', 3 )
						. sd_p( 'The Cathedral is on Harvard Road in Chiswick, West London. Rail and Underground stations are within walking distance, and several bus routes stop nearby.' )
						. $map,
					),
					array(
						'36%',
						sd_card( sd_h( 'Address', 3 ) . sd_p( 'Cathedral of the Nativity of the Mother of God<br>57 Harvard Road<br>London W4 4ED' ) )
						. sd_card( sd_h( 'Contact', 3 ) . sd_p( 'For baptisms, weddings and other services, please contact Archpriest Vitaly Serapinas on +44 7935 700 721.' ) . sd_p( 'For general enquiries or use of the Parish House, please contact the Churchwarden.' ) . sd_p( '<a href="{{url:clergy}}">All clergy contacts</a>' ) )
						. sd_card( sd_h( 'Opening times', 3 ) . sd_p( 'The Cathedral is open during all services on the <a href="{{url:services}}">schedule</a>, and at other times by appointment.' ) ),
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
						. sd_h( 'Одежда', 3 )
						. sd_p( 'Просим приходить в подобающей для храма одежде. Мужчинам — в длинных брюках и рубашке с рукавами. Женщинам — в платье или длинной юбке и с покрытой головой. Платок можно взять в церковной лавке. Открытая обувь и спортивная одежда неуместны.' )
						. sd_h( 'В храме', 3 )
						. sd_p( 'Мужчины стоят справа, женщины — слева. Несколько мест в конце храма предназначены для немощных и пожилых. На солею перед иконостасом могут подниматься только священнослужители и уполномоченные сотрудники.' )
						. sd_h( 'Участие в Таинствах', 3 )
						. sd_p( 'К Святому Причастию приступают православные христиане, подготовившиеся к нему, в том числе исповедью, обычно накануне. Все гости могут получить благословлённый хлеб в конце литургии и приложиться ко Кресту.' )
						. sd_h( 'Фотосъёмка и телефоны', 3 )
						. sd_p( 'Фото- и видеосъёмка во время богослужений запрещена. Пожалуйста, выключите телефон или переведите его в беззвучный режим до входа в храм.' )
						. sd_h( 'Как добраться', 3 )
						. sd_p( 'Собор находится на Harvard Road в Чизике, на западе Лондона. В пешей доступности — железнодорожные станции и станции метро, рядом останавливаются несколько автобусных маршрутов.' )
						. $map,
					),
					array(
						'36%',
						sd_card( sd_h( 'Адрес', 3 ) . sd_p( 'Собор Рождества Пресвятой Богородицы<br>57 Harvard Road<br>London W4 4ED' ) )
						. sd_card( sd_h( 'Контакты', 3 ) . sd_p( 'По вопросам крещения, венчания и других треб обращайтесь к протоиерею Виталию Серапинасу: +44 7935 700 721.' ) . sd_p( 'По общим вопросам и использованию Приходского дома обращайтесь к старосте.' ) . sd_p( '<a href="{{url:clergy}}">Все контакты духовенства</a>' ) )
						. sd_card( sd_h( 'Часы работы', 3 ) . sd_p( 'Собор открыт во время всех богослужений по <a href="{{url:services}}">расписанию</a>, а в другое время — по договорённости.' ) ),
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
							sd_h( 'Give online', 3 )
							. sd_p( 'Donate securely by card, Apple Pay or Google Pay, once or monthly. If you are a UK taxpayer, tick the Gift Aid box and we can claim an extra 25p for every £1 you give, at no cost to you.' )
							. sd_html( '<div class="parish-donation-form" style="padding:1rem;border:1px dashed var(--wp--preset--color--border);border-radius:4px;color:var(--wp--preset--color--muted)">[ Online donation form with Gift Aid will appear here. See docs/PROPOSAL.md, "Donations and Gift Aid". ]</div>' )
						),
						sd_card(
							sd_h( 'Gift Aid: add 25% at no cost', 3 )
							. sd_p( 'If you pay UK Income Tax or Capital Gains Tax, a Gift Aid declaration lets the Cathedral reclaim the basic-rate tax on your gifts from HMRC. £10 becomes £12.50.' )
							. sd_p( 'You only need to declare once. The declaration covers past (up to 4 years) and future gifts to the Cathedral. You can make it online when you donate, or on a paper form from the kiosk.' )
							. sd_p( '<em>Please tell us if you stop paying enough UK tax, or change your name or address.</em>' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_h( 'Bank transfer or standing order', 3 ) . sd_p( $bank ) . sd_p( 'Transfers are welcome from any account, including abroad.' ) ),
						sd_card( sd_h( 'By cheque', 3 ) . sd_p( 'Please make cheques payable to <strong>London Russian Orthodox Church</strong> and hand them in at the kiosk, or post them to 57 Harvard Road, London W4 4ED.' ) ),
						sd_card( sd_h( 'Support the Sunday School', 3 ) . sd_p( 'Sort code: 40-11-60<br>Account number: 40495042<br>Reference: Donation' ) ),
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
							sd_h( 'Пожертвовать онлайн', 3 )
							. sd_p( 'Безопасное пожертвование картой, Apple Pay или Google Pay, разово или ежемесячно. Если вы платите налоги в Великобритании, отметьте Gift Aid, и мы сможем получить дополнительно 25 пенсов с каждого фунта без каких-либо затрат с вашей стороны.' )
							. sd_html( '<div class="parish-donation-form" style="padding:1rem;border:1px dashed var(--wp--preset--color--border);border-radius:4px;color:var(--wp--preset--color--muted)">[ Здесь будет форма онлайн-пожертвования с Gift Aid. ]</div>' )
						),
						sd_card(
							sd_h( 'Gift Aid: +25% без дополнительных затрат', 3 )
							. sd_p( 'Если вы платите подоходный налог или налог на прирост капитала в Великобритании, декларация Gift Aid позволяет собору вернуть от HMRC налог по базовой ставке с ваших пожертвований: £10 превращаются в £12,50.' )
							. sd_p( 'Декларацию достаточно подписать один раз. Она распространяется на прошлые (до 4 лет) и будущие пожертвования собору. Её можно оформить онлайн при пожертвовании или на бумажном бланке в церковной лавке.' )
							. sd_p( '<em>Пожалуйста, сообщите нам, если вы перестанете платить достаточно налогов в Великобритании или смените имя или адрес.</em>' )
						),
					)
				)
				. sd_columns(
					array(
						sd_card( sd_h( 'Банковский перевод или standing order', 3 ) . sd_p( $bank_ru ) . sd_p( 'Переводы принимаются с любых счетов, в том числе из-за рубежа.' ) ),
						sd_card( sd_h( 'Чеком', 3 ) . sd_p( 'Чеки выписываются на имя <strong>London Russian Orthodox Church</strong>. Их можно передать в церковную лавку или отправить по почте: 57 Harvard Road, London W4 4ED.' ) ),
						sd_card( sd_h( 'Поддержать воскресную школу', 3 ) . sd_p( 'Sort code: 40-11-60<br>Номер счёта: 40495042<br>Назначение: Donation' ) ),
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
			'post_status'  => 'publish',
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
			'date' => '2026-03-01 12:00:00',
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
	$structure = array( 'home', 'services', 'news', 'parish-life', 'clergy', 'visit', 'donate' );
	$children  = array( 'parish-life/sisterhood', 'parish-life/youth-group', 'parish-life/sunday-school', 'parish-life/choir', 'parish-life/food-bank' );
	$labels    = array(
		'en' => array( 'home' => 'Home', 'donate' => 'Donate', 'services' => 'Services', 'visit' => 'Visit & Contact' ),
		'ru' => array( 'home' => 'Главная', 'donate' => 'Пожертвования', 'services' => 'Богослужения', 'visit' => 'Контакты' ),
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
			if ( 'parish-life' === $key ) {
				foreach ( $children as $child ) {
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
	// The Cathedral's existing public Google Calendar of services.
	update_option( 'parish_calendar_id', 'cpdlaqq6537kqb4mvedbmssmi8@group.calendar.google.com' );
}

sd_languages();

$sd_defs = sd_pages();
$sd_ids  = array();
foreach ( $sd_defs as $key => $def ) {
	$parent = isset( $def['parent'] ) ? $sd_ids[ $def['parent'] ] : array();
	foreach ( array( 'en', 'ru' ) as $lang ) {
		$sd_ids[ $key ][ $lang ] = sd_upsert_page( $def[ $lang ], $lang, (int) ( $parent[ $lang ] ?? 0 ) );
	}
	pll_save_post_translations( array_filter( $sd_ids[ $key ] ) );
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
sd_demo_leader( $sd_ids );

update_option( 'parish_flush_rewrite', 1 ); // Flushed by Parish Core on the next page load.
echo "Parish site seeded.\n";
