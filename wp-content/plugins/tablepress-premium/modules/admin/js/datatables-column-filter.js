/**
 * JavaScript code for the "Edit" section integration of the DataTables Column Filter feature.
 *
 * @package TablePress
 * @subpackage DataTables Column Filter
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect } from 'react';
import {
	SelectControl,
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

const MODULE_SLUG = 'datatables-column-filter';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const columnFilterEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const invertedFilterEnabled = ( features.includes( 'datatables-inverted-filter' ) && tableOptions.datatables_inverted_filter );
	const columnFilterSettingsDisabled = '' === tableOptions.datatables_column_filter || ! columnFilterEnabled || ( 'select' === tableOptions.datatables_column_filter && serversideProcessingEnabled ) || invertedFilterEnabled;

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( '' !== tableOptions.datatables_column_filter && (
			'table_head' !== tableOptions.datatables_column_filter_position ||
			'' !== tableOptions.datatables_column_filter_columns.trim()
			) ) {
			document.getElementById( 'tablepress-datatables_column_filter-advanced-settings' ).open = true;
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

	const columnFilterDisabledNotice = ! columnFilterEnabled ? sprintf(
		__( 'This feature is only available when the “%1$s”, “%2$s”, and “%3$s” settings in the “%4$s” and “%5$s” sections are used.', 'tablepress' ),
		__( 'Table Header', 'tablepress' ),
		__( 'Enable Visitor Features', 'tablepress' ),
		__( 'Search/Filtering', 'tablepress' ),
		__( 'Table Options', 'tablepress' ),
		__( 'Table Features for Site Visitors', 'tablepress' ),
	) : '';

	const invertedFilterEnabledNotice = invertedFilterEnabled ? sprintf(
		__( 'This feature is only available when the “%1$s” feature is turned off.', 'tablepress' ),
		__( 'Inverted Filtering', 'tablepress' ),
	) : '';

	const selectFilteringDisabledNotice = serversideProcessingEnabled ? sprintf(
		__( 'The “%1$s” option is only available when the “%2$s” feature is turned off.', 'tablepress' ),
		__( 'Drop-down', 'tablepress' ),
		__( 'Server-side Processing', 'tablepress' ),
	) : '';

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
					! columnFilterEnabled && (
						<span>
							<em>
								{ columnFilterDisabledNotice }
							</em>
						</span>
					)
				}
				{
					( invertedFilterEnabled ) && (
						<span>
							<em>
								{ invertedFilterEnabledNotice }
							</em>
						</span>
					)
				}
				<table className="tablepress-postbox-table fixed">
					<tbody>
						<tr>
							<th className="column-1 top-align" scope="row">{ __( 'Form Element', 'tablepress' ) }:</th>
							<td className="column-2">
								<VStack>
									<span>{ __( 'Choose the desired form element for the individual column filters:', 'tablepress' ) }</span>
									{
										serversideProcessingEnabled && (
											<span>
												<em>
													{ selectFilteringDisabledNotice }
												</em>
											</span>
										)
									}
									<div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-datatables_column_filter-"
												className="control-input"
												checked={ '' === tableOptions.datatables_column_filter }
												onChange={ () => updateTableOptions( { datatables_column_filter: '' } ) }
											/>
											<label htmlFor="option-datatables_column_filter-">
												<span className="box-title">{ __( 'Off', 'tablepress' ) }</span>
												<p className="description">{ __( 'No individual column filtering.', 'tablepress' ) }</p>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-datatables_column_filter-input"
												className="control-input"
												checked={ 'input' === tableOptions.datatables_column_filter }
												onChange={ () => updateTableOptions( { datatables_column_filter: 'input' } ) }
												disabled={ ! columnFilterEnabled || invertedFilterEnabled }
											/>
											<label
												htmlFor="option-datatables_column_filter-input"
												title={ ! columnFilterEnabled ? columnFilterDisabledNotice : ( invertedFilterEnabled ? invertedFilterEnabledNotice : undefined ) } // eslint-disable-line no-nested-ternary
											>
												<span className="box-title">{ __( 'Text field', 'tablepress' ) }</span>
												<p className="description">
													{
														createInterpolateElement(
															__( 'Use <input /> fields.', 'tablepress' ),
															{
																input: (
																	<input
																		type="text"
																		className="mock-field"
																		inert=""
																		defaultValue={ __( 'text input', 'tablepress' ) }
																	/>
																),
															},
														)
													}
												</p>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-datatables_column_filter-select"
												className="control-input"
												checked={ 'select' === tableOptions.datatables_column_filter }
												onChange={ () => updateTableOptions( { datatables_column_filter: 'select' } ) }
												disabled={ ! columnFilterEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											/>
											<label
												htmlFor="option-datatables_column_filter-select"
												title={ ! columnFilterEnabled ? columnFilterDisabledNotice : ( invertedFilterEnabled ? invertedFilterEnabledNotice : ( serversideProcessingEnabled ? selectFilteringDisabledNotice : undefined ) ) } // eslint-disable-line no-nested-ternary
											>
												<span className="box-title">{ __( 'Drop-down', 'tablepress' ) }</span>
												<p className="description">
													{
														createInterpolateElement(
															__( 'Use <input /> fields.', 'tablepress' ),
															{
																input: (
																	<select
																		className="mock-field"
																		inert=""
																	>
																		<option>{ __( 'drop-down', 'tablepress' ) }</option>
																	</select>
																),
															},
														)
													}
												</p>
											</label>
										</div>
									</div>
								</VStack>
							</td>
						</tr>
					</tbody>
				</table>
				<details id="tablepress-datatables_column_filter-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_column_filter_position">{ __( 'Position', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<SelectControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-datatables_column_filter_position"
											value={ tableOptions.datatables_column_filter_position }
											help={ __( 'Choose the desired position for the individual column filters.', 'tablepress' ) }
											onChange={ ( datatables_column_filter_position ) => updateTableOptions( { datatables_column_filter_position } ) }
											options={ [
												{ value: 'table_head', label: __( 'Table Head Row', 'tablepress' ) },
												{ value: 'table_foot', label: __( 'Table Foot Row', 'tablepress' ) },
											] }
											disabled={ columnFilterSettingsDisabled }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_column_filter_columns">{ __( 'Columns', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-datatables_column_filter_columns"
											title={ __( 'This field can only contain letters, numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
											pattern="[0-9A-Z, \-]*"
											value={ tableOptions.datatables_column_filter_columns }
											disabled={ columnFilterSettingsDisabled }
											onChange={ ( datatables_column_filter_columns ) => updateTableOptions( { datatables_column_filter_columns: datatables_column_filter_columns.replace( /[^0-9A-Z, -]/g, '' ) } ) }
											help={ __( 'Enter a comma-separated list of the columns which shall get an individual column filter control, e.g. “1,3-5,7”.', 'tablepress' ) + ' ' + __( 'By default, all columns will get a filter control.', 'tablepress' ) }
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
