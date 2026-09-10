/**
 * JavaScript code for the "Edit" section integration of the DataTables Buttons feature.
 *
 * @package TablePress
 * @subpackage DataTables Buttons
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useRef } from 'react';
import {
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import { extractKeysFromJsObjectString } from './common/extract-js-object-keys';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-buttons';

const Section = ( { tableOptions, updateTableOptions } ) => {
	const buttonsWrapperActive = useRef( null );
	const buttonsWrapperInactive = useRef( null );

	const requirementsFulfilled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );
	const datatablesButtonsInCustomCommands = ( '' !== tableOptions.datatables_custom_commands && extractKeysFromJsObjectString( '{' + tableOptions.datatables_custom_commands + '}' ).includes( 'buttons' ) );
	const buttonsEnabled = ( requirementsFulfilled && ! datatablesButtonsInCustomCommands );

	/**
	 * Sets the "datatables_buttons" table option according to the chosen boxes.
	 */
	const handleDragBoxMove = () => {
		const datatables_buttons = [ ...buttonsWrapperActive.current.querySelectorAll( ':scope input' ) ]
			.map( ( field ) => field.value )
			.join( ',' );
		updateTableOptions( { datatables_buttons } );
	};

	useEffect( () => {
		// Dynamically add the buttons to the respective wrapper using vanilla JS, so that React doesn't interfere with them.
		const activeButtons = tableOptions.datatables_buttons.split( ',' );
		const buttons = [
			{
				value: 'colvis',
				label: __( 'Colvis', 'tablepress' ),
			},
			{
				value: 'copy',
				label: __( 'Copy', 'tablepress' ),
			},
			{
				value: 'csv',
				label: __( 'CSV', 'tablepress' ),
			},
			{
				value: 'excel',
				label: __( 'Excel', 'tablepress' ),
			},
			{
				value: 'pdf',
				label: __( 'PDF', 'tablepress' ),
			},
			{
				value: 'print',
				label: __( 'Print', 'tablepress' ),
			},
		];

		buttons.forEach( ( button ) => {
			const buttonDragBox = document.createElement( 'div' );
			buttonDragBox.className = 'drag-box';
			buttonDragBox.innerHTML = `
				<input type="hidden" value="${ button.value }" />
				<div>${ button.label }</div>
			`;
			( activeButtons.includes( button.value ) ? buttonsWrapperActive.current : buttonsWrapperInactive.current ).appendChild( buttonDragBox );
		} );

		/**
		 * Make the list of DataTables Buttons sortable.
		 */
		jQuery( ( j$ ) => {
			j$( buttonsWrapperActive.current ).sortable( {
				containment: '#tablepress_edit-datatables-buttons .drag-box-section',
				cursor: 'move',
				placeholder: 'drag-box-placeholder',
				connectWith: '#datatables-buttons-drag-box-wrapper-inactive',
				update: handleDragBoxMove,
			} );
			j$( buttonsWrapperInactive.current ).sortable( {
				containment: '#tablepress_edit-datatables-buttons .drag-box-section',
				cursor: 'move',
				placeholder: 'drag-box-placeholder',
				connectWith: '#datatables-buttons-drag-box-wrapper-active',
			} );
		} );

		// Variables for the cleanup function, to prevent warnings about potentially changed refs.
		const buttonsWrapperActiveCurrent = buttonsWrapperActive.current;
		const buttonsWrapperInactiveCurrent = buttonsWrapperInactive.current;

		return () => {
			jQuery( ( j$ ) => {
				j$( buttonsWrapperActiveCurrent ).sortable( 'destroy' ).empty();
				j$( buttonsWrapperInactiveCurrent ).sortable( 'destroy' ).empty();
			} );
		};
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed. The dependency `handleDragBoxMove` doesn't change.

	useEffect( () => {
		const handleDragBoxDblclick = function () {
			const dragBox = this;
			const dragBoxWrapper = dragBox.closest( '.drag-box-wrapper' );
			const targetDragBoxWrapper = ( dragBoxWrapper === buttonsWrapperActive.current ) ? buttonsWrapperInactive.current : buttonsWrapperActive.current;
			targetDragBoxWrapper.appendChild( dragBox );

			handleDragBoxMove();
		};

		const disableFields = ( field ) => {
			field.disabled = ! buttonsEnabled;
			field.parentNode.ondblclick = ( buttonsEnabled ? handleDragBoxDblclick : null );
		};

		buttonsWrapperActive.current.querySelectorAll( ':scope input' ).forEach( disableFields );
		buttonsWrapperInactive.current.querySelectorAll( ':scope input' ).forEach( disableFields );

		jQuery( ( j$ ) => {
			j$( buttonsWrapperActive.current ).sortable( 'option', 'disabled', ! buttonsEnabled );
			j$( buttonsWrapperInactive.current ).sortable( 'option', 'disabled', ! buttonsEnabled );
		} );
	}, [ buttonsEnabled ] ); // eslint-disable-line react-hooks/exhaustive-deps -- The dependency `handleDragBoxMove` doesn't change.

	return (
		<>
			<ModuleHelp slug={ MODULE_SLUG }>
				<p>
					{ __( 'The “Buttons” module can add buttons for “Copy to Clipboard”, “Save to PDF”, “Save to Excel”, “Save to CSV”, a “Print view”, and “Column Visibility” above your tables, so that site visitors can perform those actions.', 'tablepress' ) }
				</p>
				<p>
					{ __( 'Choose the desired buttons and drag them into the desired order:', 'tablepress' ) }
					{ ' ' }
					{ __( 'Use drag and drop with your mouse or double-click the buttons.', 'tablepress' ) }
				</p>
			</ModuleHelp>
			<VStack
				spacing="16px"
				style={ {
					paddingTop: '6px',
				} }
			>
				{
					( ! requirementsFulfilled ) && (
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
					( requirementsFulfilled && datatablesButtonsInCustomCommands ) && (
						<span>
							<em>
								{
									createInterpolateElement(
										sprintf(
											__( 'This feature is currently being controlled via the <code /> command in the “%1$s” text field in the “%2$s” section.', 'tablepress' ),
											__( 'Custom Commands', 'tablepress' ),
											__( 'Table Features for Site Visitors', 'tablepress' ),
										),
										{
											code: <code>&quot;buttons&quot;</code>,
										},
									)
								}
							</em>
						</span>
					)
				}
				<span>{ __( 'Choose the desired buttons and drag them into the desired order:', 'tablepress' ) }</span>
				<VStack className="drag-box-section" spacing="16px">
					<div className="drag-box-section-wrapper">
						<div className="drag-box-wrapper-label">{ __( 'Available buttons:', 'tablepress' ) }</div>
						<div
							ref={ buttonsWrapperInactive }
							id="datatables-buttons-drag-box-wrapper-inactive"
							className="drag-box-wrapper"
						></div>
					</div>
					<div className="drag-box-section-wrapper">
						<div className="drag-box-wrapper-label">{ __( 'Shown buttons:', 'tablepress' ) }</div>
						<div
							ref={ buttonsWrapperActive }
							id="datatables-buttons-drag-box-wrapper-active"
							className="drag-box-wrapper"
						></div>
					</div>
				</VStack>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);

// Register the "Buttons" feature for the "DataTables Layout" feature module.
addFilter( 'tablepress.dataTablesLayoutFeatures', `tp/${ MODULE_SLUG }/add-datatables_layout-features`, ( features ) => {
	return {
		...features,
		buttons: {
			label: __( 'Buttons', 'tablepress' ),
			defaultPosition: 'top',
			option: 'datatables_buttons',
			requirements: ( tableOptions ) => ( '' !== tableOptions.datatables_buttons ),
		},
	};
} );
