/**
 * JavaScript code for the "Edit" section integration of the Cell Highlighting feature.
 *
 * @package TablePress
 * @subpackage Cell Highlighting
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
import ModuleHelp from './common/module-help';
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';

const MODULE_SLUG = 'cell-highlighting';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const advancedLoadingEnabled = ( features.includes( 'datatables-advanced-loading' ) && tableOptions.datatables_advanced_loading );
	const highlightSettingsDisabled = '' === tableOptions.highlight.trim() || '' !== tableOptions.highlight_expression.trim() || serversideProcessingEnabled || advancedLoadingEnabled;

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( '' !== tableOptions.highlight.trim() && (
			tableOptions.highlight_full_cell_match ||
			tableOptions.highlight_case_sensitive ||
			'' !== tableOptions.highlight_columns.trim() ||
			'' !== tableOptions.highlight_expression.trim()
			) ) {
			document.getElementById( 'tablepress-highlight-advanced-settings' ).open = true;
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
							<th className="column-1 top-align" scope="row"><label htmlFor="option-highlight">{ __( 'Cell Highlight term', 'tablepress' ) }:</label></th>
							<td className="column-2">
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									id="option-highlight"
									value={ tableOptions.highlight }
									disabled={ serversideProcessingEnabled || advancedLoadingEnabled || '' !== tableOptions.highlight_expression.trim() }
									onChange={ ( highlight ) => updateTableOptions( { highlight } ) }
									help={ __( 'Cells that contain this term will be highlighted.', 'tablepress' ) + ' ' + __( 'You can combine multiple highlight terms with an OR operator, e.g. “term1||term2”.', 'tablepress' ) }
								/>
							</td>
						</tr>
					</tbody>
				</table>
				<details id="tablepress-highlight-advanced-settings">
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
											checked={ tableOptions.highlight_full_cell_match }
											disabled={ highlightSettingsDisabled }
											onChange={ ( highlight_full_cell_match ) => updateTableOptions( { highlight_full_cell_match } ) }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Case sensitivity', 'tablepress' ) }:</th>
									<td className="column-2">
										<CheckboxControl
											__nextHasNoMarginBottom
											label={ __( 'The case sensitivity of the highlight term has to match the content in the cell.', 'tablepress' ) }
											checked={ tableOptions.highlight_case_sensitive }
											disabled={ highlightSettingsDisabled }
											onChange={ ( highlight_case_sensitive ) => updateTableOptions( { highlight_case_sensitive } ) }
										/>
									</td>
								</tr>
								<tr className="top-border bottom-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-highlight_columns">{ __( 'Highlight Columns', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-highlight_columns"
											title={ __( 'This field can only contain letters, numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
											pattern="[0-9A-Z, \-]*"
											value={ tableOptions.highlight_columns }
											disabled={ highlightSettingsDisabled }
											onChange={ ( highlight_columns ) => updateTableOptions( { highlight_columns: highlight_columns.replace( /[^0-9A-Z, -]/g, '' ) } ) }
											help={ __( 'Enter a comma-separated list of the columns which shall be searched for the highlight terms, e.g. “1,3-5,7”.', 'tablepress' ) }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-highlight_expression">{ __( 'Cell Highlight expression', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-highlight_expression"
											value={ tableOptions.highlight_expression }
											disabled={ serversideProcessingEnabled || advancedLoadingEnabled || '' !== tableOptions.highlight.trim() }
											onChange={ ( highlight_expression ) => updateTableOptions( { highlight_expression } ) }
											help={
												createInterpolateElement(
													__( 'For more complex cell highlighting, e.g. based on comparisons, enter a logic expression to add custom CSS classes to cells that fulfill the expression.', 'tablepress' )
													+ ' '
													+ __( 'For details and examples see the <a>logic expression documentation</a>.', 'tablepress' )
													+ ' '
													+ __( 'For advanced use only.', 'tablepress' ),
													{
														a: <a href="https://tablepress.org/modules/cell-highlighting/#logic-expressions" />, // eslint-disable-line jsx-a11y/anchor-has-content
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
