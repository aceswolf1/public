/**
 * JavaScript code for the "Responsive Tables" integration into the Default Style Customizer" feature on the "Plugin Options" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/* globals tablepress_default_style_customizer_responsive_tables_settings */

/**
 * WordPress dependencies.
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

addFilter( 'tablepress.default-style-customizer.cssProperties', 'tp/responsive-tables/add-cssProperties', ( cssProperties ) => {
	cssProperties[ '--responsive-collapse-expand-text-color' ] = {
		name: __( 'Responsiveness Collapse “+” Icon Text', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	cssProperties[ '--responsive-collapse-expand-bg-color' ] = {
		name: __( 'Responsiveness Collapse “+” Icon Background', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	cssProperties[ '--responsive-collapse-close-text-color' ] = {
		name: __( 'Responsiveness Collapse “-” Icon Text', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	cssProperties[ '--responsive-collapse-close-bg-color' ] = {
		name: __( 'Responsiveness Collapse “-” Icon Background', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	return cssProperties;
} );

addFilter( 'tablepress.default-style-customizer.styleVariations', 'tp/responsive-tables/add-styleVariations', ( styleVariations ) => {
	Object.keys( styleVariations ).forEach( ( styleVariation ) => {
		styleVariations[ styleVariation ].style[ '--responsive-collapse-expand-text-color' ] = '#ffffff';
		styleVariations[ styleVariation ].style[ '--responsive-collapse-expand-bg-color' ] = '#31b131';
		styleVariations[ styleVariation ].style[ '--responsive-collapse-close-text-color' ] = '#ffffff';
		styleVariations[ styleVariation ].style[ '--responsive-collapse-close-bg-color' ] = '#d33333';
	} );
	return styleVariations;
} );

addFilter( 'tablepress.default-style-customizer.sandboxCssImports', 'tp/responsive-tables/add-sandboxCssImports', ( sandboxCssImports ) => {
	sandboxCssImports.push( `@import "${ tablepress_default_style_customizer_responsive_tables_settings.cssUrl }";` );
	return sandboxCssImports;
} );

addFilter( 'tablepress.default-style-customizer.sandboxCssInline', 'tp/responsive-tables/add-sandboxCssInline', ( sandboxCssInline ) => {
	sandboxCssInline += `\n
.tablepress.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before,
.tablepress.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
	top: 6px;
	left: 4px;
}`;
	return sandboxCssInline;
} );

addFilter( 'tablepress.default-style-customizer.tableHtml', 'tp/responsive-tables/add-tableHtml', ( tableHtml ) => {
	tableHtml = tableHtml.replaceAll( ' dataTable"', ' dataTable dtr-inline collapsed"' );
	tableHtml = tableHtml.replaceAll( '<tr class="row-6">', '<tr class="row-6 dtr-expanded">' );
	tableHtml = tableHtml.replaceAll( '<tr class="row-7">', `<tr class="child"><td class="child" colspan="4"><ul class="dtr-details"><li><span class="dtr-title">Job Title</span> <span class="dtr-data">Developer</span></li><li><span class="dtr-title">Sport</span> <span class="dtr-data">Baseball</span></li></ul></td></tr>
<tr class="row-7">` );
	tableHtml = tableHtml.replaceAll( '<td class="column-1">', '<td class="column-1 dtr-control">' );
	return tableHtml;
} );
