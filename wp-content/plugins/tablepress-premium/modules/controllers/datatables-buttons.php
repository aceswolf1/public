<?php
/**
 * TablePress DataTables Buttons.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables Buttons feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_Buttons {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-buttons' => 'datatables.buttons.css',
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
		$table['options']['datatables_buttons'] = '';
		// Add the "Buttons" feature to the Table Layout module, while keeping previously added features.
		$table['options']['datatables_layout'] = array_merge_recursive(
			$table['options']['datatables_layout'] ?? array(),
			array(
				'top' => array( 'buttons' ),
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
		$default_atts['datatables_buttons'] = null;
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
		$js_options['datatables_buttons'] = strtolower( $render_options['datatables_buttons'] );

		// Remove invalid button names from the list.
		$js_options['datatables_buttons'] = explode( ',', $js_options['datatables_buttons'] );
		$js_options['datatables_buttons'] = array_map( 'trim', $js_options['datatables_buttons'] );
		foreach ( $js_options['datatables_buttons'] as $idx => $button ) {
			if ( ! in_array( $button, array( 'copy', 'csv', 'excel', 'pdf', 'print', 'colvis' ), true ) ) {
				unset( $js_options['datatables_buttons'][ $idx ] );
			}
		}

		// Bail out early if no button is to be shown.
		if ( 0 === count( $js_options['datatables_buttons'] ) ) {
			return $js_options;
		}

		self::maybe_enqueue_css_files();

		$js_url = plugins_url( 'modules/js/datatables.buttons.min.js', TABLEPRESS__FILE__ );
		wp_enqueue_script( 'tablepress-datatables-buttons', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );

		// If any of the export buttons is shown, we need the JS files.
		foreach ( array( 'copy', 'csv', 'excel', 'pdf' ) as $button ) {
			if ( in_array( $button, $js_options['datatables_buttons'], true ) ) {
				$js_url = plugins_url( 'modules/js/datatables.buttons.html5.min.js', TABLEPRESS__FILE__ );
				wp_enqueue_script( 'tablepress-datatables-buttons-html5', $js_url, array( 'tablepress-datatables-buttons' ), TablePress::version, true );
				break;
			}
		}

		// Add special JS files for special buttons.
		if ( in_array( 'print', $js_options['datatables_buttons'], true ) ) {
			$js_url = plugins_url( 'modules/js/datatables.buttons.print.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-buttons-print', $js_url, array( 'tablepress-datatables-buttons' ), TablePress::version, true );
		}
		if ( in_array( 'excel', $js_options['datatables_buttons'], true ) ) {
			$js_url = plugins_url( 'modules/js/datatables.buttons.jszip.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-buttons-jsmin', $js_url, array( 'tablepress-datatables-buttons' ), TablePress::version, true );
		}
		if ( in_array( 'pdf', $js_options['datatables_buttons'], true ) ) {
			$js_url = plugins_url( 'modules/js/datatables.buttons.pdfmake.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-buttons-pdfmake', $js_url, array( 'tablepress-datatables-buttons' ), TablePress::version, true );
		}
		if ( in_array( 'colvis', $js_options['datatables_buttons'], true ) ) {
			$js_url = plugins_url( 'modules/js/datatables.buttons.colvis.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-buttons-colvis', $js_url, array( 'tablepress-datatables-buttons' ), TablePress::version, true );
		}

		// Add the "Buttons" feature to the available features of the Table Layout module.
		$js_options['datatables_layout_active_features'][] = 'buttons';

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
		// Bail out early if no button is to be shown.
		if ( 0 === count( $js_options['datatables_buttons'] ) ) {
			return $parameters;
		}

		// Construct the DataTables Buttons config parameter.
		foreach ( $js_options['datatables_buttons'] as &$button ) {
			$button = "'{$button}'";
		}
		unset( $button ); // Unset use-by-reference parameter of foreach loop.
		$parameters['buttons'] = 'buttons:[' . implode( ',', $js_options['datatables_buttons'] ) . ']';

		return $parameters;
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
			'buttons' => array(
				'collection'    => _x( 'Collection', 'Buttons module', 'tablepress' ),
				'colvis'        => _x( 'Column visibility', 'Buttons module', 'tablepress' ),
				'colvisRestore' => _x( 'Restore visibility', 'Buttons module', 'tablepress' ),
				'copy'          => _x( 'Copy', 'Buttons module', 'tablepress' ),
				'copyKeys'      => _x( 'Press <i>ctrl</i> or <i>⌘</i> + <i>C</i> to copy the table data<br>to your system clipboard.<br><br>To cancel, click this message or press escape.', 'Buttons module', 'tablepress' ),
				'copySuccess'   => array(
					'1' => _x( 'Copied one row to clipboard', 'Buttons module', 'tablepress' ),
					'_' => _x( 'Copied %d rows to clipboard', 'Buttons module', 'tablepress' ),
				),
				'copyTitle'     => _x( 'Copy to clipboard', 'Buttons module', 'tablepress' ),
				'csv'           => _x( 'CSV', 'Buttons module', 'tablepress' ),
				'excel'         => _x( 'Excel', 'Buttons module', 'tablepress' ),
				'pageLength'    => array(
					'-1' => _x( 'Show all rows', 'Buttons module', 'tablepress' ),
					'_'  => _x( 'Show %d rows', 'Buttons module', 'tablepress' ),
				),
				'pdf'           => _x( 'PDF', 'Buttons module', 'tablepress' ),
				'print'         => _x( 'Print', 'Buttons module', 'tablepress' ),
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
			add_meta_box( 'tablepress_edit-datatables-buttons', __( 'User Action Buttons', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-buttons', array( 'jquery-core', 'jquery-ui-sortable' ) );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_Buttons
