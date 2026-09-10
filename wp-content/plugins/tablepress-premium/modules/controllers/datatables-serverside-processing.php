<?php
/**
 * TablePress DataTables Server-side Processing.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables Server-side Processing feature.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_ServerSide_Processing {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-serverside-processing' => 'datatables.serverside-processing.css',
	);

	/**
	 * Store for the row counts for each table, for use in the `deferLoading` DataTables parameter.
	 *
	 * @since 2.0.0
	 * @var array<string, int>
	 */
	public static array $row_counts = array();

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		TablePress::load_class( 'TablePress_Module_DataTables_ServerSide_Processing_REST_API', 'datatables-serverside-processing-rest-api.php', 'modules/controllers' );

		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'pass_render_options_to_js_options' ), 10, 3 );
		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'set_datatables_parameters' ), 10, 4 );
		add_filter( 'tablepress_datatables_command', array( __CLASS__, 'extend_datatables_command' ), 10, 6 );
		add_filter( 'tablepress_table_render_data', array( __CLASS__, 'shorten_rendered_table' ), 10, 3 );
		if ( is_admin() ) {
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_edit_screen_elements' ), 10, 2 );
		}
		if ( TablePress::$controller->use_legacy_css_loading && ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_css_files' ), 10, 0 );
		}
	}

	/**
	 * Encodes a (binary) string to base64 and replaces characters so that the output can be used without URL encoding.
	 *
	 * The output is the same as base64 encoding, but with `-` instead of `+`, `_` instead of `/`, and `=` removed.
	 *
	 * @since 2.0.4
	 *
	 * @param string $input (Binary) string that is to be encoded.
	 * @return string String that is base64 and has characters replaced so that no URL encoding is needed.
	 */
	public static function base64_url_encode( string $input ): string {
		return str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), base64_encode( $input ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decodes a base64 string that potentiall has replaced characters back to a (binary) string.
	 *
	 * Characters `-` and `_` are replaced by `+` and `/` before decoding the base64 format.
	 *
	 * @since 2.0.4
	 *
	 * @param string $input Base64 string that potentially has replaced characters.
	 * @return string|false Base64-decoded (binary) string, or false on failure.
	 */
	public static function base64_url_decode( string $input ) /* : string|false */ {
		return base64_decode( str_replace( array( '-', '_' ), array( '+', '/' ), $input ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
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
		$table['options']['datatables_serverside_processing'] = false;
		$table['options']['datatables_serverside_processing_cached_pages'] = 0;
		$table['options']['datatables_serverside_processing_periodic_refresh'] = 0;
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
		$default_atts['datatables_serverside_processing'] = null;
		$default_atts['datatables_serverside_processing_cached_pages'] = null;
		$default_atts['datatables_serverside_processing_periodic_refresh'] = null;
		$default_atts['datatables_serverside_processing_request_type'] = 'GET'; // This is only a Shortcode parameter, but not part of the UI.
		$default_atts['datatables_serverside_processing_html_rows'] = ''; // This is only a Shortcode parameter, but not part of the UI, as, by default, the "datatables_paginate_entries" value is used.
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
		if ( empty( $render_options['datatables_serverside_processing'] ) ) {
			return $js_options;
		}

		self::maybe_enqueue_css_files();

		$js_options['datatables_serverside_processing'] = $render_options['datatables_serverside_processing'];
		$js_options['datatables_serverside_processing_cached_pages'] = absint( $render_options['datatables_serverside_processing_cached_pages'] );
		$js_options['datatables_serverside_processing_periodic_refresh'] = absint( $render_options['datatables_serverside_processing_periodic_refresh'] );
		$js_options['datatables_serverside_processing_request_type'] = $render_options['datatables_serverside_processing_request_type'];

		$render_options_ssp = array(
			'id',
			'cache_table_output',
			'convert_line_breaks',
			'evaluate_formulas',
			'hide_columns',
			'hide_rows',
			'show_columns',
			'show_rows',
			'table_head',
			'table_foot',
		);

		/**
		 * Filters the list of render option keys that are passed as a request parameter in the AJAX call to the Server-side Processing REST API endpoint.
		 *
		 * This can be used to make other render options available to e.g. other filter hooks in the Render class.
		 *
		 * @since 2.0.4
		 *
		 * @param string[]             $render_options_ssp Render Options list for Server-side Processing.
		 * @param string               $table_id           Table ID.
		 * @param array<string, mixed> $render_options     Render Options.
		 * @return string[] Modified Render Options list for Server-side Processing.
		 */
		$render_options_ssp = apply_filters( 'tablepress_datatables_serverside_processing_render_options', $render_options_ssp, $table_id, $render_options );

		$request_render_options = array_intersect_key( $render_options, array_flip( $render_options_ssp ) );
		$js_options['encrypted_render_options'] = self::encrypt_render_options( $request_render_options );

		if ( 0 !== $js_options['datatables_serverside_processing_cached_pages'] ) {
			$js_url = plugins_url( 'modules/js/datatables.serverside-processing.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-serverside-processing', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
		}

		return $js_options;
	}

	/**
	 * Encrypts and encodes a Render Options array.
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $render_options Render options.
	 * @return array{request: string, nonce: string} URL-safe, base64-encoded encryted render options and nonce.
	 */
	protected static function encrypt_render_options( array $render_options ): array {
		$message = wp_json_encode( $render_options, TABLEPRESS_JSON_OPTIONS );
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$secret_key = sodium_crypto_generichash( wp_salt( 'nonce' ), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );

		$ciphertext = sodium_crypto_secretbox( $message, $nonce, $secret_key ); // @phpstan-ignore argument.type

		return array(
			'request' => self::base64_url_encode( $ciphertext ),
			'nonce'   => self::base64_url_encode( $nonce ),
		);
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
		if ( empty( $js_options['datatables_serverside_processing'] ) ) {
			return $parameters;
		}

		$parameters['serverSide'] = 'serverSide:true';
		$parameters['processing'] = 'processing:true';

		$table_rest_url = get_rest_url( null, "/tablepress/v1/ssp/{$table_id}" );
		$table_rest_url = add_query_arg( 'r', $js_options['encrypted_render_options']['request'], $table_rest_url );
		$table_rest_url = add_query_arg( 'n', $js_options['encrypted_render_options']['nonce'], $table_rest_url );
		if ( is_user_logged_in() ) {
			$table_rest_url = add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), $table_rest_url );
		}

		/* The periodic refresh is only possible when pre-caching is disabled. */
		if ( 0 < $js_options['datatables_serverside_processing_periodic_refresh'] ) {
			$js_options['datatables_serverside_processing_cached_pages'] = 0;
		}

		if ( isset( $js_options['datatables_pagination_loadmore_button'] ) && $js_options['datatables_pagination_loadmore_button'] ) {
			// Configure using the "Show More" button with SSP, which takes precedence over SSP page caching.
			$method = '';
			if ( 'POST' === $js_options['datatables_serverside_processing_request_type'] ) {
				$method = ",method:'POST'";
			}
			$parameters['ajax'] = "ajax:DataTable.pageLoadMore({url:'{$table_rest_url}'{$method}})";
		} elseif ( 0 < $js_options['datatables_serverside_processing_cached_pages'] ) {
			// Configure page caching with SSP.
			$pages = '';
			if ( 5 !== $js_options['datatables_serverside_processing_cached_pages'] ) {
				$pages = ",pages:{$js_options['datatables_serverside_processing_cached_pages']}";
			}
			$method = '';
			if ( 'POST' === $js_options['datatables_serverside_processing_request_type'] ) {
				$method = ",method:'POST'";
			}
			$parameters['ajax'] = "ajax:DataTable.pipeline({url:'{$table_rest_url}'{$pages}{$method}})";
		} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
			// Standard SSP behavior, without caching and without "Show More" button.
			if ( 'POST' === $js_options['datatables_serverside_processing_request_type'] ) {
				$parameters['ajax'] = "ajax:{url:'{$table_rest_url}',type:'POST'}";
			} else {
				$parameters['ajax'] = "ajax:'{$table_rest_url}'";
			}
		}

		if ( isset( self::$row_counts[ $html_id ] ) && ! ( isset( $js_options['datatables_pagination_loadmore_button'] ) && $js_options['datatables_pagination_loadmore_button'] ) ) {
			$row_count = self::$row_counts[ $html_id ];
			$parameters['deferLoading'] = "deferLoading:{$row_count}";
		}

		// Add an option to the "search" value, if one is already set, otherwise set it.
		$search_sub_option = 'return:true';
		if ( isset( $parameters['search'] ) ) {
			$parameters['search'] = str_replace( 'search:{', "search:{{$search_sub_option},", $parameters['search'] );
		} else {
			$parameters['search'] = "search:{{$search_sub_option}}";
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
		if ( ! empty( $js_options['datatables_serverside_processing'] ) && 0 < $js_options['datatables_serverside_processing_periodic_refresh'] && empty( $js_options['datatables_pagination_loadmore_button'] ) ) {
			$interval = 1000 * $js_options['datatables_serverside_processing_periodic_refresh'];
			$command .= "\n{$name}.ready(function(){setInterval(()=>this.ajax.reload(null,false),{$interval});});";
		}

		return $command;
	}

	/**
	 * Creates a shortened version of the table data for HTML rendering.
	 *
	 * The full data will be fetched via the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $table          The table.
	 * @param array<string, mixed> $orig_table     The unmodified table.
	 * @param array<string, mixed> $render_options Render options.
	 * @return array<string, mixed> The modified table.
	 */
	public static function shorten_rendered_table( array $table, array $orig_table, array $render_options ): array {
		if ( ! $render_options['datatables_serverside_processing'] ) {
			return $table;
		}

		if ( '' === $render_options['datatables_serverside_processing_html_rows'] ) {
			$render_options['datatables_serverside_processing_html_rows'] = $render_options['datatables_paginate_entries'];
		}
		$render_options['datatables_serverside_processing_html_rows'] = absint( $render_options['datatables_serverside_processing_html_rows'] );

		self::$row_counts[ $render_options['html_id'] ] = count( $table['data'] ) - $render_options['table_head'] - $render_options['table_foot'];

		// Cut out the unneeded body rows for HTML rendering, but keep the head and foot rows.
		array_splice(
			$table['data'],
			$render_options['table_head'] + $render_options['datatables_serverside_processing_html_rows'],
			count( $table['data'] ) - $render_options['table_head'] - $render_options['datatables_serverside_processing_html_rows'] - $render_options['table_foot'],
		);

		return $table;
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
			add_meta_box( 'tablepress_edit-datatables-serverside-processing', __( 'Server-side Processing', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'datatables-serverside-processing' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_ServerSide_Processing
