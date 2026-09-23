<?php
/**
 * Front-end render for parish/service-calendar.
 *
 * @var array $attributes
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

$calendar_id = $attributes['calendarId'] ?: get_option( PARISH_OPTION_CALENDAR );

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

$height = max( 300, min( 1400, (int) $attributes['height'] ) );
printf(
	'<div %1$s><iframe src="%2$s" height="%3$d" loading="lazy" title="%4$s"></iframe></div>',
	get_block_wrapper_attributes( array( 'class' => 'parish-embed' ) ), // phpcs:ignore WordPress.Security.EscapeOutput
	esc_url( parish_calendar_embed_url( $calendar_id, $attributes['view'] ) ),
	(int) $height,
	esc_attr__( 'Schedule of services', 'parish-core' )
);
