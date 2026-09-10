<?php
/**
 * TablePress DataTables AlphabetSearch.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables AlphabetSearch feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_AlphabetSearch {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-alphabetsearch' => 'datatables.alphabetsearch.css',
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
		add_filter( 'tablepress_datatables_language_strings', array( __CLASS__, 'add_datatables_language_strings' ), 9, 2 ); // Run at priority 9 so that overriding is easier on default priority.
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
		$table['options']['datatables_alphabetsearch'] = false;
		$table['options']['datatables_alphabetsearch_column'] = '1';
		$table['options']['datatables_alphabetsearch_alphabet'] = 'latin';
		$table['options']['datatables_alphabetsearch_numbers'] = false;
		$table['options']['datatables_alphabetsearch_letters'] = true;
		$table['options']['datatables_alphabetsearch_case_sensitive'] = false;
		// Add the "Alphabet Search" feature to the Table Layout module, while keeping previously added features.
		$table['options']['datatables_layout'] = array_merge_recursive(
			$table['options']['datatables_layout'] ?? array(),
			array(
				'top' => array( 'alphabetSearch' ),
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
		$default_atts['datatables_alphabetsearch'] = null;
		$default_atts['datatables_alphabetsearch_column'] = null;
		$default_atts['datatables_alphabetsearch_alphabet'] = null;
		$default_atts['datatables_alphabetsearch_numbers'] = null;
		$default_atts['datatables_alphabetsearch_letters'] = null;
		$default_atts['datatables_alphabetsearch_case_sensitive'] = null;
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
		$js_options['datatables_alphabetsearch'] = $render_options['datatables_alphabetsearch'] && $render_options['datatables_filter'];

		// Alphabet Search is not supported with Server-side Processing.
		if ( isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] ) {
			$js_options['datatables_alphabetsearch'] = false;
		}

		if ( false !== $js_options['datatables_alphabetsearch'] ) {
			$js_options['datatables_alphabetsearch_column'] = $render_options['datatables_alphabetsearch_column'];
			$js_options['datatables_alphabetsearch_alphabet'] = $render_options['datatables_alphabetsearch_alphabet'];
			$js_options['datatables_alphabetsearch_numbers'] = $render_options['datatables_alphabetsearch_numbers'];
			$js_options['datatables_alphabetsearch_letters'] = $render_options['datatables_alphabetsearch_letters'];
			$js_options['datatables_alphabetsearch_case_sensitive'] = $render_options['datatables_alphabetsearch_case_sensitive'];

			self::maybe_enqueue_css_files();

			$js_url = plugins_url( 'modules/js/datatables.alphabetsearch.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-alphabetsearch', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );

			// Add the "Alphabet Search" feature to the available features of the Table Layout module.
			$js_options['datatables_layout_active_features'][] = 'alphabetSearch';
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
		if ( ! empty( $js_options['datatables_alphabetsearch'] ) ) {
			$alphabet = array();

			$column = trim( $js_options['datatables_alphabetsearch_column'] );
			if ( ! is_numeric( $column ) ) {
				$column = TablePress::letter_to_number( $column );
			}
			$column = (int) $column;
			if ( 1 !== $column ) {
				$alphabet['column'] = $column - 1; // Zero-based counting.
			}
			if ( 'greek' === $js_options['datatables_alphabetsearch_alphabet'] ) {
				$alphabet['alphabet'] = 'greek';
			}
			if ( false !== $js_options['datatables_alphabetsearch_numbers'] ) {
				$alphabet['numbers'] = true;
			}
			if ( true !== $js_options['datatables_alphabetsearch_letters'] ) {
				$alphabet['letters'] = false;
			}
			if ( false !== $js_options['datatables_alphabetsearch_case_sensitive'] ) {
				$alphabet['caseSensitive'] = true;
			}

			if ( ! empty( $alphabet ) ) {
				$parameters['alphabet'] = 'alphabet:' . wp_json_encode( $alphabet, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
			}
		}
		return $parameters;
	}

	/**
	 * Adds strings that the module uses on the frontend to the DataTables language array.
	 *
	 * @since 2.1.0
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
			'alphabetSearch' => array(
				'search' => _x( 'Search: ', 'AlphabetSearch module', 'tablepress' ),
				'none'   => _x( 'None', 'AlphabetSearch module', 'tablepress' ),
			),
		);
		// Merge existing strings into the new strings, so that existing translations are not lost.
		$datatables_strings = array_replace_recursive( $new_strings, $datatables_strings );

		return $datatables_strings;
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
			add_meta_box( 'tablepress_edit-datatables-alphabetsearch', __( 'Alphabet Search', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-alphabetsearch' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_AlphabetSearch
