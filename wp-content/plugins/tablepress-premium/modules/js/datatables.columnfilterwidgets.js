/*
 * File:        ColumnFilterWidgets.js
 * Version:     2.0.0
 * Description: Controls for filtering based on unique column values in DataTables
 * Author:      Dylan Kuhn (www.cyberhobo.net)
 * Language:    Javascript
 * License:     GPL v2 or BSD 3 point style
 * Contact:     cyberhobo@cyberhobo.net
 *
 * Copyright 2011 Dylan Kuhn (except fnGetColumnData by Benedikt Forchhammer), all rights reserved.
 *
 * This source file is free software, under either the GPL v2 license or a
 * BSD style license, available at:
 *   https://datatables.net/license/gpl2
 *   https://datatables.net/license/bsd
 *
 * With modifications regarding compatibility with DataTables 2, empty cells, special characters like &, HTML, and sorting by Tobias Bäthge.
 */

/* globals jQuery, DataTable */

( function ( $ ) {
	/**
	 * Menu-based filter widgets based on distinct column values for a table.
	 *
	 * @class ColumnFilterWidgets
	 * @constructor
	 * @param {Object} oDataTableSettings Settings for the target table.
	 */
	const ColumnFilterWidgets = function ( oDataTableSettings ) {
		const me = this;
		let columns = Object.keys( oDataTableSettings.aoColumns );
		columns = columns.map( ( column ) => ( parseInt( column, 10 ) ) );
		me.$WidgetContainer = $( '<div class="column-filter-widgets"></div>' );
		me.$MenuContainer = me.$WidgetContainer;
		me.$TermContainer = null;
		me.aoWidgets = [];
		me.sSeparator = '';
		if ( 'oColumnFilterWidgets' in oDataTableSettings.oInit ) {
			if ( 'columns' in oDataTableSettings.oInit.oColumnFilterWidgets ) {
				columns = oDataTableSettings.oInit.oColumnFilterWidgets.columns;
			}
			if ( 'aiExclude' in oDataTableSettings.oInit.oColumnFilterWidgets ) {
				columns = columns.filter( ( column ) => ( ! oDataTableSettings.oInit.oColumnFilterWidgets.aiExclude.includes( column ) ) );
			}
			if ( 'bGroupTerms' in oDataTableSettings.oInit.oColumnFilterWidgets && oDataTableSettings.oInit.oColumnFilterWidgets.bGroupTerms ) {
				me.$MenuContainer = $( '<div class="column-filter-widget-menus"></div>' );
				me.$TermContainer = $( '<div class="column-filter-widget-selected-terms"></div>' ).hide();
			}
		}

		// Add a widget for each visible and filtered column.
		columns.forEach( ( columnIdx ) => {
			const $WidgetElem = $( '<div class="column-filter-widget"></div>' );
			me.aoWidgets.push( new ColumnFilterWidget( $WidgetElem, oDataTableSettings, columnIdx, me ) );
			me.$MenuContainer.append( $WidgetElem );
		} );
		if ( me.$TermContainer ) {
			me.$WidgetContainer.append( me.$MenuContainer );
			me.$WidgetContainer.append( me.$TermContainer );
		}
		oDataTableSettings.oInstance.api().on( 'draw', () => {
			me.aoWidgets.forEach( ( widget ) => ( widget.fnDraw() ) );
		} );

		return me;
	};

	/**
	 * Get the container node of the column filter widgets.
	 *
	 * @method
	 * @return {Node} The container node.
	 */
	ColumnFilterWidgets.prototype.getContainer = function () {
		return this.$WidgetContainer.get( 0 );
	};

	/**
	 * A filter widget based on data in a table column.
	 *
	 * @class ColumnFilterWidget
	 * @constructor
	 * @param {Object} $Container         The jQuery object that should contain the widget.
	 * @param {Object} oDataTableSettings The target table's settings.
	 * @param {number} i                  The numeric index of the target table column.
	 * @param {Object} widgets            The ColumnFilterWidgets instance the widget is a member of.
	 */
	const ColumnFilterWidget = function ( $Container, oDataTableSettings, i, widgets ) {
		const widget = this;
		widget.iColumn = i;
		widget.oColumn = oDataTableSettings.aoColumns[ i ];
		widget.$Container = $Container;
		widget.oDataTable = oDataTableSettings.oInstance.api();
		widget.asFilters = [];
		widget.sSeparator = '';
		widget.bSort = true;
		widget.iMaxSelections = -1;
		if ( 'oColumnFilterWidgets' in oDataTableSettings.oInit ) {
			if ( 'sSeparator' in oDataTableSettings.oInit.oColumnFilterWidgets ) {
				widget.sSeparator = oDataTableSettings.oInit.oColumnFilterWidgets.sSeparator;
			}
			if ( 'iMaxSelections' in oDataTableSettings.oInit.oColumnFilterWidgets ) {
				widget.iMaxSelections = oDataTableSettings.oInit.oColumnFilterWidgets.iMaxSelections;
			}
			if ( 'aoColumnDefs' in oDataTableSettings.oInit.oColumnFilterWidgets ) {
				oDataTableSettings.oInit.oColumnFilterWidgets.aoColumnDefs.forEach( ( columnDef ) => {
					if ( columnDef.aiTargets.includes( i ) ) {
						$.each( columnDef, ( sDef, oDef ) => ( widget[ sDef ] = oDef ) );
					}
				} );
			}
		}
		widget.$Select = $( '<select></select>' ).addClass( 'widget-' + widget.iColumn ).on( 'change', function () {
			const sSelected = widget.$Select.val();

			if ( 1 === widget.iMaxSelections ) {
				// Handling for the case with just one allowed selection, where the dropdown behaves like a normal select.
				if ( '' === sSelected ) {
					widget.asFilters = [];
				} else {
					widget.asFilters = [ sSelected ];
				}
			} else {
				// Handling for the case with multiple selections.
				if ( '' === sSelected ) {
					// The blank option is a default, not a filter, and is re-selected after filtering.
					return;
				}
				const sText = $( '<div>' + sSelected + '</div>' ).text();
				const $TermLink = $( '<a class="filter-term" href="#"></a>' )
					.addClass( 'filter-term-' + sText.toLowerCase().replace( /\W/g, '' ) )
					.text( sText )
					.on( 'click', function () {
						// Remove from current filters array.
						widget.asFilters = widget.asFilters.filter( ( sFilter ) => ( sFilter !== sSelected ) );
						$TermLink.remove();
						if ( widgets.$TermContainer && 0 === widgets.$TermContainer.find( '.filter-term' ).length ) {
							widgets.$TermContainer.hide();
						}
						// Add it back to the select.
						widget.$Select.append( $( '<option></option>' ).val( sSelected ).text( sText ) );
						if ( widget.iMaxSelections > 1 && widget.iMaxSelections > widget.asFilters.length ) {
							widget.$Select.prop( 'disabled', false );
						}
						if ( widget.bSort ) {
							widget.fnSortOptions();
						}
						widget.fnFilter();
						return false;
					} );
				widget.asFilters.push( sSelected );
				if ( widgets.$TermContainer ) {
					widgets.$TermContainer.show();
					widgets.$TermContainer.prepend( $TermLink );
				} else {
					widget.$Select.after( $TermLink );
				}
				const $SelectedOption = widget.$Select.children( 'option:selected' );
				widget.$Select.val( '' );
				$SelectedOption.remove();
				if ( widget.iMaxSelections > 1 && widget.iMaxSelections <= widget.asFilters.length ) {
					widget.$Select.prop( 'disabled', true );
				}
			}

			widget.fnFilter();
		} );
		widget.$Container.append( widget.$Select );
		widget.fnDraw();
	};

	/**
	 * Perform filtering on the target column.
	 *
	 * @method fnFilter
	 */
	ColumnFilterWidget.prototype.fnFilter = function () {
		const widget = this;
		if ( widget.asFilters.length > 0 ) {
			const asEscapedFilters = [];
			// Filters must have RegExp symbols escaped.
			widget.asFilters.forEach( ( sFilter ) => {
				// Add backslashes to regular expression symbols in a string.
				asEscapedFilters.push( sFilter.replace( /[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&' ) );
			} );
			// This regular expression filters by either whole column values or an item in a comma list.
			const sFilterStart = widget.sSeparator ? '(^|' + widget.sSeparator + ')(' : '^(';
			const sFilterEnd = widget.sSeparator ? ')(' + widget.sSeparator + '|$)' : ')$';
			widget.oDataTable.column( widget.iColumn ).search( sFilterStart + asEscapedFilters.join( '|' ) + sFilterEnd, true, false ).draw();
		} else {
			// Clear any filters for this column.
			widget.oDataTable.column( widget.iColumn ).search( '' ).draw();
		}
	};

	/**
	 * Sort the widget menu options, using a custom function if one was supplied.
	 *
	 * @method fnSortOptions
	 */
	ColumnFilterWidget.prototype.fnSortOptions = function () {
		const widget = this;
		const $options = widget.$Select.find( 'option' ).slice( 1 );

		// Default sort function.
		let fnSort = ( a, b ) => {
			return $( a ).text().localeCompare( $( b ).text(), undefined, {
				numeric: true,
				sensitivity: 'base',
			} );
		};

		// If defined, use a custom sort function instead.
		if ( widget.hasOwnProperty( 'fnSort' ) ) {
			fnSort = ( a, b ) => ( widget.fnSort( $( a ).text(), $( b ).text() ) );
		}

		$options.sort( fnSort );
		widget.$Select.append( $options );
	};

	/**
	 * On each table draw, update filter menu items as needed. This allows any process to
	 * update the table's column visibility and menus will still be accurate.
	 *
	 * @method fnDraw
	 */
	ColumnFilterWidget.prototype.fnDraw = function () {
		const widget = this;
		if ( widget.asFilters.length === 0 ) {
			const distinctOptions = [];
			// Find distinct column values.
			const aData = widget.oDataTable.column(widget.iColumn, { search: 'applied' }).data().sort().unique().toArray();
			aData.forEach( ( sValue ) => {
				const asValues = widget.sSeparator ? sValue.split( new RegExp( widget.sSeparator ) ) : [ sValue ];
				asValues.forEach( ( sOption ) => {
					sOption = $( '<div>' + sOption + '</div>' ).text().replace( /\n/g, ' ' );
					if ( '' !== sOption && ! distinctOptions.includes( sOption ) ) {
						distinctOptions.push( sOption );
					}
				} );
			} );
			// Build the menu.
			const title = $( '<div>' + widget.oColumn.sTitle + '</div>' ).text();
			widget.$Select.attr( 'aria-label', widget.oDataTable.i18n( 'dropdownFilterBy', `Filter by column “${title}”` ) ).empty().append( $( `<option selected${ widget.iMaxSelections > 1 ? ' disabled' : '' }></option>` ).val( '' ).text( title ) );
			distinctOptions.forEach( ( text ) => {
				widget.$Select.append( $( '<option></option>' ).val( text ).text( text ) );
			} );
			if ( distinctOptions.length > 0 ) {
				if ( widget.bSort ) {
					widget.fnSortOptions();
				}
				// Enable the menu.
				widget.$Select.prop( 'disabled', false );
			} else {
				// No option is not a useful menu, disable it.
				widget.$Select.prop( 'disabled', true );
			}
		}
	};

	/*
	 * Register a new feature with DataTables.
	 */
	DataTable.ext.feature.push( {
		fnInit( settings ) {
			const widgets = new ColumnFilterWidgets( settings );
			return widgets.getContainer();
		},
		cFeature: 'W',
		sFeature: 'ColumnFilterWidgets',
	} );

	DataTable.feature.register( 'columnFilterWidgets', function ( settings /*, opts */ ) {
		const widgets = new ColumnFilterWidgets( settings );
		return widgets.getContainer();
	} );

}( jQuery ) );
