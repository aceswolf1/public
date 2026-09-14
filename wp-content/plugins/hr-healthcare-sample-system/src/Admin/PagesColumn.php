<?php
/**
 * "Sample Group" column on the Pages list table (visibility of which pages are tagged).
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Admin;

use HR_Healthcare\Sample_System\Support\ImageMap;

/**
 * Read-only column showing each page's group tag on edit.php?post_type=page.
 */
final class PagesColumn {

	private const COLUMN = 'hrh_sample_group';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'manage_pages_columns', array( $this, 'add_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Add the column just before the Date column.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_column( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			if ( 'date' === $key ) {
				$out[ self::COLUMN ] = __( 'Sample Group', 'hr-healthcare-sample-system' );
			}
			$out[ $key ] = $label;
		}
		if ( ! isset( $out[ self::COLUMN ] ) ) {
			$out[ self::COLUMN ] = __( 'Sample Group', 'hr-healthcare-sample-system' );
		}
		return $out;
	}

	/**
	 * Render the tag for a page row.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Page ID.
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( self::COLUMN !== $column ) {
			return;
		}

		$slug = (string) get_post_meta( $post_id, ImageMap::META_KEY, true );

		if ( '' === $slug ) {
			printf(
				'<span aria-hidden="true">—</span><span class="screen-reader-text">%s</span>',
				esc_html__( 'Not tagged', 'hr-healthcare-sample-system' )
			);
			return;
		}

		echo '<code>' . esc_html( $slug ) . '</code>';
	}
}
