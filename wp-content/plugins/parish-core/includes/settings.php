<?php
/**
 * Settings → Parish: site-wide values editors shouldn't have to repeat.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_OPTION_CALENDAR    = 'parish_calendar_id';
const PARISH_OPTION_CALENDAR_RU = 'parish_calendar_id_ru';

/**
 * The calendar for the current language. The parish keeps separate English
 * and Russian Google Calendars; Russian falls back to English if unset.
 */
function parish_calendar_id(): string {
	$lang = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
	if ( 'ru' === $lang && get_option( PARISH_OPTION_CALENDAR_RU ) ) {
		return (string) get_option( PARISH_OPTION_CALENDAR_RU );
	}
	return (string) get_option( PARISH_OPTION_CALENDAR );
}

add_action(
	'admin_init',
	function () {
		add_settings_section( 'parish_calendar', __( 'Service schedule', 'parish-core' ), '__return_false', 'parish' );

		$fields = array(
			PARISH_OPTION_CALENDAR    => __( 'Google Calendar ID (English)', 'parish-core' ),
			PARISH_OPTION_CALENDAR_RU => __( 'Google Calendar ID (Russian)', 'parish-core' ),
		);
		foreach ( $fields as $option => $label ) {
			register_setting(
				'parish',
				$option,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'parish_sanitize_calendar_id',
					'default'           => '',
				)
			);
			add_settings_field(
				$option,
				$label,
				function () use ( $option ) {
					printf(
						'<input type="text" class="regular-text code" name="%1$s" value="%2$s" placeholder="abc123@group.calendar.google.com">',
						esc_attr( $option ),
						esc_attr( get_option( $option ) )
					);
					if ( PARISH_OPTION_CALENDAR_RU === $option ) {
						printf(
							'<p class="description">%s</p>',
							esc_html__( 'Shown on Russian pages. Leave empty to use the English calendar in both languages. In Google Calendar: Settings → (your calendar) → Integrate calendar → Calendar ID. Each calendar must be public ("Make available to public"). You may also paste the full embed URL.', 'parish-core' )
						);
					}
				},
				'parish',
				'parish_calendar'
			);
		}
	}
);

/**
 * Accept either a bare calendar ID or a pasted embed URL / iframe.
 */
function parish_sanitize_calendar_id( $value ): string {
	$value = trim( (string) $value );
	if ( preg_match( '/[?&]src=([^&"\']+)/', $value, $m ) ) {
		$value = rawurldecode( $m[1] );
	}
	return sanitize_text_field( $value );
}

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'Parish settings', 'parish-core' ),
			__( 'Parish', 'parish-core' ),
			'manage_options',
			'parish',
			function () {
				?>
				<div class="wrap">
					<h1><?php esc_html_e( 'Parish settings', 'parish-core' ); ?></h1>
					<form method="post" action="options.php">
						<?php
						settings_fields( 'parish' );
						do_settings_sections( 'parish' );
						submit_button();
						?>
					</form>
				</div>
				<?php
			}
		);
	}
);
