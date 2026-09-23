<?php
/**
 * Header and footer, rendered in PHP so they follow the current language.
 * Used from parts/header.html and parts/footer.html as [parish_header] / [parish_footer].
 *
 * @package parish-cathedral
 */

defined( 'ABSPATH' ) || exit;

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
				<div>
					<p class="parish-masthead__title"><a href="<?php echo esc_url( function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ) ); ?>"><?php echo esc_html( parish_t( 'London Orthodox Cathedral', 'Лондонский православный собор' ) ); ?></a></p>
					<p class="parish-masthead__subtitle"><?php echo esc_html( parish_t( 'of the Nativity of the Mother of God and the Holy Royal Martyrs', 'Рождества Пресвятой Богородицы и Святых Царственных Мучеников' ) ); ?></p>
				</div>
			</div>
		</div>
		<nav class="parish-nav" aria-label="<?php echo esc_attr( parish_t( 'Main menu', 'Главное меню' ) ); ?>">
			<div class="parish-nav__inner">
				<button class="parish-nav__toggle" type="button" aria-expanded="false">
					<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 5h16M2 10h16M2 15h16" stroke="currentColor" stroke-width="2"/></svg>
					<?php echo esc_html( parish_t( 'Menu', 'Меню' ) ); ?>
				</button>
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
		return ob_get_clean();
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
		return ob_get_clean();
	}
);
