<?php
/**
 * Settings → Parish: site-wide values editors shouldn't have to repeat.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_OPTION_CALENDAR = 'parish_calendar_id';

add_action(
	'admin_init',
	function () {
		register_setting(
			'parish',
			PARISH_OPTION_CALENDAR,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'parish_sanitize_calendar_id',
				'default'           => '',
			)
		);
		add_settings_section( 'parish_calendar', __( 'Service schedule', 'parish-core' ), '__return_false', 'parish' );
		add_settings_field(
			PARISH_OPTION_CALENDAR,
			__( 'Google Calendar ID', 'parish-core' ),
			function () {
				printf(
					'<input type="text" class="regular-text code" name="%1$s" value="%2$s" placeholder="abc123@group.calendar.google.com"><p class="description">%3$s</p>',
					esc_attr( PARISH_OPTION_CALENDAR ),
					esc_attr( get_option( PARISH_OPTION_CALENDAR ) ),
					esc_html__( 'In Google Calendar: Settings → (your calendar) → Integrate calendar → Calendar ID. The calendar must be public ("Make available to public"). You may also paste the full embed URL.', 'parish-core' )
				);
			},
			'parish',
			'parish_calendar'
		);
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
