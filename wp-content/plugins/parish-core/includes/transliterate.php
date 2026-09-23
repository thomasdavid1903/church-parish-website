<?php
/**
 * Latin URLs for Russian posts and pages.
 *
 * WordPress keeps Cyrillic in slugs, which become unreadable when shared
 * (/ru/%d0%b2%d0%be%d1%81…). This transliterates them when a slug is first
 * generated, using the same scheme as the seeded page URLs
 * (voskresnaya-shkola, prihodskaya-zhizn).
 *
 * @package parish-core
 */

defined( 'ABSPATH' ) || exit;

function parish_transliterate( string $text ): string {
	static $map = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh', 'ъ' => '',
		'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
		// Ukrainian and Belarusian letters, for names.
		'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g', 'ў' => 'u',
	);
	return strtr( mb_strtolower( $text, 'UTF-8' ), $map );
}

add_filter(
	'sanitize_title',
	function ( string $title, string $raw_title = '', string $context = 'display' ): string {
		if ( 'save' === $context && preg_match( '/\p{Cyrillic}/u', $title ) ) {
			return parish_transliterate( $title );
		}
		return $title;
	},
	9,
	3
);
