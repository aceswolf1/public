<?php
/**
 * TablePress DataTables Advanced Loading.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables Advanced Loading feature.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_Advanced_Loading {
	use TablePress_Module; // Use properties and methods from trait.

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
		add_filter( 'tablepress_table_content_render_data', array( __CLASS__, 'shorten_rendered_table' ), 10, 3 );
		add_filter( 'tablepress_table_output', array( __CLASS__, 'add_data_as_json_array' ), 10, 3 );
		if ( is_admin() ) {
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_edit_screen_elements' ), 10, 2 );
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
		$table['options']['datatables_advanced_loading'] = false;
		$table['options']['datatables_advanced_loading_html_rows'] = 10;
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
		$default_atts['datatables_advanced_loading'] = null;
		$default_atts['datatables_advanced_loading_html_rows'] = null;
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
		$js_options['datatables_advanced_loading'] = $render_options['datatables_advanced_loading'];

		// Advanced Loading is not supported with Server-side Processing.
		if ( isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] ) {
			$js_options['datatables_advanced_loading'] = false;
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
		if ( ! empty( $js_options['datatables_advanced_loading'] ) ) {
			$name = substr( $html_id, 11 ); // Remove "tablepress-" from the HTML ID.
			$parameters['data'] = "data:window.DT_TP_data['{$name}']";
			$parameters['deferRender'] = 'deferRender:true';
		}

		return $parameters;
	}

	/**
	 * Attaches the full render data to a shortened copy of the render data.
	 *
	 * The shortened version will be rendered as HTML while the full data will be printed as JSON.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $table          The table.
	 * @param array<string, mixed> $orig_table     The unmodified table.
	 * @param array<string, mixed> $render_options Render options.
	 * @return array<string, mixed> The modified table.
	 */
	public static function shorten_rendered_table( array $table, array $orig_table, array $render_options ): array {
		if ( empty( $render_options['datatables_advanced_loading'] ) || ! $render_options['use_datatables'] || 1 > $render_options['table_head'] ) {
			return $table;
		}

		if ( empty( $render_options['datatables_advanced_loading_html_rows'] ) ) {
			$render_options['datatables_advanced_loading_html_rows'] = 10;
		} else {
			$render_options['datatables_advanced_loading_html_rows'] = absint( $render_options['datatables_advanced_loading_html_rows'] );
		}

		// Store a copy of the full data, which will be printed as JS.
		$table['full_render_data'] = $table['data'];

		// Cut out the unneeded body rows for HTML rendering, but keep the head and foot rows.
		array_splice(
			$table['data'],
			$render_options['table_head'] + $render_options['datatables_advanced_loading_html_rows'],
			count( $table['data'] ) - $render_options['table_head'] - $render_options['datatables_advanced_loading_html_rows'] - $render_options['table_foot'],
		);

		return $table;
	}

	/**
	 * Appends a JSON array with the full table render data to the shortened table HTML output.
	 *
	 * @since 2.0.0
	 *
	 * @param string               $output         Table output.
	 * @param array<string, mixed> $table          Table.
	 * @param array<string, mixed> $render_options Render options.
	 * @return string Modified table output.
	 */
	public static function add_data_as_json_array( string $output, array $table, array $render_options ): string {
		if ( empty( $render_options['datatables_advanced_loading'] ) || ! $render_options['use_datatables'] || 1 > $render_options['table_head'] ) {
			return $output;
		}

		// Extract the table body rows. The table head and foot rows will not be replaced by DataTables.
		$render_data = array_slice(
			$table['full_render_data'],
			$render_options['table_head'],
			count( $table['full_render_data'] ) - $render_options['table_head'] - $render_options['table_foot'],
		);

		// Print the JSON data inside a `JSON.parse()` call in JS for speed gains, with necessary escaping of `\` and `'`.
		$json_data = wp_json_encode( $render_data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
		if ( false === $json_data ) {
			// JSON encoding failed, return an error object. Use a prefixed "_error" key to avoid conflicts with intentionally added "error" keys.
			$json_data = '{ "_error": "The data could not be encoded to JSON!" }';
		}
		$json_data = str_replace( array( '\\', "'" ), array( '\\\\', "\'" ), $json_data );

		$name = substr( $render_options['html_id'], 11 ); // Remove "tablepress-" from the HTML ID.
		$js_output = <<<JS
			<script>
			window.DT_TP_data=window.DT_TP_data||{};
			window.DT_TP_data['{$name}']=JSON.parse('{$json_data}');
			</script>
			JS;
		/**
		 * Filters the JavaScript output for the DataTables Advanced Loading feature.
		 *
		 * @since 2.0.0
		 *
		 * @param string                         $js_output      JavaScript output.
		 * @param string                         $json_data      JSON-encoded table data.
		 * @param array<int, array<int, string>> $render_data    Render data.
		 * @param array<string, mixed>           $table          Table.
		 * @param array<string, mixed>           $render_options Render options.
		 */
		$js_output = apply_filters( 'tablepress_datatables_advanced_loading_output', $js_output, $json_data, $render_data, $table, $render_options );

		return $output . $js_output;
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
			add_meta_box( 'tablepress_edit-datatables-advanced-loading', __( 'Advanced Loading', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-advanced-loading' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_Advanced_Loading
