<?php
/**
 * Cached slug → page_id map + featured-image resolve.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Support;

/**
 * Rebuilds on importer run and on `save_post`. Resolves each group's
 * featured image for the group-config payload; falls back to the bundled
 * placeholder — never a broken image.
 */
final class ImageMap {

	/**
	 * Option key for the slug→page_id map.
	 */
	public const OPTION_KEY = 'hrh_sample_slug_page_map';

	/**
	 * Meta key that tags a page with a group slug.
	 */
	public const META_KEY = '_hrh_sample_group_tag';

	/**
	 * Register hooks (save_post rebuild).
	 */
	public function register(): void {
		add_action( 'save_post_page', array( $this, 'on_save_post' ), 20, 2 );
		add_action( 'deleted_post', array( $this, 'on_deleted_post' ), 20, 1 );
	}

	/**
	 * Rebuild the map after a page is saved.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function on_save_post( int $post_id, $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		unset( $post );
		$this->rebuild();
	}

	/**
	 * Rebuild after a page is deleted.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_deleted_post( int $post_id ): void {
		unset( $post_id );
		$this->rebuild();
	}

	/**
	 * Scan all pages for `_hrh_sample_group_tag` and cache slug → page_id.
	 *
	 * @return array<string, int>
	 */
	public function rebuild(): array {
		$query = new \WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- rebuild is infrequent (import / save_post).
					array(
						'key'     => self::META_KEY,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$map = array();

		foreach ( $query->posts as $page_id ) {
			$page_id = (int) $page_id;
			$slug    = get_post_meta( $page_id, self::META_KEY, true );

			if ( ! is_string( $slug ) || '' === $slug ) {
				continue;
			}

			$map[ $slug ] = $page_id;
		}

		wp_reset_postdata();

		update_option( self::OPTION_KEY, $map, false );

		return $map;
	}

	/**
	 * Current slug → page_id map (from option; rebuilds if missing).
	 *
	 * @return array<string, int>
	 */
	public function map(): array {
		$stored = get_option( self::OPTION_KEY, null );

		if ( ! is_array( $stored ) ) {
			return $this->rebuild();
		}

		$out = array();

		foreach ( $stored as $slug => $page_id ) {
			if ( is_string( $slug ) && is_numeric( $page_id ) ) {
				$out[ $slug ] = (int) $page_id;
			}
		}

		return $out;
	}

	/**
	 * Resolve the featured-image URL for a group slug.
	 *
	 * @param string $slug Group identity.
	 * @return string Absolute URL (featured image or placeholder).
	 */
	public function image_url( string $slug ): string {
		$map     = $this->map();
		$page_id = $map[ $slug ] ?? 0;

		if ( $page_id > 0 ) {
			$url = get_the_post_thumbnail_url( $page_id, 'full' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return $this->placeholder_url();
	}

	/**
	 * Bundled neutral placeholder product image.
	 */
	public function placeholder_url(): string {
		return HRH_SAMPLE_URL . 'assets/src/images/product-placeholder.png';
	}
}
