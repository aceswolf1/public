<?php
/**
 * TablePress Module Trait with members and methods for all TablePress Premium Modules.
 *
 * @package TablePress
 * @subpackage Modules
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Modules trait.
 *
 * @package TablePress
 * @subpackage Modules
 * @author Tobias Bäthge
 * @since 2.0.0
 */
trait TablePress_Module {

	/**
	 * Properties for the module.
	 *
	 * @since 2.0.0
	 * @var array{slug: string, name: string, description: string, category: string, class: string, incompatible_classes: string[], minimum_plan: string, default_active: bool}
	 */
	public static array $module = array(
		'slug'                 => '',
		'name'                 => '',
		'description'          => '',
		'category'             => '',
		'class'                => '',
		'incompatible_classes' => array(),
		'minimum_plan'         => '',
		'default_active'       => false,
	);

	/**
	 * Prints the content of the module's post meta box.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public static function print_postbox_markup( array $data, array $box ): void {
		echo '<div id="tablepress-' . self::$module['slug'] . '-section"></div>';
	}

	/**
	 * Adds the module's script as a dependency for the "Edit" script, so that hooks are added before they are executed.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $dependencies List of the dependencies that the $name script relies on.
	 * @param string   $name         Name of the JS script, without extension.
	 * @return string[] Modified list of the dependencies that the $name script relies on.
	 */
	public static function add_script_dependencies( array $dependencies, string $name ): array {
		if ( 'edit' === $name ) {
			$dependencies[] = 'tablepress-' . self::$module['slug'];
		}

		return $dependencies;
	}

	/**
	 * Registers the module's JS script for the block editor.
	 *
	 * @since 2.0.0
	 */
	public static function enqueue_block_editor_js(): void {
		TablePress_Modules_Helper::enqueue_script( self::$module['slug'] . '-block' );
	}

	/**
	 * Checks if the module's CSS files should be loaded.
	 *
	 * This function is only called when a [table /] Shortcode or "TablePress Table" block is evaluated,
	 * and if the module is being used for that table, so that CSS files are only loaded when needed.
	 *
	 * If a single file name is passed, that will use the module slug as the slug in style handle.
	 * If an array of files is passed, the keys are the style slugs and the values are the file names.
	 * The first file will depend on the default TablePress CSS file, and each subsequent file will depend on the previous file.
	 *
	 * @since 3.0.1
	 *
	 * @param array<string, string> $files              The CSS files to enqueue. The keys are the style slugs and the values are the file names.
	 * @param bool                  $allow_early_return Whether to allow early return if the function has already been called.
	 */
	public static function maybe_enqueue_css_files( array $files = array(), bool $allow_early_return = true ): void {
		// Bail early if the legacy CSS loading mechanism is used, as the files will then have been enqueued already.
		if ( TablePress::$controller->use_legacy_css_loading ) {
			return;
		}

		/*
		 * Bail early if the function is called from some action hook outside of the normal rendering process.
		 * These are often used by e.g. SEO plugins that render the content in additional contexts, e.g. to get an excerpt via an output buffer.
		 * In these cases, we don't want to enqueue the CSS, as it would likely not be printed on the page.
		 */
		if ( doing_action( 'wp_head' ) || doing_action( 'wp_footer' ) ) {
			return;
		}

		// Prevent repeated execution via a static variable.
		static $css_enqueued = false;
		if ( $css_enqueued && $allow_early_return && ! doing_action( 'enqueue_block_assets' ) ) {
			return;
		}
		$css_enqueued = true;

		self::enqueue_css_files( $files );
	}

	/**
	 * Enqueues the module's frontend CSS file(s).
	 *
	 * If a single file name is passed, that will use the module slug as the slug in style handle.
	 * If an array of files is passed, the keys are the style slugs and the values are the file names.
	 * If an empty array is passed, the pre-defined CSS files for the module will be used,
	 * which happens when the function is called from the `wp_enqueue_scripts` hook, for the legacy CSS loading mechanism.
	 * The first file will depend on the default TablePress CSS file, and each subsequent file will depend on the previous file.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, string> $files The CSS files to enqueue. The keys are the style slugs and the values are the file names.
	 */
	public static function enqueue_css_files( array $files = array() ): void {
		/**
		 * Filters whether the module's frontend CSS files should be enqueued.
		 *
		 * This allows for conditionally loading these files only on desired pages.
		 *
		 * @since 2.0.0
		 *
		 * @param bool   $enqueue     Whether the CSS files for the module should be enqueued.
		 * @param string $module_slug The module's slug.
		 */
		if ( ! apply_filters( 'tablepress_module_enqueue_css_files', true, self::$module['slug'] ) ) {
			return;
		}

		// If no files were passed, use the pre-defined CSS files for the module.
		if ( empty( $files ) ) {
			$files = self::$css_files; // @phpstan-ignore staticProperty.notFound (This method is only called when that property was set.)
		}

		$dependency = 'tablepress-default';
		foreach ( $files as $slug => $file ) {
			$handle = "tablepress-{$slug}";
			$css_url = plugins_url( "modules/css/build/{$file}", TABLEPRESS__FILE__ );
			wp_enqueue_style( $handle, $css_url, array( $dependency ), TablePress::version );
			$dependency = $handle;
		}
		if ( did_action( 'wp_print_styles' ) ) {
			wp_print_styles( $dependency );
		}
	}

} // trait TablePress_Module
