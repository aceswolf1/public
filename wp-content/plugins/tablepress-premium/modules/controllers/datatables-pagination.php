<?php
/**
 * TablePress DataTables Pagination Settings functionality.
 *
 * @package TablePress
 * @subpackage DataTables
 * @author Tobias Bäthge
 * @since 3.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the DataTables Pagination Settings feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 3.0.0
 */
class TablePress_Module_DataTables_Pagination {
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
		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'set_datatables_parameters' ), 10, 4 );
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
		$table['options']['datatables_pagination_firstlast'] = false;
		$table['options']['datatables_pagination_previousnext'] = true;
		$table['options']['datatables_pagination_numbers'] = true;
		$table['options']['datatables_pagination_input'] = false;
		$table['options']['datatables_pagination_input_pageof'] = true;
		$table['options']['datatables_pagination_scrolltotop'] = false;
		$table['options']['datatables_pagination_scrolltotop_offset'] = 10;
		$table['options']['datatables_pagination_loadmore_button'] = false;
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
		$default_atts['datatables_pagination_firstlast'] = null;
		$default_atts['datatables_pagination_previousnext'] = null;
		$default_atts['datatables_pagination_numbers'] = null;
		$default_atts['datatables_pagination_input'] = null;
		$default_atts['datatables_pagination_input_pageof'] = null;
		$default_atts['datatables_pagination_buttons'] = 7; // This is only a Shortcode parameter, but not part of the UI.
		$default_atts['datatables_pagination_boundarynumbers'] = true; // This is only a Shortcode parameter, but not part of the UI.
		$default_atts['datatables_pagination_scrolltotop'] = null;
		$default_atts['datatables_pagination_scrolltotop_offset'] = null;
		$default_atts['datatables_pagination_loadmore_button'] = null;
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
		// Bail early if pagination is disabled.
		if ( ! $js_options['datatables_paginate'] ) {
			return $js_options;
		}

		$js_options['datatables_pagination_firstlast'] = $render_options['datatables_pagination_firstlast'];
		$js_options['datatables_pagination_previousnext'] = $render_options['datatables_pagination_previousnext'];
		$js_options['datatables_pagination_numbers'] = $render_options['datatables_pagination_numbers'];
		$js_options['datatables_pagination_input'] = $render_options['datatables_pagination_input'];
		$js_options['datatables_pagination_input_pageof'] = $render_options['datatables_pagination_input_pageof'];
		$js_options['datatables_pagination_buttons'] = absint( $render_options['datatables_pagination_buttons'] );
		$js_options['datatables_pagination_boundarynumbers'] = $render_options['datatables_pagination_boundarynumbers'];
		$js_options['datatables_pagination_scrolltotop'] = $render_options['datatables_pagination_scrolltotop'];
		$js_options['datatables_pagination_scrolltotop_offset'] = absint( $render_options['datatables_pagination_scrolltotop_offset'] );
		$js_options['datatables_pagination_loadmore_button'] = $render_options['datatables_pagination_loadmore_button'];

		if ( $js_options['datatables_pagination_loadmore_button'] ) {
			$js_url = plugins_url( 'modules/js/datatables.pageloadmore.min.js', TABLEPRESS__FILE__ );
			wp_enqueue_script( 'tablepress-datatables-pageloadmore', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );

			$js_options['datatables_lengthchange'] = false;

			// Add the "div" feature to the available features of the Table Layout module.
			$js_options['datatables_layout_active_features'][] = 'div';
			$paging_feature = 'div';

			// Construct the feature configuration array, to add a custom button instead of the normal "paging" feature.
			$paging = array(
				'className' => 'dt-paging',
				'html'      => '<nav aria-label="pagination"><button class="dt-paging-button dt-paging-button-show-more current" role="link" type="button" style="scroll-margin-bottom:1rem">' . esc_html_x( 'Show more', 'Advanced Pagination Settings module', 'tablepress' ) . '</button></nav>',
			);
		} else {
			// Construct the "paging" feature configuration array, by only adding values that differ from the DataTables default value.
			$paging = array();
			if ( ! $js_options['datatables_pagination_firstlast'] ) {
				$paging['firstLast'] = false;
			}
			if ( ! $js_options['datatables_pagination_previousnext'] ) {
				$paging['previousNext'] = false;
			}
			if ( ! $js_options['datatables_pagination_input'] && ! $js_options['datatables_pagination_numbers'] ) {
				$paging['numbers'] = false;
			}
			if ( $js_options['datatables_pagination_input'] && ! $js_options['datatables_pagination_input_pageof'] ) {
				$paging['pageOf'] = false;
			}
			if ( ! $js_options['datatables_pagination_input'] && 7 !== $js_options['datatables_pagination_buttons'] ) {
				$paging['buttons'] = $js_options['datatables_pagination_buttons'];
			}
			if ( ! $js_options['datatables_pagination_input'] && ! $js_options['datatables_pagination_boundarynumbers'] ) {
				$paging['boundaryNumbers'] = false;
			}

			$paging_feature = 'paging';

			if ( $js_options['datatables_pagination_input'] ) {
				$paging_feature = 'inputPaging';

				// Add the "Input Paging" feature to the available features of the Table Layout module.
				$js_options['datatables_layout_active_features'][] = 'inputPaging';

				$js_url = plugins_url( 'modules/js/datatables.inputpaging.min.js', TABLEPRESS__FILE__ );
				wp_enqueue_script( 'tablepress-datatables-inputpaging', $js_url, array( 'tablepress-datatables' ), TablePress::version, true );
			}

			// Bail early if no custom "paging" feature configuration is needed.
			if ( 'paging' === $paging_feature && empty( $paging ) ) {
				return $js_options;
			}
		}

		// Replace "paging" in the "datatables_layout" array with the constructed "paging" feature configuration array.
		foreach ( $js_options['datatables_layout'] as $position => &$position_features ) {
			if ( 'unused' === $position ) {
				continue;
			}

			// Convert the position features to an array, as they might be a string e.g. when being set via the Shortcode parameter.
			if ( ! is_array( $position_features ) ) {
				$position_features = array( $position_features );
			}

			foreach ( $position_features as &$feature ) {
				if ( 'paging' !== $feature ) {
					continue;
				}

				$feature = array(
					$paging_feature => $paging,
				);
			}
			unset( $feature ); // Unset use-by-reference parameter of foreach loop.
		}
		unset( $position_features ); // Unset use-by-reference parameter of foreach loop.

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
		// The DataTables "pagingType" parameter is deprecated, but used to set the pagination type when this feature module is inactive (in the Premium version or if the free version is used).
		unset( $parameters['pagingType'] );

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
		if ( isset( $js_options['datatables_pagination_loadmore_button'] ) && $js_options['datatables_pagination_loadmore_button'] ) {
				$ready_body = <<<JS
				function () {
					const dtApi = this;
					const noMoreRows = ! dtApi.page.hasMore();
					dtApi.table().container().querySelectorAll( ':scope .dt-paging-button-show-more' ).forEach( ( button ) => {
						if ( noMoreRows ) {
							button.disabled = true;
							button.classList.add( 'disabled' );
						}
						button.addEventListener( 'click', function () {
							this.classList.add( 'clicked-button' ); /* Mark the button as clicked, to scroll it into view after the table has been redrawn. */
							dtApi.page.loadMore();
						} );
					} );
				}
				JS;
				$command .= "\n{$name}.ready({$ready_body});";

				$draw_body = <<<JS
				() => {
					let clickedButton = null;

					/* Disable the button if there are no more rows to load. */
					const noMoreRows = ! {$name}.page.hasMore();
					const showMoreButtons = {$name}.table().container().querySelectorAll( '.dt-paging-button-show-more' );
					showMoreButtons.forEach( ( button ) => {
						button.disabled = noMoreRows;
						button.classList.toggle( 'disabled', noMoreRows );
						if ( button.classList.contains( 'clicked-button' ) ) {
							clickedButton = button;
						}
					} );

					/* Scroll the button into view, if it's not visible on the screen. */
					if ( clickedButton ) {
						clickedButton.classList.remove( 'clicked-button' );
						if ( clickedButton.getBoundingClientRect().bottom > ( window.innerHeight || document.documentElement.clientHeight ) ) {
							clickedButton.scrollIntoView( { behavior: 'smooth', block: 'end', inline: 'nearest' } );
						}
					}
				}
				JS;
				$command .= "\n{$name}.on('draw',{$draw_body});";

				$search_order_body = <<<JS
				() => {
					{$name}.page.resetMore();
				}
				JS;
				$command .= "\n{$name}.on('search',{$search_order_body});";
				$command .= "\n{$name}.on('order',{$search_order_body});";
		} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
			if ( isset( $js_options['datatables_pagination_scrolltotop'] ) && $js_options['datatables_pagination_scrolltotop'] ) {
				$offset = ( 0 === $js_options['datatables_pagination_scrolltotop_offset'] ) ? '' : "-{$js_options['datatables_pagination_scrolltotop_offset']}";

				/*
				* Use a minified version of this callback to scroll to the top of the table when changing pages (plus an optional offset).
				* `closest( '.dt-layout-table' )` is used to find the table layout container, as using the table itself does not work with Horizontal Scrolling or FixedHeader.
				* `setTimeout` is used to ensure that the position calculations and the scroll only happens after the table has been redrawn after paging.
				*
				* {$name}.on( 'page.dt', () => {
				*   setTimeout( () =>{
				*     const table = {$name}.table().node().closest( '.dt-layout-table' );
				*     if ( table.getBoundingClientRect().top < {$js_options['datatables_pagination_scrolltotop_offset']} ) {
				*       $( document ).scrollTop( $( table ).offset().top {$offset} );
				*     }
				*   }, 10);
				* } );
				*/
				$command .= "\n{$name}.on('page',()=>{setTimeout(()=>{let t={$name}.table().node().closest('.dt-layout-table');t.getBoundingClientRect().top<{$js_options['datatables_pagination_scrolltotop_offset']}&&$(document).scrollTop($(t).offset().top{$offset})},10)});";
			}
		}

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
			add_meta_box( 'tablepress_edit-datatables-pagination', __( 'Advanced Pagination Settings', 'tablepress' ), array( __CLASS__, 'print_postbox_markup' ), null, 'normal', 'low' );

			TablePress_Modules_Helper::enqueue_style( 'datatables-pagination', array( 'tablepress-modules-common' ) );
			TablePress_Modules_Helper::enqueue_script( 'datatables-pagination' );
			add_filter( 'tablepress_admin_page_script_dependencies', array( __CLASS__, 'add_script_dependencies' ), 10, 2 );
		}
		return $data;
	}

} // class TablePress_Module_DataTables_Pagination
