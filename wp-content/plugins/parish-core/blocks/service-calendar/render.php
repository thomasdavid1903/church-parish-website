<?php
/**
 * Front-end render for parish/service-calendar.
 *
 * A month grid is unreadable on a phone (service names get cut to a few
 * letters), so on narrow screens the iframe is switched to `mobileView`
 * (a list by default) before it loads.
 *
 * @var array $attributes
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

$calendar_id = $attributes['calendarId'] ?: parish_calendar_id();

if ( ! $calendar_id ) {
	if ( current_user_can( 'manage_options' ) ) {
		printf(
			'<p %s>%s</p>',
			get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html__( 'Service schedule: no Google Calendar set yet. Add it under Settings → Parish.', 'parish-core' )
		);
	}
	return;
}

$height      = max( 300, min( 1400, (int) $attributes['height'] ) );
$mobile_view = $attributes['mobileView'] ?? 'AGENDA';
$ru          = 'ru' === parish_calendar_lang();
$subscribe   = parish_calendar_subscribe_urls( $calendar_id );

printf(
	'<div %1$s><iframe src="%2$s" data-narrow-src="%3$s" height="%4$d" loading="lazy" title="%5$s"></iframe>'
		. '<p class="parish-embed__link"><a href="%6$s">%7$s</a></p>'
		. '<p class="parish-embed__subscribe">%8$s <a href="%9$s">Google</a> · <a href="%10$s">%11$s</a></p></div>',
	get_block_wrapper_attributes( array( 'class' => 'parish-embed parish-calendar' ) ), // phpcs:ignore WordPress.Security.EscapeOutput
	esc_url( parish_calendar_embed_url( $calendar_id, $attributes['view'] ) ),
	esc_url( parish_calendar_embed_url( $calendar_id, $mobile_view ) ),
	(int) $height,
	esc_attr( $ru ? 'Расписание богослужений' : 'Schedule of services' ),
	esc_url( parish_calendar_open_url( $calendar_id ) ),
	esc_html( $ru ? 'Открыть в Google Календаре' : 'Open in Google Calendar' ),
	esc_html( $ru ? 'Добавить расписание в свой календарь:' : 'Add the services to your calendar:' ),
	esc_url( $subscribe['google'] ),
	esc_url( $subscribe['ical'], array( 'webcal' ) ),
	esc_html( 'iPhone / Outlook' )
);

// Inline and immediately after the iframe, so the swap happens before a lazy iframe starts loading.
echo '<script>document.querySelectorAll(".parish-calendar iframe[data-narrow-src]").forEach(function(f){if(window.matchMedia("(max-width: 600px)").matches){f.src=f.dataset.narrowSrc;f.height=Math.min(f.height,500);}f.removeAttribute("data-narrow-src");});</script>';
