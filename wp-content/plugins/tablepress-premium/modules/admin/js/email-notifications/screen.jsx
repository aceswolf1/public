/**
 * JavaScript code for the "Email Notifications Screen" component.
 *
 * @package TablePress
 * @subpackage Email Notifications Screen
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	KeyboardShortcuts,
	Panel,
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
import EventPanel from './components/event-panel';
import processAjaxRequest from '../../../../admin/js/common/ajax-request';
import { Notifications } from '../../../../admin/js/common/notifications';

/**
 * Returns the "Email Notifications Screen" component's JSX markup.
 *
 * @return {Object} Email Notifications Screen component.
 */
const Screen = withNotices( ( { noticeOperations, noticeUI } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const [ screenData, setScreenData ] = useState( () => ( {
		config: tp.emailNotifications.config,
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
	 * Saves the Email Notifications configuration to the server.
	 */
	const saveEmailNotificationsConfig = () => {
		const requestData = {
			action: 'tablepress_email_notifications',
			_ajax_nonce: document.getElementById( '_wpnonce' ).value,
			tablepress: JSON.stringify( {
				config: screenData.config,
			} ),
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
		saveEmailNotificationsConfig();
	};

	return (
		<>
			<div
				style={ {
					maxWidth: '1000px',
				} }
			>
				<Panel>
					<EventPanel
						title={ __( 'A table was saved', 'tablepress' ) }
						label={ __( 'Send an email notification when a table is saved.', 'tablepress' ) }
						config={ screenData.config.saved_table }
						updateConfig={ ( updatedConfig ) => updateScreenData( { config: { ...screenData.config, saved_table: updatedConfig } } ) }
					/>
					<EventPanel
						title={ __( 'A table was added', 'tablepress' ) }
						label={ __( 'Send an email notification when a table is added.', 'tablepress' ) }
						config={ screenData.config.added_table }
						updateConfig={ ( updatedConfig ) => updateScreenData( { config: { ...screenData.config, added_table: updatedConfig } } ) }
					/>
					<EventPanel
						title={ __( 'A table was deleted', 'tablepress' ) }
						label={ __( 'Send an email notification when a table is deleted.', 'tablepress' ) }
						config={ screenData.config.deleted_table }
						updateConfig={ ( updatedConfig ) => updateScreenData( { config: { ...screenData.config, deleted_table: updatedConfig } } ) }
					/>
					<EventPanel
						title={ __( 'A table was copied', 'tablepress' ) }
						label={ __( 'Send an email notification when a table is copied.', 'tablepress' ) }
						config={ screenData.config.copied_table }
						updateConfig={ ( updatedConfig ) => updateScreenData( { config: { ...screenData.config, copied_table: updatedConfig } } ) }
					/>
					<EventPanel
						title={ __( 'A table ID was changed', 'tablepress' ) }
						label={ __( 'Send an email notification when a table ID is changed.', 'tablepress' ) }
						config={ screenData.config.changed_table_id }
						updateConfig={ ( updatedConfig ) => updateScreenData( { config: { ...screenData.config, changed_table_id: updatedConfig } } ) }
					/>
				</Panel>
			</div>
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
						onClick={ () => saveEmailNotificationsConfig() }
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
