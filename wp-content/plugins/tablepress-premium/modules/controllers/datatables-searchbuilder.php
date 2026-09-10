<?php
/**
 * TablePress DataTables SearchBuilder.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables SearchBuilder feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_SearchBuilder {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-datetime'      => 'datatables.datetime.css',
		'datatables-searchbuilder' => 'datatables.searchbuilder.css',
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
		$table['options']['datatables_searchbuilder'] = false;
		// Add the "Custom Search Builder" feature to the Table Layout module, while keeping previously added features.
		$table['options']['datatables_layout'] = array_merge_recursive(
			$table['options']['datatables_layout'] ?? array(),
			array(
				'top' => array( 'searchBuilder' ),
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
		$default_atts['datatables_searchbuilder'] = null;
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
		$js_options['datatables_searchbuilder'] = $render_options['datatables_searchbuilder'];

		// Custom Search Builder is not supported with Server-side Processing.
		if ( isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] ) {
			$js_options['datatables_searchbuilder'] = false;
		}

		if ( false !== $js_options['datatables_searchbuilder'] ) {
			self::maybe_enqueue_css_files();

			$js_url = plugins_url( 'modules/js/datatables.datetime.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-datetime', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
			$js_url = plugins_url( 'modules/js/datatables.searchbuilder.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-searchbuilder', $js_url, array( 'tablepress-datatables', 'tablepress-datatables-datetime' ), TablePress::version, true );

			// Add the "Custom Search Builder" feature to the available features of the Table Layout module.
			$js_options['datatables_layout_active_features'][] = 'searchBuilder';
		}

		return $js_options;
	}

	/**
	 * Adds strings that the module uses on the frontend to the DataTables language array.
	 *
	 * @since 2.0.0
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
			'searchBuilder' => array(
				'add'         => _x( 'Add Condition', 'SearchBuilder module', 'tablepress' ),
				'button'      => array(
					'0' => _x( 'Search Builder', 'SearchBuilder module', 'tablepress' ),
					'_' => _x( 'Search Builder (%d)', 'SearchBuilder module', 'tablepress' ),
				),
				'clearAll'    => _x( 'Clear All', 'SearchBuilder module', 'tablepress' ),
				'condition'   => _x( 'Condition', 'SearchBuilder module', 'tablepress' ),
				'conditions'  => array(
					'array'  => array(
						'contains' => _x( 'Contains', 'SearchBuilder module', 'tablepress' ),
						'empty'    => _x( 'Empty', 'SearchBuilder module', 'tablepress' ),
						'equals'   => _x( 'Equals', 'SearchBuilder module', 'tablepress' ),
						'not'      => _x( 'Not', 'SearchBuilder module', 'tablepress' ),
						'notEmpty' => _x( 'Not Empty', 'SearchBuilder module', 'tablepress' ),
						'without'  => _x( 'Without', 'SearchBuilder module', 'tablepress' ),
					),
					'date'   => array(
						'after'      => _x( 'After', 'SearchBuilder module', 'tablepress' ),
						'before'     => _x( 'Before', 'SearchBuilder module', 'tablepress' ),
						'between'    => _x( 'Between', 'SearchBuilder module', 'tablepress' ),
						'empty'      => _x( 'Empty', 'SearchBuilder module', 'tablepress' ),
						'equals'     => _x( 'Equals', 'SearchBuilder module', 'tablepress' ),
						'not'        => _x( 'Not', 'SearchBuilder module', 'tablepress' ),
						'notBetween' => _x( 'Not Between', 'SearchBuilder module', 'tablepress' ),
						'notEmpty'   => _x( 'Not Empty', 'SearchBuilder module', 'tablepress' ),
					),
					'number' => array(
						'between'    => _x( 'Between', 'SearchBuilder module', 'tablepress' ),
						'empty'      => _x( 'Empty', 'SearchBuilder module', 'tablepress' ),
						'equals'     => _x( 'Equals', 'SearchBuilder module', 'tablepress' ),
						'gt'         => _x( 'Greater Than', 'SearchBuilder module', 'tablepress' ),
						'gte'        => _x( 'Greater Than Equal To', 'SearchBuilder module', 'tablepress' ),
						'lt'         => _x( 'Less Than', 'SearchBuilder module', 'tablepress' ),
						'lte'        => _x( 'Less Than Equal To', 'SearchBuilder module', 'tablepress' ),
						'not'        => _x( 'Not', 'SearchBuilder module', 'tablepress' ),
						'notBetween' => _x( 'Not Between', 'SearchBuilder module', 'tablepress' ),
						'notEmpty'   => _x( 'Not Empty', 'SearchBuilder module', 'tablepress' ),
					),
					'string' => array(
						'contains'      => _x( 'Contains', 'SearchBuilder module', 'tablepress' ),
						'empty'         => _x( 'Empty', 'SearchBuilder module', 'tablepress' ),
						'endsWith'      => _x( 'Ends With', 'SearchBuilder module', 'tablepress' ),
						'equals'        => _x( 'Equals', 'SearchBuilder module', 'tablepress' ),
						'not'           => _x( 'Not', 'SearchBuilder module', 'tablepress' ),
						'notContains'   => _x( 'Does Not Contain', 'SearchBuilder module', 'tablepress' ),
						'notEmpty'      => _x( 'Not Empty', 'SearchBuilder module', 'tablepress' ),
						'notEndsWith'   => _x( 'Does Not End With', 'SearchBuilder module', 'tablepress' ),
						'notStartsWith' => _x( 'Does Not Start With', 'SearchBuilder module', 'tablepress' ),
						'startsWith'    => _x( 'Starts With', 'SearchBuilder module', 'tablepress' ),
					),
				),
				'data'        => _x( 'Data', 'SearchBuilder module', 'tablepress' ),
				'delete'      => _x( '&times;', 'SearchBuilder module', 'tablepress' ),
				'deleteTitle' => _x( 'Delete filtering rule', 'SearchBuilder module', 'tablepress' ),
				'left'        => _x( '<', 'SearchBuilder module', 'tablepress' ),
				'leftTitle'   => _x( 'Outdent criteria', 'SearchBuilder module', 'tablepress' ),
				'logicAnd'    => _x( 'And', 'SearchBuilder module', 'tablepress' ),
				'logicOr'     => _x( 'Or', 'SearchBuilder module', 'tablepress' ),
				'right'       => _x( '>', 'SearchBuilder module', 'tablepress' ),
				'rightTitle'  => _x( 'Indent criteria', 'SearchBuilder module', 'tablepress' ),
				'search'      => _x( 'Search', 'SearchBuilder module', 'tablepress' ),
				'title'       => array(
					'0' => _x( 'Custom Search Builder', 'SearchBuilder module', 'tablepress' ),
					'_' => _x( 'Custom Search Builder (%d)', 'SearchBuilder module', 'tablepress' ),
				),
				'value'       => _x( 'Value', 'SearchBuilder module', 'tablepress' ),
				'valueJoiner' => _x( 'and', 'SearchBuilder module', 'tablepress' ),
			),
			'datetime'      => array(
				'clear'    => _x( 'Clear', 'SearchBuilder module', 'tablepress' ),
				'previous' => _x( 'Previous', 'SearchBuilder module', 'tablepress' ),
				'next'     => _x( 'Next', 'SearchBuilder module', 'tablepress' ),
				'months'   => array(
					_x( 'January', 'SearchBuilder module', 'tablepress' ),
					_x( 'February', 'SearchBuilder module', 'tablepress' ),
					_x( 'March', 'SearchBuilder module', 'tablepress' ),
					_x( 'April', 'SearchBuilder module', 'tablepress' ),
					_x( 'May', 'SearchBuilder module', 'tablepress' ),
					_x( 'June', 'SearchBuilder module', 'tablepress' ),
					_x( 'July', 'SearchBuilder module', 'tablepress' ),
					_x( 'August', 'SearchBuilder module', 'tablepress' ),
					_x( 'September', 'SearchBuilder module', 'tablepress' ),
					_x( 'October', 'SearchBuilder module', 'tablepress' ),
					_x( 'November', 'SearchBuilder module', 'tablepress' ),
					_x( 'December', 'SearchBuilder module', 'tablepress' ),
				),
				'weekdays' => array(
					_x( 'Sun', 'SearchBuilder module', 'tablepress' ),
					_x( 'Mon', 'SearchBuilder module', 'tablepress' ),
					_x( 'Tue', 'SearchBuilder module', 'tablepress' ),
					_x( 'Wed', 'SearchBuilder module', 'tablepress' ),
					_x( 'Thu', 'SearchBuilder module', 'tablepress' ),
					_x( 'Fri', 'SearchBuilder module', 'tablepress' ),
					_x( 'Sat', 'SearchBuilder module', 'tablepress' ),
				),
				'amPm'     => array(
					_x( 'am', 'SearchBuilder module', 'tablepress' ),
					_x( 'pm', 'SearchBuilder module', 'tablepress' ),
				),
				'hours'    => _x( 'Hour', 'SearchBuilder module', 'tablepress' ),
				'minutes'  => _x( 'Minute', 'SearchBuilder module', 'tablepress' ),
				'seconds'  => _x( 'Second', 'SearchBuilder module', 'tablepress' ),
				'unknown'  => _x( '-', 'SearchBuilder module', 'tablepress' ),
				'today'    => _x( 'Today', 'SearchBuilder module', 'tablepress' ),
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
			add_meta_box( 'tablepress_edit-datatables-searchbuilder', __( 'Custom Search Builder', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-searchbuilder' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_SearchBuilder
