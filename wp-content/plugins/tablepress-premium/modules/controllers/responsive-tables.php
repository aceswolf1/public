<?php
/**
 * TablePress Responsive Tables.
 *
 * @package TablePress
 * @subpackage Responsive Tables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the Responsive Tables feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_Responsive_Tables {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'responsive-tables' => 'responsive-tables.css', // The RTL version will be configured in the `__construct` method.
	);

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_render_options', array( __CLASS__, 'process_table_render_options' ), 10, 2 );
		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'pass_render_options_to_js_options' ), 10, 3 );
		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'set_datatables_parameters' ), 10, 4 );
		add_filter( 'tablepress_table_html', array( __CLASS__, 'modify_table_html' ), 10, 3 );
		if ( is_admin() ) {
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_edit_screen_elements' ), 10, 2 );
		}
		if ( TablePress::$controller->use_legacy_css_loading && ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_css_files' ), 10, 0 );
		}
		// Load the RTL version of the module's CSS file, if needed.
		if ( is_rtl() ) {
			self::$css_files['responsive-tables'] = 'responsive-tables-rtl.css';
		}
	}

	/**
	 * Adds the module's table options to the table template.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $table Current table template.
	 * @return array<string, mixed> Extended table template.
	 */
	public static function add_option_to_table_template( array $table ): array {
		$table['options']['responsive'] = '';
		$table['options']['responsive_breakpoint'] = 'phone'; // 'phone', 'tablet', 'desktop', 'all'
		$table['options']['responsive_scroll_buttons'] = false;
		return $table;
	}

	/**
	 * Adds the module's parameters to the [table /] Shortcode.
	 *
	 * By using null as the default value, the table options's value will be used (if set).
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $default_atts Default Shortcode attributes.
	 * @return array<string, mixed> Extended Shortcode attributes.
	 */
	public static function add_shortcode_parameters( array $default_atts ): array {
		$default_atts['responsive'] = null;
		$default_atts['responsive_breakpoint'] = null;
		$default_atts['responsive_scroll_buttons'] = null;
		return $default_atts;
	}

	/**
	 * Sets required render options based on the module's settings.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $render_options Render Options.
	 * @param array<string, mixed> $table          Table.
	 * @return array<string, mixed> Modified Render Options.
	 */
	public static function process_table_render_options( array $render_options, array $table ): array {
		if ( '' === $render_options['responsive'] ) {
			return $render_options;
		}

		$render_options['responsive'] = strtolower( $render_options['responsive'] );
		$render_options['responsive_breakpoint'] = strtolower( $render_options['responsive_breakpoint'] );

		// Convert legacy parameter values to modern Shortcode parameters.
		if ( in_array( $render_options['responsive'], array( 'phone', 'tablet', 'desktop', 'all' ), true ) ) {
			$render_options['responsive_breakpoint'] = $render_options['responsive'];
			$render_options['responsive'] = 'flip';
		}

		// Add "Extra CSS class".
		if ( '' !== $render_options['extra_css_classes'] ) {
			$render_options['extra_css_classes'] .= ' ';
		}
		$render_options['extra_css_classes'] .= 'tablepress-responsive';

		self::maybe_enqueue_css_files();

		// Scroll mode.
		if ( 'scroll' === $render_options['responsive'] ) {
			// Horizontal Scrolling from DataTables has to be turned off.
			$render_options['datatables_scrollx'] = false;

			if ( $render_options['responsive_scroll_buttons'] ) {
				// Enqueue JS file for the scroll buttons.
				$js_url = plugins_url( 'modules/js/responsive-scroll-buttons.min.js', TABLEPRESS__FILE__ );
				wp_enqueue_script( 'tablepress-responsive-scroll-buttons', $js_url, array(), TablePress::version, true );
			}
		}

		// Flip mode.
		if ( 'flip' === $render_options['responsive'] && in_array( $render_options['responsive_breakpoint'], array( 'phone', 'tablet', 'desktop', 'all' ), true ) ) {
			// Horizontal Scrolling from DataTables has to be turned off.
			$render_options['datatables_scrollx'] = false;
			// Add "Extra CSS class".
			$render_options['extra_css_classes'] .= " tablepress-responsive-{$render_options['responsive_breakpoint']}";
		}

		// Stack mode.
		if ( 'stack' === $render_options['responsive'] && in_array( $render_options['responsive_breakpoint'], array( 'phone', 'tablet', 'desktop', 'all' ), true ) ) {
			// Horizontal Scrolling from DataTables has to be turned off.
			$render_options['datatables_scrollx'] = false;
			// Add "Extra CSS class".
			$render_options['extra_css_classes'] .= " tablepress-responsive-stack-headers tablepress-responsive-stack-{$render_options['responsive_breakpoint']}";

			// Enqueue JS file for the Stack mode.
			$js_url = plugins_url( 'modules/js/responsive-stack.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-responsive-stack', $js_url, array(), TablePress::version, true );
		}

		// Collapse and Modal modes.
		if ( in_array( $render_options['responsive'], array( 'collapse', 'modal' ), true ) ) {
			// DataTables and with that the table header must be turned on for DataTables Responsive to be usable.
			$render_options['use_datatables'] = true;
			if ( 1 > $render_options['table_head'] ) { // No 0 === comparison to allow for backward-compatible `false` value.
				$render_options['table_head'] = 1;
			}
		}

		return $render_options;
	}

	/**
	 * Passes the module's Shortcode parameters to JavaScript arguments.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $js_options     Current JS options.
	 * @param string               $table_id       Table ID.
	 * @param array<string, mixed> $render_options Render Options.
	 * @return array<string, mixed> Modified JS options.
	 */
	public static function pass_render_options_to_js_options( array $js_options, string $table_id, array $render_options ): array {
		$js_options['responsive'] = $render_options['responsive'];

		// Enqueue JS file for the Collapse and Modal modes.
		if ( in_array( $js_options['responsive'], array( 'collapse', 'modal' ), true ) ) {
			$js_url = plugins_url( 'modules/js/datatables.responsive.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-responsive', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
		}

		return $js_options;
	}

	/**
	 * Evaluates JS parameters and converts them to DataTables parameters.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $parameters DataTables parameters.
	 * @param string               $table_id   Table ID.
	 * @param string               $html_id    HTML ID of the table.
	 * @param array<string, mixed> $js_options JS options for DataTables.
	 * @return array<string, mixed> Extended DataTables parameters.
	 */
	public static function set_datatables_parameters( array $parameters, string $table_id, string $html_id, array $js_options ): array {
		if ( 'collapse' === $js_options['responsive'] ) {
			// Collapse mode.
			$parameters['responsive'] = 'responsive:true';
		} elseif ( 'modal' === $js_options['responsive'] ) {
			// Modal mode.
			$parameters['responsive'] = 'responsive:{details:{display:DataTable.Responsive.display.modal(),renderer:DataTable.Responsive.renderer.tableAll()}}';
		}

		return $parameters;
	}

	/**
	 * Possibly adds extra HTML code around the table element.
	 *
	 * @since 2.0.0
	 *
	 * @param string               $output         Table HTML code.
	 * @param array<string, mixed> $table          The table.
	 * @param array<string, mixed> $render_options Render Options.
	 * @return string Modified/extended table HTML code.
	 */
	public static function modify_table_html( string $output, array $table, array $render_options ): string {
		// Add wrapper divs and buttons for the Scroll mode, except for the block preview.
		if ( 'scroll' === $render_options['responsive'] && ! $render_options['block_preview'] ) {
			$output = <<<HTML
				<div id="{$render_options['html_id']}-scroll-wrapper" class="tablepress-scroll-wrapper">
				{$output}
				</div>
				HTML;

			if ( $render_options['responsive_scroll_buttons'] ) {
				$button_left_title = esc_attr__( 'Scroll table left', 'tablepress' );
				$button_right_title = esc_attr__( 'Scroll table right', 'tablepress' );
				$output = <<<HTML
					<div id="{$render_options['html_id']}-scroll-buttons-wrapper" class="tablepress-scroll-buttons-wrapper">
					<button class="tablepress-scroll-button tablepress-scroll-button-left" title="{$button_left_title}">❮</button>
					{$output}
					<button class="tablepress-scroll-button tablepress-scroll-button-right" title="{$button_right_title}">❯</button>
					</div>
					HTML;
			}
		}

		return $output;
	}

	/**
	 * Registers the module's "Edit" screen elements.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $data   Data for this screen.
	 * @param string               $action Action for this screen.
	 * @return array<string, mixed> Modified data for this screen.
	 */
	public static function add_edit_screen_elements( array $data, string $action ): array {
		if ( 'edit' === $action ) {
			// Add a meta box below the default meta boxes, by using the "low" priority.
			add_meta_box( 'tablepress_edit-responsive-tables', __( 'Behavior on different screen sizes (Responsiveness)', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'responsive-tables' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		} elseif ( 'options' === $action ) {
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies_default_style_customizer' ), 10, 2 );
		}
		return $data;
	}

	/**
	 * Enqueues the module's "Default Style Customizer" integration script and registers it as a dependency.
	 *
	 * @since 3.0.0
	 *
	 * @param string[] $dependencies List of the dependencies that the $name script relies on.
	 * @param string   $name         Name of the JS script, without extension.
	 * @return string[] Modified list of the dependencies that the $name script relies on.
	 */
	public static function add_script_dependencies_default_style_customizer( array $dependencies, string $name ): array {
		if ( 'default-style-customizer' === $name ) {
			$rtl = ( is_rtl() ) ? '-rtl' : '';
			TablePress_Modules_Helper::enqueue_script(
				'default-style-customizer-responsive-tables',
				array(),
				array(
					'default_style_customizer_responsive_tables_settings' => array(
						'cssUrl' => plugins_url( "modules/css/build/responsive-tables{$rtl}.css", TABLEPRESS__FILE__ ),
					),
				),
			);
			$dependencies[] = 'tablepress-default-style-customizer-responsive-tables';
		}

		return $dependencies;
	}

} // class TablePress_Module_Responsive_Tables
