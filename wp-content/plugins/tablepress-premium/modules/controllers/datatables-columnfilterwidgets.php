<?php
/**
 * TablePress DataTables ColumnFilterWidgets.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables ColumnFilterWidgets feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_ColumnFilterWidgets {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-columnfilterwidgets' => 'datatables.columnfilterwidgets.css',
	);

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'pass_render_options_to_js_options' ), 10, 3 );
		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'set_datatables_parameters' ), 10, 4 );
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
		$table['options']['datatables_columnfilterwidgets'] = false;
		$table['options']['datatables_columnfilterwidgets_columns'] = '';
		$table['options']['datatables_columnfilterwidgets_exclude_columns'] = '';
		$table['options']['datatables_columnfilterwidgets_separator'] = '';
		$table['options']['datatables_columnfilterwidgets_max_selections'] = '';
		$table['options']['datatables_columnfilterwidgets_group_terms'] = false;
		// Add the "Column Filter Dropdowns" feature to the Table Layout module, while keeping previously added features.
		$table['options']['datatables_layout'] = array_merge_recursive(
			$table['options']['datatables_layout'] ?? array(),
			array(
				'top' => array( 'columnFilterWidgets' ),
			),
		);
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
		$default_atts['datatables_columnfilterwidgets'] = null;
		$default_atts['datatables_columnfilterwidgets_columns'] = null;
		$default_atts['datatables_columnfilterwidgets_exclude_columns'] = null;
		$default_atts['datatables_columnfilterwidgets_separator'] = null;
		$default_atts['datatables_columnfilterwidgets_max_selections'] = null;
		$default_atts['datatables_columnfilterwidgets_group_terms'] = null;
		return $default_atts;
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
		$js_options['datatables_columnfilterwidgets'] = $render_options['datatables_columnfilterwidgets'];
		$js_options['datatables_columnfilterwidgets_columns'] = $render_options['datatables_columnfilterwidgets_columns'];
		$js_options['datatables_columnfilterwidgets_exclude_columns'] = $render_options['datatables_columnfilterwidgets_exclude_columns'];
		$js_options['datatables_columnfilterwidgets_separator'] = $render_options['datatables_columnfilterwidgets_separator'];
		$js_options['datatables_columnfilterwidgets_max_selections'] = $render_options['datatables_columnfilterwidgets_max_selections'];
		$js_options['datatables_columnfilterwidgets_group_terms'] = $render_options['datatables_columnfilterwidgets_group_terms'];

		// Column Filter Dropdowns is not supported with Server-side Processing.
		if ( isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] ) {
			$js_options['datatables_columnfilterwidgets'] = false;
		}

		if ( false !== $js_options['datatables_columnfilterwidgets'] ) {
			self::maybe_enqueue_css_files();

			$js_url = plugins_url( 'modules/js/datatables.columnfilterwidgets.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-columnfilterwidgets', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );

			// Add the "Column Filter Dropdowns" feature to the available features of the Table Layout module.
			$js_options['datatables_layout_active_features'][] = 'columnFilterWidgets';
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
		if ( ! $js_options['datatables_columnfilterwidgets'] ) {
			return $parameters;
		}

		$columnfilterwidgets_parameters = array();

		$columns = trim( $js_options['datatables_columnfilterwidgets_columns'] );
		if ( '' !== $columns ) {
			// We have a list of columns (possibly with ranges in it).
			$columns_ranges = explode( ',', $columns );
			$columns = array();
			foreach ( $columns_ranges as $value ) {
				$range_dash = strpos( $value, '-' );
				if ( false === $range_dash ) {
					// Parse single letters.
					$value = trim( $value );
					if ( ! is_numeric( $value ) ) {
						$value = TablePress::letter_to_number( $value );
					}
					$columns[] = $value - 1; // Convert column number to 0-based column index.
				} else {
					// Support for ranges like 3-6 or A-BA.
					$start = trim( substr( $value, 0, $range_dash ) );
					if ( ! is_numeric( $start ) ) {
						$start = TablePress::letter_to_number( $start );
					}
					$end = trim( substr( $value, $range_dash + 1 ) );
					if ( ! is_numeric( $end ) ) {
						$end = TablePress::letter_to_number( $end );
					}
					$range = range( $start - 1, $end - 1 ); // Convert column number to 0-based column index.
					$columns = array_merge( $columns, $range );
				}
			}
			$columns = implode( ',', $columns );

			if ( '' !== $columns ) {
				$columnfilterwidgets_parameters['columns'] = "columns:[{$columns}]";
			}
		}

		$excluded_columns = trim( $js_options['datatables_columnfilterwidgets_exclude_columns'] );
		if ( '' !== $excluded_columns ) {
			// We have a list of columns (possibly with ranges in it).
			$excluded_columns = explode( ',', $excluded_columns );
			// Support for ranges like 3-6 or A-BA.
			$range_cells = array();
			foreach ( $excluded_columns as $key => $value ) {
				$range_dash = strpos( $value, '-' );
				if ( false !== $range_dash ) {
					unset( $excluded_columns[ $key ] );
					$start = trim( substr( $value, 0, $range_dash ) );
					if ( ! is_numeric( $start ) ) {
						$start = TablePress::letter_to_number( $start );
					}
					$end = trim( substr( $value, $range_dash + 1 ) );
					if ( ! is_numeric( $end ) ) {
						$end = TablePress::letter_to_number( $end );
					}
					$current_range = range( $start, $end );
					$range_cells = array_merge( $range_cells, $current_range );
				}
			}
			$excluded_columns = array_merge( $excluded_columns, $range_cells );
			// Parse single letters.
			foreach ( $excluded_columns as $key => $value ) {
				$value = trim( $value );
				if ( ! is_numeric( $value ) ) {
					$value = TablePress::letter_to_number( $value );
				}
				$excluded_columns[ $key ] = ( (int) $value ) - 1; // Convert column number to 0-based column index.
			}
			// Remove duplicate entries and sort the array.
			$excluded_columns = array_unique( $excluded_columns, SORT_NUMERIC );
			sort( $excluded_columns );
			$excluded_columns = implode( ',', $excluded_columns );

			if ( '' !== $excluded_columns ) {
				$columnfilterwidgets_parameters['aiExclude'] = "aiExclude:[{$excluded_columns}]";
			}
		}

		if ( '' !== $js_options['datatables_columnfilterwidgets_separator'] ) {
			$separator = wp_json_encode( $js_options['datatables_columnfilterwidgets_separator'], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
			$columnfilterwidgets_parameters['sSeparator'] = "sSeparator:{$separator}";
		}

		if ( '' !== $js_options['datatables_columnfilterwidgets_max_selections'] ) {
			$limit = absint( $js_options['datatables_columnfilterwidgets_max_selections'] );
			$columnfilterwidgets_parameters['iMaxSelections'] = "iMaxSelections:{$limit}";
		}

		if ( false !== $js_options['datatables_columnfilterwidgets_group_terms'] ) {
			$columnfilterwidgets_parameters['bGroupTerms'] = 'bGroupTerms:true';
		}

		if ( ! empty( $columnfilterwidgets_parameters ) ) {
			$columnfilterwidgets_parameters = implode( ',', $columnfilterwidgets_parameters );
			$parameters['oColumnFilterWidgets'] = 'oColumnFilterWidgets:{' . $columnfilterwidgets_parameters . '}';
		}

		return $parameters;
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
			add_meta_box( 'tablepress_edit-datatables-columnfilterwidgets', __( 'Column Filter Dropdowns', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-columnfilterwidgets' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_ColumnFilterWidgets
