/**
 * JavaScript code for the "Edit" section integration of the DataTables Pagination Settings feature.
 *
 * @package TablePress
 * @subpackage DataTables Pagination Settings
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
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

const MODULE_SLUG = 'datatables-pagination';

const Section = ( { tableOptions, updateTableOptions } ) => {
	const paginationEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_paginate );
	const classicPaginationEnabled = paginationEnabled && ! tableOptions.datatables_pagination_loadmore_button;

	return (
		<>
			<ModuleHelp slug={ MODULE_SLUG }>
				<p>
					{ __( 'The “Advanced Pagination Settings” module makes it possible to configure which specific control elements should be shown to the site visitor.', 'tablepress' ) }
				</p>
			</ModuleHelp>
			<VStack
				spacing="16px"
				style={ {
					paddingTop: '6px',
				} }
			>
				{
					! paginationEnabled && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s”, “%2$s”, and “%3$s” settings in the “%4$s” and “%5$s” sections are used.', 'tablepress' ),
										__( 'Table Header', 'tablepress' ),
										__( 'Enable Visitor Features', 'tablepress' ),
										__( 'Pagination', 'tablepress' ),
										__( 'Table Options', 'tablepress' ),
										__( 'Table Features for Site Visitors', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				<HStack
					alignment="topLeft"
					spacing="40px"
				>
					<VStack
						spacing="16px"
					>
						<span>
							{ __( 'Select which pagination elements should be displayed:', 'tablepress' ) }
						</span>
						<VStack>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( '“First” and “Last” buttons (« and »)', 'tablepress' ) }
								checked={ tableOptions.datatables_pagination_firstlast }
								disabled={ ! classicPaginationEnabled }
								onChange={ ( datatables_pagination_firstlast ) => updateTableOptions( { datatables_pagination_firstlast } ) }
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( '“Previous” and “Next” buttons (‹ and ›)', 'tablepress' ) }
								checked={ tableOptions.datatables_pagination_previousnext }
								disabled={ ! classicPaginationEnabled }
								onChange={ ( datatables_pagination_previousnext ) => updateTableOptions( { datatables_pagination_previousnext } ) }
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Page numbers' ) }
								checked={ tableOptions.datatables_pagination_numbers }
								disabled={ ! classicPaginationEnabled }
								onChange={ ( datatables_pagination_numbers ) => {
									const updatedTableOptions = { datatables_pagination_numbers };
									if ( datatables_pagination_numbers ) {
										updatedTableOptions.datatables_pagination_input = false;
									}
									updateTableOptions( updatedTableOptions );
								} }
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'An input field to jump to a specific page', 'tablepress' ) }
								checked={ tableOptions.datatables_pagination_input }
								disabled={ ! classicPaginationEnabled }
								onChange={ ( datatables_pagination_input ) => {
									const updatedTableOptions = { datatables_pagination_input };
									if ( datatables_pagination_input ) {
										updatedTableOptions.datatables_pagination_numbers = false;
									}
									updateTableOptions( updatedTableOptions );
								} }
							/>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'The “page of” text “/N” after the input', 'tablepress' ) }
								checked={ tableOptions.datatables_pagination_input_pageof }
								disabled={ ! classicPaginationEnabled || ! tableOptions.datatables_pagination_input }
								onChange={ ( datatables_pagination_input_pageof ) => updateTableOptions( { datatables_pagination_input_pageof } ) }
							/>
						</VStack>
						<VStack
							style={ {
								paddingTop: '6px',
							} }
						>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Scroll to the top of the table when the visitor goes to a different page.', 'tablepress' ) }
								checked={ tableOptions.datatables_pagination_scrolltotop }
								disabled={ ! classicPaginationEnabled }
								onChange={ ( datatables_pagination_scrolltotop ) => updateTableOptions( { datatables_pagination_scrolltotop } ) }
							/>
							<HStack alignment="left">
								<label
									htmlFor="option-datatables_pagination_scrolltotop_offset"
									style={ {
										paddingLeft: '24px',
									} }
								>
									<HStack>
										{
											createInterpolateElement(
												__( 'Scroll offset: <input /> pixels', 'tablepress' ),
												{
													input: (
														<NumberControl
															size="compact"
															id="option-datatables_pagination_scrolltotop_offset"
															title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
															isDragEnabled={ false }
															value={ tableOptions.datatables_pagination_scrolltotop_offset }
															disabled={ ! classicPaginationEnabled || ! tableOptions.datatables_pagination_scrolltotop }
															onChange={ ( datatables_pagination_scrolltotop_offset ) => {
																datatables_pagination_scrolltotop_offset = '' !== datatables_pagination_scrolltotop_offset ? parseInt( datatables_pagination_scrolltotop_offset, 10 ) : 0;
																updateTableOptions( { datatables_pagination_scrolltotop_offset } );
															} }
															min={ 0 }
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
						<VStack
							style={ {
								paddingTop: '6px',
							} }
						>
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Use a “Show More” button instead of normal pagination.', 'tablepress' ) }
								checked={ tableOptions.datatables_pagination_loadmore_button }
								disabled={ ! paginationEnabled }
								onChange={ ( datatables_pagination_loadmore_button ) => {
									const updatedTableOptions = { datatables_pagination_loadmore_button };
									if ( datatables_pagination_loadmore_button ) {
										updatedTableOptions.datatables_serverside_processing_cached_pages = 0;
										updatedTableOptions.datatables_serverside_processing_periodic_refresh = 0;
									}
									updateTableOptions( updatedTableOptions );
								} }
							/>
						</VStack>
					</VStack>
					<VStack
						spacing="16px"
						alignment="topLeft"
						style={ {
							whiteSpace: 'nowrap',
						} }
					>
						<span>
							{ __( 'Preview:', 'tablepress' ) }
						</span>
						{
							! tableOptions.datatables_pagination_loadmore_button && (
								<div className="dt-paging">
									{
										( tableOptions.datatables_pagination_firstlast ) && (
											<button className="dt-paging-button" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>«</button>
										)
									}
									{
										( tableOptions.datatables_pagination_previousnext ) && (
											<button className="dt-paging-button" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>‹</button>
										)
									}
									{
										( tableOptions.datatables_pagination_numbers ) && (
											<div className="dt-paging-button-wrapper">
												<button className="dt-paging-button current" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>1</button>
												<button className="dt-paging-button" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>2</button>
												<button className="dt-paging-button" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>3</button>
												<button className="dt-paging-button" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>4</button>
											</div>
										)
									}
									{
										( tableOptions.datatables_pagination_input ) && (
											<div className="dt-paging-input">
												<input className="dt-input" type="text" defaultValue="1" tabIndex="-1" />{ ( tableOptions.datatables_pagination_input_pageof ) && ( <span className="dt-paging-of"> / 4</span> ) }
											</div>
										)
									}
									{
										( tableOptions.datatables_pagination_previousnext ) && (
											<button className="dt-paging-button" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>›</button>
										)
									}
									{
										( tableOptions.datatables_pagination_firstlast ) && (
											<button className="dt-paging-button" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>»</button>
										)
									}
								</div>
							)
						}
						{
							tableOptions.datatables_pagination_loadmore_button && (
								<div className="dt-paging">
									<button className="dt-paging-button current" tabIndex="-1" onClick={ ( event ) => event.preventDefault() }>Show More</button>
								</div>
							)
						}
					</VStack>
				</HStack>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);
