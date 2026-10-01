<?php
/**
 * Header and footer, rendered in PHP so they follow the current language.
 * Used from parts/header.html and parts/footer.html as [parish_header] / [parish_footer].
 *
 * @package parish-cathedral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drop the whitespace between tags. The shortcode block runs wpautop, which
 * otherwise turns our line breaks into stray <br> and <p> tags.
 */
function parish_compact_html( string $html ): string {
	return trim( preg_replace( array( '/\s*\n\s*/', '/>\s+</' ), array( ' ', '><' ), $html ) );
}

/**
 * Where the search form submits: the language's root (/ru/), not home_url(),
 * which Polylang points at the Russian static front page, where ?s= is ignored.
 */
function parish_search_url(): string {
	if ( function_exists( 'PLL' ) && PLL()->curlang && isset( PLL()->links ) ) {
		return PLL()->links->get_home_url( PLL()->curlang, true );
	}
	return home_url( '/' );
}

/**
 * Language switcher links (EN | RU). Empty if Polylang isn't active.
 */
function parish_language_switcher(): string {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return '';
	}
	$langs = pll_the_languages(
		array(
			'raw'           => 1,
			'hide_if_empty' => 0,
		)
	);
	if ( empty( $langs ) ) {
		return '';
	}
	$items = '';
	foreach ( $langs as $lang ) {
		$items .= sprintf(
			'<li class="%s"><a href="%s" lang="%s" hreflang="%s"%s>%s</a></li>',
			$lang['current_lang'] ? 'current-lang' : '',
			esc_url( $lang['url'] ),
			esc_attr( $lang['locale'] ),
			esc_attr( $lang['locale'] ),
			$lang['current_lang'] ? ' aria-current="true"' : '',
			esc_html( 'ru' === $lang['slug'] ? 'Русский' : 'English' )
		);
	}
	return '<ul class="parish-lang" aria-label="' . esc_attr( parish_t( 'Language', 'Язык' ) ) . '">' . $items . '</ul>';
}

add_shortcode(
	'parish_header',
	function () {
		$cross = '<svg class="parish-masthead__cross" viewBox="0 0 34 44" aria-hidden="true" focusable="false"><g fill="currentColor"><rect x="15" y="0" width="4" height="44"/><rect x="10" y="5" width="14" height="3"/><rect x="4" y="13" width="26" height="4"/><rect x="9" y="29" width="16" height="3.2" transform="rotate(-18 17 30.6)"/></g></svg>';

		ob_start();
		?>
		<div class="parish-topbar">
			<div class="parish-topbar__inner">
				<span class="parish-topbar__tagline"><?php echo esc_html( parish_t( 'Russian Orthodox Church Outside Russia · Diocese of Great Britain and Western Europe', 'Русская Православная Церковь Заграницей · Епархия Великобритании и Западной Европы' ) ); ?></span>
				<?php echo parish_language_switcher(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?>
			</div>
		</div>
		<div class="parish-masthead-wrap">
			<div class="parish-masthead">
				<?php echo $cross; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
				<div class="parish-masthead__text">
					<?php // Not a heading: every page, the home page included (its photo banner), has its own H1. ?>
					<p class="parish-masthead__title"><a href="<?php echo esc_url( function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ) ); ?>"><?php echo esc_html( parish_t( 'London Orthodox Cathedral', 'Лондонский православный собор' ) ); ?></a></p>
					<p class="parish-masthead__subtitle"><?php echo esc_html( parish_t( 'of the Nativity of the Mother of God and the Holy Royal Martyrs', 'Рождества Пресвятой Богородицы и Святых Царственных Мучеников' ) ); ?></p>
				</div>
				<button class="parish-nav__toggle" type="button" aria-expanded="false" aria-controls="parish-nav">
					<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 5h16M2 10h16M2 15h16" stroke="currentColor" stroke-width="2"/></svg>
					<?php echo esc_html( parish_t( 'Menu', 'Меню' ) ); ?>
				</button>
			</div>
		</div>
		<nav class="parish-nav" id="parish-nav" aria-label="<?php echo esc_attr( parish_t( 'Main menu', 'Главное меню' ) ); ?>">
			<div class="parish-nav__inner">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'depth'          => 2,
						'fallback_cb'    => 'wp_page_menu',
					)
				);
				?>
			</div>
		</nav>
		<?php
		return parish_compact_html( ob_get_clean() );
	}
);

/**
 * [parish_post_nav]: under a news post, "← All news" plus labelled previous / next
 * posts in the same language (Polylang filters adjacent posts by language).
 */
add_shortcode(
	'parish_post_nav',
	function () {
		$post = get_post( get_queried_object_id() );
		if ( ! $post || 'post' !== $post->post_type ) {
			return '';
		}
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- get_adjacent_post() reads it.
		$news_id  = (int) get_option( 'page_for_posts' );
		$news_url = $news_id ? get_permalink( function_exists( 'pll_get_post' ) ? ( pll_get_post( $news_id ) ?: $news_id ) : $news_id ) : home_url( '/' );
		$links    = array(
			array( get_previous_post(), 'parish-post-nav__prev', parish_t( '← Previous', '← Предыдущая' ) ),
			array( get_next_post(), 'parish-post-nav__next', parish_t( 'Next →', 'Следующая →' ) ),
		);

		$out = '<p class="parish-more"><a href="' . esc_url( $news_url ) . '">' . esc_html( parish_t( '← All news', '← Все новости' ) ) . '</a></p><nav class="parish-post-nav" aria-label="' . esc_attr( parish_t( 'More news', 'Другие новости' ) ) . '">';
		foreach ( $links as [ $adjacent, $class, $label ] ) {
			if ( $adjacent ) {
				$out .= sprintf(
					'<a class="%s" href="%s"><span class="parish-post-nav__label">%s</span>%s</a>',
					esc_attr( $class ),
					esc_url( get_permalink( $adjacent ) ),
					esc_html( $label ),
					esc_html( get_the_title( $adjacent ) )
				);
			}
		}
		return $out . '</nav>';
	}
);

add_shortcode(
	'parish_footer',
	function () {
		ob_start();
		?>
		<div class="parish-footer__inner">
			<div>
				<h2><?php echo esc_html( parish_t( 'Cathedral address', 'Адрес собора' ) ); ?></h2>
				<p>57 Harvard Road<br>London W4 4ED</p>
				<p><a href="https://maps.app.goo.gl/6hcgWaNTynMqAhcx6"><?php echo esc_html( parish_t( 'Open in Google Maps', 'Открыть в Google Картах' ) ); ?></a></p>
				<p><a href="<?php echo esc_url( parish_page_url( 'visit' ) ); ?>"><?php echo esc_html( parish_t( 'Directions and visiting', 'Как добраться' ) ); ?></a></p>
			</div>
			<div>
				<h2><?php echo esc_html( parish_t( 'Services', 'Богослужения' ) ); ?></h2>
				<p><a href="<?php echo esc_url( parish_page_url( 'services' ) ); ?>"><?php echo esc_html( parish_t( 'Full schedule of services', 'Полное расписание богослужений' ) ); ?></a></p>
				<p><a href="<?php echo esc_url( parish_page_url( 'clergy' ) ); ?>"><?php echo esc_html( parish_t( 'Clergy and contacts', 'Духовенство и контакты' ) ); ?></a></p>
			</div>
			<div>
				<h2><?php echo esc_html( parish_t( 'Support the Cathedral', 'Поддержать собор' ) ); ?></h2>
				<p><?php echo esc_html( parish_t( 'UK taxpayers can add 25% to their gift through Gift Aid.', 'Налогоплательщики Великобритании могут увеличить пожертвование на 25% через Gift Aid.' ) ); ?></p>
				<p><a href="<?php echo esc_url( parish_page_url( 'donate' ) ); ?>"><?php echo esc_html( parish_t( 'Make a donation', 'Сделать пожертвование' ) ); ?></a></p>
			</div>
			<div>
				<h2><?php echo esc_html( parish_t( 'Search', 'Поиск' ) ); ?></h2>
				<form class="parish-search" role="search" method="get" action="<?php echo esc_url( parish_search_url() ); ?>">
					<label class="screen-reader-text" for="parish-search-input"><?php echo esc_html( parish_t( 'Search the website', 'Поиск по сайту' ) ); ?></label>
					<input id="parish-search-input" type="search" name="s" placeholder="<?php echo esc_attr( parish_t( 'Search the website', 'Поиск по сайту' ) ); ?>">
					<button type="submit"><?php echo esc_html( parish_t( 'Search', 'Найти' ) ); ?></button>
				</form>
			</div>
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<div>
					<h2><?php echo esc_html( parish_t( 'Links', 'Ссылки' ) ); ?></h2>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'depth'          => 1,
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
		<div class="parish-footer__legal">
			© <?php echo esc_html( gmdate( 'Y' ) ); ?>
			<?php echo esc_html( parish_t( 'Cathedral of the Nativity of the Mother of God and the Holy Royal Martyrs (ROCOR). Registered Charity no. 234203.', 'Собор Рождества Пресвятой Богородицы и Святых Царственных Мучеников (РПЦЗ). Зарегистрированная благотворительная организация № 234203.' ) ); ?>
		</div>
		<?php
		return parish_compact_html( ob_get_clean() );
	}
);
