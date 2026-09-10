/**
 * JavaScript code for the "DataTables RowGroup" integration into the Default Style Customizer" feature on the "Plugin Options" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/* globals tablepress_default_style_customizer_datatables_rowgroup_settings */

/**
 * WordPress dependencies.
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

addFilter( 'tablepress.default-style-customizer.cssProperties', 'tp/datatables-rowgroup/add-cssProperties', ( cssProperties ) => {
	cssProperties[ '--rowgroup-text-color' ] = {
		name: __( 'Rowgroup Group Text', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	cssProperties[ '--rowgroup-bg-color' ] = {
		name: __( 'Rowgroup Group Background', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	return cssProperties;
} );

addFilter( 'tablepress.default-style-customizer.styleVariations', 'tp/datatables-rowgroup/add-styleVariations', ( styleVariations ) => {
	Object.keys( styleVariations ).forEach( ( styleVariation ) => {
		styleVariations[ styleVariation ].style[ '--rowgroup-text-color' ] = 'var(--text-color)';
		styleVariations[ styleVariation ].style[ '--rowgroup-bg-color' ] = '#e0e0e0';
	} );
	styleVariations.dark.style[ '--rowgroup-bg-color' ] = '#404040'; // The "Dark" style variation needs a darker background color.
	return styleVariations;
} );

addFilter( 'tablepress.default-style-customizer.sandboxCssImports', 'tp/datatables-rowgroup/add-sandboxCssImports', ( sandboxCssImports ) => {
	sandboxCssImports.push( `@import "${ tablepress_default_style_customizer_datatables_rowgroup_settings.cssUrl }";` );
	return sandboxCssImports;
} );

addFilter( 'tablepress.default-style-customizer.tableHtml', 'tp/datatables-rowgroup/add-tableHtml', tableHtml => {
	tableHtml = tableHtml.replaceAll( '<tr class="row-2">', `<tr class="dtrg-group dtrg-start dtrg-level-0"><th colspan="4" scope="row">New York</th></tr>
		<tr class="row-2">` );
	tableHtml = tableHtml.replaceAll( '<tr class="row-5">', `<tr class="dtrg-group dtrg-start dtrg-level-0"><th colspan="4" scope="row">Berlin</th></tr>
		<tr class="row-5">` );
	return tableHtml;
} );
