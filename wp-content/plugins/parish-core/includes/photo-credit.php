<?php
/**
 * Photo credits.
 *
 * Each image in the Media Library has a "Photo credit" field (e.g.
 * "Protodeacon Sergei Baranoff"). When it is filled in, the credit is shown
 * in small text in the bottom corner of the photo wherever it appears:
 * image and gallery blocks, cover blocks and page banners. Leave it empty
 * and nothing is shown, so only the photos that need signing are signed.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_PHOTO_CREDIT_META = 'parish_photo_credit';

/**
 * "Photo credit" field in the Media Library.
 */
add_filter(
	'attachment_fields_to_edit',
	function ( array $fields, WP_Post $post ): array {
		if ( ! wp_attachment_is_image( $post ) ) {
			return $fields;
		}
		$fields[ PARISH_PHOTO_CREDIT_META ] = array(
			'label' => __( 'Photo credit', 'parish-core' ),
			'input' => 'text',
			'value' => (string) get_post_meta( $post->ID, PARISH_PHOTO_CREDIT_META, true ),
			'helps' => __( 'Shown in the corner of the photo, e.g. “Protodeacon Sergei Baranoff”. Leave empty for no credit.', 'parish-core' ),
		);
		return $fields;
	},
	10,
	2
);

add_filter(
	'attachment_fields_to_save',
	function ( array $post, array $attachment ): array {
		if ( isset( $attachment[ PARISH_PHOTO_CREDIT_META ] ) ) {
			$credit = sanitize_text_field( $attachment[ PARISH_PHOTO_CREDIT_META ] );
			if ( '' === $credit ) {
				delete_post_meta( $post['ID'], PARISH_PHOTO_CREDIT_META );
			} else {
				update_post_meta( $post['ID'], PARISH_PHOTO_CREDIT_META, $credit );
			}
		}
		return $post;
	},
	10,
	2
);

/**
 * The credit overlay for an attachment, or '' when it has none.
 */
function parish_photo_credit_html( int $attachment_id ): string {
	$credit = $attachment_id ? (string) get_post_meta( $attachment_id, PARISH_PHOTO_CREDIT_META, true ) : '';
	if ( '' === $credit ) {
		return '';
	}
	$label = 'ru' === ( function_exists( 'parish_lang' ) ? parish_lang() : 'en' ) ? 'Фото: ' : 'Photo: ';
	return '<span class="parish-photo-credit">' . esc_html( $label . $credit ) . '</span>';
}

/**
 * Put $credit just before the block's closing tag (</figure> or </div>).
 */
function parish_photo_credit_insert( string $html, string $credit, string $tag ): string {
	$pos = strrpos( $html, '</' . $tag . '>' );
	if ( '' === $credit || false === $pos ) {
		return $html;
	}
	$html = substr_replace( $html, $credit . '</' . $tag . '>', $pos, strlen( $tag ) + 3 );
	// Mark the block so the CSS can position the credit inside it.
	return preg_replace( '/class="/', 'class="has-photo-credit ', $html, 1 );
}

add_filter(
	'render_block',
	function ( string $html, array $block ): string {
		switch ( $block['blockName'] ?? '' ) {
			case 'core/image':
				return parish_photo_credit_insert( $html, parish_photo_credit_html( (int) ( $block['attrs']['id'] ?? 0 ) ), 'figure' );
			case 'core/cover':
				return parish_photo_credit_insert( $html, parish_photo_credit_html( (int) ( $block['attrs']['id'] ?? 0 ) ), 'div' );
			case 'core/post-featured-image':
				return parish_photo_credit_insert( $html, parish_photo_credit_html( (int) get_post_thumbnail_id() ), 'figure' );
		}
		return $html;
	},
	10,
	2
);
