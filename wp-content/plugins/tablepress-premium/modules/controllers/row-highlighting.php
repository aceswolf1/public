<?php
/**
 * TablePress Row Highlighting.
 *
 * @package TablePress
 * @subpackage Row Highlighting.
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the Row Highlighting feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_Row_Highlighting {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Instance of the TablePress\ExpressionParser class.
	 *
	 * @since 3.1.0
	 * @var TablePress\ExpressionParser\ExpressionParser
	 */
	protected static object $expression_parser;

	/**
	 * The parsed Row Highlighting expression.
	 *
	 * @since 3.1.0
	 * @var TablePress\Symfony\Component\ExpressionLanguage\Expression
	 */
	protected static object $highlight_expression;

	/**
	 * Helper string that contains the name of the function that is used for the content comparison.
	 *
	 * @since 2.0.0
	 * @var callable
	 */
	protected static $highlight_compare_function;

	/**
	 * Helper string that contains the name of the function that is used for the content matching.
	 *
	 * @since 2.0.0
	 * @var callable
	 */
	protected static $highlight_match_function;

	/**
	 * Helper array that contains the highlight terms.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	protected static array $highlight_terms = array();

	/**
	 * Helper array that contains the columns in which highlighting should be performed.
	 *
	 * @since 2.0.0
	 * @var int[]
	 */
	protected static array $highlight_columns = array();

	/**
	 * Helper array that contains the rows in which highlighting should be performed.
	 *
	 * @since 2.0.0
	 * @var int[]
	 */
	protected static array $highlight_rows = array();

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_render_options', array( __CLASS__, 'process_table_render_options' ), 10, 2 );
		add_filter( 'tablepress_table_render_data', array( __CLASS__, 'process_parameters' ), 10, 3 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_js' ) );
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
		$table['options']['row_highlight'] = '';
		$table['options']['row_highlight_full_cell_match'] = true;
		$table['options']['row_highlight_case_sensitive'] = false;
		$table['options']['row_highlight_columns'] = ''; // '' equates to 'all'.
		$table['options']['row_highlight_rows'] = ''; // '' equates to 'all'.
		$table['options']['row_highlight_expression'] = '';
		return $table;
	}

	/**
	 * Adds the module's parameters to the [table /] Shortcode.
	 *
	 * By using null as the default value, the table options's value will be used (if set).
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $default_atts Default Shortcode attributes.
	 * @return array<string, mixed> Extended Shortcode attributes.
	 */
	public static function add_shortcode_parameters( array $default_atts ): array {
		$default_atts['row_highlight'] = null;
		$default_atts['row_highlight_full_cell_match'] = null;
		$default_atts['row_highlight_case_sensitive'] = null;
		$default_atts['row_highlight_columns'] = null;
		$default_atts['row_highlight_rows'] = null;
		$default_atts['row_highlight_url_parameter'] = ''; // This is only a Shortcode parameter, but not part of the UI.
		$default_atts['row_highlight_expression'] = null;
		return $default_atts;
	}

	/**
	 * Replaces the static row highlight term with the one from the configured URL parameter, if set.
	 *
	 * This needs to be done in this filter hook, so that the changed `row_highlight` parameter can be used for retrieving/setting the correct table output cache.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $render_options Render Options.
	 * @param array<string, mixed> $table          Table.
	 * @return array<string, mixed> Modified Render Options.
	 */
	public static function process_table_render_options( array $render_options, array $table ): array {
		// If given, use the URL row highlighting parameter.
		if ( ! empty( $render_options['row_highlight_url_parameter'] ) ) {
			// Only allow characters a-z, A-Z, 0-9, _, and - in the URL parameter name. The filter term can be anything.
			$render_options['row_highlight_url_parameter'] = (string) preg_replace( '#[^a-zA-Z0-9_-]#', '', $render_options['row_highlight_url_parameter'] );
			if ( ! empty( $_GET[ $render_options['row_highlight_url_parameter'] ] ) ) {
				$render_options['row_highlight'] = $_GET[ $render_options['row_highlight_url_parameter'] ];
			}
		}

		return $render_options;
	}

	/**
	 * Helper function for exact matching (strcmp() and strcasecmp() return 0 in case of exact match).
	 *
	 * @since 2.0.0
	 *
	 * @param string $a Row content.
	 * @param string $b Search term.
	 * @return bool Whether string $a and $ are equal (thus the highlighting matches).
	 */
	public static function _full_cell_match( string $a, string $b ): bool {
		return ( 0 === call_user_func( self::$highlight_compare_function, $a, $b ) );
	}

	/**
	 * Helper function for part matching (strpos() and stripos() return false in case of no match).
	 *
	 * @since 2.0.0
	 *
	 * @param string $a Row content.
	 * @param string $b Search term.
	 * @return bool Whether string $b can be found somewhere in $a (thus the highlighting matches).
	 */
	public static function _cell_partial_match( string $a, string $b ): bool {
		return ( false !== call_user_func( self::$highlight_compare_function, $a, $b ) );
	}

	/**
	 * Extracts Row Highlighting parameters and save them locally, because they are not available in the row class filter hook.
	 *
	 * The function is used as a filter hook handler, but the passed parameter `$table` is not changed.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $table          The table.
	 * @param array<string, mixed> $orig_table     The previous state of the table, including hidden rows/columns.
	 * @param array<string, mixed> $render_options Render options for the table.
	 * @return array<string, mixed> Unmodified table.
	 */
	public static function process_parameters( array $table, array $orig_table, array $render_options ): array {
		// Exit early, if no or only empty "row_highlight" or "row_highlight_expression" parameters are given.
		if ( empty( $render_options['row_highlight'] ) && empty( $render_options['row_highlight_expression'] ) ) {
			return $table;
		}

		// Row Highlighting is not supported with Server-side Processing or Advanced Loading.
		if (
			( isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] )
			|| ( isset( $render_options['datatables_advanced_loading'] ) && $render_options['datatables_serverside_processing'] )
		) {
			return $table;
		}

		// Exit early if there's no actual table data (e.g. after using the Row Filter module).
		if ( 0 === count( $table['data'] ) ) {
			return $table;
		}

		// Parse the Row Highlight expression.
		if ( '' !== $render_options['row_highlight_expression'] ) {
			// Convert the HTML entity `&amp;` back to `&` manually, as entities in Shortcodes in normal text paragraphs are sometimes double-encoded.
			$highlight_expression = str_replace( '&amp;', '&', $render_options['row_highlight_expression'] );

			// Convert HTML entities like `&lt;`, `&lsqb;`, `&#91;`, and `&amp;` back to their respective characters.
			$highlight_expression = html_entity_decode( $highlight_expression, ENT_QUOTES | ENT_HTML5, get_option( 'blog_charset' ) );

			// @phpstan-ignore assign.propertyType (The `load_class()` method returns `object` and not a specific type.)
			self::$expression_parser = TablePress::load_class( 'TablePress\ExpressionParser\ExpressionParser', 'class-expression-parser.php', 'modules/classes' );

			try {
				// Parse the Row Highlight expression once, so that the evaluation for each row does not need to re-parse it.
				self::$highlight_expression = self::$expression_parser->parse( $highlight_expression, array( 'row', 'row_number' ) );
			} catch ( \Throwable $error ) {
				// If the expression could not be parsed, return an empty table that wraps the error message.
				$table['data'] = array( array( $error->getMessage() ) );
				$table['visibility'] = array(
					'rows'    => array( 1 ),
					'columns' => array( 1 ),
				);
				return $table;
			}

			// Register actual filter and cleanup filter.
			add_filter( 'tablepress_row_css_class', array( __CLASS__, 'highlight_rows_expression' ), 10, 5 );
			add_filter( 'tablepress_table_output', array( __CLASS__, 'remove_row_css_class_filter' ), 10, 3 );

			// Don't process the "row_highlight" and related parameters, as the more powerful "row_highlight_expression" parameter is used.
			return $table;
		}

		// The Highlight values.
		self::$highlight_terms = explode( '||', $render_options['row_highlight'] );

		// The columns that shall be searched for the Highlight values.
		$highlight_columns = $render_options['row_highlight_columns'];
		// Add a range with all columns to the list if "" or "all" is set for the columns parameter.
		if ( '' === $highlight_columns || 'all' === $highlight_columns ) {
			$highlight_columns = '1-' . count( $table['data'][0] );
		}
		// We have a list of columns (possibly with ranges in it).
		$highlight_columns = explode( ',', $highlight_columns );
		// Support for ranges like 3-6 or A-BA.
		$range_cells = array();
		foreach ( $highlight_columns as $key => $value ) {
			$range_dash = strpos( $value, '-' );
			if ( false !== $range_dash ) {
				unset( $highlight_columns[ $key ] );
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
		$highlight_columns = array_merge( $highlight_columns, $range_cells );
		// Parse single letters.
		foreach ( $highlight_columns as $key => $value ) {
			$value = trim( $value );
			if ( ! is_numeric( $value ) ) {
				$value = TablePress::letter_to_number( $value );
			}
			$highlight_columns[ $key ] = (int) $value;
		}
		// Remove duplicate entries and sort the array.
		$highlight_columns = array_unique( $highlight_columns, SORT_NUMERIC );
		self::$highlight_columns = $highlight_columns;

		// The rows that shall be searched for the Highlight values.
		$highlight_rows = $render_options['row_highlight_rows'];
		// Add a range with all rows to the list if "" or "all" is set for the rows parameter.
		if ( '' === $highlight_rows || 'all' === $highlight_rows ) {
			$highlight_rows = '1-' . count( $table['data'] );
		}
		// We have a list of rows (possibly with ranges in it).
		$highlight_rows = explode( ',', $highlight_rows );
		// Support for ranges like 3-6.
		$range_cells = array();
		foreach ( $highlight_rows as $key => $value ) {
			$range_dash = strpos( $value, '-' );
			if ( false !== $range_dash ) {
				unset( $highlight_rows[ $key ] );
				$start = trim( substr( $value, 0, $range_dash ) );
				$end = trim( substr( $value, $range_dash + 1 ) );
				$current_range = range( $start, $end );
				$range_cells = array_merge( $range_cells, $current_range );
			}
		}
		$highlight_rows = array_merge( $highlight_rows, $range_cells );
		$highlight_rows = array_map( 'absint', $highlight_rows );
		$highlight_rows = array_unique( $highlight_rows, SORT_NUMERIC );
		self::$highlight_rows = $highlight_rows;

		// Determine which functions should be used for matching, depending on parameters.
		if ( $render_options['row_highlight_full_cell_match'] ) {
			// The entire cell content has to match the search term.
			self::$highlight_match_function = array( __CLASS__, '_full_cell_match' );
			if ( $render_options['row_highlight_case_sensitive'] ) {
				self::$highlight_compare_function = 'strcmp';
			} else {
				self::$highlight_compare_function = 'strcasecmp';
			}
		} else {
			// The search term can be anywhere in the cell content.
			self::$highlight_match_function = array( __CLASS__, '_cell_partial_match' );
			if ( $render_options['row_highlight_case_sensitive'] ) {
				self::$highlight_compare_function = function_exists( 'mb_strpos' ) ? 'mb_strpos' : 'strpos';
			} else {
				self::$highlight_compare_function = function_exists( 'mb_stripos' ) ? 'mb_stripos' : 'stripos';
			}
		}

		// Register actual filter and cleanup filter.
		add_filter( 'tablepress_row_css_class', array( __CLASS__, 'highlight_rows' ), 10, 5 );
		add_filter( 'tablepress_table_output', array( __CLASS__, 'remove_row_css_class_filter' ), 10, 3 );

		return $table;
	}

	/**
	 * Searches current row for highlight terms, and add another CSS class on find.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $row_class  Current CSS classes of the row.
	 * @param string   $table_id   Table ID.
	 * @param string[] $row_cells  HTML code for the cells.
	 * @param int      $row_number Row number.
	 * @param string[] $row_data   Row data array.
	 * @return string Row's new CSS classes.
	 */
	public static function highlight_rows( string $row_class, string $table_id, array $row_cells, int $row_number, array $row_data ): string {
		if ( empty( $row_data ) ) {
			return $row_class;
		}

		if ( ! in_array( $row_number, self::$highlight_rows, true ) ) {
			return $row_class;
		}

		foreach ( self::$highlight_terms as $highlight_term ) {
			foreach ( $row_data as $column_idx => $cell_content ) {
				$column_number = $column_idx + 1;
				if ( ! in_array( $column_number, self::$highlight_columns, true ) ) {
					continue;
				}

				if ( call_user_func( self::$highlight_match_function, $cell_content, $highlight_term ) ) {
					$row_class .= ' row-highlight-' . strtolower( sanitize_title_with_dashes( $highlight_term ) );
					break; // No need to check remaining cells in the row after a match.
				}
			}
		}

		return $row_class;
	}

	/**
	 * Evaluates the pre-parsed Row Highlight expression and adds the return value as additional CSS classes to the row.
	 *
	 * @since 3.1.0
	 *
	 * @param string   $row_class  Current CSS classes of the row.
	 * @param string   $table_id   Table ID.
	 * @param string[] $row_cells  HTML code for the cells.
	 * @param int      $row_number Row number.
	 * @param string[] $row_data   Row data array.
	 * @return string Row's new CSS classes.
	 */
	public static function highlight_rows_expression( string $row_class, string $table_id, array $row_cells, int $row_number, array $row_data ): string {
		try {
			$highlight_expression_output = self::$expression_parser->evaluate(
				self::$highlight_expression,
				array(
					'row'        => $row_data, // Pass the $row_data array as the `row` variable to the expression.
					'row_number' => $row_number, // Pass the $row_number integer as the `row_number` variable to the expression.
				),
			);
		} catch ( \Throwable $error ) {
			// If there is an error when evaluating the expression, add a CSS class that indicates the error.
			return $row_class . ' highlight-expression-evaluation-error';
		}

		// If the expression evaluates to `null`, nothing is to be done. This can e.g. happen with the short ternary expression `condition ? "class"`.
		if ( is_null( $highlight_expression_output ) ) {
			return $row_class;
		}

		// If the expression evaluates to an empty string, nothing is to be done.
		if ( '' === $highlight_expression_output ) {
			return $row_class;
		}

		// If the expression does not evaluate to a string, add a CSS class that indicates the error.
		if ( ! is_string( $highlight_expression_output ) ) {
			return $row_class . ' highlight-expression-error-no-string';
		}

		// Strip out any percent-encoded characters.
		$highlight_expression_output = (string) preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $highlight_expression_output );
		// Limit to A-Z, a-z, 0-9, '_', '-', ':', and ' '.
		$highlight_expression_output = (string) preg_replace( '/[^A-Za-z0-9: _-]/', '', $highlight_expression_output );

		$row_class .= ' ' . trim( $highlight_expression_output );
		return $row_class;
	}

	/**
	 * Removes filters on the row CSS class again, to allow for the class to be used again on the same page.
	 *
	 * The function is used as a filter hook handler, but the passed parameter `$output` is not changed.
	 *
	 * @since 2.0.0
	 *
	 * @param string               $output         Table output.
	 * @param array<string, mixed> $table          Table.
	 * @param array<string, mixed> $render_options Render options.
	 * @return string Table output.
	 */
	public static function remove_row_css_class_filter( string $output, array $table, array $render_options ): string {
		if ( '' !== $render_options['row_highlight_expression'] ) {
			remove_filter( 'tablepress_row_css_class', array( __CLASS__, 'highlight_rows_expression' ), 10 );
		} else {
			remove_filter( 'tablepress_row_css_class', array( __CLASS__, 'highlight_rows' ), 10 );
		}

		return $output;
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
			add_meta_box( 'tablepress_edit-row-highlighting', __( 'Highlight certain rows based on their content', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_script( 'row-highlighting' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_Row_Highlighting
