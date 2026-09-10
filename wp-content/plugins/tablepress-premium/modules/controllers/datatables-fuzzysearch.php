<?php
/**
 * TablePress DataTables FuzzySearch.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.4.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables FuzzySearch feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.4.0
 */
class TablePress_Module_DataTables_FuzzySearch {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 2.4.0
	 */
	public function __construct() {
		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'pass_render_options_to_js_options' ), 10, 3 );
		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'set_datatables_parameters' ), 10, 4 );
		add_filter( 'tablepress_datatables_language_strings', array( __CLASS__, 'add_datatables_language_strings' ), 9, 2 ); // Run at priority 9 so that overriding is easier on default priority.
		if ( is_admin() ) {
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_edit_screen_elements' ), 10, 2 );
		}
	}

	/**
	 * Adds the module's table options to the table template.
	 *
	 * @since 2.4.0
	 *
	 * @param array<string, mixed> $table Current table template.
	 * @return array<string, mixed> Extended table template.
	 */
	public static function add_option_to_table_template( array $table ): array {
		$table['options']['datatables_fuzzysearch'] = false;
		$table['options']['datatables_fuzzysearch_threshold'] = 0.5;
		$table['options']['datatables_fuzzysearch_togglesmart'] = true;
		$table['options']['datatables_fuzzysearch_rankcolumn'] = '';
		return $table;
	}

	/**
	 * Adds the module's parameters to the [table /] Shortcode.
	 *
	 * By using null as the default value, the table options's value will be used (if set).
	 *
	 * @since 2.4.0
	 *
	 * @param array<string, mixed> $default_atts Default Shortcode attributes.
	 * @return array<string, mixed> Extended Shortcode attributes.
	 */
	public static function add_shortcode_parameters( array $default_atts ): array {
		$default_atts['datatables_fuzzysearch'] = null;
		$default_atts['datatables_fuzzysearch_threshold'] = null;
		$default_atts['datatables_fuzzysearch_togglesmart'] = null;
		$default_atts['datatables_fuzzysearch_rankcolumn'] = null;
		return $default_atts;
	}

	/**
	 * Passes the module's Shortcode parameters to JavaScript arguments.
	 *
	 * @since 2.4.0
	 *
	 * @param array<string, mixed> $js_options     Current JS options.
	 * @param string               $table_id       Table ID.
	 * @param array<string, mixed> $render_options Render Options.
	 * @return array<string, mixed> Modified JS options.
	 */
	public static function pass_render_options_to_js_options( array $js_options, string $table_id, array $render_options ): array {
		$js_options['datatables_fuzzysearch'] = (bool) $render_options['datatables_fuzzysearch'];
		$js_options['datatables_fuzzysearch_threshold'] = (float) $render_options['datatables_fuzzysearch_threshold'];
		$js_options['datatables_fuzzysearch_togglesmart'] = (bool) $render_options['datatables_fuzzysearch_togglesmart'];
		$js_options['datatables_fuzzysearch_rankcolumn'] = trim( $render_options['datatables_fuzzysearch_rankcolumn'] );

		// Fuzzy Search is not supported with Server-side Processing.
		if ( isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] ) {
			$js_options['datatables_fuzzysearch'] = false;
		}

		// Change parameters and register files if the feature is enabled.
		if ( $js_options['datatables_fuzzysearch'] ) {
			$js_url = plugins_url( 'modules/js/datatables.fuzzysearch.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-fuzzysearch', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
		}

		return $js_options;
	}

	/**
	 * Evaluates JS parameters and converts them to DataTables parameters.
	 *
	 * @since 2.4.0
	 *
	 * @param array<string, mixed> $parameters DataTables parameters.
	 * @param string               $table_id   Table ID.
	 * @param string               $html_id    HTML ID of the table.
	 * @param array<string, mixed> $js_options JS options for DataTables.
	 * @return array<string, mixed> Extended DataTables parameters.
	 */
	public static function set_datatables_parameters( array $parameters, string $table_id, string $html_id, array $js_options ): array {
		if ( $js_options['datatables_fuzzysearch'] ) {
			$fuzzysearch_parameters = array();
			if ( 0.5 !== $js_options['datatables_fuzzysearch_threshold'] ) {
				$fuzzysearch_parameters[] = "threshold:{$js_options['datatables_fuzzysearch_threshold']}";
			}
			$fuzzysearch_parameters[] = 'toggleSmart:' . ( $js_options['datatables_fuzzysearch_togglesmart'] ? 'true' : 'false' );
			if ( '' !== $js_options['datatables_fuzzysearch_rankcolumn'] ) {
				$column = $js_options['datatables_fuzzysearch_rankcolumn'];
				if ( ! is_numeric( $column ) ) {
					$column = TablePress::letter_to_number( strtoupper( $column ) );
				}
				$column = ( (int) $column ) - 1;
				$fuzzysearch_parameters[] = "rankColumn:{$column}";
			}
			$fuzzysearch_parameters = implode( ',', $fuzzysearch_parameters );
			$parameters['fuzzySearch'] = "fuzzySearch:{{$fuzzysearch_parameters}}";
		}
		return $parameters;
	}

	/**
	 * Adds strings that the module uses on the frontend to the DataTables language array.
	 *
	 * @since 2.4.0
	 *
	 * @param array<string, string|mixed[]> $datatables_strings The language strings for DataTables.
	 * @param string                        $datatables_locale  Current locale/language for the DataTables JS library.
	 * @return array<string, string|mixed[]> Extended array of strings for DataTables.
	 */
	public static function add_datatables_language_strings( array $datatables_strings, string $datatables_locale ): array {
		if ( 'en_US' === $datatables_locale ) {
			return $datatables_strings;
		}

		TablePress_Modules_Loader::load_language_file();

		$new_strings = array(
			'fuzzySearch' => array(
				'exact'      => _x( 'Exact', 'FuzzySearch module', 'tablepress' ),
				'fuzzy'      => _x( 'Fuzzy', 'FuzzySearch module', 'tablepress' ),
				'searchType' => _x( 'Search Type', 'FuzzySearch module', 'tablepress' ),
			),
		);
		// Merge existing strings into the new strings, so that existing translations are not lost.
		$datatables_strings = array_replace_recursive( $new_strings, $datatables_strings );

		return $datatables_strings;
	}

	/**
	 * Registers the module's "Edit" screen elements.
	 *
	 * @since 2.4.0
	 *
	 * @param array<string, mixed> $data   Data for this screen.
	 * @param string               $action Action for this screen.
	 * @return array<string, mixed> Modified data for this screen.
	 */
	public static function add_edit_screen_elements( array $data, string $action ): array {
		if ( 'edit' === $action ) {
			// Add a meta box below the default meta boxes, by using the "low" priority.
			add_meta_box( 'tablepress_edit-datatables-fuzzysearch', __( 'Fuzzy Search', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-fuzzysearch' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_FuzzySearch
