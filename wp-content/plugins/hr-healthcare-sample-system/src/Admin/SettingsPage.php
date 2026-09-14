<?php
/**
 * Single settings screen with four tabs.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Admin;

use HR_Healthcare\Sample_System\Data\Groups;
use HR_Healthcare\Sample_System\Data\Importer;
use HR_Healthcare\Sample_System\Data\Resolver;
use HR_Healthcare\Sample_System\Support\ImageMap;
use HR_Healthcare\Sample_System\Support\Settings;

/**
 * Import / Configuration / Setup & Usage / Options viewer.
 */
final class SettingsPage {

	public const MENU_SLUG      = 'hrh-sample-settings';
	public const NONCE_ACTION   = 'hrh_sample_settings';
	public const NONCE_NAME     = 'hrh_sample_settings_nonce';
	public const MARKER_CLASSES = array(
		'hrh-sample-field-selections',
		'hrh-sample-field-readable',
		'hrh-sample-field-json',
	);

	/**
	 * Image map service.
	 *
	 * @var ImageMap
	 */
	private ImageMap $image_map;

	/**
	 * Flash notices for the current request.
	 *
	 * @var list<array{type:string,message:string}>
	 */
	private array $notices = array();

	/**
	 * Last import preview (pre-commit), if any.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $import_preview = null;

	/**
	 * Constructor.
	 *
	 * @param ImageMap $image_map Image map.
	 */
	public function __construct( ImageMap $image_map ) {
		$this->image_map = $image_map;
	}

	/**
	 * Register admin menu + actions.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_post' ) );
	}

	/**
	 * Add the top-level menu page.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Sample System', 'hr-healthcare-sample-system' ),
			__( 'Sample System', 'hr-healthcare-sample-system' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' ),
			'dashicons-cart',
			58
		);
	}

	/**
	 * Handle POSTed actions (config save / import preview / import commit).
	 */
	public function handle_post(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$action = isset( $_POST['hrh_sample_action'] )
			? sanitize_key( wp_unslash( (string) $_POST['hrh_sample_action'] ) )
			: '';

		switch ( $action ) {
			case 'save_config':
				$this->save_config();
				break;
			case 'import_preview':
				$this->run_import_preview();
				break;
			case 'import_commit':
				$this->run_import_commit();
				break;
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'import'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch.
		$allowed = array( 'import', 'configuration', 'setup', 'options' );

		if ( ! in_array( $tab, $allowed, true ) ) {
			$tab = 'import';
		}

		foreach ( $this->notices as $notice ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}
		?>
		<div class="wrap hrh-sample-settings">
			<h1><?php esc_html_e( 'HR Healthcare Sample System', 'hr-healthcare-sample-system' ); ?></h1>
			<nav class="nav-tab-wrapper">
				<?php
				$tabs = array(
					'import'        => __( 'Import', 'hr-healthcare-sample-system' ),
					'configuration' => __( 'Configuration', 'hr-healthcare-sample-system' ),
					'setup'         => __( 'Setup & Usage', 'hr-healthcare-sample-system' ),
					'options'       => __( 'Options viewer', 'hr-healthcare-sample-system' ),
				);
				foreach ( $tabs as $key => $label ) {
					$url   = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=' . $key );
					$class = 'nav-tab' . ( $tab === $key ? ' nav-tab-active' : '' );
					printf(
						'<a href="%1$s" class="%2$s">%3$s</a>',
						esc_url( $url ),
						esc_attr( $class ),
						esc_html( $label )
					);
				}
				?>
			</nav>
			<?php
			switch ( $tab ) {
				case 'configuration':
					$this->render_configuration();
					break;
				case 'setup':
					$this->render_setup();
					break;
				case 'options':
					$this->render_options_viewer();
					break;
				case 'import':
				default:
					$this->render_import();
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Import tab.
	 */
	private function render_import(): void {
		?>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
			<input type="hidden" name="hrh_sample_action" value="import_preview" />
			<h2><?php esc_html_e( 'Import product sheet', 'hr-healthcare-sample-system' ); ?></h2>
			<p><?php esc_html_e( 'Upload the cleaned .xlsx. The importer is an idempotent diff-sync — preview the changes before committing.', 'hr-healthcare-sample-system' ); ?></p>
			<p>
				<label for="hrh_sample_sheet"><?php esc_html_e( 'Spreadsheet (.xlsx)', 'hr-healthcare-sample-system' ); ?></label><br />
				<input type="file" name="hrh_sample_sheet" id="hrh_sample_sheet" accept=".xlsx,.csv" required />
			</p>
			<?php submit_button( __( 'Preview import', 'hr-healthcare-sample-system' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
		if ( null !== $this->import_preview ) {
			$this->render_import_preview( $this->import_preview );
		}
	}

	/**
	 * Render a pre-commit diff summary + commit form.
	 *
	 * @param array<string, mixed> $preview Preview payload.
	 */
	private function render_import_preview( array $preview ): void {
		if ( empty( $preview['ok'] ) ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( (string) ( $preview['error'] ?? 'Import failed.' ) )
			);
			return;
		}

		$groups  = $preview['groups'];
		$options = $preview['options'];
		$skus    = $preview['skus'];
		?>
		<hr />
		<h2><?php esc_html_e( 'Pre-commit summary', 'hr-healthcare-sample-system' ); ?></h2>
		<ul>
			<li><?php echo esc_html( sprintf( 'Groups — added %d, updated %d, removed %d, unchanged %d', $groups['added'], $groups['updated'], $groups['removed'], $groups['unchanged'] ) ); ?></li>
			<li><?php echo esc_html( sprintf( 'Options — added %d, updated %d, removed %d, unchanged %d', $options['added'], $options['updated'], $options['removed'], $options['unchanged'] ) ); ?></li>
			<li><?php echo esc_html( sprintf( 'SKUs — added %d, updated %d, removed %d, unchanged %d', $skus['added'], $skus['updated'], $skus['removed'], $skus['unchanged'] ) ); ?></li>
		</ul>
		<?php if ( ! empty( $preview['skipped'] ) ) : ?>
			<h3><?php esc_html_e( 'Skipped — no webpage-link', 'hr-healthcare-sample-system' ); ?></h3>
			<ul>
				<?php foreach ( $preview['skipped'] as $skip ) : ?>
					<li>
						<?php
						echo esc_html(
							sprintf(
								'Rows %d–%d, products: %s',
								(int) $skip['start_row'],
								(int) $skip['end_row'],
								implode( ', ', array_unique( $skip['products'] ?? array() ) )
							)
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php
		$token = wp_generate_password( 20, false );
		set_transient(
			'hrh_sample_import_' . $token,
			array(
				'plan' => $preview['plan'],
				'path' => (string) ( $preview['_path'] ?? '' ),
			),
			15 * MINUTE_IN_SECONDS
		);
		?>
		<form method="post">
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
			<input type="hidden" name="hrh_sample_action" value="import_commit" />
			<input type="hidden" name="hrh_sample_token" value="<?php echo esc_attr( $token ); ?>" />
			<?php submit_button( __( 'Commit import', 'hr-healthcare-sample-system' ), 'primary', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Configuration tab.
	 */
	private function render_configuration(): void {
		$form_id = Settings::checkout_form_id();
		$page_id = Settings::checkout_page_id();
		$cap     = Settings::cart_cap();
		$forms   = $this->gravity_forms_list();
		$scan    = $this->scan_form_markers( $form_id );
		?>
		<form method="post">
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
			<input type="hidden" name="hrh_sample_action" value="save_config" />

			<h2><?php esc_html_e( 'Checkout form', 'hr-healthcare-sample-system' ); ?></h2>
			<p>
				<label for="hrh_sample_checkout_form_id"><?php esc_html_e( 'Gravity Forms form', 'hr-healthcare-sample-system' ); ?></label><br />
				<select name="hrh_sample_checkout_form_id" id="hrh_sample_checkout_form_id">
					<option value="0"><?php esc_html_e( '— Select —', 'hr-healthcare-sample-system' ); ?></option>
					<?php foreach ( $forms as $id => $title ) : ?>
						<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $form_id, $id ); ?>>
							<?php echo esc_html( sprintf( '%s (ID %d)', $title, $id ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php if ( $form_id > 0 ) : ?>
				<?php if ( $scan['ok'] ) : ?>
					<div class="notice notice-success inline"><p><?php esc_html_e( 'All three marker-class fields found on this form.', 'hr-healthcare-sample-system' ); ?></p></div>
				<?php else : ?>
					<div class="notice notice-warning inline">
						<p><?php esc_html_e( 'Missing marker-class fields. Add these in the form editor (Appearance → Custom CSS Class):', 'hr-healthcare-sample-system' ); ?></p>
						<ul>
							<?php foreach ( $scan['missing'] as $class ) : ?>
								<li><code><?php echo esc_html( $class ); ?></code></li>
							<?php endforeach; ?>
						</ul>
						<p><?php esc_html_e( 'Required: a Hidden field with class hrh-sample-field-selections; two Hidden/admin-only fields with classes hrh-sample-field-readable and hrh-sample-field-json. The plugin never creates or modifies the form.', 'hr-healthcare-sample-system' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Checkout page', 'hr-healthcare-sample-system' ); ?></h2>
			<p>
				<label for="hrh_sample_checkout_page_id"><?php esc_html_e( 'Page', 'hr-healthcare-sample-system' ); ?></label><br />
				<?php
				$dropdown = wp_dropdown_pages(
					array(
						'name'              => 'hrh_sample_checkout_page_id',
						'id'                => 'hrh_sample_checkout_page_id',
						'selected'          => absint( $page_id ),
						'show_option_none'  => esc_html__( '— Select —', 'hr-healthcare-sample-system' ),
						'option_none_value' => '0',
						'echo'              => 0,
					)
				);
				echo is_string( $dropdown ) ? $dropdown : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core dropdown HTML.
				?>
			</p>
			<p class="description"><?php esc_html_e( 'Stored as a page ID so it survives renames. Drives the cart “Proceed to Checkout” button only.', 'hr-healthcare-sample-system' ); ?></p>

			<h2><?php esc_html_e( 'Cart item cap', 'hr-healthcare-sample-system' ); ?></h2>
			<p>
				<label for="hrh_sample_cart_cap"><?php esc_html_e( 'Max samples per request', 'hr-healthcare-sample-system' ); ?></label><br />
				<input type="number" min="1" max="50" name="hrh_sample_cart_cap" id="hrh_sample_cart_cap" value="<?php echo esc_attr( (string) $cap ); ?>" />
			</p>

			<?php submit_button( __( 'Save configuration', 'hr-healthcare-sample-system' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Setup & Usage tab (static wiring guide).
	 */
	private function render_setup(): void {
		?>
		<h2><?php esc_html_e( 'Modal trigger', 'hr-healthcare-sample-system' ); ?></h2>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: CSS class name wrapped in code tags */
					__( 'Add the CSS class %s to any Elementor button on a product page. JS binds via document delegation.', 'hr-healthcare-sample-system' ),
					'<code>hrh-sample-open-modal</code>'
				),
				array( 'code' => array() )
			);
			?>
		</p>

		<h2><?php esc_html_e( 'Shortcodes', 'hr-healthcare-sample-system' ); ?></h2>
		<ul>
			<li>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: shortcode */
						__( '%s — header mini-cart (icon + count + centered cart dialog).', 'hr-healthcare-sample-system' ),
						'<code>[hrh_sample_cart]</code>'
					),
					array( 'code' => array() )
				);
				?>
			</li>
			<li>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: shortcode */
						__( '%s — checkout-page order review list.', 'hr-healthcare-sample-system' ),
						'<code>[hrh_sample_cart_summary]</code>'
					),
					array( 'code' => array() )
				);
				?>
			</li>
		</ul>

		<h2><?php esc_html_e( 'Activation rule', 'hr-healthcare-sample-system' ); ?></h2>
		<p><?php esc_html_e( 'A page only loads the modal/cart machinery when it has a group tag set via the Sample System metabox. Untagged pages are inert by design.', 'hr-healthcare-sample-system' ); ?></p>

		<h2><?php esc_html_e( 'Gravity Forms markers', 'hr-healthcare-sample-system' ); ?></h2>
		<p><?php esc_html_e( 'Add these three fields to the client\'s existing checkout form. Set each field\'s Custom CSS Class under Appearance. The plugin never creates or modifies the form — it only hooks the configured form ID and locates fields by these classes.', 'hr-healthcare-sample-system' ); ?></p>
		<ol>
			<li>
				<strong><code>hrh-sample-field-selections</code></strong> —
				<?php esc_html_e( 'Hidden field. Frontend populates it with raw selections JSON [{slug, options}, …] before submit. Cleared by the pre-submission hook after the snapshot is written.', 'hr-healthcare-sample-system' ); ?>
			</li>
			<li>
				<strong><code>hrh-sample-field-readable</code></strong> —
				<?php esc_html_e( 'Hidden or admin-only field. Hook-written multi-line fulfillment summary (product name, SKU, HCPCS, selected properties, sample amount as Quantity). This is the dashboard fulfillment interface.', 'hr-healthcare-sample-system' ); ?>
			</li>
			<li>
				<strong><code>hrh-sample-field-json</code></strong> —
				<?php esc_html_e( 'Hidden or admin-only field. Hook-written durable JSON snapshot of resolved lines (source for the entries-list Samples column).', 'hr-healthcare-sample-system' ); ?>
			</li>
		</ol>
		<p><?php esc_html_e( 'After adding the fields, select the form under Configuration. The field scan confirms all three markers are present.', 'hr-healthcare-sample-system' ); ?></p>
		<?php
	}

	/**
	 * Options viewer tab (read-only).
	 */
	private function render_options_viewer(): void {
		$slug = isset( $_GET['slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['slug'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$all  = Groups::all();
		?>
		<h2><?php esc_html_e( 'Imported groups', 'hr-healthcare-sample-system' ); ?></h2>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
			<input type="hidden" name="tab" value="options" />
			<select name="slug">
				<option value=""><?php esc_html_e( '— Select a group —', 'hr-healthcare-sample-system' ); ?></option>
				<?php foreach ( $all as $group ) : ?>
					<option value="<?php echo esc_attr( (string) $group['slug'] ); ?>" <?php selected( $slug, (string) $group['slug'] ); ?>>
						<?php echo esc_html( (string) $group['slug'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'View', 'hr-healthcare-sample-system' ), 'secondary', '', false ); ?>
		</form>
		<?php
		if ( '' === $slug ) {
			return;
		}

		$config = Resolver::group_config( $slug, $this->image_map->image_url( $slug ) );

		if ( null === $config ) {
			echo '<p>' . esc_html__( 'Group not found.', 'hr-healthcare-sample-system' ) . '</p>';
			return;
		}
		?>
		<h3><?php echo esc_html( $slug ); ?></h3>
		<p>
			<strong><?php esc_html_e( 'Product line:', 'hr-healthcare-sample-system' ); ?></strong>
			<?php echo esc_html( (string) $config['product_line'] ); ?><br />
			<strong><?php esc_html_e( 'Page URL:', 'hr-healthcare-sample-system' ); ?></strong>
			<?php echo esc_html( (string) $config['page_url'] ); ?><br />
			<strong><?php esc_html_e( 'Image:', 'hr-healthcare-sample-system' ); ?></strong>
			<a href="<?php echo esc_url( (string) $config['image_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $config['image_url'] ); ?></a>
		</p>
		<p><img src="<?php echo esc_url( (string) $config['image_url'] ); ?>" alt="" style="max-width:160px;height:auto;" /></p>

		<h4><?php esc_html_e( 'Properties', 'hr-healthcare-sample-system' ); ?></h4>
		<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;overflow:auto;"><?php echo esc_html( (string) wp_json_encode( $config['properties'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>

		<h4><?php esc_html_e( 'Combinations', 'hr-healthcare-sample-system' ); ?></h4>
		<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;overflow:auto;max-height:480px;"><?php echo esc_html( (string) wp_json_encode( $config['combinations'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
		<?php
	}

	/**
	 * Save configuration fields.
	 */
	private function save_config(): void {
		// Nonce verified in handle_post().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$form_id = isset( $_POST['hrh_sample_checkout_form_id'] ) ? (int) $_POST['hrh_sample_checkout_form_id'] : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$page_id = isset( $_POST['hrh_sample_checkout_page_id'] ) ? (int) $_POST['hrh_sample_checkout_page_id'] : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$cap = isset( $_POST['hrh_sample_cart_cap'] ) ? (int) $_POST['hrh_sample_cart_cap'] : Settings::DEFAULT_CART_CAP;

		Settings::set_checkout_form_id( $form_id );
		Settings::set_checkout_page_id( $page_id );
		Settings::set_cart_cap( $cap );

		$this->notices[] = array(
			'type'    => 'success',
			'message' => __( 'Configuration saved.', 'hr-healthcare-sample-system' ),
		);
	}

	/**
	 * Handle uploaded sheet → preview.
	 */
	private function run_import_preview(): void {
		// Nonce verified in handle_post().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES['hrh_sample_sheet']['tmp_name'] ) ) {
			$this->notices[] = array(
				'type'    => 'error',
				'message' => __( 'No file uploaded.', 'hr-healthcare-sample-system' ),
			);
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$tmp = (string) $_FILES['hrh_sample_sheet']['tmp_name'];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$name = isset( $_FILES['hrh_sample_sheet']['name'] ) ? sanitize_file_name( (string) $_FILES['hrh_sample_sheet']['name'] ) : 'import.xlsx';
		$dest = trailingslashit( get_temp_dir() ) . 'hrh-sample-' . wp_generate_password( 8, false ) . '-' . $name;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- move uploaded tmp to our temp path.
		if ( ! move_uploaded_file( $tmp, $dest ) ) {
			$this->notices[] = array(
				'type'    => 'error',
				'message' => __( 'Could not store the uploaded file.', 'hr-healthcare-sample-system' ),
			);
			return;
		}

		$importer         = new Importer( $this->image_map );
		$preview          = $importer->preview( $dest );
		$preview['_path'] = $dest;

		$this->import_preview = $preview;

		if ( empty( $preview['ok'] ) ) {
			$this->notices[] = array(
				'type'    => 'error',
				'message' => (string) ( $preview['error'] ?? 'Preview failed.' ),
			);
		}
	}

	/**
	 * Commit a previously previewed plan.
	 */
	private function run_import_commit(): void {
		// Nonce verified in handle_post().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$token  = isset( $_POST['hrh_sample_token'] ) ? sanitize_key( wp_unslash( (string) $_POST['hrh_sample_token'] ) ) : '';
		$stored = '' !== $token ? get_transient( 'hrh_sample_import_' . $token ) : false;

		if ( ! is_array( $stored ) || empty( $stored['plan'] ) || empty( $stored['path'] ) ) {
			$this->notices[] = array(
				'type'    => 'error',
				'message' => __( 'Import plan missing or expired — preview again.', 'hr-healthcare-sample-system' ),
			);
			return;
		}

		$path = (string) $stored['path'];
		$plan = $stored['plan'];

		if ( ! is_readable( $path ) || ! is_array( $plan ) ) {
			$this->notices[] = array(
				'type'    => 'error',
				'message' => __( 'Import plan missing or expired — preview again.', 'hr-healthcare-sample-system' ),
			);
			return;
		}

		$importer = new Importer( $this->image_map );
		$result   = $importer->commit( $path, $plan );

		delete_transient( 'hrh_sample_import_' . $token );

		if ( ! empty( $result['committed'] ) ) {
			$this->notices[] = array(
				'type'    => 'success',
				'message' => __( 'Import committed.', 'hr-healthcare-sample-system' ),
			);
		} else {
			$this->notices[] = array(
				'type'    => 'error',
				'message' => (string) ( $result['error'] ?? 'Commit failed.' ),
			);
		}

		if ( is_readable( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * List Gravity Forms as id => title.
	 *
	 * @return array<int, string>
	 */
	private function gravity_forms_list(): array {
		if ( ! class_exists( 'GFAPI' ) ) {
			return array();
		}

		$forms = \GFAPI::get_forms( true );
		$out   = array();

		if ( ! is_array( $forms ) ) {
			return array();
		}

		foreach ( $forms as $form ) {
			$id = (int) ( $form['id'] ?? 0 );
			if ( $id > 0 ) {
				$out[ $id ] = (string) ( $form['title'] ?? ( 'Form ' . $id ) );
			}
		}

		return $out;
	}

	/**
	 * Scan a GF form for the three marker CSS classes.
	 *
	 * @param int $form_id Form ID.
	 * @return array{ok:bool,missing:list<string>,found:list<string>}
	 */
	private function scan_form_markers( int $form_id ): array {
		$found   = array();
		$missing = self::MARKER_CLASSES;

		if ( $form_id <= 0 || ! class_exists( 'GFAPI' ) ) {
			return array(
				'ok'      => false,
				'missing' => $missing,
				'found'   => $found,
			);
		}

		$form = \GFAPI::get_form( $form_id );

		if ( ! is_array( $form ) || empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return array(
				'ok'      => false,
				'missing' => $missing,
				'found'   => $found,
			);
		}

		$present = array();

		foreach ( $form['fields'] as $field ) {
			$css = '';
			if ( is_object( $field ) && isset( $field->cssClass ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GF API.
				$css = (string) $field->cssClass; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GF API.
			} elseif ( is_array( $field ) && isset( $field['cssClass'] ) ) {
				$css = (string) $field['cssClass'];
			}

			foreach ( self::MARKER_CLASSES as $marker ) {
				if ( preg_match( '/(?:^|\s)' . preg_quote( $marker, '/' ) . '(?:\s|$)/', $css ) ) {
					$present[ $marker ] = true;
				}
			}
		}

		$found   = array_keys( $present );
		$missing = array_values( array_diff( self::MARKER_CLASSES, $found ) );

		return array(
			'ok'      => array() === $missing,
			'missing' => $missing,
			'found'   => $found,
		);
	}
}
