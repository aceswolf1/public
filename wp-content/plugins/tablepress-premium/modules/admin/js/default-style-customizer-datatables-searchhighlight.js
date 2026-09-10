/**
 * JavaScript code for the "DataTables SearchHighlight" integration into the Default Style Customizer" feature on the "Plugin Options" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/* globals tablepress_default_style_customizer_datatables_searchhighlight_settings */

/**
 * WordPress dependencies.
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

addFilter( 'tablepress.default-style-customizer.cssProperties', 'tp/datatables-searchhighlight/add-cssProperties', ( cssProperties ) => {
	cssProperties[ '--searchhighlight-text-color' ] = {
		name: __( 'Search Highlight Text', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	cssProperties[ '--searchhighlight-bg-color' ] = {
		name: __( 'Search Highlight Background', 'tablepress' ),
		type: 'color',
		category: 'ui',
	};
	return cssProperties;
} );

addFilter( 'tablepress.default-style-customizer.styleVariations', 'tp/datatables-searchhighlight/add-styleVariations', ( styleVariations ) => {
	Object.keys( styleVariations ).forEach( ( styleVariation ) => {
		styleVariations[ styleVariation ].style[ '--searchhighlight-text-color' ] = 'var(--text-color)';
		styleVariations[ styleVariation ].style[ '--searchhighlight-bg-color' ] = '#ffff88';
	} );
	return styleVariations;
} );

addFilter( 'tablepress.default-style-customizer.sandboxCssImports', 'tp/datatables-searchhighlight/add-sandboxCssImports', ( sandboxCssImports ) => {
	sandboxCssImports.push( `@import "${ tablepress_default_style_customizer_datatables_searchhighlight_settings.cssUrl }";` );
	return sandboxCssImports;
} );

addFilter( 'tablepress.default-style-customizer.tableHtml', 'tp/datatables-searchhighlight/add-tableHtml', ( tableHtml ) => {
	tableHtml = tableHtml.replaceAll( 'Julia', '<span class="highlight">Julia</span>' );
	return tableHtml;
} );
