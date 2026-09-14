<?php
/**
 * Query + write helpers for hrh_sample_options.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Data;

/**
 * Options table access. Stable key = (group_id, axis, value).
 */
final class Options {

	/**
	 * All option rows for a group, ordered by prop_sort then value_sort.
	 *
	 * @param int $group_id Group ID.
	 * @return list<array<string, mixed>>
	 */
	public static function for_group( int $group_id ): array {
		global $wpdb;

		$table = Schema::options_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Schema.
				"SELECT * FROM {$table} WHERE group_id = %d ORDER BY prop_sort ASC, value_sort ASC",
				$group_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Insert an option row. Returns new ID or 0.
	 *
	 * Expected keys: group_id, axis, label, value, value_label,
	 * selectable, prop_sort, value_sort.
	 *
	 * @param array<string, mixed> $data Row data.
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		$wpdb->insert(
			Schema::options_table(),
			array(
				'group_id'    => (int) $data['group_id'],
				'axis'        => $data['axis'],
				'label'       => $data['label'],
				'value'       => $data['value'],
				'value_label' => $data['value_label'],
				'selectable'  => (int) (bool) $data['selectable'],
				'prop_sort'   => (int) $data['prop_sort'],
				'value_sort'  => (int) $data['value_sort'],
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an option row by ID.
	 *
	 * @param int                  $id   Option ID.
	 * @param array<string, mixed> $data Fields to update.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$allowed = array( 'label', 'value_label', 'selectable', 'prop_sort', 'value_sort', 'axis', 'value' );
		$fields  = array();
		$format  = array();

		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$fields[ $key ] = $data[ $key ];
			$format[]       = in_array( $key, array( 'selectable', 'prop_sort', 'value_sort' ), true ) ? '%d' : '%s';
		}

		if ( array() === $fields ) {
			return false;
		}

		$result = $wpdb->update(
			Schema::options_table(),
			$fields,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete an option by ID.
	 *
	 * @param int $id Option ID.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			Schema::options_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete every option row for a group.
	 *
	 * @param int $group_id Group ID.
	 */
	public static function delete_for_group( int $group_id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			Schema::options_table(),
			array( 'group_id' => $group_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Key options for a group as "axis\0value" → row.
	 *
	 * @param int $group_id Group ID.
	 * @return array<string, array<string, mixed>>
	 */
	public static function keyed_for_group( int $group_id ): array {
		$out = array();

		foreach ( self::for_group( $group_id ) as $row ) {
			$key         = self::stable_key( (string) $row['axis'], (string) $row['value'] );
			$out[ $key ] = $row;
		}

		return $out;
	}

	/**
	 * Stable key for an option within a group.
	 *
	 * @param string $axis  Property machine key.
	 * @param string $value Value machine key.
	 */
	public static function stable_key( string $axis, string $value ): string {
		return $axis . "\0" . $value;
	}

	/**
	 * Assemble the ordered `properties` list for a group config payload.
	 *
	 * Selectable axes → `values[]`; fixed axes → single `value`.
	 *
	 * @param int $group_id Group ID.
	 * @return list<array<string, mixed>>
	 */
	public static function properties_for_group( int $group_id ): array {
		$rows_by_axis = array();
		$order        = array();

		foreach ( self::for_group( $group_id ) as $row ) {
			$axis = (string) $row['axis'];

			if ( ! isset( $rows_by_axis[ $axis ] ) ) {
				$rows_by_axis[ $axis ] = array();
				$order[]               = $axis;
			}

			$rows_by_axis[ $axis ][] = $row;
		}

		$properties = array();

		foreach ( $order as $axis ) {
			$axis_rows  = $rows_by_axis[ $axis ];
			$first      = $axis_rows[0];
			$selectable = 1 === (int) $first['selectable'];

			$property = array(
				'key'        => $axis,
				'label'      => (string) $first['label'],
				'selectable' => $selectable,
			);

			if ( $selectable ) {
				$values = array();

				foreach ( $axis_rows as $row ) {
					$values[] = array(
						'key'   => (string) $row['value'],
						'label' => (string) $row['value_label'],
					);
				}

				$property['values'] = $values;
			} else {
				$property['value'] = array(
					'key'   => (string) $first['value'],
					'label' => (string) $first['value_label'],
				);
			}

			$properties[] = $property;
		}

		return $properties;
	}
}
