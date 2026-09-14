<?php
/**
 * Query + write helpers for hrh_sample_groups.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Data;

/**
 * Groups table access. Identity key = `slug`.
 */
final class Groups {

	/**
	 * Fetch every group row, ordered by slug.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all(): array {
		global $wpdb;

		$table = Schema::groups_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Schema.
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY slug ASC", ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch a group by slug.
	 *
	 * @param string $slug Group identity.
	 * @return array<string, mixed>|null
	 */
	public static function find_by_slug( string $slug ): ?array {
		global $wpdb;

		$table = Schema::groups_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Schema.
				"SELECT * FROM {$table} WHERE slug = %s LIMIT 1",
				$slug
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Fetch a group by primary key.
	 *
	 * @param int $id Group ID.
	 * @return array<string, mixed>|null
	 */
	public static function find( int $id ): ?array {
		global $wpdb;

		$table = Schema::groups_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Schema.
				"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
				$id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Insert a group. Returns the new ID, or 0 on failure.
	 *
	 * @param array{slug:string,page_url:string,product_line?:string} $data Row data.
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		$wpdb->insert(
			Schema::groups_table(),
			array(
				'slug'         => $data['slug'],
				'page_url'     => $data['page_url'],
				'product_line' => $data['product_line'] ?? '',
				'updated_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update non-key fields for a group by ID.
	 *
	 * @param int                                                       $id   Group ID.
	 * @param array{page_url?:string,product_line?:string,slug?:string} $data Fields to update.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$fields = array( 'updated_at' => current_time( 'mysql', true ) );
		$format = array( '%s' );

		foreach ( array( 'slug', 'page_url', 'product_line' ) as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$fields[ $key ] = $data[ $key ];
				$format[]       = '%s';
			}
		}

		$result = $wpdb->update(
			Schema::groups_table(),
			$fields,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a group by ID.
	 *
	 * @param int $id Group ID.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			Schema::groups_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Map of slug → row for diff-sync.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function keyed_by_slug(): array {
		$out = array();

		foreach ( self::all() as $row ) {
			$out[ (string) $row['slug'] ] = $row;
		}

		return $out;
	}
}
