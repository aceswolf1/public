<?php
/**
 * Table names and create/upgrade SQL for the sample system.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Data;

/**
 * Owns the three `$wpdb->prefix` tables. All SQL creation goes through
 * {@see create()} so activation and future upgrades stay in one place.
 */
final class Schema {

	/**
	 * Schema version stamped into the `hrh_sample_db_version` option.
	 */
	public const DB_VERSION = '1.0.0';

	/**
	 * Option key for the stamped schema version.
	 */
	public const DB_VERSION_OPTION = 'hrh_sample_db_version';

	/**
	 * Groups table name (with `$wpdb->prefix`).
	 */
	public static function groups_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'hrh_sample_groups';
	}

	/**
	 * Options table name (with `$wpdb->prefix`).
	 */
	public static function options_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'hrh_sample_options';
	}

	/**
	 * SKUs table name (with `$wpdb->prefix`).
	 */
	public static function skus_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'hrh_sample_skus';
	}

	/**
	 * Create or upgrade all tables via dbDelta and stamp the version.
	 */
	public static function create(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$groups  = self::groups_table();
		$options = self::options_table();
		$skus    = self::skus_table();

		$sql = array();

		$sql[] = "CREATE TABLE {$groups} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(191) NOT NULL,
			page_url text NOT NULL,
			product_line varchar(191) NOT NULL DEFAULT '',
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$charset};";

		$sql[] = "CREATE TABLE {$options} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			axis varchar(64) NOT NULL,
			label varchar(191) NOT NULL DEFAULT '',
			value varchar(191) NOT NULL,
			value_label varchar(191) NOT NULL DEFAULT '',
			selectable tinyint(1) NOT NULL DEFAULT 1,
			prop_sort int(11) NOT NULL DEFAULT 0,
			value_sort int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY group_axis_value (group_id,axis,value),
			KEY group_prop_sort (group_id,prop_sort)
		) {$charset};";

		$sql[] = "CREATE TABLE {$skus} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			sku varchar(64) NOT NULL,
			combo_json longtext NOT NULL,
			product_name varchar(255) NOT NULL DEFAULT '',
			hcpcs varchar(64) NOT NULL DEFAULT '',
			sample_amount varchar(64) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY group_id (group_id),
			KEY sku (sku)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}
}
