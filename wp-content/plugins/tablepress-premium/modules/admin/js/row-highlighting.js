/**
 * JavaScript code for the "Edit" section integration of the Row Highlighting feature.
 *
 * @package TablePress
 * @subpackage Row Highlighting
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect } from 'react';
import {
	CheckboxControl,
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'row-highlighting';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const advancedLoadingEnabled = ( features.includes( 'datatables-advanced-loading' ) && tableOptions.datatables_advanced_loading );
	const rowHighlightSettingsDisabled = '' === tableOptions.row_highlight.trim() || '' !== tableOptions.row_highlight_expression.trim() || serversideProcessingEnabled || advancedLoadingEnabled;

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( '' !== tableOptions.row_highlight.trim() && (
			! tableOptions.row_highlight_full_cell_match ||
			tableOptions.row_highlight_case_sensitive ||
			'' !== tableOptions.row_highlight_columns.trim() ||
			'' !== tableOptions.row_highlight_rows.trim() ||
			'' !== tableOptions.row_highlight_expression.trim()
			) ) {
			document.getElementById( 'tablepress-row_highlight-advanced-settings' ).open = true;
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

	return (
		<>
			<ModuleHelp slug={ MODULE_SLUG } />
			<VStack
				spacing="16px"
				style={ {
					paddingTop: '6px',
				} }
			>
				{
					( serversideProcessingEnabled || advancedLoadingEnabled ) && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s” feature is turned off.', 'tablepress' ),
										serversideProcessingEnabled ? __( 'Server-side Processing', 'tablepress' ) : __( 'Advanced Loading', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				<table className="tablepress-postbox-table fixed">
					<tbody>
						<tr>
							<th className="column-1 top-align" scope="row"><label htmlFor="option-row_highlight">{ __( 'Row Highlight term', 'tablepress' ) }:</label></th>
							<td className="column-2">
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									id="option-row_highlight"
									value={ tableOptions.row_highlight }
									disabled={ serversideProcessingEnabled || advancedLoadingEnabled || '' !== tableOptions.row_highlight_expression.trim() }
									onChange={ ( row_highlight ) => updateTableOptions( { row_highlight } ) }
									help={ __( 'Rows that contain this term will be highlighted.', 'tablepress' ) + ' ' + __( 'You can combine multiple highlight terms with an OR operator, e.g. “term1||term2”.', 'tablepress' ) }
								/>
							</td>
						</tr>
					</tbody>
				</table>
				<details id="tablepress-row_highlight-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Full cell matching', 'tablepress' ) }:</th>
									<td className="column-2">
										<CheckboxControl
											__nextHasNoMarginBottom
											label={ __( 'The full cell content has to match the highlight term.', 'tablepress' ) }
											checked={ tableOptions.row_highlight_full_cell_match }
											disabled={ rowHighlightSettingsDisabled }
											onChange={ ( row_highlight_full_cell_match ) => updateTableOptions( { row_highlight_full_cell_match } ) }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Case sensitivity', 'tablepress' ) }:</th>
									<td className="column-2">
										<CheckboxControl
											__nextHasNoMarginBottom
											label={ __( 'The case sensitivity of the highlight term has to match the content in the cell.', 'tablepress' ) }
											checked={ tableOptions.row_highlight_case_sensitive }
											disabled={ rowHighlightSettingsDisabled }
											onChange={ ( row_highlight_case_sensitive ) => updateTableOptions( { row_highlight_case_sensitive } ) }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-row_highlight_columns">{ __( 'Highlight Columns', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-row_highlight_columns"
											title={ __( 'This field can only contain letters, numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
											pattern="[0-9A-Z, \-]*"
											value={ tableOptions.row_highlight_columns }
											disabled={ rowHighlightSettingsDisabled }
											onChange={ ( row_highlight_columns ) => updateTableOptions( { row_highlight_columns: row_highlight_columns.replace( /[^0-9A-Z, -]/g, '' ) } ) }
											help={ __( 'Enter a comma-separated list of the columns which shall be searched for the highlight terms, e.g. “1,3-5,7”.', 'tablepress' ) }
										/>
									</td>
								</tr>
								<tr className="top-border bottom-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-row_highlight_rows">{ __( 'Highlight Rows', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-row_highlight_rows"
											title={ __( 'This field can only contain numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
											pattern="[0-9, \-]*"
											value={ tableOptions.row_highlight_rows }
											disabled={ rowHighlightSettingsDisabled }
											onChange={ ( row_highlight_rows ) => updateTableOptions( { row_highlight_rows: row_highlight_rows.replace( /[^0-9, -]/g, '' ) } ) }
											help={ __( 'Enter a comma-separated list of the rows which shall be searched for the highlight terms, e.g. “1,3-5,7”.', 'tablepress' ) }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-row_highlight_expression">{ __( 'Row Highlight expression', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-row_highlight_expression"
											value={ tableOptions.row_highlight_expression }
											disabled={ serversideProcessingEnabled || advancedLoadingEnabled || '' !== tableOptions.row_highlight.trim() }
											onChange={ ( row_highlight_expression ) => updateTableOptions( { row_highlight_expression } ) }
											help={
												createInterpolateElement(
													__( 'For more complex row highlighting, e.g. based on comparisons, enter a logic expression to add custom CSS classes to rows that fulfill the expression.', 'tablepress' )
													+ ' '
													+ __( 'For details and examples see the <a>logic expression documentation</a>.', 'tablepress' )
													+ ' '
													+ __( 'For advanced use only.', 'tablepress' ),
													{
														a: <a href="https://tablepress.org/modules/row-highlighting/#logic-expressions" />, // eslint-disable-line jsx-a11y/anchor-has-content
													},
												)
											}
										/>
									</td>
								</tr>
							</tbody>
						</table>
					</VStack>
				</details>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);
