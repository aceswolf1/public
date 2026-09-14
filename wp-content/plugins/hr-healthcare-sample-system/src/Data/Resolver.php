<?php
/**
 * The ONE resolver: {slug, options} → SKU payload.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Data;

/**
 * Shared by REST, Gravity Forms, and (mirrored) client JS.
 * Matches selectable-key combos only; never trusts a cookie-stored SKU.
 */
final class Resolver {

	/**
	 * Resolve a selection to a SKU row payload.
	 *
	 * @param string                $slug    Group identity.
	 * @param array<string, string> $options Selectable axis → machine value.
	 * @return array{
	 *   sku: string,
	 *   valid: bool,
	 *   product_name: string,
	 *   hcpcs: string,
	 *   sample_amount: string
	 * }
	 */
	public static function resolve( string $slug, array $options ): array {
		$invalid = array(
			'sku'           => '',
			'valid'         => false,
			'product_name'  => '',
			'hcpcs'         => '',
			'sample_amount' => '',
		);

		$slug = sanitize_title( $slug );

		if ( '' === $slug ) {
			return $invalid;
		}

		$group = Groups::find_by_slug( $slug );

		if ( null === $group ) {
			return $invalid;
		}

		$normalized = self::normalize_options( $options );
		$target     = Skus::encode_combo( $normalized );

		foreach ( Skus::for_group( (int) $group['id'] ) as $row ) {
			$candidate = Skus::encode_combo( Skus::decode_combo( (string) $row['combo_json'] ) );

			if ( $candidate !== $target ) {
				continue;
			}

			return array(
				'sku'           => (string) $row['sku'],
				'valid'         => true,
				'product_name'  => (string) $row['product_name'],
				'hcpcs'         => (string) $row['hcpcs'],
				'sample_amount' => (string) $row['sample_amount'],
			);
		}

		return $invalid;
	}

	/**
	 * Full group config payload (properties + combinations + image_url).
	 *
	 * Image resolution is injected by the caller (ImageMap) so this class
	 * stays free of WP post lookups.
	 *
	 * @param string      $slug      Group identity.
	 * @param string|null $image_url Resolved featured-image URL (or placeholder).
	 * @return array<string, mixed>|null Null when the group is unknown.
	 */
	public static function group_config( string $slug, ?string $image_url = null ): ?array {
		$slug  = sanitize_title( $slug );
		$group = Groups::find_by_slug( $slug );

		if ( null === $group ) {
			return null;
		}

		$group_id = (int) $group['id'];

		return array(
			'slug'         => (string) $group['slug'],
			'product_line' => (string) $group['product_line'],
			'page_url'     => (string) $group['page_url'],
			'image_url'    => $image_url ?? '',
			'properties'   => Options::properties_for_group( $group_id ),
			'combinations' => Skus::combinations_for_group( $group_id ),
		);
	}

	/**
	 * Drop empty keys and cast values to strings for stable matching.
	 *
	 * @param array<string, mixed> $options Raw options map.
	 * @return array<string, string>
	 */
	private static function normalize_options( array $options ): array {
		$out = array();

		foreach ( $options as $axis => $value ) {
			if ( ! is_string( $axis ) || '' === $axis ) {
				continue;
			}

			if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
				continue;
			}

			$value = (string) $value;

			if ( '' === $value ) {
				continue;
			}

			$out[ $axis ] = $value;
		}

		ksort( $out );

		return $out;
	}
}
