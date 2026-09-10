/**
 * JavaScript code for the "Edit" section integration of the DataTables FixedHeader and FixedColumns feature.
 *
 * @package TablePress
 * @subpackage DataTables FixedHeader and FixedColumns
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
import { $ } from '../../../admin/js/common/functions';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-fixedheader-fixedcolumns';

const Section = ( { tableOptions, updateTableOptions } ) => {
	const fixedHeaderFixedColumnsEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_fixedheader.includes( 'top' ) && tableOptions.datatables_fixedheader_offsettop > 0 ) {
			$( '#tablepress-datatables_fixedheader-advanced-settings' ).open = true;
		}
		if ( tableOptions.datatables_fixedcolumns_left_columns > 1 || tableOptions.datatables_fixedcolumns_right_columns > 1 || tableOptions.datatables_scrollx_buttons ) {
			$( '#tablepress-datatables_fixedcolumns-advanced-settings' ).open = true;
		}

	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

	const fixedHeaderFixedColumnsNotice = ! fixedHeaderFixedColumnsEnabled ? sprintf(
		__( 'This feature is only available when the “%1$s” and “%2$s” settings in the “%3$s” and “%4$s” sections are used.', 'tablepress' ),
		__( 'Table Header', 'tablepress' ),
		__( 'Enable Visitor Features', 'tablepress' ),
		__( 'Table Options', 'tablepress' ),
		__( 'Table Features for Site Visitors', 'tablepress' ),
	) : '';

	const tableFootNotice = ( 0 === tableOptions.table_foot ) ? sprintf(
		__( 'This feature is only available when the “%1$s” checkbox in the “%2$s” section is checked.', 'tablepress' ),
		__( 'Table Footer', 'tablepress' ),
		__( 'Table Options', 'tablepress' ),
	) : undefined;

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
					! fixedHeaderFixedColumnsEnabled && (
						<span>
							<em>
								{ fixedHeaderFixedColumnsNotice }
							</em>
						</span>
					)
				}
				<table className="tablepress-postbox-table fixed">
					<tbody>
						<tr>
							<th className="column-1 top-align" scope="row">{ __( 'Fixed Rows', 'tablepress' ) }:</th>
							<td className="column-2">
								<VStack>
									<span>{ __( 'Choose the rows that shall be fixed at the screen edges when scrolling:', 'tablepress' ) }</span>
									<div>
										<div className="input-field-box">
											<input
												type="checkbox"
												id="option-datatables_fixedheader-top"
												className="control-input"
												checked={ tableOptions.datatables_fixedheader.includes( 'top' ) }
												onChange={ ( event ) => {
													let datatables_fixedheader = '' !== tableOptions.datatables_fixedheader ? tableOptions.datatables_fixedheader.split( ',' ) : [];
													datatables_fixedheader = event.target.checked
														? [ ...datatables_fixedheader, 'top' ]
														: datatables_fixedheader.filter( ( fixedHeader ) => fixedHeader !== 'top' );
													updateTableOptions( { datatables_fixedheader: datatables_fixedheader.join( ',' ) } )
												} }
												disabled={ ! fixedHeaderFixedColumnsEnabled }
											/>
											<label htmlFor="option-datatables_fixedheader-top">
												<span className="box-title">{ __( 'Head Row', 'tablepress' ) }</span>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="checkbox"
												id="option-datatables_fixedheader-bottom"
												className="control-input"
												checked={ tableOptions.datatables_fixedheader.includes( 'bottom' ) }
												onChange={ ( event ) => {
													let datatables_fixedheader = '' !== tableOptions.datatables_fixedheader ? tableOptions.datatables_fixedheader.split( ',' ) : [];
													datatables_fixedheader = event.target.checked
														? [ ...datatables_fixedheader, 'bottom' ]
														: datatables_fixedheader.filter( ( fixedHeader ) => fixedHeader !== 'bottom' );
													updateTableOptions( { datatables_fixedheader: datatables_fixedheader.join( ',' ) } )
												} }
												disabled={ ! fixedHeaderFixedColumnsEnabled || 0 === tableOptions.table_foot }
											/>
											<label
												htmlFor="option-datatables_fixedheader-bottom"
												title={ tableFootNotice }
											>
												<span className="box-title">{ __( 'Foot Row', 'tablepress' ) }</span>
											</label>
										</div>
										<details id="tablepress-datatables_fixedheader-advanced-settings">
											<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
											<VStack>
												<HStack alignment="left">
													<label htmlFor="option-datatables_fixedheader_offsettop">
														<HStack
															title={ ( ! fixedHeaderFixedColumnsEnabled || ! tableOptions.datatables_fixedheader.includes( 'top' ) ) ? sprintf( __( 'This feature is only available when the “%1$s” checkbox is checked.', 'tablepress' ), __( 'Head Row', 'tablepress' ) ) : undefined }
														>
															{
																createInterpolateElement(
																	__( 'Fix the Head Row at <input /> pixels from the top edge.', 'tablepress' ),
																	{
																		input: (
																			<NumberControl
																				size="compact"
																				id="option-datatables_fixedheader_offsettop"
																				title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
																				isDragEnabled={ false }
																				value={ tableOptions.datatables_fixedheader_offsettop }
																				disabled={ ! fixedHeaderFixedColumnsEnabled || ! tableOptions.datatables_fixedheader.includes( 'top' ) }
																				onChange={ ( datatables_fixedheader_offsettop ) => {
																					datatables_fixedheader_offsettop = '' !== datatables_fixedheader_offsettop ? parseInt( datatables_fixedheader_offsettop, 10 ) : 0;
																					updateTableOptions( { datatables_fixedheader_offsettop } );
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
												</HStack>
											</VStack>
										</details>
									</div>
								</VStack>
							</td>
						</tr>
						<tr>
							<th className="column-1 top-align" scope="row">{ __( 'Fixed Columns', 'tablepress' ) }:</th>
							<td className="column-2">
								<VStack>
									<span>{ __( 'Choose the columns that shall be fixed at the screen edges when scrolling:', 'tablepress' ) }</span>
									<div>
										<div className="input-field-box">
											<input
												type="checkbox"
												id="option-datatables_fixedcolumns-left"
												className="control-input"
												checked={ tableOptions.datatables_fixedcolumns.includes( 'left' ) }
												onChange={ ( event ) => {
													let datatables_fixedcolumns = '' !== tableOptions.datatables_fixedcolumns ? tableOptions.datatables_fixedcolumns.split( ',' ) : [];
													datatables_fixedcolumns = event.target.checked
														? [ ...datatables_fixedcolumns, 'left' ]
														: datatables_fixedcolumns.filter( ( fixedColumn ) => fixedColumn !== 'left' );
													updateTableOptions( {
														datatables_fixedcolumns: datatables_fixedcolumns.join( ',' ),
														datatables_fixedcolumns_left_columns: event.target.checked ? 1 : 0,
													} );
												} }
												disabled={ ! fixedHeaderFixedColumnsEnabled }
											/>
											<label htmlFor="option-datatables_fixedcolumns-left">
												<span className="box-title">{ __( 'First column', 'tablepress' ) }</span>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="checkbox"
												id="option-datatables_fixedcolumns-right"
												className="control-input"
												checked={ tableOptions.datatables_fixedcolumns.includes( 'right' ) }
												onChange={ ( event ) => {
													let datatables_fixedcolumns = '' !== tableOptions.datatables_fixedcolumns ? tableOptions.datatables_fixedcolumns.split( ',' ) : [];
													datatables_fixedcolumns = event.target.checked
														? [ ...datatables_fixedcolumns, 'right' ]
														: datatables_fixedcolumns.filter( ( fixedColumn ) => fixedColumn !== 'right' );
													updateTableOptions( {
														datatables_fixedcolumns: datatables_fixedcolumns.join( ',' ),
														datatables_fixedcolumns_right_columns: event.target.checked ? 1 : 0,
													} );
												} }
												disabled={ ! fixedHeaderFixedColumnsEnabled  }
											/>
											<label htmlFor="option-datatables_fixedcolumns-right">
												<span className="box-title">{ __( 'Last column', 'tablepress' ) }</span>
											</label>
										</div>
										<details id="tablepress-datatables_fixedcolumns-advanced-settings">
											<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
											<VStack>
												<HStack alignment="left">
													<label
														htmlFor="option-datatables_fixedcolumns_left_columns"
														title={ ( ! fixedHeaderFixedColumnsEnabled || ! tableOptions.datatables_fixedcolumns.includes( 'left' ) ) ? sprintf( __( 'This feature is only available when the “%1$s” checkbox is checked.', 'tablepress' ), __( 'First column', 'tablepress' ) ) : undefined }
													>
														<HStack>
															{
																createInterpolateElement(
																	__( 'Fix the first <input /> columns from the left.', 'tablepress' ),
																	{
																		input: (
																			<NumberControl
																				size="compact"
																				id="option-datatables_fixedcolumns_left_columns"
																				title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
																				isDragEnabled={ false }
																				value={ tableOptions.datatables_fixedcolumns_left_columns }
																				disabled={ ! fixedHeaderFixedColumnsEnabled || ! tableOptions.datatables_fixedcolumns.includes( 'left' ) }
																				onChange={ ( datatables_fixedcolumns_left_columns ) => {
																					datatables_fixedcolumns_left_columns = '' !== datatables_fixedcolumns_left_columns ? parseInt( datatables_fixedcolumns_left_columns, 10 ) : 0;
																					updateTableOptions( { datatables_fixedcolumns_left_columns } );
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
												</HStack>
												<HStack alignment="left">
													<label
														htmlFor="option-datatables_fixedcolumns_right_columns"
														title={ ( ! fixedHeaderFixedColumnsEnabled || ! tableOptions.datatables_fixedcolumns.includes( 'right' ) ) ? sprintf( __( 'This feature is only available when the “%1$s” checkbox is checked.', 'tablepress' ), __( 'Last column', 'tablepress' ) ) : undefined }
													>

														<HStack>
															{
																createInterpolateElement(
																	__( 'Fix the last <input /> columns from the right.', 'tablepress' ),
																	{
																		input: (
																			<NumberControl
																				size="compact"
																				id="option-datatables_fixedcolumns_right_columns"
																				title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
																				isDragEnabled={ false }
																				value={ tableOptions.datatables_fixedcolumns_right_columns }
																				disabled={ ! fixedHeaderFixedColumnsEnabled || ! tableOptions.datatables_fixedcolumns.includes( 'right' ) }
																				onChange={ ( datatables_fixedcolumns_right_columns ) => {
																					datatables_fixedcolumns_right_columns = '' !== datatables_fixedcolumns_right_columns ? parseInt( datatables_fixedcolumns_right_columns, 10 ) : 0;
																					updateTableOptions( { datatables_fixedcolumns_right_columns } );
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
												</HStack>
												<CheckboxControl
													__nextHasNoMarginBottom
													label={ __( 'Show left/right buttons for the horizontal scrolling.', 'tablepress' ) }
													checked={ tableOptions.datatables_scrollx_buttons }
													disabled={ ! fixedHeaderFixedColumnsEnabled || ! ( tableOptions.datatables_fixedcolumns.includes( 'left' ) || tableOptions.datatables_fixedcolumns.includes( 'right' ) || tableOptions.datatables_scrollx ) }
													onChange={ ( datatables_scrollx_buttons ) => updateTableOptions( { datatables_scrollx_buttons } ) }
												/>
											</VStack>
										</details>
									</div>
								</VStack>
							</td>
						</tr>
					</tbody>
				</table>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);
