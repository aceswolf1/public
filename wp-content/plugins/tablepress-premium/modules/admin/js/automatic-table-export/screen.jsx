/**
 * JavaScript code for the "Automatic Table Export Screen" component.
 *
 * @package TablePress
 * @subpackage Automatic Table Export Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { useRef, useState } from 'react';
import {
	Button,
	CheckboxControl,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	withNotices,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import processAjaxRequest from '../../../../admin/js/common/ajax-request';
import { Alert } from '../../../../admin/js/common/alert';
import { Notifications } from '../../../../admin/js/common/notifications';

const csvDelimitersSelectOptions = Object.entries( tp.automaticTableExport.csvDelimiters ).map( ( [ csvDelimiter, csvDelimiterName ] ) => ( { value: csvDelimiter, label: csvDelimiterName } ) );

/**
 * Returns the "Automatic Table Export Screen" component's JSX markup.
 *
 * @return {Object} Automatic Table Export Screen component.
 */
const Screen = withNotices( ( { noticeOperations, noticeUI } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const [ screenData, setScreenData ] = useState( {
		active: tp.automaticTableExport.active,
		path: tp.automaticTableExport.path,
		selectedFormats: tp.automaticTableExport.selectedFormats,
		csvDelimiter: tp.automaticTableExport.csvDelimiter,
		isSaving: false,
		alertInvalidServerPathIsShown: false,
		alertNoExportFormatIsShown: false,
	} );

	// Add a React reference to the "Server Path" input field, to be able to focus the DOM element.
	const pathField = useRef( null );

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
	 * Saves the Automatic Table Export configuration to the server.
	 */
	const saveAutomaticTableExportConfig = () => {
		// Don't submit the form if no path is given, while the export is active.
		if ( screenData.active && '' === screenData.path.trim() ) {
			updateScreenData( { alertInvalidServerPathIsShown: true } );
			return;
		}

		// Don't submit the form if no export format was selected, while the export is active.
		if ( screenData.active && 0 === screenData.selectedFormats.length ) {
			updateScreenData( { alertNoExportFormatIsShown: true } );
			return;
		}

		// Prepare the data for the AJAX request.
		const requestData = {
			action: 'tablepress_export',
			_ajax_nonce: document.getElementById( '_wpnonce' ).value,
			tablepress: JSON.stringify( screenData ),
		};

		const setBusyState = ( isBusy ) => updateScreenData( { isSaving: isBusy } );

		processAjaxRequest( { requestData, setBusyState, noticeOperations, noticesStoreDispatch } );
	};

	return (
		<>
			<p>
				{ __( 'To automatically export a table to a file on your server after it has been edited, configure the desired path and export formats below.', 'tablepress' ) }
			</p>
			<table className="tablepress-postbox-table fixed">
			<tbody>
				<tr className="bottom-border">
					<th className="column-1" scope="row"></th>
					<td className="column-2">
						<ToggleControl
							__nextHasNoMarginBottom
							checked={ screenData.active }
							label={ __( 'Activate Automatic Table Export', 'tablepress' ) }
							onChange={ ( active ) => {
								updateScreenData( { active } );
							} }
						/>
					</td>
				</tr>
				<tr className="top-border">
					<th className="column-1" scope="row">
						<label htmlFor="auto-export-path">
							{ __ ( 'Server Path', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							ref={ pathField }
							id="auto-export-path"
							className="code"
							disabled={ ! screenData.active }
							value={ screenData.path }
							onChange={ ( path ) => {
								updateScreenData( { path } );
							} }
						/>
					</td>
				</tr>
				<tr className="top-border">
					<th className="column-1 top-align" scope="row">
						{ __ ( 'Export Format', 'tablepress' ) }:
					</th>
					<td className="column-2">
						<VStack>
							{
								Object.entries( tp.automaticTableExport.exportFormats ).map( ( [ exportFormat, exportFormatName ] ) => (
									<CheckboxControl
										__nextHasNoMarginBottom
										key={ exportFormat }
										label={ exportFormatName }
										disabled={ ! screenData.active }
										checked={ screenData.selectedFormats.includes( exportFormat ) }
										onChange={ ( checked ) => {
											let selectedFormats = [ ...screenData.selectedFormats ];
											selectedFormats = checked
												? [ ...selectedFormats, exportFormat ]
												: selectedFormats.filter( ( format ) => exportFormat !== format );
											updateScreenData( { selectedFormats } );
										} }
									/>
								) )
							}
						</VStack>
					</td>
				</tr>
				<tr className="top-border bottom-border">
					<th className="column-1" scope="row">
						<label htmlFor="auto-export-csv-delimiter">
							{ __ ( 'CSV Delimiter', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<HStack
							alignment="left"
						>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								value={ screenData.csvDelimiter }
								label={ __( 'CSV Delimiter', 'tablepress' ) }
								hideLabelFromVision={ true }
								onChange={ ( csvDelimiter ) => updateScreenData( { csvDelimiter } ) }
								options={ csvDelimitersSelectOptions }
								disabled={ ! screenData.active || ! screenData.selectedFormats.includes( 'csv' ) }
							/>
							{ ! screenData.selectedFormats.includes( 'csv' ) &&
								<span>
									{ __( '(Only needed for CSV export.)', 'tablepress' ) }
								</span>
							}
						</HStack>
					</td>
				</tr>
				<tr className="top-border">
					<td className="column-1"></td>
					<td className="column-2">
						<VStack>
							<HStack alignment="left">
								<Button
									variant="secondary"
									text={ __( 'Save Automatic Export configuration', 'tablepress' ) }
									isBusy={ screenData.isSaving }
									disabled={ screenData.isSaving }
									accessibleWhenDisabled={ true }
									onClick={ () => saveAutomaticTableExportConfig() }
								/>
								{
									( screenData.isSaving ) && (
										<Spinner style={ { margin: 0} }	/>
									)
								}
							</HStack>
							{ noticeUI }
						</VStack>
					</td>
				</tr>
				</tbody>
			</table>
			<Notifications />
			{ screenData.alertInvalidServerPathIsShown && (
				<Alert
					title={ __( 'Automatic Table Export', 'tablepress' ) }
					text={ __( 'You must set a server path for the automatic table export.', 'tablepress' ) }
					onConfirm={ () => {
						updateScreenData( { alertInvalidServerPathIsShown: false } );
						pathField.current.focus();
					} }
				/>
			) }
			{ screenData.alertNoExportFormatIsShown && (
				<Alert
					title={ __( 'Automatic Table Export', 'tablepress' ) }
					text={ __( 'You must select at least one export format for the automatic table export.', 'tablepress' ) }
					onConfirm={ () => updateScreenData( { alertNoExportFormatIsShown: false } ) }
				/>
			) }
		</>
	);
} );

export default Screen;
