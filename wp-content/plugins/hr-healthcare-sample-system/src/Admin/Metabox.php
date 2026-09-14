<?php
/**
 * Native page metabox for `_hrh_sample_group_tag`.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Admin;

use HR_Healthcare\Sample_System\Data\Groups;
use HR_Healthcare\Sample_System\Support\ImageMap;

/**
 * Dropdown of imported group slugs on the Page editor.
 */
final class Metabox {

	public const NONCE_ACTION = 'hrh_sample_save_group_tag';
	public const NONCE_NAME   = 'hrh_sample_group_tag_nonce';

	/**
	 * Register metabox hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Add the metabox to pages.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'hrh_sample_group_tag',
			__( 'Sample System — Group Tag', 'hr-healthcare-sample-system' ),
			array( $this, 'render' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render the select control.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render( $post ): void {
		$current = get_post_meta( (int) $post->ID, ImageMap::META_KEY, true );
		$current = is_string( $current ) ? $current : '';
		$groups  = Groups::all();

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<p>
			<label for="hrh_sample_group_tag_select">
				<?php esc_html_e( 'Product group', 'hr-healthcare-sample-system' ); ?>
			</label>
		</p>
		<select name="hrh_sample_group_tag" id="hrh_sample_group_tag_select" style="width:100%">
			<option value=""><?php esc_html_e( '— None —', 'hr-healthcare-sample-system' ); ?></option>
			<?php foreach ( $groups as $group ) : ?>
				<?php
				$slug  = (string) $group['slug'];
				$label = $slug;
				$line  = (string) ( $group['product_line'] ?? '' );
				if ( '' !== $line ) {
					$label = sprintf( '%s (%s)', $slug, $line );
				}
				?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Tagging a page activates the sample modal on that page. Choices come from the imported Excel groups.', 'hr-healthcare-sample-system' ); ?>
		</p>
		<?php
	}

	/**
	 * Persist the meta value.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save( int $post_id, $post ): void {
		unset( $post );

		if ( ! isset( $_POST[ self::NONCE_NAME ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['hrh_sample_group_tag'] )
			? sanitize_title( wp_unslash( (string) $_POST['hrh_sample_group_tag'] ) )
			: '';

		if ( '' === $raw ) {
			delete_post_meta( $post_id, ImageMap::META_KEY );
			return;
		}

		update_post_meta( $post_id, ImageMap::META_KEY, $raw );
	}
}
