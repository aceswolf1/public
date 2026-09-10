<?php
/**
 * TablePress DataTables SearchHighlight.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables SearchHighlight feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_SearchHighlight {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-searchhighlight' => 'datatables.searchhighlight.css',
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
		$table['options']['datatables_searchhighlight'] = false;
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
		$default_atts['datatables_searchhighlight'] = null;
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
		$js_options['datatables_searchhighlight'] = $render_options['datatables_searchhighlight'] && $render_options['datatables_filter'];

		if ( false !== $js_options['datatables_searchhighlight'] ) {
			self::maybe_enqueue_css_files();

			$js_url = plugins_url( 'modules/js/datatables.searchhighlight.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-searchhighlight', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
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
		if ( ! empty( $js_options['datatables_searchhighlight'] ) ) {
			$parameters['searchHighlight'] = 'searchHighlight:true';
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
			add_meta_box( 'tablepress_edit-datatables-searchhighlight', __( 'Search Highlighting', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-searchhighlight' );
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
			TablePress_Modules_Helper::enqueue_script(
				'default-style-customizer-datatables-searchhighlight',
				array(),
				array(
					'default_style_customizer_datatables_searchhighlight_settings' => array(
						'cssUrl' => plugins_url( 'modules/css/build/datatables.searchhighlight.css', TABLEPRESS__FILE__ ),
					),
				),
			);
			$dependencies[] = 'tablepress-default-style-customizer-datatables-searchhighlight';
		}

		return $dependencies;
	}

} // class TablePress_Module_DataTables_SearchHighlight
