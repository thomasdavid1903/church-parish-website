<?php
/**
 * "Service schedule" block: embeds the parish's public Google Calendar
 * (requirement 5). Services keep being managed in Google Calendar; the
 * website always shows the live calendar, in the visitor's language.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_block_type( PARISH_CORE_DIR . '/blocks/service-calendar' );
	}
);

/**
 * Build the Google Calendar embed URL.
 */
function parish_calendar_embed_url( string $calendar_id, string $view = 'AGENDA' ): string {
	$lang = function_exists( 'pll_current_language' ) ? ( pll_current_language( 'slug' ) ?: 'en' ) : 'en';
	return add_query_arg(
		array(
			'src'           => rawurlencode( $calendar_id ),
			'ctz'           => rawurlencode( 'Europe/London' ),
			'mode'          => in_array( $view, array( 'AGENDA', 'WEEK', 'MONTH' ), true ) ? $view : 'AGENDA',
			'hl'            => 'ru' === $lang ? 'ru' : 'en_GB',
			'wkst'          => 2, // Weeks start on Monday.
			'showTitle'     => 0,
			'showPrint'     => 0,
			'showCalendars' => 0,
			'showTz'        => 0,
		),
		'https://calendar.google.com/calendar/embed'
	);
}
