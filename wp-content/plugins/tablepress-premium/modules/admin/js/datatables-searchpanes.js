/**
 * JavaScript code for the "Edit" section integration of the DataTables SearchPanes feature.
 *
 * @package TablePress
 * @subpackage DataTables SearchPanes
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
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-searchpanes';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const searchPanesEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const invertedFilterEnabled = ( features.includes( 'datatables-inverted-filter' ) && tableOptions.datatables_inverted_filter );

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_searchpanes && '' !== tableOptions.datatables_searchpanes_columns.trim() ) {
			document.getElementById( 'tablepress-datatables_searchpanes-advanced-settings' ).open = true;
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
					! searchPanesEnabled && (
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
					label={ __( 'Show panes for filtering the columns.', 'tablepress' ) }
					checked={ tableOptions.datatables_searchpanes }
					disabled={ ! searchPanesEnabled || serversideProcessingEnabled || invertedFilterEnabled }
					onChange={ ( datatables_searchpanes ) => updateTableOptions( { datatables_searchpanes } ) }
				/>
				<details id="tablepress-datatables_searchpanes-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_searchpanes_columns">{ __( 'Columns', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-datatables_searchpanes_columns"
											title={ __( 'This field can only contain letters, numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
											pattern="[0-9A-Z, \-]*"
											value={ tableOptions.datatables_searchpanes_columns }
											disabled={ ! tableOptions.datatables_searchpanes || ! searchPanesEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											onChange={ ( datatables_searchpanes_columns ) => updateTableOptions( { datatables_searchpanes_columns: datatables_searchpanes_columns.replace( /[^0-9A-Z, -]/g, '' ) } ) }
											help={ __( 'Enter a comma-separated list of the columns for which a search pane should be shown, in the desired order, e.g. “3-5,1,7”. By default, the visible searches panes will be determined automatically.', 'tablepress' ) }
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

// Register the "SearchPanes" feature for the "DataTables Layout" feature module.
addFilter( 'tablepress.dataTablesLayoutFeatures', `tp/${ MODULE_SLUG }/add-datatables_layout-features`, ( features ) => {
	return {
		...features,
		searchPanes: {
			label: __( 'Search Panes', 'tablepress' ),
			defaultPosition: 'top',
			option: 'datatables_searchpanes',
			requirements: ( tableOptions ) => ( tableOptions.datatables_filter && tableOptions.datatables_searchpanes ),
		},
	};
} );
