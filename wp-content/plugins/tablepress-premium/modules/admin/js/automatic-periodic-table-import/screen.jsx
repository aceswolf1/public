/**
 * JavaScript code for the "Automatic Periodic Table Import Screen" component.
 *
 * @package TablePress
 * @subpackage Automatic Periodic Table Import Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Button,
	Card,
	CardBody,
	CheckboxControl,
	FormTokenField,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Panel,
	PanelBody,
	PanelRow,
	SearchControl,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	withNotices,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { isEmail } from '@wordpress/url';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import IntervalControl from './components/interval-control';
import processAjaxRequest from '../../../../admin/js/common/ajax-request';
import { Notifications } from '../../../../admin/js/common/notifications';

// Ensure that all tables have a configuration.
tp.automaticPeriodicTableImport.tables = Object.fromEntries( Object.keys( tp.import.tables ).map( ( tableId ) => {
	const tableImportConfig = {
		selected: false,
		active: false,
		location: 'https://',
		interval: /* DAY_IN_SECONDS */ 86400,
		last_import: '-',
		...tp.automaticPeriodicTableImport.tables[ tableId ],
	};

	// Only use the `message` part of the `last_import` property. The type check is needed for backward compatibility.
	if ( 'string' !== typeof tableImportConfig.last_import ) {
		tableImportConfig.last_import = tableImportConfig.last_import.message;
	}

	return [ tableId, tableImportConfig ];
} ) );

/**
 * Returns the "Automatic Periodic Table Import Screen" component's JSX markup.
 *
 * @return {Object} Automatic Periodic Table Import Screen component.
 */
const Screen = withNotices( ( { noticeOperations, noticeUI } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const [ screenData, setScreenData ] = useState( () => ( {
		tables: tp.automaticPeriodicTableImport.tables,
		notifications: tp.automaticPeriodicTableImport.notifications,
		shownTableIds: Object.keys( tp.automaticPeriodicTableImport.tables ),
		lastCheckboxTableId: null,
		searchTerm: '',
		sort: {
			column: '', // '' means no sorting, otherwise the object key to sort by.
			direction: 1, // 1 for descending, -1 for ascending.
		},
		bulkAction: '',
		bulkActionInterval: /* DAY_IN_SECONDS */ 86400,
		isSaving: false,
	} ) );

	/**
	 * Handles screen data state changes.
	 *
	 * @param {Object} updatedScreenData Data in the screen data state that should be updated.
	 */
	const updateScreenData = ( updatedScreenData ) => {
		setScreenData( ( currentScreenData ) => ( {
			...currentScreenData,
			...updatedScreenData,
		} ) );
	};

	/**
	 * Saves the Automatic Periodic Table Import configuration to the server.
	 */
	const saveAutomaticPeriodicTableImportConfig = () => {
		const tables = Object.fromEntries( Object.entries( screenData.tables )
			// Only save the config for tables that have changes and not just the default settings.
			.filter( ( [ , table ] ) => ( table.active || 'https://' !== table.location.trim() || /* DAY_IN_SECONDS */ 86400 !== table.interval ) )
			// Don't save the "last_import" property.
			.map( ( [ tableId, table ] ) => {
				const newTable = { ...table };
				delete newTable.selected;
				delete newTable.last_import;
				return [ tableId, newTable ];
			} )
		);

		// Prepare the data for the AJAX request.
		const requestData = {
			action: 'tablepress_import',
			_ajax_nonce: document.getElementById( '_wpnonce' ).value,
			tablepress: JSON.stringify( {
				tables,
				notifications: screenData.notifications,
			} ),
		};

		const setBusyState = ( isBusy ) => updateScreenData( { isSaving: isBusy } );

		processAjaxRequest( { requestData, setBusyState, noticeOperations, noticesStoreDispatch } );
	};

	/**
	 * Triggers an automatic import of given tables.
	 *
	 * @param {Object} tables Tables to import now.
	 */
	const triggerAutomaticPeriodicTableImport = ( tables ) => {
		// Prepare the data for the AJAX request.
		const requestData = {
			action: 'tablepress_import',
			_ajax_nonce: document.getElementById( '_wpnonce' ).value,
			tablepress: JSON.stringify( {
				tables,
			} ),
			immediate_import: true,
		};

		/**
		 * Callback for handling specifics of a successful request.
		 *
		 * @param {Object} data
		 */
		const onSuccessfulRequest = ( data ) => {
			const actionMessages = {
				success_trigger: __( 'The import was triggered successfully.', 'tablepress' ),
			};

			const notice = {
				status: ( data.message.includes( 'error' ) ) ? 'error' : 'success',
				content: actionMessages[ data.message ],
				type: ( data.message.includes( 'error' ) ) ? 'notice' : 'snackbar',
			};

			return { notice };
		};

		const setBusyState = ( isBusy ) => updateScreenData( { isSaving: isBusy } );

		processAjaxRequest( { requestData, onSuccessfulRequest, setBusyState, noticeOperations, noticesStoreDispatch } );
	};

	/**
	 * Filters and sorts the table list to generate the list of tables that is to be shown.
	 *
	 * @param {string} searchTerm Search term.
	 * @param {Object} sort       Sort configuration.
	 * @return {Array} List of table IDs that is to be shown.
	 */
	const getShownTableIdsFromFilterAndSort = ( searchTerm, sort ) => {
		let tables = Object.entries( screenData.tables );

		if ( '' !== searchTerm ) {
			searchTerm = searchTerm.toLowerCase();
			tables = tables.filter( ( [ tableId, tableData ] ) => (
				tableId.toLowerCase().includes( searchTerm ) ||
				tp.import.tables[ tableId ].toLowerCase().includes( searchTerm ) ||
				tableData.location.toLowerCase().includes( searchTerm )
			) );
		}

		if ( '' !== sort.column ) {
			tables.sort( ( [ tableIdA, tableDataA ], [ tableIdB, tableDataB ] ) => {
				let sortDataA;
				let sortDataB;
				if ( 'id' === sort.column ) {
					sortDataA = tableIdA;
					sortDataB = tableIdB;
				} else if ( 'name' === sort.column ) {
					sortDataA = tp.import.tables[ tableIdA ];
					sortDataB = tp.import.tables[ tableIdB ];
				} else {
					sortDataA = tableDataA[ sort.column ].toString();
					sortDataB = tableDataB[ sort.column ].toString();
				}
				const sortResult = sortDataA.localeCompare( sortDataB, undefined, {
					numeric: true,
					sensitivity: 'base',
				} );
				return sort.direction * sortResult;
			} );
		}

		return tables.map( ( [ tableId, ] ) => tableId );
	};

	// Create the "Save Automatic Import configuration" button once, so that it can be used in multiple places.
	const submitButton = (
		<VStack
			style={ {
				margin: '1rem 0',
			} }
		>
			<HStack alignment="left">
				<Button
					variant="secondary"
					text={ __( 'Save Automatic Import configuration', 'tablepress' ) }
					isBusy={ screenData.isSaving }
					disabled={ screenData.isSaving }
					accessibleWhenDisabled={ true }
					onClick={ () => saveAutomaticPeriodicTableImportConfig() }
				/>
				{
					( screenData.isSaving ) && (
						<Spinner style={ { margin: 0} }	/>
					)
				}
			</HStack>
			{ noticeUI }
		</VStack>
	);

	/**
	 * Returns the JSX markup for a sortable table head cell.
	 *
	 * @param {Object} props        Component properties.
	 * @param {string} props.column Column name to sort by.
	 * @param {string} props.text   Column text.
	 * @return {Object} JSX markup for a sortable table head cell.
	 */
	const HeadCell = ( { column, text } ) => (
		<th
			className={ column === screenData.sort.column ? `sorted ${ 1 === screenData.sort.direction ? 'asc' : 'desc' }` : 'sortable desc' }
		>
			{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
			<a
				href=""
				role="button"
				onClick={ ( event ) => {
					event.preventDefault();
					const sort = {
						column,
						direction: event.target.closest( 'th' ).classList.contains( 'asc' ) ? -1 : 1,
					};
					const shownTableIds = getShownTableIdsFromFilterAndSort( screenData.searchTerm, sort );
					updateScreenData( { shownTableIds, sort } );
				} }
			>
				<span>{ text }</span>
				<span className="sorting-indicators">
					<span className="sorting-indicator asc" aria-hidden="true"></span>
					<span className="sorting-indicator desc" aria-hidden="true"></span>
				</span>
			</a>
		</th>
	);

	return (
		<>
			<p>
				{ __( 'To periodically import tables from files that were uploaded to a server, configure the desired interval and table source information below.', 'tablepress' ) }
			</p>
			<p className="description">
				{
					__( 'Please note: In general, it is recommended to use a reasonably long interval, to reduce traffic on the import source server.', 'tablepress' ) + ' '
					+ __( 'In addition, it is recommended to set up a suitable server cron job that replaces the WP Cron system, for improved reliability.', 'tablepress' ) + ' '
					+ sprintf( __( 'To trigger an immediate import of a table, use the “%s” button in the table’s row.', 'tablepress' ), __( 'Run now', 'tablepress' ) )
				}
			</p>
			{ submitButton }
			<Panel>
				<PanelBody
					title={ __( 'Failure Notification Settings', 'tablepress' ) }
					initialOpen={ false }
				>
					<PanelRow>
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row">{ __( 'Active', 'tablepress' ) }:</th>
									<td className="column-2">
										<ToggleControl
											__nextHasNoMarginBottom
											checked={ screenData.notifications.active }
											label={ __( 'Send email notifications on import failures', 'tablepress' ) }
											onChange={ ( checked ) => {
												const notifications = { ...screenData.notifications };
												notifications.active = checked;
												updateScreenData( { notifications } );
											} }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row">{ __( 'Levels', 'tablepress' ) }:</th>
									<td className="column-2">
										<VStack>
											<CheckboxControl
												__nextHasNoMarginBottom
												label={ 'Warnings' }
												checked={ screenData.notifications.levels.includes( 'warning' ) }
												onChange={ ( checked ) => {
													const notifications = { ...screenData.notifications };
													notifications.levels = checked
														? [ ...notifications.levels, 'warning' ]
														: notifications.levels.filter( ( level ) => 'warning' !== level );
													updateScreenData( { notifications } );
												} }
											/>
											<CheckboxControl
												__nextHasNoMarginBottom
												label={ 'Errors' }
												checked={ screenData.notifications.levels.includes( 'error' ) }
												onChange={ ( checked ) => {
													const notifications = { ...screenData.notifications };
													notifications.levels = checked
														? [ ...notifications.levels, 'error' ]
														: notifications.levels.filter( ( level ) => 'error' !== level );
													updateScreenData( { notifications } );
												} }
											/>
										</VStack>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1 top-align" scope="row"><label htmlFor="auto-import-notifications-recipients">{ __( 'Recipients', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<FormTokenField
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="auto-import-notifications-recipients"
											label=""
											onChange={ ( recipients ) => {
												const notifications = { ...screenData.notifications };
												notifications.recipients = [ ...recipients ];
												updateScreenData( { notifications } );
											} }
											value={ screenData.notifications.recipients }
											suggestions={ tp.automaticPeriodicTableImport.defaultEmails }
											__experimentalValidateInput={ ( input ) => isEmail( input ) }
											tokenizeOnBlur={ true }
											tokenizeOnSpace={ true }
										/>
									</td>
								</tr>
							</tbody>
						</table>
					</PanelRow>
				</PanelBody>
			</Panel>
			<div style={ {
				borderLeft: '1px solid #e0e0e0',
				borderRight: '1px solid #e0e0e0',
				borderBottom: '1px solid #e0e0e0',
			} }>
				<Card>
					<CardBody>
						<h2 className="card-title">
							{ __( 'Table Configuration', 'tablepress' ) }
						</h2>
						<VStack>
							<HStack>
								<HStack
									expanded={ false }
								>
									<HStack>
										<SelectControl
											__nextHasNoMarginBottom
											size="compact"
											value={ screenData.bulkAction }
											label={ __( 'Select Bulk Action', 'tablepress' ) }
											hideLabelFromVision={ true }
											onChange={ ( value ) => {
												updateScreenData( { bulkAction: value } );
											} }
											options={ [
												{ label: __( 'Bulk Actions', 'tablepress' ), value: '', disabled: true },
												{ label: __( 'Activate import', 'tablepress' ), value: 'activate' },
												{ label: __( 'Deactivate import', 'tablepress' ), value: 'deactivate' },
												{ label: __( 'Run import now', 'tablepress' ), value: 'run-now' },
												{ label: __( 'Set import interval', 'tablepress' ), value: 'set-interval' },
											] }
										/>
										{ 'set-interval' === screenData.bulkAction &&
											<IntervalControl
												value={ screenData.bulkActionInterval }
												onChange={ ( value ) => {
													updateScreenData( { bulkActionInterval: value } );
												} }
											/>
										}
										<Button
											variant="secondary"
											size="compact"
											disabled={ '' === screenData.bulkAction || 0 === screenData.shownTableIds.length || ! screenData.shownTableIds.some( ( tableId ) => screenData.tables[ tableId ].selected ) }
											text={ __( 'Apply', 'tablepress' ) }
											onClick={ () => {
												const selectedTableIds = screenData.shownTableIds.filter( ( tableId ) => screenData.tables[ tableId ].selected );

												const tables = { ...screenData.tables };
												if ( 'activate' === screenData.bulkAction ) {
													selectedTableIds.forEach( ( tableId ) => {
														tables[ tableId ].active = true;
													} );
												} else if ( 'deactivate' === screenData.bulkAction ) {
													selectedTableIds.forEach( ( tableId ) => {
														tables[ tableId ].active = false;
													} );
												} else if ( 'run-now' === screenData.bulkAction ) {
													const tablesToImport = {};
													selectedTableIds.forEach( ( tableId ) => {
														const location = tables[ tableId ].location.trim();
														if ( '' !== location && 'https://' !== location ) {
															tablesToImport[ tableId ] = location;
														}
													} );
													if ( 0 < Object.keys( tablesToImport ).length ) {
														triggerAutomaticPeriodicTableImport( tablesToImport );
													}
												} else if ( 'set-interval' === screenData.bulkAction ) {
													selectedTableIds.forEach( ( tableId ) => {
														tables[ tableId ].interval = screenData.bulkActionInterval;
													} );
												}

												// Update table data and reset the bulk action dropdown.
												updateScreenData( {
													tables,
													bulkAction: '',
													bulkActionInterval: /* DAY_IN_SECONDS */ 86400,
												} );
											} }
										/>
									</HStack>
								</HStack>
								<HStack
									expanded={ false }
								>
									<label htmlFor="tables_search-search-input">{ __( 'Filter list:', 'tablepress' ) }</label>
									<SearchControl
										__nextHasNoMarginBottom
										id="tables_search-search-input"
										label={ __( 'Filter list', 'tablepress' ) }
										size="compact"
										value={ screenData.searchTerm }
										placeholder=""
										onChange={ ( searchTerm ) => {
											const shownTableIds = getShownTableIdsFromFilterAndSort( searchTerm, screenData.sort );
											updateScreenData( {	shownTableIds, searchTerm } )
										} }
									/>
								</HStack>
							</HStack>
							<table id="tablepress-automatic-periodic-import-tables" className="widefat striped">
								<thead>
									<tr>
										<th className="column-checkbox">
											<input
												type="checkbox"
												id="auto-import-select-all-thead"
												checked={ 0 < screenData.shownTableIds.length && screenData.shownTableIds.every( ( tableId ) => screenData.tables[ tableId ].selected ) }
												disabled={ 0 === screenData.shownTableIds.length }
												onChange={ ( event ) => {
													const tables = { ...screenData.tables };
													screenData.shownTableIds.forEach( ( tableId ) => {
														tables[ tableId ].selected = event.target.checked;
													} );
													updateScreenData( { tables } );
												} }
											/>
											<label htmlFor="auto-import-select-all-thead">
												<span className="screen-reader-text">{ __( 'Select All' ) }</span>
											</label>
										</th>
										<HeadCell column="id" text={ __( 'ID', 'tablepress' ) } />
										<HeadCell column="name" text={ __( 'Table Name', 'tablepress' ) } />
										<HeadCell column="active" text={ __( 'Active', 'tablepress' ) } />
										<HeadCell column="location" text={ __( 'Import File Location', 'tablepress' ) } />
										<HeadCell column="interval" text={ __( 'Import Interval', 'tablepress' ) } />
										<HeadCell column="last_import" text={ __( 'Last Automatic Import', 'tablepress' ) } />
										<th></th>
									</tr>
								</thead>
								<tbody>
									{
										0 === screenData.shownTableIds.length
										? (
											<tr>
												<td colSpan="8">
													{ __( 'No tables found.', 'tablepress' ) }
												</td>
											</tr>
										)
										: screenData.shownTableIds.map( ( tableId ) => {
											const tableConfig = screenData.tables[ tableId ];
											const tableName = '' === tp.import.tables[ tableId ].trim() ? __( '(no name)', 'tablepress' ) : tp.import.tables[ tableId ];
											return (
												<tr key={ tableId }>
													<th scope="row" className="column-checkbox">
														<input
															type="checkbox"
															checked={ tableConfig.selected }
															id={ `cb-select-${ tableId }` }
															onChange={ ( event ) => {
																// Find index of the current table ID, as these are not in consecutive order.
																const currentCheckboxIdx = screenData.shownTableIds.indexOf( tableId );

																// Retrieve the last pressed checkbox table ID from screen data state and find the checkbox position.
																let lastCheckboxIdx = screenData.shownTableIds.indexOf( screenData.lastCheckboxTableId );

																// If no checkbox had been pressed before, or if the Shift key was not held, only change the current checkbox.
																if ( null === screenData.lastCheckboxTableId || ! event.nativeEvent.shiftKey ) {
																	lastCheckboxIdx = currentCheckboxIdx;
																}

																// Determine first and last table ID indices, as these determine the range of checkboxes.
																const firstIdx = ( lastCheckboxIdx < currentCheckboxIdx ) ? lastCheckboxIdx : currentCheckboxIdx;
																const lastIdx = ( currentCheckboxIdx > lastCheckboxIdx ) ? currentCheckboxIdx : lastCheckboxIdx;

																// Loop over the range and activate/deactivate all in that range, to also toggle their checkbox.
																const tables = { ...screenData.tables };
																for ( let tableIdIdx = firstIdx; tableIdIdx <= lastIdx; tableIdIdx++ ) {
																	const checkboxTableId = screenData.shownTableIds[ tableIdIdx ];
																	tables[ checkboxTableId ].selected = event.target.checked;
																}
																updateScreenData( {
																	tables,
																	lastCheckboxTableId: tableId, // After processing the clicks, the current checkbox is the last clicked checkbox.
																} );
															} }
														/>
														<label htmlFor={ `cb-select-${ tableId }` }>
															<span className="screen-reader-text">
																{ sprintf( __( 'Select table “%s”', 'tablepress' ), tableName ) }
															</span>
														</label>
													</th>
													<td className="column-table-id">{ tableId }</td>
													<td className="column-table-name">{ tableName }</td>
													<td className="column-import-active">
														<ToggleControl
															__nextHasNoMarginBottom
															checked={ tableConfig.active }
															onChange={ ( checked ) => {
																const tables = { ...screenData.tables };
																tables[ tableId ].active = checked;
																updateScreenData( { tables } );
															} }
														/>
													</td>
													<td className="column-import-location">
														<TextControl
															__nextHasNoMarginBottom
															__next40pxDefaultSize
															className="code"
															disabled={ ! tableConfig.active }
															value={ tableConfig.location }
															onChange={ ( enteredValue ) => {
																const tables = { ...screenData.tables };
																tables[ tableId ].location = enteredValue;
																updateScreenData( { tables } );
															} }
														/>
													</td>
													<td className="column-import-interval">
														<IntervalControl
															disabled={ ! tableConfig.active }
															value={ tableConfig.interval }
															onChange={ ( value ) => {
																const tables = { ...screenData.tables };
																tables[ tableId ].interval = value;
																updateScreenData( { tables } );
															} }
														/>
													</td>
													<td
														className="column-import-last-import"
														dangerouslySetInnerHTML={ {
															__html: tableConfig.last_import
														} }
													/>
													<td className="column-import-run-now">
														<Button
															variant="secondary"
															size="small"
															text={ __( 'Run now', 'tablepress' ) }
															onClick={ () => triggerAutomaticPeriodicTableImport( { [ tableId ]: tableConfig.location } ) }
															disabled={ [ '', 'https://' ].includes( tableConfig.location.trim() ) }
															label={ __( 'Import the table from the configured source now', 'tablepress' ) }
															showTooltip={ ! [ '', 'https://' ].includes( tableConfig.location.trim() ) }
														/>
													</td>
												</tr>
											);
										} )
									}
								</tbody>
								<tfoot>
									<tr>
										<th className="column-checkbox">
											<input
												type="checkbox"
												id="auto-import-select-all-tfoot"
												checked={ 0 < screenData.shownTableIds.length && screenData.shownTableIds.every( ( tableId ) => screenData.tables[ tableId ].selected ) }
												disabled={ 0 === screenData.shownTableIds.length }
												onChange={ ( event ) => {
													const tables = { ...screenData.tables };
													screenData.shownTableIds.forEach( ( tableId ) => {
														tables[ tableId ].selected = event.target.checked;
													} );
													updateScreenData( { tables } );
												} }
											/>
											<label htmlFor="auto-import-select-all-tfoot">
												<span className="screen-reader-text">{ __( 'Select All' ) }</span>
											</label>
										</th>
										<th>{ __( 'ID', 'tablepress' ) }</th>
										<th>{ __( 'Table Name', 'tablepress' ) }</th>
										<th>{ __( 'Active', 'tablepress' ) }</th>
										<th>{ __( 'Import File Location', 'tablepress' ) }</th>
										<th>{ __( 'Import Interval', 'tablepress' ) }</th>
										<th>{ __( 'Last Automatic Import', 'tablepress' ) }</th>
										<th></th>
									</tr>
								</tfoot>
							</table>
						</VStack>
					</CardBody>
				</Card>
			</div>
			{ submitButton }
			<Notifications />
		</>
	);
} );

export default Screen;
