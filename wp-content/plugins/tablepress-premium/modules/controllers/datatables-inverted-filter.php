<?php
/**
 * TablePress DataTables Inverted Filter.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 3.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables Inverted Filter feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 3.0.0
 */
class TablePress_Module_DataTables_Inverted_Filter {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'pass_render_options_to_js_options' ), 10, 3 );
		add_filter( 'tablepress_datatables_command', array( __CLASS__, 'extend_datatables_command' ), 10, 6 );
		if ( is_admin() ) {
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_edit_screen_elements' ), 10, 2 );
		}
	}

	/**
	 * Adds the module's table options to the table template.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $table Current table template.
	 * @return array<string, mixed> Extended table template.
	 */
	public static function add_option_to_table_template( array $table ): array {
		$table['options']['datatables_inverted_filter'] = false;
		return $table;
	}

	/**
	 * Adds the module's parameters to the [table /] Shortcode.
	 *
	 * By using null as the default value, the table options's value will be used (if set).
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $default_atts Default attributes for the TablePress [table /] Shortcode.
	 * @return array<string, mixed> Extended attributes for the Shortcode.
	 */
	public static function add_shortcode_parameters( array $default_atts ): array {
		$default_atts['datatables_inverted_filter'] = null;
		return $default_atts;
	}

	/**
	 * Passes the module's Shortcode parameters to JavaScript arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $js_options     Current JS options.
	 * @param string               $table_id       Table ID.
	 * @param array<string, mixed> $render_options Render Options.
	 * @return array<string, mixed> Modified JS options.
	 */
	public static function pass_render_options_to_js_options( array $js_options, string $table_id, array $render_options ): array {
		$js_options['datatables_inverted_filter'] = $render_options['datatables_inverted_filter'];

		// Inverted Filtering requires the normal Search/Filtering feature.
		if ( ! $render_options['datatables_filter'] ) {
			$js_options['datatables_inverted_filter'] = false;
		}

		// Inverted Filtering is not supported with Server-side Processing.
		if ( isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] ) {
			$js_options['datatables_inverted_filter'] = false;
		}

		return $js_options;
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
		if ( ! $js_options['datatables_inverted_filter'] ) {
			return $command;
		}

		/*
		 * Use a minified version of this callback to toggle all control elements except the Search field and to invert the filtering:
		 *
		 * function ( searchStr, data, index ) {
		 *    const result = '' !== {$name}.search();
		 *    jQuery( '.dt-layout-cell > *:not( .dt-search )', {$name}.table().container() ).toggle( result );
		 *    return result;
		 * }
		 */
		$callback = "()=>{var r=''!=={$name}.search();\$('.dt-layout-cell>*:not(.dt-search)',{$name}.table().container()).toggle(r);return r;}";
		$command .= "\n{$name}.search.fixed('invert',{$callback}).draw();";
		return $command;
	}

	/**
	 * Registers the module's "Edit" screen elements.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data   Data for this screen.
	 * @param string               $action Action for this screen.
	 * @return array<string, mixed> Modified data for this screen.
	 */
	public static function add_edit_screen_elements( array $data, string $action ): array {
		if ( 'edit' === $action ) {
			// Add a meta box below the default meta boxes, by using the "low" priority.
			add_meta_box( 'tablepress_edit-datatables-inverted-filter', __( 'Inverted Filtering', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-inverted-filter' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_Inverted_Filter
