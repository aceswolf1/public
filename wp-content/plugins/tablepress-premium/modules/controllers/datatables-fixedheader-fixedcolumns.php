<?php
/**
 * TablePress DataTables FixedHeader and FixedColumns.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables FixedHeader and FixedColumns feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_FixedHeader_FixedColumns {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-fixedheader'    => 'datatables.fixedheader.css',
		'datatables-fixedcolumns'   => 'datatables.fixedcolumns.css',
		'datatables-scroll-buttons' => 'datatables.scroll-buttons.css',
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
		add_filter( 'tablepress_datatables_command', array( __CLASS__, 'extend_datatables_command' ), 10, 6 );
		if ( is_admin() ) {
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_edit_screen_elements' ), 10, 2 );
		}
		if ( TablePress::$controller->use_legacy_css_loading && ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_css_files' ), 10, 0 );
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
		$table['options']['datatables_fixedheader'] = '';
		$table['options']['datatables_fixedheader_offsettop'] = 0;
		$table['options']['datatables_fixedcolumns'] = '';
		$table['options']['datatables_fixedcolumns_left_columns'] = 0;
		$table['options']['datatables_fixedcolumns_right_columns'] = 0;
		$table['options']['datatables_scrollx_buttons'] = false;
		return $table;
	}

	/**
	 * Adds the module's parameters to the [table /] Shortcode.
	 *
	 * By using null as the default value, the table options's value will be used (if set).
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $default_atts Default attributes for the TablePress [table /] Shortcode.
	 * @return array<string, mixed> Extended attributes for the Shortcode.
	 */
	public static function add_shortcode_parameters( array $default_atts ): array {
		$default_atts['datatables_fixedheader'] = null;
		$default_atts['datatables_fixedheader_offsettop'] = null;
		$default_atts['datatables_fixedcolumns'] = null;
		$default_atts['datatables_fixedcolumns_left_columns'] = null;
		$default_atts['datatables_fixedcolumns_right_columns'] = null;
		$default_atts['datatables_scrollx_buttons'] = null;
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
		$render_options['datatables_fixedcolumns'] = strtolower( $render_options['datatables_fixedcolumns'] );
		$render_options['datatables_fixedcolumns_left_columns'] = absint( $render_options['datatables_fixedcolumns_left_columns'] );
		$render_options['datatables_fixedcolumns_right_columns'] = absint( $render_options['datatables_fixedcolumns_right_columns'] );

		if ( $render_options['use_datatables']
			&& 0 < $render_options['table_head']
			&& (
				'' !== $render_options['datatables_fixedcolumns']
				|| $render_options['datatables_fixedcolumns_left_columns'] > 0
				|| $render_options['datatables_fixedcolumns_right_columns'] > 0
			)
		) {
			// Potentially unset the responsiveness mode if FixedColumns is used with this table.
			if ( isset( $render_options['responsive'] ) && ! in_array( $render_options['responsive'], array( 'collapse', 'modal' ), true ) ) {
				$render_options['responsive'] = '';
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
		$js_options['datatables_fixedheader'] = strtolower( $render_options['datatables_fixedheader'] );
		$js_options['datatables_fixedheader_offsettop'] = absint( $render_options['datatables_fixedheader_offsettop'] );

		// Change parameters and register files if the header or footer are fixed.
		if ( '' !== $js_options['datatables_fixedheader'] ) {
			// Convert the "both" shortcut to "top" and "bottom".
			$js_options['datatables_fixedheader'] = str_replace( 'both', 'top,bottom', $js_options['datatables_fixedheader'] );

			self::maybe_enqueue_css_files(
				array( 'datatables-fixedheader' => 'datatables.fixedheader.css' ), // Only enqueue a specific file out of the module's CSS files.
				false, // Don't return early if the function has already been called, to allow `datatables-fixedcolumns` or `datatables-scroll-buttons` to be enqueued.
			);

			// Register the JS files.
			$js_url = plugins_url( 'modules/js/datatables.fixedheader.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-fixedheader', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
		}

		// Sanitization of these options happens in `process_table_render_options()`.
		$js_options['datatables_fixedcolumns'] = $render_options['datatables_fixedcolumns'];
		$js_options['datatables_fixedcolumns_left_columns'] = $render_options['datatables_fixedcolumns_left_columns'];
		$js_options['datatables_fixedcolumns_right_columns'] = $render_options['datatables_fixedcolumns_right_columns'];

		/*
		 * Convert shortcut parameter value to detailed parameter values.
		 * The conversion is necessary for BC reasons, as previous versions supported a value like "right,left".
		 */
		if ( '' !== $js_options['datatables_fixedcolumns'] && 0 === $js_options['datatables_fixedcolumns_left_columns'] && 0 === $js_options['datatables_fixedcolumns_right_columns'] ) {
			// Convert the "both" shortcude to "left" and "right".
			$js_options['datatables_fixedcolumns'] = str_replace( 'both', 'left,right', $js_options['datatables_fixedcolumns'] );
			$fixedcolumns = explode( ',', $js_options['datatables_fixedcolumns'] );
			$fixedcolumns = array_map( 'trim', $fixedcolumns );
			foreach ( $fixedcolumns as $column ) {
				if ( 'left' === $column ) {
					$js_options['datatables_fixedcolumns_left_columns'] = 1;
				} elseif ( 'right' === $column ) {
					$js_options['datatables_fixedcolumns_right_columns'] = 1;
				}
			}
		}

		// Change parameters and register files if at least one column is fixed.
		if ( $js_options['datatables_fixedcolumns_left_columns'] > 0 || $js_options['datatables_fixedcolumns_right_columns'] > 0 ) {
			// Horizontal Scrolling is mandatory for the FixedColumns functionality.
			$js_options['datatables_scrollx'] = true;

			self::maybe_enqueue_css_files(
				array( 'datatables-fixedcolumns' => 'datatables.fixedcolumns.css' ), // Only enqueue a specific file out of the module's CSS files.
				false, // Don't return early if the function has already been called, to allow `datatables-fixedheader` or `datatables-scroll-buttons` to be enqueued.
			);

			// Register the JS files.
			$js_url = plugins_url( 'modules/js/datatables.fixedcolumns.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-fixedcolumns', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
		}

		/*
		 * If horizontal scrolling (either separately or due to a fixed column) is enabled while the footer row is fixed,
		 * vertical scrolling must also be used, to prevent visual glitches.
		 */
		if ( $js_options['datatables_scrollx'] && ! $js_options['datatables_scrolly'] && str_contains( $js_options['datatables_fixedheader'], 'bottom' ) ) {
			$js_options['datatables_scrolly'] = '400px'; // 400px should be a reasonable default height for the scrolling container.
		}

		$js_options['datatables_scrollx_buttons'] = $render_options['datatables_scrollx_buttons'];

		if ( $js_options['datatables_scrollx_buttons'] && $js_options['datatables_scrollx'] ) {
			self::maybe_enqueue_css_files(
				array( 'datatables-scroll-buttons' => 'datatables.scroll-buttons.css' ), // Only enqueue a specific file out of the module's CSS files.
				false, // Don't return early if the function has already been called, to allow `datatables-fixedheader` or `datatables-fixedcolumns` to be enqueued.
			);

			$js_url = plugins_url( 'modules/js/datatables-scroll-buttons.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-scroll-buttons', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
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
		if ( '' !== $js_options['datatables_fixedheader'] ) {
			/*
			 * Construct the DataTables FixedHeader config parameter.
			 * We use str_contains() instead of string comparison for BC reasons, as previous versions supported a value like "top,left,right,bottom".
			 */
			$parameters['fixedHeader'] = array();
			// The header only needs to be set if changing the default of true (i.e. if it's not in the Shortcode parameter).
			if ( ! str_contains( $js_options['datatables_fixedheader'], 'top' ) ) {
				$parameters['fixedHeader'][] = 'header:false';
			}
			// The footer only needs to be set if changing the default of false (i.e. if it's in the Shortcode parameter).
			if ( str_contains( $js_options['datatables_fixedheader'], 'bottom' ) ) {
				$parameters['fixedHeader'][] = 'footer:true';
			}
			// Possibly add an offset to the header.
			if ( 0 !== $js_options['datatables_fixedheader_offsettop'] ) {
				$parameters['fixedHeader'][] = 'headerOffset:' . absint( $js_options['datatables_fixedheader_offsettop'] );
			}
			$parameters['fixedHeader'] = 'fixedHeader:{' . implode( ',', $parameters['fixedHeader'] ) . '}';
		}

		if ( 0 !== $js_options['datatables_fixedcolumns_left_columns'] || 0 !== $js_options['datatables_fixedcolumns_right_columns'] ) {
			// Construct the DataTables FixedColumns config parameter.
			$parameters['fixedColumns'] = array();
			// The number of fixed columns on the left only needs to be set if changing the default of 1.
			if ( 1 !== $js_options['datatables_fixedcolumns_left_columns'] ) {
				$parameters['fixedColumns'][] = 'start:' . absint( $js_options['datatables_fixedcolumns_left_columns'] );
			}
			// The number of fixed columns on the right only needs to be set if changing the default of 0.
			if ( 0 !== $js_options['datatables_fixedcolumns_right_columns'] ) {
				$parameters['fixedColumns'][] = 'end:' . absint( $js_options['datatables_fixedcolumns_right_columns'] );
			}
			$parameters['fixedColumns'] = 'fixedColumns:{' . implode( ',', $parameters['fixedColumns'] ) . '}';
		}

		return $parameters;
	}

	/**
	 * Extends the DataTables command with extra commands for this table instance.
	 *
	 * @since 3.0.0
	 *
	 * @param string               $command    The JS command for the DataTables JS library.
	 * @param string               $html_id    The ID of the table HTML element.
	 * @param string               $parameters The parameters for the DataTables JS library.
	 * @param string               $table_id   The current table ID.
	 * @param array<string, mixed> $js_options The options for the JS library.
	 * @param string               $name       The name of the DataTable instance.
	 * @return string Modified JS command for the DataTables JS library.
	 */
	public static function extend_datatables_command( string $command, string $html_id, string $parameters, string $table_id, array $js_options, string $name ): string {
		if ( $js_options['datatables_scrollx_buttons'] && $js_options['datatables_scrollx'] ) {
			$button_left_title = esc_js( __( 'Scroll table left', 'tablepress' ) );
			$button_right_title = esc_js( __( 'Scroll table right', 'tablepress' ) );

			/*
			 * Use a minified version of this callback:
			 *
			 * function () {
			 *    const addScrollButtons = () => DataTable.addScrollButtons( this, '{$button_left_title}', '{$button_right_title}' );
			 *    window.addEventListener( 'resize', DataTable.util.debounce( addScrollButtons, 50 ) );
			 *    addScrollButtons();
			 * }
			 */
			$command .= "\n{$name}.ready(function(){const r=()=>DataTable.addScrollButtons(this,'{$button_left_title}','{$button_right_title}');window.addEventListener('resize',DataTable.util.debounce(r,50));r();});";
		}

		return $command;
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
			add_meta_box( 'tablepress_edit-datatables-fixedheader-fixedcolumns', __( 'Fixed Rows and Fixed Columns', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_style( 'datatables-fixedheader-fixedcolumns', array( 'tablepress-modules-common' ) );
			TablePress_Modules_Helper::enqueue_script( 'datatables-fixedheader-fixedcolumns' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_FixedHeader_FixedColumns
