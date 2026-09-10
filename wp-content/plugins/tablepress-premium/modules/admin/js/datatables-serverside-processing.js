/**
 * JavaScript code for the "Edit" section integration of the DataTables Server-side Processing feature.
 *
 * @package TablePress
 * @subpackage DataTables Server-side Processing
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect } from 'react';
import {
	CheckboxControl,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-serverside-processing';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const serversideProcessingEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );
	const advancedLoadingEnabled = ( features.includes( 'datatables-advanced-loading' ) && tableOptions.datatables_advanced_loading );
	const paginationLoadMoreButtonActive = ( features.includes( 'datatables-pagination' ) && tableOptions.datatables_pagination_loadmore_button );

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_serverside_processing && (
			tableOptions.datatables_serverside_processing_cached_pages > 0 ||
			tableOptions.datatables_serverside_processing_periodic_refresh > 0
			) ) {
			document.getElementById( 'tablepress-datatables_serverside_processing-advanced-settings' ).open = true;
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
					! serversideProcessingEnabled && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s” and “%2$s” settings in the “%3$s” and “%4$s” sections are used.', 'tablepress' ),
										__( 'Table Header', 'tablepress' ),
										__( 'Enable Visitor Features', 'tablepress' ),
										__( 'Table Options', 'tablepress' ),
										__( 'Table Features for Site Visitors', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				{
					advancedLoadingEnabled && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s” feature is turned off.', 'tablepress' ),
										__( 'Advanced Loading', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Load the table via the TablePress REST API.', 'tablepress' ) }
					checked={ tableOptions.datatables_serverside_processing }
					disabled={ ! serversideProcessingEnabled || advancedLoadingEnabled }
					onChange={ ( datatables_serverside_processing ) => {
						const updatedTableOptions = { datatables_serverside_processing };
						if ( datatables_serverside_processing ) {
							updatedTableOptions.datatables_advanced_loading = false;
							updatedTableOptions.datatables_alphabetsearch = false;
							if ( 'select' === tableOptions.datatables_column_filter ) {
								updatedTableOptions.datatables_column_filter = '';
							}
							updatedTableOptions.datatables_columnfilterwidgets = false;
							updatedTableOptions.datatables_fuzzysearch = false;
							updatedTableOptions.datatables_inverted_filter = false;
							updatedTableOptions.datatables_searchbuilder = false;
							updatedTableOptions.datatables_searchpanes = false;
							updatedTableOptions.highlight = '';
							updatedTableOptions.row_highlight = '';
						}
						updateTableOptions( updatedTableOptions );
					} }
				/>
				<details id="tablepress-datatables_serverside_processing-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						{
							paginationLoadMoreButtonActive && (
								<span>
									<em>
										{
											sprintf(
												__( 'These settings are not available because the “%1$s” feature is turned on.', 'tablepress' ),
												__( 'Pagination ‘Show More’ Button', 'tablepress' ),
											)
										}
									</em>
								</span>
							)
						}
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Cache', 'tablepress' ) }:</th>
									<td className="column-2">
										<label htmlFor="option-datatables_serverside_processing_cached_pages">
											<HStack alignment="left">
												{
													createInterpolateElement(
														__( 'Cache <input /> pages to reduce requests to the server.', 'tablepress' ),
														{
															input: (
																<NumberControl
																	size="compact"
																	id="option-datatables_serverside_processing_cached_pages"
																	title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
																	isDragEnabled={ false }
																	value={ tableOptions.datatables_serverside_processing_cached_pages }
																	disabled={ ! serversideProcessingEnabled || advancedLoadingEnabled || paginationLoadMoreButtonActive || ! tableOptions.datatables_serverside_processing }
																	onChange={ ( datatables_serverside_processing_cached_pages ) => {
																		datatables_serverside_processing_cached_pages = '' !== datatables_serverside_processing_cached_pages ? parseInt( datatables_serverside_processing_cached_pages, 10 ) : 0;
																		const updatedTableOptions = { datatables_serverside_processing_cached_pages };
																		if ( datatables_serverside_processing_cached_pages > 0 ) {
																			updatedTableOptions.datatables_serverside_processing_periodic_refresh = 0;
																		}
																		updateTableOptions( updatedTableOptions );
																	} }
																	min={ 0 }
																	max={ 10 }
																	required={ true }
																	style={ {
																		width: '65px',
																	} }
																/>
															),
														},
													)
												}
											</HStack>
										</label>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row">{ __( 'Periodic Refresh', 'tablepress' ) }:</th>
									<td className="column-2">
										<VStack>
											<label htmlFor="option-datatables_serverside_processing_periodic_refresh">
												<HStack alignment="left">
													{
														createInterpolateElement(
															__( 'Automatically reload the table data every <input /> seconds.', 'tablepress' ),
															{
																input: (
																	<NumberControl
																		size="compact"
																		id="option-datatables_serverside_processing_periodic_refresh"
																		title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
																		isDragEnabled={ false }
																		value={ tableOptions.datatables_serverside_processing_periodic_refresh }
																		disabled={ ! serversideProcessingEnabled || advancedLoadingEnabled || paginationLoadMoreButtonActive || ! tableOptions.datatables_serverside_processing }
																		onChange={ ( datatables_serverside_processing_periodic_refresh ) => {
																			datatables_serverside_processing_periodic_refresh = '' !== datatables_serverside_processing_periodic_refresh ? parseInt( datatables_serverside_processing_periodic_refresh, 10 ) : 0;
																			const updatedTableOptions = { datatables_serverside_processing_periodic_refresh };
																			if ( datatables_serverside_processing_periodic_refresh > 0 ) {
																				updatedTableOptions.datatables_serverside_processing_cached_pages = 0;
																			}
																			updateTableOptions( updatedTableOptions );
																		} }
																		min={ 0 }
																		required={ true }
																		style={ {
																			width: '65px',
																		} }
																	/>
																),
															},
														)
													}
												</HStack>
											</label>
											<p className="description">
												{ __( 'This allows site visitors to stay on the page while always getting the latest data, which is useful for often updated tables, like race result tables or a leader board.', 'tablepress' ) }
												{ __( 'The refresh interval should be chosen reasonably big, to not overload the server.', 'tablepress' ) }
											</p>
										</VStack>
									</td>
								</tr>
							</tbody>
						</table>
					</VStack>
				</details>
				<span>
					<em>
						{ __( 'Please note that features like “Advanced Loading”, “Alphabet Search”, “Cell Highlighting”, “Column Filter Dropdowns”, “Custom Search Builder”, “Fuzzy Search”, “Individual Column Filtering”, “Inverted Filtering”, “Row Highlighting”, and “Search Panes” are not fully compatible with Server-side Processing.', 'tablepress' ) }
					</em>
				</span>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);
