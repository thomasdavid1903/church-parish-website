<?php
/**
 * [parish_readings]: the day's Scripture readings, saints and troparia from the
 * Holy Trinity Orthodox Church calendar (Old Style, as kept by ROCOR), for the
 * "Daily readings" page. ?on=YYYY-MM-DD shows another day.
 *
 * Feed docs: https://www.holytrinityorthodox.com/htc/orthodox-calendar/for-webmasters/5-integration-with-php/
 *
 * The feed is English only. The Russian page shows the dates, the week, tone,
 * fast and the reading references in Russian, and links to azbyka.ru for the
 * texts, lives of the saints and troparia in Russian.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_READINGS_FEED = 'https://www.holytrinityorthodox.com/calendar/calendar.php';

/**
 * The day to show: ?on=YYYY-MM-DD within a year of today, otherwise today (London time).
 */
function parish_readings_day(): DateTimeImmutable {
	$tz    = new DateTimeZone( 'Europe/London' );
	$today = new DateTimeImmutable( 'today', $tz );
	$on    = isset( $_GET['on'] ) ? sanitize_text_field( wp_unslash( $_GET['on'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $on ) ) {
		$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $on, $tz );
		if ( $day && $day->format( 'Y-m-d' ) === $on && abs( $day->diff( $today )->days ) <= 366 ) {
			return $day;
		}
	}
	return $today;
}

/**
 * Fetch and parse one day of the feed. Cached per day: a day's readings don't change.
 *
 * @return array{header: string, fast: string, fasting: bool, saints: string, readings: array<array{ref: string, url: string, note: string}>, troparia: string}|null
 */
function parish_readings_get( DateTimeImmutable $day ): ?array {
	$key    = 'parish_readings_' . $day->format( 'Ymd' );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	if ( 'failed' === $cached ) {
		return null;
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'year'      => $day->format( 'Y' ),
				'month'     => $day->format( 'n' ),
				'today'     => $day->format( 'j' ),
				'dt'        => 0,
				'header'    => 1,
				'lives'     => 4, // Major saints, as one paragraph.
				'trp'       => 1,
				'scripture' => 1,
			),
			PARISH_READINGS_FEED
		),
		array( 'timeout' => 10 )
	);
	$html = wp_remote_retrieve_body( $response );
	$data = 200 === wp_remote_retrieve_response_code( $response ) ? parish_readings_parse( $html ) : null;

	if ( ! $data ) {
		// Their server is slow at times: try again in a while rather than on every page view.
		set_transient( $key, 'failed', 15 * MINUTE_IN_SECONDS );
		return null;
	}
	set_transient( $key, $data, 30 * DAY_IN_SECONDS );
	return $data;
}

/**
 * Turn the feed's HTML into plain parts.
 */
function parish_readings_parse( string $html ): ?array {
	// Declared windows-1251, but the text is English with cp1252 dashes; 0xFF is a stray soft hyphen.
	$html = mb_convert_encoding( str_replace( "\xFF", '', $html ), 'UTF-8', 'Windows-1252' );
	$html = preg_replace( '#<sup>(.*?)</sup>#i', '$1', $html );

	$section = function ( string $name ) use ( $html ): string {
		$pattern = '#<p class="p' . $name . 'header">.*?</p>\s*<span class="normaltext">(.*?)</span>\s*(?=<p class="p|$)#s';
		return preg_match( $pattern, $html, $m ) ? $m[1] : '';
	};

	$readings = array();
	if ( preg_match_all( '#<a [^>]*href="([^"]+)"[^>]*>([^<]+)</a>([^<]*)<br>#', $section( 'scripture' ), $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $m ) {
			$readings[] = array(
				'ref'  => trim( preg_replace( '/\s*-\s*/', '–', $m[2] ) ),
				'url'  => set_url_scheme( $m[1], 'https' ),
				'note' => preg_replace( '/^\((.*)\)$/', '$1', trim( $m[3] ) ),
			);
		}
	}
	if ( ! $readings ) {
		return null;
	}

	$header  = '';
	$fast    = '';
	$fasting = false;
	if ( preg_match( '#<span class="headerheader">(.*?)<br>#s', $html, $m ) ) {
		$header = trim( wp_strip_all_tags( $m[1] ) );
	}
	if ( preg_match( '#<span class="header(no)?fast">(.*?)</span>#s', $html, $m ) ) {
		$fast    = trim( wp_strip_all_tags( $m[2] ) );
		$fasting = '' === $m[1];
	}

	// Saints: the first block, before the readings. Troparia: their own block.
	$saints = preg_match( '#</p>\s*<span class="normaltext">(.*?)</span>\s*<p class="pscriptureheader">#s', $html, $m ) ? $m[1] : '';
	$saints = preg_replace( '#<img[^>]*>#i', '', $saints );

	$troparia = $section( 'troparion' );
	$troparia = preg_replace( '#<table.*?</table>#s', '<hr>', $troparia );

	$allowed = array(
		'a'  => array( 'href' => true ),
		'b'  => array(),
		'i'  => array(),
		'p'  => array(),
		'br' => array(),
		'hr' => array(),
	);
	$clean   = function ( string $part ) use ( $allowed ): string {
		$part = wp_kses( $part, $allowed );
		$part = str_replace( 'href="http://', 'href="https://', $part );
		return trim( preg_replace( '/\s+/', ' ', $part ) );
	};

	return array(
		'header'   => $header,
		'fast'     => $fast,
		'fasting'  => $fasting,
		'saints'   => $clean( $saints ),
		'readings' => $readings,
		'troparia' => $clean( $troparia ),
	);
}

/* -------------------------------------------------------------------------
 * Russian: the feed's English, translated where the wording is predictable.
 * ---------------------------------------------------------------------- */

function parish_readings_ru_ref( string $ref ): string {
	static $books = array(
		'Matthew' => 'Мф.', 'Mark' => 'Мк.', 'Luke' => 'Лк.', 'John' => 'Ин.', 'Acts' => 'Деян.',
		'James' => 'Иак.', '1 Peter' => '1 Пет.', '2 Peter' => '2 Пет.', '1 John' => '1 Ин.', '2 John' => '2 Ин.', '3 John' => '3 Ин.', 'Jude' => 'Иуд.',
		'Romans' => 'Рим.', '1 Corinthians' => '1 Кор.', '2 Corinthians' => '2 Кор.', 'Galatians' => 'Гал.', 'Ephesians' => 'Еф.',
		'Philippians' => 'Флп.', 'Colossians' => 'Кол.', '1 Thessalonians' => '1 Фес.', '2 Thessalonians' => '2 Фес.',
		'1 Timothy' => '1 Тим.', '2 Timothy' => '2 Тим.', 'Titus' => 'Тит.', 'Philemon' => 'Флм.', 'Hebrews' => 'Евр.', 'Revelation' => 'Откр.',
		'Genesis' => 'Быт.', 'Exodus' => 'Исх.', 'Leviticus' => 'Лев.', 'Numbers' => 'Чис.', 'Deuteronomy' => 'Втор.', 'Joshua' => 'Нав.',
		'Judges' => 'Суд.', 'Ruth' => 'Руф.', '1 Kings' => '3 Цар.', '2 Kings' => '4 Цар.', '3 Kings' => '3 Цар.', '4 Kings' => '4 Цар.',
		'Job' => 'Иов.', 'Psalm' => 'Пс.', 'Psalms' => 'Пс.', 'Proverbs' => 'Притч.', 'Ecclesiastes' => 'Еккл.', 'Wisdom of Solomon' => 'Прем.',
		'Wisdom' => 'Прем.', 'Isaiah' => 'Ис.', 'Jeremiah' => 'Иер.', 'Baruch' => 'Вар.', 'Ezekiel' => 'Иез.', 'Daniel' => 'Дан.',
		'Joel' => 'Иоил.', 'Jonah' => 'Ион.', 'Micah' => 'Мих.', 'Zephaniah' => 'Соф.', 'Zechariah' => 'Зах.', 'Malachi' => 'Мал.',
	);
	if ( preg_match( '/^(\d?\s?[A-Za-z ]+?)\s+(\d.*)$/', $ref, $m ) && isset( $books[ $m[1] ] ) ) {
		return $books[ $m[1] ] . ' ' . $m[2];
	}
	return $ref;
}

/**
 * The Great Feasts and other major days, as the feed words them (checked
 * against the feed for each date), with their English and Russian names.
 *
 * @return array<array{0: string, 1: string, 2: string}> [ pattern, English, Russian ]
 */
function parish_readings_feasts(): array {
	return array(
		array( '/Pascha of the Lord/i', 'Pascha: the Bright Resurrection of Christ', 'Светлое Христово Воскресение. Пасха' ),
		array( '/Entry of the Lord into Jerusalem/i', 'Palm Sunday: the Entry of the Lord into Jerusalem', 'Вход Господень в Иерусалим' ),
		array( '/^The Ascension of our Lord|\. The Ascension of our Lord/i', 'The Ascension of our Lord', 'Вознесение Господне' ),
		array( '/Pentecost\W+Trinity Sunday/iu', 'Pentecost: Holy Trinity Sunday', 'День Святой Троицы. Пятидесятница' ),
		array( '/Day Of the Holy Spirit/i', 'Day of the Holy Spirit', 'День Святого Духа' ),
		array( '/Nativity of Our Most Holy Lady/i', 'The Nativity of the Mother of God', 'Рождество Пресвятой Богородицы' ),
		array( '/Universal Exaltation/i', 'The Exaltation of the Precious and Life-giving Cross', 'Воздвижение Креста Господня' ),
		array( '/Protection of Our Most Holy Lady/i', 'The Protection of the Mother of God', 'Покров Пресвятой Богородицы' ),
		array( '/Entry of the Most Holy Theotokos into the Temple/i', 'The Entry of the Mother of God into the Temple', 'Введение во храм Пресвятой Богородицы' ),
		array( '/Nativity according to the Flesh/i', 'The Nativity of Christ', 'Рождество Христово' ),
		array( '/Circumcision of Our Lord/i', 'The Circumcision of the Lord', 'Обрезание Господне' ),
		array( '/Holy Theophany/i', 'Holy Theophany: the Baptism of the Lord', 'Святое Богоявление. Крещение Господне' ),
		array( '/Meeting of Our Lord/i', 'The Meeting of the Lord', 'Сретение Господне' ),
		array( '/Annunciation of Our Most Holy Lady/i', 'The Annunciation', 'Благовещение Пресвятой Богородицы' ),
		array( '/Nativity of St\.? John the Baptist/i', 'The Nativity of St John the Baptist', 'Рождество Иоанна Предтечи' ),
		array( '/Leaders of the Apostles, Peter and Paul/i', 'The Holy Apostles Peter and Paul', 'Святые первоверховные апостолы Петр и Павел' ),
		array( '/Holy Transfiguration/i', 'The Transfiguration of the Lord', 'Преображение Господне' ),
		array( '/Dormition of Our Most Holy Lady/i', 'The Dormition of the Mother of God', 'Успение Пресвятой Богородицы' ),
		array( '/Beheading of the Glorious Prophet/i', 'The Beheading of St John the Baptist', 'Усекновение главы Иоанна Предтечи' ),
	);
}

/**
 * The day's feast, if it is one of parish_readings_feasts(): [ English, Russian ].
 */
function parish_readings_feast( array $data ): ?array {
	$text = $data['header'] . '. ' . wp_strip_all_tags( $data['saints'] );
	foreach ( parish_readings_feasts() as [ $pattern, $en, $ru ] ) {
		if ( preg_match( $pattern, $text ) ) {
			return array( $en, $ru );
		}
	}
	return null;
}

/**
 * Russian label for a reading: the service it is read at, the feast, or the saint of the day.
 */
function parish_readings_ru_note( string $note, ?array $feast = null ): string {
	if ( '' === $note ) {
		return '';
	}
	foreach ( array(
		'Matins'            => 'На утрене',
		'Vespers'           => 'На вечерне',
		'Royal Hours'       => 'На Царских часах',
		'Hour'              => 'На часах',
		'Blessing of Waters' => 'На водоосвящении',
		'Washing of the Feet' => 'На умовении ног',
	) as $en => $ru ) {
		if ( str_contains( $note, $en ) ) {
			return $ru;
		}
	}
	// "Epistle, Theotokos" → "Theotokos"; a bare "Gospel" is the day's own reading.
	$note = trim( preg_replace( '/^(Epistle|Gospel)\b,?\s*/i', '', $note ) );
	if ( '' === $note ) {
		return '';
	}
	if ( preg_match( '/^(Saturday|Sunday) (before|after)\b(.*)$/i', $note, $m ) ) {
		$when = 'sunday' === strtolower( $m[1] ) ? 'В неделю' : 'В субботу';
		if ( str_contains( $m[3], 'Elevation' ) || str_contains( $m[3], 'Exaltation' ) ) {
			return $when . ( 'before' === strtolower( $m[2] ) ? ' пред Воздвижением' : ' по Воздвижении' );
		}
		return $when . ( 'before' === strtolower( $m[2] ) ? ' пред праздником' : ' по празднике' );
	}
	$saint = preg_match( '/\b(St|Sts|Venerable|Martyrs?|Hieromartyrs?|Great-?martyrs?|Righteous|Apostle|Prophet|Hierarch|Blessed|Unmercenaries)\b/i', $note );
	if ( $feast && ! $saint ) {
		return $feast[1];
	}
	foreach ( array( 'Theotokos' => 'Богородице', 'Forerunner' => 'Предтече', 'Cross' => 'Кресту', 'Apostles' => 'Апостолам', 'Church' => 'Обновлению храма' ) as $en => $ru ) {
		if ( str_contains( $note, $en ) ) {
			return $ru;
		}
	}
	return 'Святому дня';
}

/**
 * Keep only the sentences we can translate reliably (week, tone, fast rules).
 * Feast names are dropped here and shown from parish_readings_feast() instead.
 */
function parish_readings_ru_line( string $line ): string {
	static $tones = array( 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8 );
	static $phrases = array(
		'Fast'                                    => 'Постный день',
		'Great Lent'                              => 'Великий пост',
		'Food with Oil'                           => 'Пища с растительным маслом',
		'Fish Allowed'                            => 'Разрешается рыба',
		'Caviar Allowed'                          => 'Разрешается рыбная икра',
		'Hot Food without Oil'                    => 'Горячая пища без масла',
		'Strict Fast (Bread, Vegetables, Fruits)' => 'Сухоядение',
		'Total abstinence from food'              => 'Полное воздержание от пищи',
		'Fast-free'                               => 'Поста нет',
		'Fast-free Week'                          => 'Сплошная седмица',
		'Sviatki'                                 => 'Святки',
		"Nativity (St. Philip's Fast)"            => 'Рождественский (Филиппов) пост',
		'Dormition (Theotokos) Fast'              => 'Успенский пост',
		"Apostles' (Peter & Paul) Fast"           => 'Петров пост',
		'Cheesefare Week (Butter Week)'           => 'Сырная седмица (Масленица)',
		'The End of the Great Lent'               => 'Окончание Великого поста',
		'Food without Oil'                        => 'Пища без масла',
		'Full abstention from food'               => 'Полное воздержание от пищи',
		'Meat is excluded'                        => 'Мясо исключается',
		'Maslenitsa'                              => 'Масленица',
		'Cheesefare Sunday'                       => 'Прощёное воскресенье',
		'Bright Week'                             => 'Светлая седмица',
		'Eve of the Nativity Fast'                => 'Заговенье на Рождественский пост',
	);
	static $ordinals = array( 'First' => 1, 'Second' => 2, 'Third' => 3, 'Fourth' => 4, 'Fifth' => 5, 'Sixth' => 6, 'Seventh' => 7 );

	$out = array();
	foreach ( preg_split( '/(?<!\bSt)\.(?:\s+|$)/', $line, -1, PREG_SPLIT_NO_EMPTY ) as $part ) {
		$part    = trim( $part );
		$charter = (bool) preg_match( '/^By Monastic Charter\s*[:-]\s*/', $part, $m );
		$part    = $charter ? substr( $part, strlen( $m[0] ) ) : $part;

		if ( preg_match( '/^(\d+)\w* (Week|Sunday) after Pentecost$/', $part, $m ) ) {
			$ru = ( 'Sunday' === $m[2] ? 'Неделя ' : 'Седмица ' ) . $m[1] . '-я по Пятидесятнице';
		} elseif ( preg_match( '/^(\w+) (Week|Sunday) of (Pascha|the Great Lent)\b/', $part, $m ) && isset( $ordinals[ $m[1] ] ) ) {
			$ru = ( 'Sunday' === $m[2] ? 'Неделя ' : 'Седмица ' ) . $ordinals[ $m[1] ] . ( 'Pascha' === $m[3] ? '-я по Пасхе' : '-я Великого поста' );
		} elseif ( preg_match( '/^Tone (\w+)$/', $part, $m ) && isset( $tones[ $m[1] ] ) ) {
			$ru = 'Глас ' . $tones[ $m[1] ];
		} else {
			$ru = $phrases[ $part ] ?? '';
		}
		if ( $ru ) {
			$out[] = $charter ? 'По монастырскому уставу: ' . mb_strtolower( $ru ) : $ru;
		}
	}
	return implode( '. ', $out );
}

function parish_readings_date( DateTimeImmutable $day, bool $ru, bool $weekday = true ): string {
	if ( ! $ru ) {
		return $day->format( $weekday ? 'l j F Y' : 'j F' );
	}
	$months = array( 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' );
	$days   = array( 'Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота' );
	$date   = $day->format( 'j' ) . ' ' . $months[ $day->format( 'n' ) - 1 ];
	return $weekday ? $days[ $day->format( 'w' ) ] . ', ' . $date . ' ' . $day->format( 'Y' ) . ' г.' : $date;
}

/* -------------------------------------------------------------------------
 * Output.
 * ---------------------------------------------------------------------- */

add_shortcode(
	'parish_readings',
	function () {
		$ru    = function_exists( 'parish_lang' ) ? 'ru' === parish_lang() : 'ru' === parish_calendar_lang();
		$day   = parish_readings_day();
		$today = new DateTimeImmutable( 'today', new DateTimeZone( 'Europe/London' ) );
		$data  = parish_readings_get( $day );
		$t     = fn( string $en, string $ru_text ) => $ru ? $ru_text : $en;
		$link  = fn( DateTimeImmutable $d ) => $d == $today ? remove_query_arg( 'on' ) : add_query_arg( 'on', $d->format( 'Y-m-d' ) ); // phpcs:ignore Universal.Operators.StrictComparisons

		$prev = $day->modify( '-1 day' );
		$next = $day->modify( '+1 day' );

		ob_start();
		?>
		<div class="parish-readings">
			<div class="parish-readings__day">
				<p class="parish-readings__date"><?php echo esc_html( parish_readings_date( $day, $ru ) ); ?></p>
				<p class="parish-readings__old-style">
					<?php
					/* Julian calendar: 13 days behind until 2100. */
					echo esc_html( parish_readings_date( $day->modify( '-13 days' ), $ru, false ) . ' ' . $t( '(Old Style)', 'по старому стилю' ) );
					?>
				</p>
				<?php if ( $data ) : ?>
					<?php $feast = parish_readings_feast( $data ); ?>
					<?php if ( $feast ) : ?>
						<p class="parish-readings__feast"><?php echo esc_html( $ru ? $feast[1] : $feast[0] ); ?></p>
					<?php endif; ?>
					<?php $header = $ru ? parish_readings_ru_line( $data['header'] ) : $data['header']; ?>
					<?php $fast = $ru ? parish_readings_ru_line( $data['fast'] ) : $data['fast']; ?>
					<?php if ( $header ) : ?>
						<p class="parish-readings__week"><?php echo esc_html( $header ); ?></p>
					<?php endif; ?>
					<?php if ( $fast ) : ?>
						<p class="parish-readings__fast<?php echo $data['fasting'] ? ' is-fast' : ''; ?>"><?php echo esc_html( $fast ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php if ( ! $data ) : ?>
				<p><?php echo esc_html( $t( 'The readings could not be loaded just now. Please try again later.', 'Не удалось загрузить чтения. Пожалуйста, попробуйте позже.' ) ); ?></p>
			<?php else : ?>
				<h2 class="wp-block-heading is-style-parish-section-title"><?php echo esc_html( $t( 'Scripture readings', 'Чтения из Священного Писания' ) ); ?></h2>
				<ul class="parish-readings__list">
					<?php foreach ( $data['readings'] as $r ) : ?>
						<?php $note = $ru ? parish_readings_ru_note( $r['note'], $feast ) : $r['note']; ?>
						<li>
							<?php if ( $ru ) : ?>
								<span class="parish-readings__ref"><?php echo esc_html( parish_readings_ru_ref( $r['ref'] ) ); ?></span>
							<?php else : ?>
								<a class="parish-readings__ref" href="<?php echo esc_url( $r['url'] ); ?>"><?php echo esc_html( $r['ref'] ); ?></a>
							<?php endif; ?>
							<?php if ( $note ) : ?>
								<span class="parish-readings__note"><?php echo esc_html( $note ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $ru ) : ?>
					<p class="parish-more"><a href="<?php echo esc_url( 'https://azbyka.ru/days/' . $day->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( 'Тексты чтений, жития святых и тропари дня — на сайте «Азбука веры» →' ); ?></a></p>
				<?php else : ?>
					<p class="parish-readings__hint"><?php echo esc_html( 'Select a reading to open its text.' ); ?></p>

					<?php if ( $data['saints'] ) : ?>
						<h2 class="wp-block-heading is-style-parish-section-title">Saints and feasts</h2>
						<p class="parish-readings__saints"><?php echo $data['saints']; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses'd when parsed. ?></p>
					<?php endif; ?>

					<?php if ( $data['troparia'] ) : ?>
						<details class="parish-readings__troparia">
							<summary>Troparia and kontakia</summary>
							<?php echo $data['troparia']; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses'd when parsed. ?>
						</details>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>

			<nav class="parish-readings__nav" aria-label="<?php echo esc_attr( $t( 'Other days', 'Другие дни' ) ); ?>">
				<a href="<?php echo esc_url( $link( $prev ) ); ?>" rel="nofollow">← <?php echo esc_html( parish_readings_date( $prev, $ru, false ) ); ?></a>
				<?php if ( $day != $today ) : // phpcs:ignore Universal.Operators.StrictComparisons ?>
					<a href="<?php echo esc_url( $link( $today ) ); ?>"><?php echo esc_html( $t( 'Today', 'Сегодня' ) ); ?></a>
				<?php endif; ?>
				<a href="<?php echo esc_url( $link( $next ) ); ?>" rel="nofollow"><?php echo esc_html( parish_readings_date( $next, $ru, false ) ); ?> →</a>
			</nav>

			<p class="parish-readings__source">
				<?php echo esc_html( $t( 'Calendar:', 'Календарь:' ) ); ?>
				<a href="https://www.holytrinityorthodox.com/calendar/"><?php echo esc_html( $t( 'Holy Trinity Russian Orthodox Church', 'Holy Trinity Russian Orthodox Church (на английском)' ) ); ?></a>
			</p>
		</div>
		<?php
		return parish_readings_compact( ob_get_clean() );
	}
);

/**
 * Language switch on the readings page: stay on the same day.
 */
add_filter(
	'pll_the_language_link',
	function ( $url ) {
		if ( ! $url || empty( $_GET['on'] ) || ! is_singular() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $url;
		}
		$post = get_queried_object();
		if ( ! $post || ! has_shortcode( $post->post_content, 'parish_readings' ) ) {
			return $url;
		}
		$day   = parish_readings_day();
		$today = new DateTimeImmutable( 'today', new DateTimeZone( 'Europe/London' ) );
		return $day == $today ? $url : add_query_arg( 'on', $day->format( 'Y-m-d' ), $url ); // phpcs:ignore Universal.Operators.StrictComparisons
	}
);

/**
 * One line: keeps wpautop from adding stray <p>/<br> between the tags.
 */
function parish_readings_compact( string $html ): string {
	return trim( preg_replace( '/\s*\n\s*/', ' ', $html ) );
}
