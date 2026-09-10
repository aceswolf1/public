/**
 * JavaScript code for the "Default Style Customizer Screen" sandbox data.
 *
 * @package TablePress
 * @subpackage Default Style Customizer Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/* globals tablepress_default_style_customizer_settings */

/**
 * WordPress dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

// Allow other modules to load CSS files in the sandbox.
const sandboxCssImports = applyFilters(
	'tablepress.default-style-customizer.sandboxCssImports',
	[
		`@import "${ tablepress_default_style_customizer_settings.defaultCssUrl }";`,
	]
);

// Allow other modules to load inline CSS code in the sandbox.
const sandboxCssInline = applyFilters(
	'tablepress.default-style-customizer.sandboxCssInline',
	`body {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	font-size: 14px;
}`
);

export const sandboxCss = sandboxCssImports.join( '\n' ) + '\n' + sandboxCssInline;

// Allow other modules to modify the HTML code in the sandbox.
export const tableHtml = applyFilters(
	'tablepress.default-style-customizer.tableHtml',
	`<div style="overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
<table id="tablepress-sandbox" class="tablepress tablepress-id-sandbox dataTable" style="margin-bottom: 0;">
<thead>
<tr class="row-1"><th class="column-1 dt-orderable-asc dt-orderable-desc dt-ordering-asc"><span class="dt-column-title" role="button">First Name</span><span class="dt-column-order"></span></th><th class="column-2 dt-orderable-asc dt-orderable-desc"><span class="dt-column-title" role="button">Last Name</span><span class="dt-column-order"></span></th><th class="column-3 dt-orderable-asc dt-orderable-desc"><span class="dt-column-title" role="button">Birthday</span><span class="dt-column-order"></span></th><th class="column-4 dt-orderable-asc dt-orderable-desc"><span class="dt-column-title" role="button">Points</span><span class="dt-column-order"></span></th></tr>
</thead>
<tbody class="row-striping row-hover">
<tr class="row-2"><td class="column-1">Abra</td><td class="column-2">House</td><td class="column-3">08/10/1980</td><td class="column-4">6</td></tr>
<tr class="row-3"><td class="column-1">Cameron</td><td class="column-2">Walls</td><td class="column-3">11/20/1981</td><td class="column-4">2</td></tr>
<tr class="row-4"><td class="column-1">Dillon</td><td class="column-2">Bradford</td><td class="column-3">01/20/1985</td><td class="column-4">7</td></tr>
<tr class="row-5"><td class="column-1">Fillian</td><td class="column-2">Julian</td><td class="column-3">05/12/1988</td><td class="column-4">10</td></tr>
<tr class="row-6"><td class="column-1">Graham</td><td class="column-2">Bonner</td><td class="column-3">12/07/1983</td><td class="column-4">4</td></tr>
<tr class="row-7"><td class="column-1">Haley</td><td class="column-2">Mcleod</td><td class="column-3">04/12/1980</td><td class="column-4">4</td></tr>
<tr class="row-8"><td class="column-1">Julia</td><td class="column-2">Haupt</td><td class="column-3">03/15/1991</td><td class="column-4">10</td></tr>
<tr class="row-9"><td class="column-1">Lionel</td><td class="column-2">Barry</td><td class="column-3">02/17/1980</td><td class="column-4">7</td></tr>
</tbody>
</table>
</div>`
);
