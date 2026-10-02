<?php
/**
 * Photo credits.
 *
 * Each image in the Media Library has "Photo credit" fields in English and
 * Russian (e.g. "Protodeacon Sergei Baranoff" / "протодиакон Сергий Баранов").
 * When filled in, the credit is shown in small text in the corner of the
 * photo wherever it appears: image and gallery blocks, cover blocks, page
 * banners and the enlarged (lightbox) view. On phones it moves just below
 * the photo so it never covers faces. Leave the fields empty and nothing is
 * shown, so only the photos that need signing are signed.
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

const PARISH_PHOTO_CREDIT_META    = 'parish_photo_credit';
const PARISH_PHOTO_CREDIT_RU_META = 'parish_photo_credit_ru';

/**
 * "Photo credit" fields in the Media Library, full width, right after the
 * standard fields.
 */
add_filter(
	'attachment_fields_to_edit',
	function ( array $fields, WP_Post $post ): array {
		if ( ! wp_attachment_is_image( $post ) ) {
			return $fields;
		}
		$input = function ( string $meta, string $placeholder ) use ( $post ): string {
			return sprintf(
				'<input type="text" class="widefat" style="width:100%%" name="attachments[%1$d][%2$s]" id="attachments-%1$d-%2$s" value="%3$s" placeholder="%4$s" />',
				$post->ID,
				$meta,
				esc_attr( (string) get_post_meta( $post->ID, $meta, true ) ),
				esc_attr( $placeholder )
			);
		};
		$fields[ PARISH_PHOTO_CREDIT_META ]    = array(
			'label' => __( 'Photo credit (English)', 'parish-core' ),
			'input' => 'html',
			'html'  => $input( PARISH_PHOTO_CREDIT_META, 'Protodeacon Sergei Baranoff' ),
			'helps' => __( 'Shown in the corner of the photo. Only fill in if the photographer asked to be named. Leave empty for no credit.', 'parish-core' ),
		);
		$fields[ PARISH_PHOTO_CREDIT_RU_META ] = array(
			'label' => __( 'Photo credit (Russian)', 'parish-core' ),
			'input' => 'html',
			'html'  => $input( PARISH_PHOTO_CREDIT_RU_META, 'протодиакон Сергий Баранов' ),
			'helps' => __( 'Used on Russian pages. If empty, the English credit is used.', 'parish-core' ),
		);
		return $fields;
	},
	10,
	2
);

/**
 * Other copies of the same photo: its Polylang translations and copies
 * imported from the same source (the seed imports one copy per language).
 *
 * @return int[]
 */
function parish_photo_siblings( int $id ): array {
	$ids = array();
	if ( function_exists( 'pll_get_post_translations' ) ) {
		$ids = array_values( pll_get_post_translations( $id ) );
	}
	$source = (string) get_post_meta( $id, 'parish_photo_source', true );
	if ( '' !== $source ) {
		$ids = array_merge(
			$ids,
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
					'numberposts' => -1,
					'fields'      => 'ids',
					'lang'        => '',
					'meta_key'    => 'parish_photo_source', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'  => $source, // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			)
		);
	}
	return array_diff( array_unique( array_map( 'intval', $ids ) ), array( $id ) );
}

add_filter(
	'attachment_fields_to_save',
	function ( array $post, array $attachment ): array {
		foreach ( array( PARISH_PHOTO_CREDIT_META, PARISH_PHOTO_CREDIT_RU_META ) as $meta ) {
			if ( ! isset( $attachment[ $meta ] ) ) {
				continue;
			}
			$credit = sanitize_text_field( $attachment[ $meta ] );
			// Save on this photo and on its other-language copies, so the
			// credit is the same whichever copy was edited.
			foreach ( array_merge( array( (int) $post['ID'] ), parish_photo_siblings( (int) $post['ID'] ) ) as $id ) {
				if ( '' === $credit ) {
					delete_post_meta( $id, $meta );
				} else {
					update_post_meta( $id, $meta, $credit );
				}
			}
		}
		return $post;
	},
	10,
	2
);

/**
 * The credit text for an attachment in the current language, or ''.
 */
function parish_photo_credit_text( int $attachment_id ): string {
	if ( ! $attachment_id ) {
		return '';
	}
	$ru     = 'ru' === ( function_exists( 'parish_lang' ) ? parish_lang() : 'en' );
	$credit = $ru ? (string) get_post_meta( $attachment_id, PARISH_PHOTO_CREDIT_RU_META, true ) : '';
	if ( '' === $credit ) {
		$credit = (string) get_post_meta( $attachment_id, PARISH_PHOTO_CREDIT_META, true );
	}
	if ( '' === $credit ) {
		return '';
	}
	return ( $ru ? 'Фото: ' : 'Photo: ' ) . $credit;
}

/**
 * The credit overlay for an attachment, or '' when it has none.
 */
function parish_photo_credit_html( int $attachment_id ): string {
	$text = parish_photo_credit_text( $attachment_id );
	return '' === $text ? '' : '<span class="parish-photo-credit">' . esc_html( $text ) . '</span>';
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
				$id = (int) ( $block['attrs']['id'] ?? 0 );
				$html = parish_photo_credit_insert( $html, parish_photo_credit_html( $id ), 'figure' );
				// The enlarged (lightbox) view reads the credit from here.
				$text = parish_photo_credit_text( $id );
				return '' === $text ? $html : preg_replace( '/<img /', '<img data-photo-credit="' . esc_attr( $text ) . '" ', $html, 1 );
			case 'core/cover':
				return parish_photo_credit_insert( $html, parish_photo_credit_html( (int) ( $block['attrs']['id'] ?? 0 ) ), 'div' );
			case 'core/post-featured-image':
				return parish_photo_credit_insert( $html, parish_photo_credit_html( (int) get_post_thumbnail_id() ), 'figure' );
			case 'core/gallery':
				return parish_photo_credit_gallery( $html, $block );
		}
		return $html;
	},
	10,
	2
);

/**
 * In a gallery the same credit under every photo is noise for screen-reader
 * users: hide the per-photo credits from them and say it once under the
 * gallery instead ("Photos: …").
 */
function parish_photo_credit_gallery( string $html, array $block ): string {
	$credits = array();
	foreach ( $block['innerBlocks'] ?? array() as $inner ) {
		$text = parish_photo_credit_text( (int) ( $inner['attrs']['id'] ?? 0 ) );
		if ( '' !== $text ) {
			$credits[ preg_replace( '/^(Photo|Фото): /u', '', $text ) ] = true;
		}
	}
	if ( ! $credits ) {
		return $html;
	}
	$html  = str_replace( '<span class="parish-photo-credit">', '<span class="parish-photo-credit" aria-hidden="true">', $html );
	$label = 'ru' === ( function_exists( 'parish_lang' ) ? parish_lang() : 'en' ) ? 'Фото: ' : 'Photos: ';
	return $html . '<p class="screen-reader-text">' . esc_html( $label . implode( ', ', array_keys( $credits ) ) ) . '</p>';
}

/**
 * Show the credit in the enlarged (lightbox) view too.
 */
add_action(
	'wp_footer',
	function (): void {
		?>
<script>
(function () {
	var overlay;
	function update() {
		overlay = overlay || document.querySelector('.wp-lightbox-overlay');
		if (!overlay) return;
		var big = overlay.querySelector('img');
		var file = big && (big.currentSrc || big.src || '').split('/').pop().replace(/-\d+x\d+(?=\.\w+$)/, '').replace(/-scaled(?=\.\w+$)/, '');
		var match = file && Array.prototype.find.call(document.querySelectorAll('img[data-photo-credit]'), function (img) {
			return (img.currentSrc || img.src).split('/').pop().replace(/-\d+x\d+(?=\.\w+$)/, '').replace(/-scaled(?=\.\w+$)/, '') === file;
		});
		var credit = match ? match.getAttribute('data-photo-credit') : '';
		var el = overlay.querySelector('.parish-photo-credit--lightbox');
		if (!credit) { if (el) el.remove(); return; }
		if (!el) {
			el = document.createElement('span');
			el.className = 'parish-photo-credit parish-photo-credit--lightbox';
			overlay.appendChild(el);
		}
		el.textContent = credit;
	}
	document.addEventListener('click', function () { setTimeout(update, 60); }, true);
	document.addEventListener('keyup', function () { setTimeout(update, 60); }, true);
})();
</script>
		<?php
	}
);
