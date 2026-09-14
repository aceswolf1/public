<?php
/**
 * Query + write helpers for hrh_sample_skus.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Data;

/**
 * SKUs table access. Stable key within a group = normalized combo_json.
 */
final class Skus {

	/**
	 * All SKU rows for a group.
	 *
	 * @param int $group_id Group ID.
	 * @return list<array<string, mixed>>
	 */
	public static function for_group( int $group_id ): array {
		global $wpdb;

		$table = Schema::skus_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Schema.
				"SELECT * FROM {$table} WHERE group_id = %d",
				$group_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Insert a SKU row. Returns new ID or 0.
	 *
	 * Expected keys: group_id, sku, combo_json (string|array),
	 * product_name, hcpcs, sample_amount.
	 *
	 * @param array<string, mixed> $data Row data.
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		$wpdb->insert(
			Schema::skus_table(),
			array(
				'group_id'      => (int) $data['group_id'],
				'sku'           => $data['sku'],
				'combo_json'    => self::encode_combo( $data['combo_json'] ),
				'product_name'  => $data['product_name'],
				'hcpcs'         => $data['hcpcs'],
				'sample_amount' => $data['sample_amount'],
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a SKU row by ID.
	 *
	 * @param int                  $id   SKU row ID.
	 * @param array<string, mixed> $data Fields to update.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$fields = array();
		$format = array();

		$map = array(
			'sku'           => '%s',
			'combo_json'    => '%s',
			'product_name'  => '%s',
			'hcpcs'         => '%s',
			'sample_amount' => '%s',
		);

		foreach ( $map as $key => $fmt ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$value          = 'combo_json' === $key ? self::encode_combo( $data[ $key ] ) : $data[ $key ];
			$fields[ $key ] = $value;
			$format[]       = $fmt;
		}

		if ( array() === $fields ) {
			return false;
		}

		$result = $wpdb->update(
			Schema::skus_table(),
			$fields,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a SKU row by ID.
	 *
	 * @param int $id SKU row ID.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			Schema::skus_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete every SKU for a group.
	 *
	 * @param int $group_id Group ID.
	 */
	public static function delete_for_group( int $group_id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			Schema::skus_table(),
			array( 'group_id' => $group_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Key SKUs for a group by canonical combo JSON string.
	 *
	 * @param int $group_id Group ID.
	 * @return array<string, array<string, mixed>>
	 */
	public static function keyed_for_group( int $group_id ): array {
		$out = array();

		foreach ( self::for_group( $group_id ) as $row ) {
			$decoded               = self::decode_combo( (string) $row['combo_json'] );
			$key                   = self::encode_combo( $decoded );
			$out[ $key ]           = $row;
			$out[ $key ]['_combo'] = $decoded;
		}

		return $out;
	}

	/**
	 * Combinations payload for a group config (selectable keys only).
	 *
	 * @param int $group_id Group ID.
	 * @return list<array<string, mixed>>
	 */
	public static function combinations_for_group( int $group_id ): array {
		$out = array();

		foreach ( self::for_group( $group_id ) as $row ) {
			$out[] = array(
				'options'       => self::decode_combo( (string) $row['combo_json'] ),
				'sku'           => (string) $row['sku'],
				'product_name'  => (string) $row['product_name'],
				'hcpcs'         => (string) $row['hcpcs'],
				'sample_amount' => (string) $row['sample_amount'],
			);
		}

		return $out;
	}

	/**
	 * Canonical JSON encoding of a combo map (sorted keys).
	 *
	 * @param array<string, string>|string $combo Combo map or JSON string.
	 */
	public static function encode_combo( array|string $combo ): string {
		if ( is_string( $combo ) ) {
			$decoded = self::decode_combo( $combo );
			$combo   = $decoded;
		}

		ksort( $combo );

		$json = wp_json_encode( $combo, JSON_UNESCAPED_SLASHES );

		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Decode combo JSON to a string→string map.
	 *
	 * @param string $json Combo JSON.
	 * @return array<string, string>
	 */
	public static function decode_combo( string $json ): array {
		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$out = array();

		foreach ( $decoded as $key => $value ) {
			if ( is_string( $key ) && ( is_string( $value ) || is_numeric( $value ) ) ) {
				$out[ $key ] = (string) $value;
			}
		}

		ksort( $out );

		return $out;
	}
}
