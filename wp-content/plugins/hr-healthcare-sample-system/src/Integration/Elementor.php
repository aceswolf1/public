<?php
/**
 * Elementor integration — set the group tag from Elementor's Page Settings.
 *
 * Products on this site are Elementor-built pages, so the classic WP metabox
 * (Admin\Metabox) is not visible inside the Elementor editor and Elementor's
 * AJAX save does not submit it. This adds a "Sample Group" control to
 * Elementor's Page Settings panel and syncs its value into the authoritative
 * `_hrh_sample_group_tag` post meta on save — the same meta every read path
 * (frontend detect, ImageMap, importer) already uses.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Integration;

use HR_Healthcare\Sample_System\Data\Groups;
use HR_Healthcare\Sample_System\Support\ImageMap;

/**
 * Bridges the group tag into Elementor's document (page) settings.
 */
final class Elementor {

	private const CONTROL_ID = 'hrh_sample_group_tag';

	/**
	 * Image map (rebuilt on save so featured-image resolution stays fresh).
	 *
	 * @var ImageMap
	 */
	private ImageMap $image_map;

	/**
	 * Constructor.
	 *
	 * @param ImageMap $image_map Image map service.
	 */
	public function __construct( ImageMap $image_map ) {
		$this->image_map = $image_map;
	}

	/**
	 * Register hooks.
	 *
	 * Hooks are always added; the callbacks only ever fire when Elementor
	 * itself triggers them, so this is a no-op when Elementor is inactive
	 * (and avoids plugins_loaded ordering issues with `elementor/loaded`).
	 */
	public function register(): void {
		add_action( 'elementor/documents/register_controls', array( $this, 'register_controls' ) );
		add_action( 'elementor/document/after_save', array( $this, 'sync_on_save' ), 10, 2 );
	}

	/**
	 * Add the "Sample Group" control to a page document's Settings tab.
	 *
	 * @param mixed $document Elementor document instance.
	 */
	public function register_controls( $document ): void {
		if ( ! $document instanceof \Elementor\Core\DocumentTypes\PageBase
			|| ! $document::get_property( 'has_elements' ) ) {
			return;
		}

		$post = $document->get_post();
		if ( ! $post || 'page' !== $post->post_type ) {
			return;
		}

		$options = array( '' => __( '— None —', 'hr-healthcare-sample-system' ) );
		foreach ( Groups::all() as $group ) {
			$slug = (string) ( $group['slug'] ?? '' );
			if ( '' === $slug ) {
				continue;
			}
			$line             = (string) ( $group['product_line'] ?? '' );
			$options[ $slug ] = '' !== $line ? sprintf( '%s (%s)', $slug, $line ) : $slug;
		}

		$current = (string) get_post_meta( (int) $post->ID, ImageMap::META_KEY, true );

		$document->start_controls_section(
			'hrh_sample_section',
			array(
				'label' => __( 'Sample System', 'hr-healthcare-sample-system' ),
				'tab'   => \Elementor\Controls_Manager::TAB_SETTINGS,
			)
		);

		$document->add_control(
			self::CONTROL_ID,
			array(
				'label'       => __( 'Sample Group', 'hr-healthcare-sample-system' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => false,
				'label_block' => true,
				'options'     => $options,
				'default'     => $current,
				'description' => __( 'Tagging this page activates the sample request modal/cart on it. Choices come from the imported Excel groups.', 'hr-healthcare-sample-system' ),
			)
		);

		$document->end_controls_section();
	}

	/**
	 * Persist the control value into `_hrh_sample_group_tag` after an Elementor save.
	 *
	 * @param mixed $document Elementor document instance.
	 * @param mixed $data     Save payload (unused).
	 */
	public function sync_on_save( $document, $data = array() ): void {
		unset( $data );

		if ( ! is_object( $document ) || ! method_exists( $document, 'get_main_id' ) ) {
			return;
		}

		$post_id = (int) $document->get_main_id();
		if ( $post_id <= 0 || 'page' !== get_post_type( $post_id ) ) {
			return;
		}

		$raw  = method_exists( $document, 'get_settings' ) ? $document->get_settings( self::CONTROL_ID ) : '';
		$slug = sanitize_title( (string) $raw );

		if ( '' === $slug ) {
			delete_post_meta( $post_id, ImageMap::META_KEY );
		} else {
			update_post_meta( $post_id, ImageMap::META_KEY, $slug );
		}

		// Keep the slug -> page_id map fresh for image resolution.
		$this->image_map->rebuild();
	}
}
