<?php
/**
 * Uninstall cleanup — drop sample tables and plugin options.
 *
 * Fired only when the plugin is deleted via wp-admin (not on deactivate).
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$hrh_sample_tables = array(
	$wpdb->prefix . 'hrh_sample_skus',
	$wpdb->prefix . 'hrh_sample_options',
	$wpdb->prefix . 'hrh_sample_groups',
);

foreach ( $hrh_sample_tables as $hrh_sample_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- uninstall drop; table name is prefix-controlled.
	$wpdb->query( "DROP TABLE IF EXISTS {$hrh_sample_table}" );
}

$hrh_sample_options = array(
	'hrh_sample_checkout_form_id',
	'hrh_sample_checkout_page_id',
	'hrh_sample_cart_cap',
	'hrh_sample_db_version',
	'hrh_sample_slug_page_map',
);

foreach ( $hrh_sample_options as $hrh_sample_option ) {
	delete_option( $hrh_sample_option );
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- uninstall meta cleanup by known key.
$wpdb->delete(
	$wpdb->postmeta,
	array( 'meta_key' => '_hrh_sample_group_tag' ),
	array( '%s' )
);
// phpcs:enable
