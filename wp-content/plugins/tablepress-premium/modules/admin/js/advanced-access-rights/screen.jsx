/**
 * JavaScript code for the "Advanced Access Rights Screen" component.
 *
 * @package TablePress
 * @subpackage Advanced Access Rights Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	KeyboardShortcuts,
	Spinner,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	withNotices,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import processAjaxRequest from '../../../../admin/js/common/ajax-request';
import { Notifications } from '../../../../admin/js/common/notifications';

// Add the "New tables" and "New users" entries to allow defining default behavior.
tp.advancedAccessRights.tables['#new_tables'] = __( 'New tables', 'tablepress' );
tp.advancedAccessRights.users['#new_users'] = {};

const tableIds = Object.keys( tp.advancedAccessRights.tables );
const userIds = Object.keys( tp.advancedAccessRights.users );

// Ensure that the Access Rights map has an entry for every table and user.
tp.advancedAccessRights.map = Object.fromEntries( tableIds.map( ( tableId ) => {
	const tableAccessRights = Object.fromEntries( userIds.map( ( userId ) => {
		const userHasAccess = tp.advancedAccessRights.map[ tableId ]?.[ userId ] ? 1 : 0;
		return [ userId, userHasAccess ];
	} ) );
	return [ tableId, tableAccessRights ];
} ) );

/**
 * Returns the "Advanced Access Rights Screen" component's JSX markup.
 *
 * @return {Object} Advanced Access Rights Screen component.
 */
const Screen = withNotices( ( { noticeOperations, noticeUI } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const [ screenData, setScreenData ] = useState( {
		map: tp.advancedAccessRights.map,
		lastCheckboxIdx: null,
		isSaving: false,
	} );

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
	 * Saves the Advanced Access Rights configuration to the server.
	 */
	const saveAdvancedAccessRightsConfig = () => {
		const requestData = {
			action: 'tablepress_advanced_access_rights',
			_ajax_nonce: document.getElementById( '_wpnonce' ).value,
			tablepress: JSON.stringify( screenData.map ),
		};

		const setBusyState = ( isBusy ) => updateScreenData( { isSaving: isBusy } );

		processAjaxRequest( { requestData, setBusyState, noticeOperations, noticesStoreDispatch } );
	};

	/**
	 * Callback for the keyboard shortcut for the "Save Changes" button.
	 *
	 * @param {Object} event Event object.
	 */
	const saveChangesCallback = ( event ) => {
		event.preventDefault();
		// Blur the focussed element to make sure that all change events were triggered.
		document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element
		saveAdvancedAccessRightsConfig();
	};

	return (
		<>
			<VStack
				style={ {
					margin: '1rem 0',
				} }
			>
				<HStack	alignment="left">
					<Button
						variant="primary"
						text={ __( 'Save Changes', 'tablepress' ) }
						shortcut={ screenData.isSaving ? undefined :
							{
								ariaLabel: shortcutAriaLabel.primary( 's' ),
								display: displayShortcut.primary( 's' ),
							}
						}
						isBusy={ screenData.isSaving }
						disabled={ screenData.isSaving }
						accessibleWhenDisabled={ true }
						onClick={ () => saveAdvancedAccessRightsConfig() }
					/>
					{
						( screenData.isSaving ) && (
							<Spinner style={ { margin: 0} }	/>
						)
					}
				</HStack>
				{ noticeUI }
			</VStack>
			<table id="tablepress-access-rights-map" className="tablepress-access-rights-map striped">
				<thead>
					<tr>
						<th></th>
						<th></th>
						{
							Object.entries( tp.advancedAccessRights.users ).map( ( [ userId, user ] ) => (
								<th
									key={ userId }
									className={ ( '#new_users' === userId ) ? 'new-users-column' : undefined }
								>
									{ ( '#new_users' === userId ) && __( 'New users', 'tablepress' ) }
									{ ( '#new_users' !== userId ) && (
										<>
											{ userId }:
											<br />
											<abbr title={ user.displayName }>{ user.userLogin }</abbr>
										</>
									) }
								</th>
							) )
						}
					</tr>
				</thead>
				<tbody>
					{
						Object.entries( tp.advancedAccessRights.tables ).map( ( [ tableId, tableName ] ) => (
							<tr
								key={ tableId }
								className={ ( '#new_tables' === tableId ) ? 'new-tables-row' : undefined }
							>
								<th
									className={ ( '#new_tables' !== tableId ) ? 'column-table-id' : undefined }
								>
									{ ( '#new_tables' !== tableId ) && `${ tableId }:` }
								</th>
								<th
									className={ ( '#new_tables' !== tableId ) ? 'column-table-name' : undefined}
								>
									{ '' !== tableName.trim() ? tableName : __( '(no name)', 'tablepress' ) }
								</th>
								{
									Object.keys( tp.advancedAccessRights.users ).map( ( userId ) => (
										<td
											key={ userId }
											className={ ( '#new_users' === userId ) ? 'new-users-column' : undefined }
										>
											<input
												type="checkbox"
												checked={ 1 === screenData.map[ tableId ][ userId ] }
												onChange={ ( event ) => {
													// Find indices of the current table and user IDs, as these are not in consecutive order.
													const currentCheckboxIdx = {
														tableId: tableIds.indexOf( tableId ),
														userId: userIds.indexOf( userId ),
													};

													// Retrieve the last pressed checkbox table and user ID indices from screen data state.
													let lastCheckboxIdx = screenData.lastCheckboxIdx ? { ...screenData.lastCheckboxIdx } : null;

													// If no checkbox had been pressed before, or if the Shift key was not held, only change the current checkbox.
													if ( null === lastCheckboxIdx || ! event.nativeEvent.shiftKey ) {
														lastCheckboxIdx = currentCheckboxIdx;
													}

													// Determine first and last table and user ID indices, as these determine the range of checkboxes.
													const firstIdx = {
														tableId: ( lastCheckboxIdx.tableId < currentCheckboxIdx.tableId ) ? lastCheckboxIdx.tableId : currentCheckboxIdx.tableId,
														userId: ( lastCheckboxIdx.userId < currentCheckboxIdx.userId ) ? lastCheckboxIdx.userId : currentCheckboxIdx.userId,
													};
													const lastIdx = {
														tableId: ( currentCheckboxIdx.tableId > lastCheckboxIdx.tableId ) ? currentCheckboxIdx.tableId : lastCheckboxIdx.tableId,
														userId: ( currentCheckboxIdx.userId > lastCheckboxIdx.userId ) ? currentCheckboxIdx.userId : lastCheckboxIdx.userId,
													};

													// Loop over the range and grant/revoke access for all in that range, to also toggle their checkbox.
													const map = { ...screenData.map };
													for ( let tableIdIdx = firstIdx.tableId; tableIdIdx <= lastIdx.tableId; tableIdIdx++ ) {
														for ( let userIdIdx = firstIdx.userId; userIdIdx <= lastIdx.userId; userIdIdx++ ) {
															const checkboxTableId = tableIds[ tableIdIdx ];
															const checkboxUserId = userIds[ userIdIdx ];
															map[ checkboxTableId ][ checkboxUserId ] = event.target.checked ? 1 : 0;
														}
													}
													updateScreenData( { map } );

													// After processing the clicks, the current checkbox is the last clicked checkbox.
													updateScreenData( { lastCheckboxIdx: currentCheckboxIdx } );
												} }
											/>
										</td>
									) )
								}
							</tr>
						) )
					}
				</tbody>
			</table>
			<VStack
				style={ {
					margin: '1rem 0',
				} }
			>
				<HStack alignment="left">
					<Button
						variant="primary"
						text={ __( 'Save Changes', 'tablepress' ) }
						shortcut={ screenData.isSaving ? undefined :
							{
								ariaLabel: shortcutAriaLabel.primary( 's' ),
								display: displayShortcut.primary( 's' ),
							}
						}
						isBusy={ screenData.isSaving }
						disabled={ screenData.isSaving }
						accessibleWhenDisabled={ true }
						onClick={ () => saveAdvancedAccessRightsConfig() }
					/>
					{
						( screenData.isSaving ) && (
							<Spinner style={ { margin: 0} }	/>
						)
					}
				</HStack>
				{ noticeUI }
			</VStack>
			<KeyboardShortcuts
				bindGlobal={ true }
				shortcuts={ {
					'mod+s': saveChangesCallback,
				} }
			/>
			<Notifications />
		</>
	);
} );

export default Screen;
