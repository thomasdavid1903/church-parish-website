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
function parish_calendar_lang(): string {
	return function_exists( 'pll_current_language' ) ? ( pll_current_language( 'slug' ) ?: 'en' ) : 'en';
}

function parish_calendar_embed_url( string $calendar_id, string $view = 'MONTH' ): string {
	$lang = parish_calendar_lang();
	return add_query_arg(
		array(
			'src'           => rawurlencode( $calendar_id ),
			'ctz'           => rawurlencode( 'Europe/London' ),
			'mode'          => in_array( $view, array( 'AGENDA', 'WEEK', 'MONTH' ), true ) ? $view : 'MONTH',
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

/**
 * Subscribe links, so the services show up (and stay up to date) in the
 * visitor's own calendar app.
 *
 * @return array{google: string, ical: string}
 */
function parish_calendar_subscribe_urls( string $calendar_id ): array {
	return array(
		'google' => 'https://calendar.google.com/calendar/r?cid=' . rawurlencode( $calendar_id ),
		// webcal:// opens the "Subscribe" dialog on iPhone, Mac and Outlook.
		'ical'   => 'webcal://calendar.google.com/calendar/ical/' . rawurlencode( $calendar_id ) . '/public/basic.ics',
	);
}

/**
 * Full-page Google Calendar.
 */
function parish_calendar_open_url( string $calendar_id ): string {
	return add_query_arg(
		array(
			'src' => rawurlencode( $calendar_id ),
			'ctz' => rawurlencode( 'Europe/London' ),
		),
		'https://calendar.google.com/calendar/embed'
	);
}
