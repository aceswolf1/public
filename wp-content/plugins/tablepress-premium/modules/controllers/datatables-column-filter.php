<?php
/**
 * TablePress DataTables Column Filter.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables Column Filter feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_DataTables_Column_Filter {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Frontend CSS files to enqueue for this module.
	 *
	 * @since 3.0.1
	 * @var array<string, string>
	 */
	protected static array $css_files = array(
		'datatables-column-filter' => 'datatables.column-filter.css',
	);

	/**
	 * Registers necessary plugin filter hooks.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_filter( 'tablepress_table_template', array( __CLASS__, 'add_option_to_table_template' ) );
		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'add_shortcode_parameters' ) );
		add_filter( 'tablepress_table_render_options', array( __CLASS__, 'process_table_render_options' ), 10, 2 );
		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'pass_render_options_to_js_options' ), 10, 3 );
		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'set_datatables_parameters' ), 11, 4 ); // Priority 11 so that this runs after the DataTables Buttons module.
		add_filter( 'tablepress_datatables_command', array( __CLASS__, 'extend_datatables_command' ), 10, 6 );
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
		$table['options']['datatables_column_filter'] = '';
		$table['options']['datatables_column_filter_position'] = 'table_head';
		$table['options']['datatables_column_filter_columns'] = '';
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
		$default_atts['datatables_columnfilter'] = ''; // This parameter is deprecated and only kept for backward compatibility.
		$default_atts['datatables_column_filter'] = null;
		$default_atts['datatables_column_filter_position'] = null;
		$default_atts['datatables_column_filter_columns'] = null;
		return $default_atts;
	}

	/**
	 * Sets required render options based on the module's settings.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $render_options Render Options.
	 * @param array<string, mixed> $table          Table.
	 * @return array<string, mixed> Modified Render Options.
	 */
	public static function process_table_render_options( array $render_options, array $table ): array {
		// If the deprecated `datatables_columnfilter` parameter is used while the `datatables_column_filter` parameter is not, use that.
		if ( '' === $render_options['datatables_column_filter'] && '' !== $render_options['datatables_columnfilter'] ) {
			$render_options['datatables_column_filter'] = $render_options['datatables_columnfilter'];
		}

		if ( '' !== $render_options['datatables_column_filter'] && ! $render_options['datatables_filter'] ) {
			$render_options['datatables_column_filter'] = '';
		}

		return $render_options;
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
		$js_options['datatables_column_filter'] = $render_options['datatables_column_filter'];
		$js_options['datatables_column_filter_position'] = $render_options['datatables_column_filter_position'];
		$js_options['datatables_column_filter_columns'] = $render_options['datatables_column_filter_columns'];

		// The "select" value is not supported with Server-side Processing.
		if ( 'select' === $js_options['datatables_column_filter'] && isset( $render_options['datatables_serverside_processing'] ) && $render_options['datatables_serverside_processing'] ) {
			$js_options['datatables_column_filter'] = '';
		}

		if ( '' !== $js_options['datatables_column_filter'] ) {
			self::maybe_enqueue_css_files();
		}

		return $js_options;
	}

	/**
	 * Adjusts the DataTables parameters for the DataTables Buttons module, if configured, to be compatible.
	 *
	 * An additionally added header or footer row with input fields is removed, as it should not be part of the export output.
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
		// Modifying the buttons' `exportOptions` is only needed when "input" fields are used as an additional header or footer row is then added.
		if ( 'input' !== $js_options['datatables_column_filter'] && true !== $js_options['datatables_column_filter'] ) { // The `true` case is for backward compatibility.
			return $parameters;
		}

		// Bail out early if no button is to be shown.
		if ( ! isset( $js_options['datatables_buttons'] ) || 0 === count( $js_options['datatables_buttons'] ) ) {
			return $parameters;
		}

		// Remove the added header or footer row.
		if ( 'table_head' === $js_options['datatables_column_filter_position'] ) {
			$customize_data_body = 'd.headerStructure.pop()';
		} else {
			$customize_data_body = 'd.footerStructure.shift()';
		}

		// Construct the DataTables Buttons config parameter, with added `exportOptions`.
		foreach ( $js_options['datatables_buttons'] as &$button ) {
			if ( 'colvis' === $button ) {
				$button = "'{$button}'";
			} else {
				$button = "{extend:'{$button}',exportOptions:{customizeData:(d)=>({$customize_data_body})}}";
			}
		}
		unset( $button ); // Unset use-by-reference parameter of foreach loop.
		$parameters['buttons'] = 'buttons:[' . implode( ',', $js_options['datatables_buttons'] ) . ']';

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
		if ( '' === $js_options['datatables_column_filter'] ) {
			return $command;
		}

		$columns_list = '';
		if ( '' !== $js_options['datatables_column_filter_columns'] ) {
			$columns = $js_options['datatables_column_filter_columns'];

			// We have a list of columns (possibly with ranges in it).
			$columns = explode( ',', $columns );
			// Support for ranges like 3-6 or A-BA.
			$range_cells = array();
			foreach ( $columns as $key => $value ) {
				$range_dash = strpos( $value, '-' );
				if ( false !== $range_dash ) {
					unset( $columns[ $key ] );
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
			$columns = array_merge( $columns, $range_cells );
			// Parse single letters.
			foreach ( $columns as $key => $value ) {
				$value = trim( $value );
				if ( ! is_numeric( $value ) ) {
					$value = TablePress::letter_to_number( $value );
				}
				$columns[ $key ] = ( (int) $value ) - 1; // Convert column number to 0-based column index.
			}
			// Remove duplicate entries and sort the array.
			$columns = array_unique( $columns, SORT_NUMERIC );
			sort( $columns );
			$columns = implode( ',', $columns );

			if ( '' !== $columns ) {
				$columns_list = "[{$columns}]";
			}
		}

		if ( 'table_head' === $js_options['datatables_column_filter_position'] ) {
			$target_element = 'header';
		} else {
			$target_element = 'footer';
		}

		switch ( true ) {
			case ( true === $js_options['datatables_column_filter'] ): // For backward compatibility.
			case ( 'input' === $js_options['datatables_column_filter'] ):
				if ( 'header' === $target_element ) {
					/*
					* Add `data-dt-order="disable"` to the header row with the input fields, to prevent sorting (and some JS events) on it.
					* An IIFE is used to keep the variable names local.
					*/
					$command_init = <<<JS
						(() => {
						const table = document.getElementById( '{$html_id}' );
						if ( ! table ) {
							return;
						}
						const tHead = table.tHead;
						const inputRow = tHead.rows[ tHead.rows.length - 1 ].cloneNode( true );
						inputRow.setAttribute( 'data-dt-order', 'disable' );
						inputRow.classList.add( 'individual-column-filter-row' );
						tHead.appendChild( inputRow );
						})();
						JS;

				} else {
					/*
					* Add an additional (or a first) footer row.
					* An IIFE is used to keep the variable names local.
					*/
					$command_init = <<<JS
						(() => {
						const table = document.getElementById( '{$html_id}' );
						if ( ! table ) {
							return;
						}
						const tFoot = table.createTFoot();
						const inputRow = tFoot.rows.length
							? tFoot.rows[ tFoot.rows.length - 1 ].cloneNode( true )
							: table.tHead.rows[ table.tHead.rows.length - 1 ].cloneNode( true );
						inputRow.classList.add( 'individual-column-filter-row' );
						if ( tFoot.rows.length ) {
							tFoot.insertBefore( inputRow, tFoot.rows[0] );
						} else {
							tFoot.appendChild( inputRow );
						}
						})();
						JS;
				}

				$command = $command_init . "\n" . $command;

				$i18n_search = esc_js( __( 'Search', 'tablepress' ) );

				$fixedheader_adjust = ( isset( $js_options['datatables_fixedheader'] ) && '' !== $js_options['datatables_fixedheader'] ) ? 'dtApi.fixedHeader?.adjust();' : '';
				$fixedcolumns_adjust = (
					( isset( $js_options['datatables_fixedcolumns_left_columns'] ) && 0 !== $js_options['datatables_fixedcolumns_left_columns'] ) ||
					( isset( $js_options['datatables_fixedcolumns_right_columns'] ) && 0 !== $js_options['datatables_fixedcolumns_right_columns'] )
				) ? 'dtApi.columns.adjust().draw();' : '';

				$command_body = <<<JS
					function () {
						const dtApi = this;
						const searchReturn = dtApi.settings()[0].oPreviousSearch.return;
						dtApi
							.columns( {$columns_list} )
							.every( function () {
								const column = this;
								const targetElement = column.{$target_element}();
								const input = $( `<input type="\${ searchReturn ? 'input' : 'search' }" class="individual-column-filter">` )
									.attr( 'aria-label', dtApi.i18n( 'searchPlaceholder', '{$i18n_search}' ) )
									.attr( 'placeholder', dtApi.i18n( 'searchPlaceholder', '{$i18n_search}' ) )
									.prependTo( targetElement )
									.on( 'click', ( event ) => {
										event.stopPropagation();
									} )
									.on( searchReturn ? 'keyup' : 'input', function ( event ) {
										if( searchReturn && 'Enter' !== event.key ) {
											return;
										}
										if ( column.search() !== this.value ) {
											column.search( this.value ).draw();
										}
									} );
							} );
						{$fixedheader_adjust}
						{$fixedcolumns_adjust}
					}
					JS;
				break;

			case ( 'select' === $js_options['datatables_column_filter'] ):
				if ( 'footer' === $target_element ) {
					/*
					* Add a footer row if none exists.
					* An IIFE is used to keep the variable names local.
					*/
					$command_init = <<<JS
						(() => {
						const table = document.getElementById( '{$html_id}' );
						if ( ! table ) {
							return;
						}
						const tFoot = table.createTFoot();
						if ( ! tFoot.rows.length ) {
							tFoot.appendChild( table.tHead.rows[ table.tHead.rows.length - 1 ].cloneNode( true ) );
						}
						})();
						JS;
					$command = $command_init . "\n" . $command;
				}

				$i18n_filter_by = esc_js( __( 'Filter by column “${title}”', 'tablepress' ) );

				$fixedheader_adjust = ( isset( $js_options['datatables_fixedheader'] ) && '' !== $js_options['datatables_fixedheader'] ) ? 'dtApi.fixedHeader?.adjust();' : '';
				$fixedcolumns_adjust = (
					( isset( $js_options['datatables_fixedcolumns_left_columns'] ) && 0 !== $js_options['datatables_fixedcolumns_left_columns'] ) ||
					( isset( $js_options['datatables_fixedcolumns_right_columns'] ) && 0 !== $js_options['datatables_fixedcolumns_right_columns'] )
				) ? 'dtApi.columns.adjust().draw();' : '';

				$command_body = <<<JS
					function () {
						const dtApi = this;
						dtApi
							.columns( {$columns_list} )
							.every( function () {
								const column = this;
								const targetElement = column.{$target_element}();
								const title = targetElement.textContent;

								const select = $( '<select class="individual-column-filter"></select>' );

								$( '<option value=""></option>' )
									.text( title )
									.appendTo( select );

								column
									.data()
									.map( ( value ) => $( '<div>' + value + '</div>' ).text().replace( /\\n/g, ' ' ) )
									.filter( ( value ) => '' !== value )
									.unique()
									.sort( ( a, b ) => {
										return a.localeCompare( b, undefined, {
											numeric: true,
											sensitivity: 'base',
										} );
									} )
									.each( ( value ) => {
										$( '<option></option>' )
											.val( value )
											.text( value )
											.appendTo( select );
									} );

								select
									.attr( 'aria-label', `{$i18n_filter_by}` )
									.prependTo( targetElement )
									.on( 'click', ( event ) => {
										event.stopPropagation();
									} )
									.on( 'change', function () {
										const value = DataTable.util.escapeRegex( this.value );
										column.search( value ? '^' + value + '$' : '', true, false ).draw();
									} );
							} );
						{$fixedheader_adjust}
						{$fixedcolumns_adjust}
					}
					JS;
				break;

			default:
				// No valid value was passed, so bail.
				return $command;
				// break; // unreachable.
		}

		$command .= "\n{$name}.ready({$command_body});";
		return $command;
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
			add_meta_box( 'tablepress_edit-datatables-column-filter', __( 'Individual Column Filtering', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_style( 'datatables-column-filter', array( 'tablepress-modules-common' ) );
			TablePress_Modules_Helper::enqueue_script( 'datatables-column-filter' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_Column_Filter
