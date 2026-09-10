/**
 * JavaScript code for the EventPanel component.
 *
 * @package TablePress
 * @subpackage Email Notifications Screen
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { useRef, useState } from 'react';
import {
	Disabled,
	DropdownMenu,
	FormTokenField,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	PanelBody,
	PanelRow,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { replace } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { isEmail } from '@wordpress/url';

// Custom component to conditionally disable its children, used for the PlaceholderInserter.
const ConditionalDisabled = ( { condition, children } ) => (
	condition ? ( <Disabled>{ children }</Disabled> ) : children
);

/**
 * Returns the PlaceholderInserter component's JSX markup.
 *
 * @param {Object}   props             Function parameters.
 * @param {string}   props.field       Field to insert the placeholder into.
 * @param {string}   props.fieldValue  Field value.
 * @param {Function} props.updateField Callback for field value changes.
 * @return {Object} PlaceholderInserter component.
 */
const PlaceholderInserter = ( { field, fieldValue, updateField } ) => {
	const insertPlaceholder = ( placeholder ) => {
		const textarea = field.current;
		const newFieldValue = fieldValue.substring( 0, textarea.selectionStart ) + placeholder + fieldValue.substring( textarea.selectionEnd );
		updateField( newFieldValue );
	}

	return (
		<DropdownMenu
			icon={ replace }
			text={ __( 'Placeholder', 'tablepress' ) }
			label={ __( 'Insert a placeholder at the cursor position.', 'tablepress' ) }
			controls={ [
				{
					title: __( 'Table ID', 'tablepress' ),
					onClick: () => insertPlaceholder( '%table_id%' ),
				},
				{
					title: __( 'Table Name', 'tablepress' ),
					onClick: () => insertPlaceholder( '%table_name%' ),
				},
				{
					title: __( 'Table Description', 'tablepress' ),
					onClick: () => insertPlaceholder( '%table_description%' ),
				},
				{
					title: __( 'Site URL', 'tablepress' ),
					onClick: () => insertPlaceholder( '%site_url%' ),
				},
				{
					title: __( 'Table Edit URL', 'tablepress' ),
					onClick: () => insertPlaceholder( '%edit_url%' ),
				},
				{
					title: __( 'List of Tables URL', 'tablepress' ),
					onClick: () => insertPlaceholder( '%list_url%' ),
				},
				{
					title: __( 'User', 'tablepress' ),
					onClick: () => insertPlaceholder( '%user%' ),
				},
				{
					title: __( 'Date and Time', 'tablepress' ),
					onClick: () => insertPlaceholder( '%date_time%' ),
				},
				{
					title: __( 'Old Table ID', 'tablepress' ),
					onClick: () => insertPlaceholder( '%old_table_id%' ),
				},
				{
					title: __( 'Old Table Name', 'tablepress' ),
					onClick: () => insertPlaceholder( '%old_table_name%' ),
				},
				{
					title: __( 'Old Table Description', 'tablepress' ),
					onClick: () => insertPlaceholder( '%old_table_description%' ),
				},
			] }
		/>
	);
};

/**
 * Returns the EventPanel component's JSX markup.
 *
 * @param {Object}   props              Function parameters.
 * @param {string}   props.title        Panel title.
 * @param {string}   props.label        Active ToggleControl label.
 * @param {Object}   props.config       Notification configuration.
 * @param {Function} props.updateConfig Callback for configuration changes.
 * @return {Object} EventPanel component.
 */
const EventPanel = ( { title, label, config, updateConfig } ) => {
	const fieldRefs = {
		subject: useRef(),
		message: useRef(),
	};
	const [ panelInitialOpen ] = useState( config.active );

	return (
		<PanelBody
			title={ title }
			initialOpen={ panelInitialOpen }
		>
			<PanelRow>
				<div
					style={ {
						flex: 1
					} }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ config.active }
						label={ label }
						onChange={ ( checked ) => {
							const updatedConfig = { ...config };
							updatedConfig.active = checked;
							updateConfig( updatedConfig );
						} }
					/>
				</div>
			</PanelRow>
			<PanelRow>
				<div
					style={ {
						flex: 1
					} }
				>
					<FormTokenField
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Recipients', 'tablepress' ) }
						onChange={ ( recipients ) => {
							const updatedConfig = { ...config };
							updatedConfig.recipients = [ ...recipients ];
							updateConfig( updatedConfig );
						} }
						value={ config.recipients }
						suggestions={ tp.emailNotifications.defaultEmails }
						__experimentalValidateInput={ ( input ) => isEmail( input ) }
						disabled={ ! config.active }
						tokenizeOnBlur={ true }
						tokenizeOnSpace={ true }
					/>
				</div>
			</PanelRow>
			<PanelRow>
				<HStack
					alignment="bottom"
				>
					<div
						style={ {
							flex: 1
						} }
					>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							ref={ fieldRefs.subject }
							label={ __( 'Email subject', 'tablepress' ) }
							onChange={ ( subject ) => {
								const updatedConfig = { ...config };
								updatedConfig.subject = subject;
								updateConfig( updatedConfig );
							} }
							value={ config.subject }
							disabled={ ! config.active }
						/>
					</div>
					<ConditionalDisabled condition={ ! config.active }>
						<PlaceholderInserter
							field={ fieldRefs.subject }
							fieldValue={ config.subject }
							updateField={ ( subject ) => {
								const updatedConfig = { ...config };
								updatedConfig.subject = subject;
								updateConfig( updatedConfig );
							} }
						/>
					</ConditionalDisabled>
				</HStack>
			</PanelRow>
			<PanelRow>
				<HStack>
					<div
						style={ {
							flex: 1
						} }
					>
						<TextareaControl
							__nextHasNoMarginBottom
							ref={ fieldRefs.message }
							label={ __( 'Email message', 'tablepress' ) }
							onChange={ ( message ) => {
								const updatedConfig = { ...config };
								updatedConfig.message = message;
								updateConfig( updatedConfig );
							} }
							value={ config.message }
							disabled={ ! config.active }
							rows="5"
						/>
					</div>
					<ConditionalDisabled condition={ ! config.active }>
						<PlaceholderInserter
							field={ fieldRefs.message }
							fieldValue={ config.message }
							updateField={ ( message ) => {
								const updatedConfig = { ...config };
								updatedConfig.message = message;
								updateConfig( updatedConfig );
							} }
						/>
					</ConditionalDisabled>
				</HStack>
			</PanelRow>
		</PanelBody>
	);
};

export default EventPanel;
