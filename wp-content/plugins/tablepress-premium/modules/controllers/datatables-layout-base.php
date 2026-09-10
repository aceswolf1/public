<?php
/**
 * TablePress DataTables Layout Base functionality.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 3.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables Layout Base feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 3.0.0
 */
class TablePress_Module_Base_DataTables_Layout {

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_table_options_before_merge', array( __CLASS__, 'merge_datatables_layout_option' ), 10, 4 );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'pass_render_options_to_js_options' ), 9, 3 ); // Run at priority 9 so that overriding is easier on default priority.
		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'set_datatables_parameters' ), 10, 4 );
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
		// Add the standard features to the table layout, while keeping previously added features.
		$table['options']['datatables_layout'] = array_merge_recursive(
			$table['options']['datatables_layout'] ?? array(),
			array(
				'unused'      => array(),
				'top'         => array(),
				'topStart'    => array( 'pageLength' ),
				'topEnd'      => array( 'search' ),
				'bottom'      => array(),
				'bottomStart' => array( 'info' ),
				'bottomEnd'   => array( 'paging' ),
			),
		);
		return $table;
	}

	/**
	 * Merges the `datatables_layout` option from the table template with the default table options.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $table_options         Table Options.
	 * @param array<string, mixed> $default_table_options Default Table Options.
	 * @param bool                 $remove_old_options    Whether old table options should be removed from the database.
	 * @param int                  $post_id               Post ID of the table.
	 * @return array<string, mixed> Modified Table Options.
	 */
	public static function merge_datatables_layout_option( array $table_options, array $default_table_options, bool $remove_old_options, int $post_id ): array {
		// Bail if the `datatables_layout` option is not set yet -- it will be added in `TablePress_Table_Model->merge_table_options_defaults()`.
		if ( ! isset( $table_options['datatables_layout'] ) ) {
			return $table_options;
		}

		// Bail if the `datatables_layout` option is already the same in current and in default table options.
		if ( $table_options['datatables_layout'] === $default_table_options['datatables_layout'] ) {
			return $table_options;
		}

		// Check if all default features appear anywhere in the `datatables_layout` option, and add them in their default position if not.
		foreach ( $default_table_options['datatables_layout'] as $default_position => $default_features ) {
			foreach ( $default_features as $default_feature ) {
				foreach ( $table_options['datatables_layout'] as $features ) {
					if ( in_array( $default_feature, $features, true ) ) {
						// The default feature was found in the `datatables_layout` option, so we can continue with the next default feature.
						continue 2;
					}
				}
				// At this point, the default feature was not found in the table options, so we add it.
				if ( ! isset( $table_options['datatables_layout'][ $default_position ] ) ) {
					$table_options['datatables_layout'][ $default_position ] = array();
				}
				$table_options['datatables_layout'][ $default_position ][] = $default_feature;
			}
		}

		return $table_options;
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
		$default_atts['datatables_layout'] = null;
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
		$js_options['datatables_layout'] = $render_options['datatables_layout'];

		// `datatables_layout` is an object, but could have been overwritten by a string in a Shortcode parameter.
		if ( is_string( $js_options['datatables_layout'] ) ) {
			// Convert the HTML entity `&amp;` back to `&` manually, as entities in Shortcodes in normal text paragraphs are sometimes double-encoded.
			$js_options['datatables_layout'] = str_replace( '&amp;', '&', $js_options['datatables_layout'] );

			// Convert HTML entities like `&lt;`, `&lsqb;`, `&#91;`, and `&amp;` back to their respective characters.
			$js_options['datatables_layout'] = html_entity_decode( $js_options['datatables_layout'], ENT_QUOTES | ENT_HTML5, get_option( 'blog_charset' ) );

			$js_options['datatables_layout'] = json_decode( $js_options['datatables_layout'], true );
			if ( ! is_array( $js_options['datatables_layout'] ) ) {
				$js_options['datatables_layout'] = array();
			}
		}

		// Add the standard features to the available features of the Table Layout module.
		if ( ! isset( $js_options['datatables_layout_active_features'] ) ) {
			$js_options['datatables_layout_active_features'] = array();
		}
		$js_options['datatables_layout_active_features'][] = 'pageLength';
		$js_options['datatables_layout_active_features'][] = 'search';
		$js_options['datatables_layout_active_features'][] = 'info';
		$js_options['datatables_layout_active_features'][] = 'paging';

		return $js_options;
	}

	/**
	 * Evaluates JS parameters and converts them to DataTables parameters.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $parameters DataTables parameters.
	 * @param string               $table_id   Table ID.
	 * @param string               $html_id    HTML ID of the table.
	 * @param array<string, mixed> $js_options JS options for DataTables.
	 * @return array<string, mixed> Extended DataTables parameters.
	 */
	public static function set_datatables_parameters( array $parameters, string $table_id, string $html_id, array $js_options ): array {
		// If the "dom" or "layout" parameters are set in "Custom Commands", use those for backward compatibility and performance reasons.
		if ( isset( $parameters['custom_commands'] ) && '' !== $parameters['custom_commands'] ) {
			$parameters_in_custom_commands = TablePress::extract_keys_from_js_object_string( '{' . $parameters['custom_commands'] . '}' );
			if ( in_array( 'dom', $parameters_in_custom_commands, true ) || in_array( 'layout', $parameters_in_custom_commands, true ) ) {
				return $parameters;
			}
		}

		// The "unused" features are not needed in the final DataTables parameters.
		unset( $js_options['datatables_layout']['unused'] );

		// Remove features that are not active, to prevent DataTables error messages.
		foreach ( $js_options['datatables_layout'] as &$position_features ) {
			// Convert the position features to an array, as they might be a string e.g. when being set via the Shortcode parameter.
			if ( ! is_array( $position_features ) ) {
				$position_features = array( $position_features );
			}
			foreach ( $position_features as $idx => $feature ) {
				if ( is_array( $feature ) ) {
					$feature = array_key_first( $feature );
				}
				if ( ! in_array( $feature, $js_options['datatables_layout_active_features'], true ) ) {
					unset( $position_features[ $idx ] );
				}
			}
			$position_features = array_values( $position_features );
		}
		unset( $position_features ); // Unset use-by-reference parameter of foreach loop.

		// Remove entries from the "layout" parameter that are the default anyways.
		$default_features = array(
			'top'         => array(),
			'topStart'    => array( 'pageLength' ),
			'topEnd'      => array( 'search' ),
			'bottom'      => array(),
			'bottomStart' => array( 'info' ),
			'bottomEnd'   => array( 'paging' ),
		);
		foreach ( $default_features as $position => $features ) {
			if ( isset( $js_options['datatables_layout'][ $position ] ) && $features === $js_options['datatables_layout'][ $position ] ) {
				unset( $js_options['datatables_layout'][ $position ] );
			}
		}

		// Add the "layout" parameter to the DataTables parameters if it's not empty.
		if ( ! empty( $js_options['datatables_layout'] ) ) {
			$parameters['layout'] = 'layout:' . wp_json_encode( $js_options['datatables_layout'], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
		}

		return $parameters;
	}

} // class TablePress_Module_Base_DataTables_Layout
