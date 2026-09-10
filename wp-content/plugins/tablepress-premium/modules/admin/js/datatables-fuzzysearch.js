/**
 * JavaScript code for the "Edit" section integration of the DataTables FuzzySearch feature.
 *
 * @package TablePress
 * @subpackage DataTables FuzzySearch
 * @author Tobias Bäthge
 * @since 2.4.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect } from 'react';
import {
	CheckboxControl,
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import { $ } from '../../../admin/js/common/functions';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-fuzzysearch';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const fuzzySearchEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const invertedFilterEnabled = ( features.includes( 'datatables-inverted-filter' ) && tableOptions.datatables_inverted_filter );

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_fuzzysearch && (
			0.5 !== tableOptions.datatables_fuzzysearch_threshold ||
			! tableOptions.datatables_fuzzysearch_togglesmart ||
			'' !== tableOptions.datatables_fuzzysearch_rankcolumn
			) ) {
			$( '#tablepress-datatables_fuzzysearch-advanced-settings' ).open = true;
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

	return (
		<>
			<ModuleHelp slug={ MODULE_SLUG }>
				<p>
					{ __( 'The “Fuzzy Search” module allows the table search to match results that are not necessarily exactly the same as the search term. Rows will be found even if a search term has typos, spelling mistakes, or is written in a dialect.', 'tablepress' ) }
				</p>
				<p>
					{ __( 'A common example for use of fuzzy search e.g. in databases is name searching. While “Smith” and “Smythe” are pronounced in the same way, a regular search for “Smith” would not find “Smythe”, whereas a fuzzy search would.', 'tablepress' ) }
				</p>
			</ModuleHelp>
			<VStack
				spacing="16px"
				style={ {
					paddingTop: '6px',
				} }
			>
				{
					! fuzzySearchEnabled && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s”, “%2$s”, and “%3$s” settings in the “%4$s” and “%5$s” sections are used.', 'tablepress' ),
										__( 'Table Header', 'tablepress' ),
										__( 'Enable Visitor Features', 'tablepress' ),
										__( 'Search/Filtering', 'tablepress' ),
										__( 'Table Options', 'tablepress' ),
										__( 'Table Features for Site Visitors', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				{
					( serversideProcessingEnabled || invertedFilterEnabled ) && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s” feature is turned off.', 'tablepress' ),
										serversideProcessingEnabled ? __( 'Server-side Processing', 'tablepress' ) : __( 'Inverted Filtering', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Activate fuzzy search for this table.', 'tablepress' ) }
					checked={ tableOptions.datatables_fuzzysearch }
					disabled={ ! fuzzySearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
					onChange={ ( datatables_fuzzysearch ) => updateTableOptions( { datatables_fuzzysearch } ) }
				/>
				<details id="tablepress-datatables_fuzzysearch-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Toggle control', 'tablepress' ) }:</th>
									<td className="column-2">
										<CheckboxControl
											__nextHasNoMarginBottom
											label={ __( 'Allow the visitor to switch between exact and fuzzy search.', 'tablepress' ) }
											checked={ tableOptions.datatables_fuzzysearch_togglesmart }
											disabled={ ! tableOptions.datatables_fuzzysearch || ! fuzzySearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											onChange={ ( datatables_fuzzysearch_togglesmart ) => updateTableOptions( { datatables_fuzzysearch_togglesmart } ) }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_fuzzysearch_threshold">{ __( 'Threshold', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<NumberControl
											__next40pxDefaultSize
											id="option-datatables_fuzzysearch_threshold"
											title={ __( 'Similarity score that needs to be reached.', 'tablepress' ) }
											isDragEnabled={ false }
											value={ tableOptions.datatables_fuzzysearch_threshold }
											disabled={ ! tableOptions.datatables_fuzzysearch || ! fuzzySearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											onChange={ ( datatables_fuzzysearch_threshold ) => {
												datatables_fuzzysearch_threshold = '' !== datatables_fuzzysearch_threshold ? parseFloat( datatables_fuzzysearch_threshold ) : 0;
												updateTableOptions( { datatables_fuzzysearch_threshold } );
											} }
											help={ __( 'The threshold, a number between 0 and 1, defines the similarity that is required for a row to be found.', 'tablepress' ) }
											min={ 0 }
											max={ 1 }
											step={ 0.1 }
											style={ {
												width: '65px',
											} }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_fuzzysearch_rankcolumn">{ __( 'Show rank column', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-datatables_fuzzysearch_rankcolumn"
											title={ __( 'This field can only contain a number or letters.', 'tablepress' ) }
											pattern="[1-9]?|[1-9][0-9]+|[A-Z]*"
											value={ tableOptions.datatables_fuzzysearch_rankcolumn }
											disabled={ ! tableOptions.datatables_fuzzysearch || ! fuzzySearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											onChange={ ( datatables_fuzzysearch_rankcolumn ) => updateTableOptions( { datatables_fuzzysearch_rankcolumn: datatables_fuzzysearch_rankcolumn.replace( /[^0-9A-Z]/g, '' ) } ) }
											help={ __( 'Enter a column number or letter, e.g. “1” or “C”, for a column that should show the search’s similarity score.', 'tablepress' ) }
											style={ {
												width: '65px',
											}}
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

addFilter( 'tablepress.optionsValidateFields', `tp/${ MODULE_SLUG }/validate-fields`, ( formValid, tableOptions ) => {
	// The "FuzzySearch rank column" field must be a number or a letter sequence, if set.
	if ( tableOptions.datatables_fuzzysearch && ! ( /^$|^([1-9][0-9]*|[A-Z]+)$/ ).test( tableOptions.datatables_fuzzysearch_rankcolumn ) ) {
		// This alert can not be replaced by the `Alert` component, as that does not stop the cmd+S keyboard shortcut from running.
		window.alert( sprintf( __( 'The entered value in the “%1$s” field is invalid.', 'tablepress' ), __( 'Show rank column', 'tablepress' ) ) );
		const $field = $( '#option-datatables_fuzzysearch_rankcolumn' );
		$field.closest( 'details' ).open = true;
		$field.focus();
		$field.select();
		formValid = false;
	}

	return formValid;
} );
