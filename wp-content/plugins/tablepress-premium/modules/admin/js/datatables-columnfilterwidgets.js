/**
 * JavaScript code for the "Edit" section integration of the DataTables ColumnFilterWidgets feature.
 *
 * @package TablePress
 * @subpackage DataTables ColumnFilterWidgets
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
	Icon,
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { info } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-columnfilterwidgets';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const columnFilterWidgetsEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const invertedFilterEnabled = ( features.includes( 'datatables-inverted-filter' ) && tableOptions.datatables_inverted_filter );
	const columnFilterWidgetsSettingsDisabled = ! tableOptions.datatables_columnfilterwidgets || ! columnFilterWidgetsEnabled || serversideProcessingEnabled || invertedFilterEnabled;

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_columnfilterwidgets && (
			'' !== tableOptions.datatables_columnfilterwidgets_columns.trim() ||
			'' !== tableOptions.datatables_columnfilterwidgets_exclude_columns.trim() ||
			'' !== tableOptions.datatables_columnfilterwidgets_separator.trim() ||
			'' !== tableOptions.datatables_columnfilterwidgets_max_selections.trim() ||
			tableOptions.datatables_columnfilterwidgets_group_terms
			) ) {
			document.getElementById( 'tablepress-datatables_columnfilterwidgets-advanced-settings' ).open = true;
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
					! columnFilterWidgetsEnabled && (
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
					label={ __( 'Add Column Filter Dropdowns.', 'tablepress' ) }
					checked={ tableOptions.datatables_columnfilterwidgets }
					disabled={ ! columnFilterWidgetsEnabled || serversideProcessingEnabled || invertedFilterEnabled }
					onChange={ ( datatables_columnfilterwidgets ) => updateTableOptions( { datatables_columnfilterwidgets } ) }
				/>
				<details id="tablepress-datatables_columnfilterwidgets-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_columnfilterwidgets_columns">{ __( 'Columns', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-datatables_columnfilterwidgets_columns"
											title={ __( 'This field can only contain letters, numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
											pattern="[0-9A-Z, \-]*"
											value={ tableOptions.datatables_columnfilterwidgets_columns }
											disabled={ columnFilterWidgetsSettingsDisabled }
											onChange={ ( datatables_columnfilterwidgets_columns ) => updateTableOptions( { datatables_columnfilterwidgets_columns: datatables_columnfilterwidgets_columns.replace( /[^0-9A-Z, -]/g, '' ) } ) }
											help={ __( 'Enter a comma-separated list of the columns for which a dropdown should be shown, in the desired order, e.g. “3-5,1,7”. By default, all columns will get a dropdown.', 'tablepress' ) }
										/>
									</td>
								</tr>
								{
									// Hide the "Excluded Columns" field if it is empty, as a soft-deprecation in favor of the "Columns" field.
									( '' !== tableOptions.datatables_columnfilterwidgets_exclude_columns.trim() ) && (
										<tr className="top-border">
											<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_columnfilterwidgets_exclude_columns">{ __( 'Excluded Columns', 'tablepress' ) }:</label></th>
											<td className="column-2">
												<TextControl
													__nextHasNoMarginBottom
													__next40pxDefaultSize
													id="option-datatables_columnfilterwidgets_exclude_columns"
													title={ __( 'This field can only contain letters, numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
													pattern="[0-9A-Z, \-]*"
													value={ tableOptions.datatables_columnfilterwidgets_exclude_columns }
													disabled={ columnFilterWidgetsSettingsDisabled }
													onChange={ ( datatables_columnfilterwidgets_exclude_columns ) => updateTableOptions( { datatables_columnfilterwidgets_exclude_columns: datatables_columnfilterwidgets_exclude_columns.replace( /[^0-9A-Z, -]/g, '' ) } ) }
													help={ __( 'Enter a comma-separated list of the columns which shall not get a filter dropdown, e.g. “1,3-5,7”.', 'tablepress' ) }
												/>
											</td>
										</tr>
									)
								}
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Filter Term Separator', 'tablepress' ) }:</th>
									<td className="column-2">
										<label htmlFor="option-datatables_columnfilterwidgets_separator">
											<HStack alignment="left">
												{
													createInterpolateElement(
														__( 'Split cell content by the string <input /> to get individual filter terms.', 'tablepress' ),
														{
															input: (
																<TextControl
																	__nextHasNoMarginBottom
																	__next40pxDefaultSize
																	id="option-datatables_columnfilterwidgets_separator"
																	title={ __( 'This field can only contain commas, semicolons, slashes, hyphens (-), and spaces.', 'tablepress' ) }
																	pattern="[,;\/ \-]*" // This pattern is not enforced to not prevent other character separators.
																	value={ tableOptions.datatables_columnfilterwidgets_separator }
																	disabled={ columnFilterWidgetsSettingsDisabled }
																	onChange={ ( datatables_columnfilterwidgets_separator ) => updateTableOptions( { datatables_columnfilterwidgets_separator } ) }
																	className="code"
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
								<tr className="top-border top-align">
									<th className="column-1" scope="row">{ __( 'Maximum selections', 'tablepress' ) }:</th>
									<td className="column-2">
										<VStack>
											<label htmlFor="option-datatables_columnfilterwidgets_max_selections">
												<HStack alignment="left">
													{
														createInterpolateElement(
															__( 'Allow a maximum number of <input /> selections from each filter dropdown.', 'tablepress' ),
															{
																input: (
																	<NumberControl
																		size="compact"
																		id="option-datatables_columnfilterwidgets_max_selections"
																		title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
																		isDragEnabled={ false }
																		value={ tableOptions.datatables_columnfilterwidgets_max_selections }
																		disabled={ columnFilterWidgetsSettingsDisabled }
																		// The table option could be passed through `parseInt()` if not empty, but for backward compatibility it's kept as a string.
																		onChange={ ( datatables_columnfilterwidgets_max_selections ) => updateTableOptions( { datatables_columnfilterwidgets_max_selections } ) }
																		min={ 1 }
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
											<HStack
												alignment="left"
												spacing="4px"
											>
												<Icon
													icon={ info }
													style={ {
														fill: '#757575',
													} }
												/>
												<span
													style={ {
														color: '#757575',
													} }
												>
													{ sprintf( __( 'Set “%s” to “1” to get classical single-selection dropdown controls.', 'tablepress' ), __( 'Maximum selections', 'tablepress' ) ) }
												</span>
											</HStack>
										</VStack>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Filter Terms Grouping', 'tablepress' ) }:</th>
									<td className="column-2">
										<CheckboxControl
											__nextHasNoMarginBottom
											label={ __( 'List the selected filter terms in one common section instead of underneath each dropdown.', 'tablepress' ) }
											checked={ tableOptions.datatables_columnfilterwidgets_group_terms }
											disabled={ columnFilterWidgetsSettingsDisabled }
											onChange={ ( datatables_columnfilterwidgets_group_terms ) => updateTableOptions( { datatables_columnfilterwidgets_group_terms } ) }
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

// Register the "Column Filter Dropdowns" feature for the "DataTables Layout" feature module.
addFilter( 'tablepress.dataTablesLayoutFeatures', `tp/${ MODULE_SLUG }/add-datatables_layout-features`, ( features ) => {
	return {
		...features,
		columnFilterWidgets: {
			label: __( 'Column Filter Dropdowns', 'tablepress' ),
			defaultPosition: 'top',
			option: 'datatables_columnfilterwidgets',
			requirements: ( tableOptions ) => ( tableOptions.datatables_filter && tableOptions.datatables_columnfilterwidgets ),
		},
	};
} );
