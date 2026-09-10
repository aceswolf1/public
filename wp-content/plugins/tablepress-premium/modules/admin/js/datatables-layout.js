/**
 * JavaScript code for the "Edit" section integration of the DataTables Layout feature.
 *
 * @package TablePress
 * @subpackage DataTables Layout
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useRef } from 'react';
import {
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import { $ } from '../../../admin/js/common/functions';
import { extractKeysFromJsObjectString } from './common/extract-js-object-keys';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-layout';

/*
* The available layout positions.
*/
const layoutPositions = [
	{
		'position': 'available',
		'label': __( 'Available features', 'tablepress' ) + ':',
		'className': 'full',
	},
	{
		'position': 'top',
		'className': 'full',
	},
	{
		'position': 'topStart',
		'className': 'start',
	},
	{
		'position': 'topEnd',
		'className': 'end',
	},
	{
		'position': 'table',
		'className': 'full drag-box-section-wrapper-table',
	},
	{
		'position': 'bottom',
		'className': 'full',
	},
	{
		'position': 'bottomStart',
		'className': 'start',
	},
	{
		'position': 'bottomEnd',
		'className': 'end',
	},
];

// Register the default features for the "DataTables Layout" feature module.
let registeredDataTablesLayoutFeatures = {
	pageLength: {
		label: __( 'Page Length', 'tablepress' ),
		defaultPosition: 'topStart',
		option: 'datatables_lengthchange',
		requirements: ( tableOptions ) => ( tableOptions.datatables_lengthchange && tableOptions.datatables_paginate ),
	},
	search: {
		label: __( 'Search Field', 'tablepress' ),
		defaultPosition: 'topEnd',
		option: 'datatables_filter',
		requirements: ( tableOptions ) => ( tableOptions.datatables_filter ),
	},
	info: {
		label: __( 'Table Info', 'tablepress' ),
		defaultPosition: 'bottomStart',
		option: 'datatables_info',
		requirements: ( tableOptions ) => ( tableOptions.datatables_info ),
	},
	paging: {
		label: __( 'Pagination', 'tablepress' ),
		defaultPosition: 'bottomEnd',
		option: 'datatables_paginate',
		requirements: ( tableOptions ) => ( tableOptions.datatables_paginate ),
	},
};

const Section = ( { tableOptions, updateTableOptions } ) => {
	const dragBoxSectionRef = useRef( null );

	const requirementsFulfilled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );
	const customCommandsObjectKeys = extractKeysFromJsObjectString( '{' + tableOptions.datatables_custom_commands + '}' );
	const datatablesLayoutInCustomCommands = ( '' !== tableOptions.datatables_custom_commands && ( customCommandsObjectKeys.includes( 'layout' ) || customCommandsObjectKeys.includes( 'dom' ) ) );
	const layoutEnabled = ( requirementsFulfilled && ! datatablesLayoutInCustomCommands );

	// Initialize the drag box sections and the drag boxes.
	useEffect( () => {
		// Allow other feature modules to register their features.
		registeredDataTablesLayoutFeatures = applyFilters( 'tablepress.dataTablesLayoutFeatures', registeredDataTablesLayoutFeatures );

		/**
		 * Creates a drag box section wrapper.
		 *
		 * @param {Object} props           Function parameters.
		 * @param {string} props.position  The position name of the section.
		 * @param {string} props.label     The label of the section
		 * @param {string} props.className The className of the section.
		 * @return {HTMLElement} The created drag box section wrapper element.
		 */
		const createDragBoxSectionWrapper = ( { position, label = '', className } ) => {
			const dragBoxSectionWrapper = document.createElement( 'div' );
			dragBoxSectionWrapper.className = `drag-box-section-wrapper drag-box-section-wrapper-${ className }`;
			if ( '' !== label ) {
				label = `<div class="drag-box-section-label">${ label }</div>`;
			}
			dragBoxSectionWrapper.innerHTML = `${ label }<div class="drag-box-wrapper drag-box-wrapper-${ position }" data-position="${ position }"></div>`;
			return dragBoxSectionWrapper;
		};

		/*
		* Add the drag box wrappers sections to the screen.
		*/
		let dragBoxSection = dragBoxSectionRef.current;
		layoutPositions.forEach( ( layoutPosition ) => {
			const dragBoxSectionWrapper = createDragBoxSectionWrapper( layoutPosition );
			dragBoxSection.appendChild( dragBoxSectionWrapper );
			layoutPosition.wrapper = dragBoxSectionWrapper.querySelector( '.drag-box-wrapper' );
			if ( 'table' === layoutPosition.position ) {
				layoutPosition.wrapper.classList.remove( 'drag-box-wrapper' );
				layoutPosition.wrapper.innerHTML = `<span>${ __( 'Table', 'tablepress' ) }</span>`;
			}

			// Wrap elements that belong to the table section in a an additional div.
			if ( 'available' === layoutPosition.position ) {
				const dragBoxTableSection = document.createElement( 'div' );
				dragBoxTableSection.className = 'drag-box-table-section';
				dragBoxSection.appendChild( dragBoxTableSection );
				dragBoxSection = dragBoxTableSection;
			}
		} );

		/*
		* Make the list of DataTables Layout features sortable by initializing the jQuery UI Sortable interaction.
		*/
		jQuery( ( j$ ) => {
			j$( '#tablepress_edit-datatables-layout .drag-box-wrapper' ).sortable( {
				containment: '#tablepress_edit-datatables-layout .drag-box-section',
				cursor: 'move',
				placeholder: 'drag-box-placeholder',
				connectWith: '#tablepress_edit-datatables-layout .drag-box-wrapper',
			} );
			// Use a clone when dragging a drag box from the "available" section, so that it's kept there as well.
			j$( '#tablepress_edit-datatables-layout .drag-box-wrapper-available' ).sortable( 'option', 'helper', 'clone' );
		} );

		return () => {
			jQuery( ( j$ ) => {
				j$( '#tablepress_edit-datatables-layout .drag-box-wrapper' ).sortable( 'destroy' );
				j$( '#tablepress_edit-datatables-layout .drag-box-section' ).empty();
			} );
		};
	}, [] );

	useEffect( () => {
		/**
		 * Handles updating the internal state after moving a drag box via drag and drop.
		 *
		 * @param {Event}  event The event object.
		 * @param {Object} ui    The jQuery UI object.
		 */
		const handleDragBoxMove = function ( event, ui ) {
			// Only handle the second call of this callback, and ignore the one that is trigger when a wrapper is left.
			if ( this !== ui.item.parent()[0] ) {
				return;
			}

			const datatables_layout = { ...tableOptions.datatables_layout };

			const dragBox = jQuery( ui.item )[0];
			const feature = dragBox.dataset.feature;

			const from_index = dragBox.dataset.index;
			const from_position = jQuery( ui.sender )[0]?.dataset.position;
			const to_index = jQuery( dragBox ).index();
			const to_position = this.dataset.position;

			if ( ui.sender !== null ) {
				// Move a feature from one position to another.

				if ( 'available' === from_position ) {
					// If a feature is dragged out of the "available" section, it will no longer be "unused".
					datatables_layout.unused = tableOptions.datatables_layout.unused.filter( ( unused_feature ) => feature !== unused_feature );
				} else {
					datatables_layout[ from_position ].splice( from_index, 1 );
				}

				if ( 'available' === to_position ) {
					// If a feature is dragged into the "available" section, it's possible that it will be "unused".
					if ( ! Object.values( tableOptions.datatables_layout ).some( ( position ) => position.includes( feature ) ) ) {
						datatables_layout.unused.push( feature );
					}
				} else {
					datatables_layout[ to_position ].splice( to_index, 0, feature );
				}
			} else {
				// Move a feature within the same position.

				// eslint-disable-next-line no-lonely-if
				if ( 'available' !== to_position ) {
					// Handle position changes within the same wrapper, but disallow reordering the "available" features.
					const offset = ( from_index < to_index ) ? 1 : 0;
					datatables_layout[ to_position ].splice( to_index + offset, 0, tableOptions.datatables_layout[ to_position ].splice( from_index, 1 )[0] );
				}
			}

			updateTableOptions( { datatables_layout } );
		};

		/**
		 * Handles the double click event on a drag box.
		 * This event is used to add or remove features from the layout.
		 *
		 * @param {Event} event The event object.
		 */
		const handleDragBoxDblclick = ( event ) => {
			if ( ! event.target ) {
				return;
			}

			const dragBox = event.target.closest( '.drag-box' );
			if ( ! dragBox ) {
				return;
			}

			const datatables_layout = { ...tableOptions.datatables_layout };

			const feature = dragBox.dataset.feature;
			const position = dragBox.parentNode.dataset.position;

			if ( 'available' === position ) {
				// If a feature is added from the "available" section, it will no longer be "unused".
				const featureDefaultPosition = registeredDataTablesLayoutFeatures[ feature ].defaultPosition;
				datatables_layout[ featureDefaultPosition ].push( feature );
				datatables_layout.unused = tableOptions.datatables_layout.unused.filter( ( unused_feature ) => feature !== unused_feature );
			} else {
				const index = dragBox.dataset.index;
				datatables_layout[ position ].splice( index, 1 );
				// If a feature is removed from a section, it's possible that it will be "unused".
				if ( ! Object.values( tableOptions.datatables_layout ).some( ( checked_position ) => checked_position.includes( feature ) ) ) {
					datatables_layout.unused.push( feature );
				}
			}

			updateTableOptions( { datatables_layout } );
		};

		jQuery( ( j$ ) => {
			j$( '#tablepress_edit-datatables-layout .drag-box-wrapper' ).sortable( 'option', 'update', handleDragBoxMove );
		} );
		dragBoxSectionRef.current.ondblclick = handleDragBoxDblclick;
	}, [ tableOptions.datatables_layout ] ); // eslint-disable-line react-hooks/exhaustive-deps -- The dependency `updateTableOptions` doesn't change.

	// Re-create the drag boxes based on the current table options.
	useEffect( () => {
		/**
		 * Creates a drag box for a feature.
		 *
		 * @param {string} feature The feature name.
		 * @param {string} label   The feature label.
		 * @param {number} index   The drag box's position/index in its wrapper.
		 * @return {HTMLElement} The created drag box element.
		 */
		const createDragBox = ( feature, label, index ) => {
			const dragBox = document.createElement( 'div' );
			dragBox.className = 'drag-box';
			dragBox.dataset.feature = feature;
			dragBox.dataset.index = index;
			dragBox.innerHTML = `
				<input type="hidden">
				<div>${ label }</div>
			`;
			return dragBox;
		};

		const availableFeaturesWrapper = layoutPositions.find( ( layoutPosition ) => 'available' === layoutPosition.position ).wrapper;

		// Keep a list of features which have unfulfilled requirements.
		const unavailableFeatures = [];

		const requirementsTableOptions = {
			datatables_alphabetsearch: tableOptions.datatables_alphabetsearch,
			datatables_buttons: tableOptions.datatables_buttons,
			datatables_columnfilterwidgets: tableOptions.datatables_columnfilterwidgets,
			datatables_filter: tableOptions.datatables_filter,
			datatables_info: tableOptions.datatables_info,
			datatables_lengthchange: tableOptions.datatables_lengthchange,
			datatables_paginate: tableOptions.datatables_paginate,
			datatables_searchbuilder: tableOptions.datatables_searchbuilder,
			datatables_searchpanes: tableOptions.datatables_searchpanes,
		};

		Object.entries( registeredDataTablesLayoutFeatures ).forEach( ( [ availableFeature, availableFeatureData ], index ) => {
			if ( availableFeatureData.requirements( requirementsTableOptions ) ) {
				/*
				* If a feature for which the requirements are (now) fulfilled is not yet in any position (including "unused"), add it to the default position.
				* (Note: Normally, this should never be the case, as features are added to the default position in PHP, but let's keep this as a safety net.)
				*/
				/*
				// @TODO: Keep this commented out until the issue maybe happens again, as updating the state from within the effect could have side effects.
				const datatables_layout = { ...tableOptions.datatables_layout };
				if ( ! Object.values( datatables_layout ).some( ( position ) => position.includes( availableFeature ) ) ) {
					if ( undefined === datatables_layout[ availableFeatureData.defaultPosition ] ) {
						datatables_layout[ availableFeatureData.defaultPosition ] = [];
					}
					datatables_layout[ availableFeatureData.defaultPosition ].push( availableFeature );
				}
				updateTableOptions( { datatables_layout } );
				*/

				// Add a drag box for each feature to the "Available features" section.
				availableFeaturesWrapper.appendChild( createDragBox( availableFeature, availableFeatureData.label, index ) );
			} else {
				unavailableFeatures.push( availableFeature );
			}
		} );

		// For each position except "unused", add drag boxes for the configured features.
		const datatables_layout = { ...tableOptions.datatables_layout };
		delete datatables_layout.unused;
		Object.entries( datatables_layout ).forEach( ( [ position, positionFeatures ] ) => {
			const positionWrapper = layoutPositions.find( ( layoutPosition ) => layoutPosition.position === position ).wrapper;
			positionFeatures.forEach( ( positionFeature, index ) => {
				if ( registeredDataTablesLayoutFeatures[ positionFeature ] ) {
					const positionFeatureData = registeredDataTablesLayoutFeatures[ positionFeature ];
					if ( positionFeatureData.requirements( requirementsTableOptions) ) {
						positionWrapper.appendChild( createDragBox( positionFeature, positionFeatureData.label, index ) );
					}
				}
			} );
		} )

		$( '#notice-datatables-layout-unavailable-features-notice' ).style.display = ( unavailableFeatures.length > 0 ) ? 'block' : 'none';
		$( '#notice-datatables-layout-unavailable-features-list' ).innerText = unavailableFeatures.map( ( unavailableFeature ) => registeredDataTablesLayoutFeatures[ unavailableFeature ].label ).join( ', ' );

		// Clean-up function for the effect.
		return () => {
			// Remove all drag boxes.
			document.querySelectorAll( '#tablepress_edit-datatables-layout .drag-box-section .drag-box' ).forEach( ( dragBox ) => ( dragBox.remove() ) );
		};
	}, [
		/*
		 * All dependencies that are used by requirements() checks need to be listed here.
		 * Using the full `tableOpttions` object would lead to the effect being called on every render.
		 */
		tableOptions.datatables_layout,
		tableOptions.datatables_alphabetsearch,
		tableOptions.datatables_buttons,
		tableOptions.datatables_columnfilterwidgets,
		tableOptions.datatables_filter,
		tableOptions.datatables_info,
		tableOptions.datatables_lengthchange,
		tableOptions.datatables_paginate,
		tableOptions.datatables_searchbuilder,
		tableOptions.datatables_searchpanes,
	] );

	return (
		<>
			<ModuleHelp slug={ MODULE_SLUG }>
				<p>
					{ __( 'The “Table Layout” allows you to change the position of features around the table.', 'tablepress' ) }
				</p>
				<p>
					{ __( 'Choose the desired features and drag them to the desired position around the table:', 'tablepress' ) }
					{ ' ' }
					{ __( 'Use drag and drop with your mouse or double-click to remove a feature.', 'tablepress' ) }
					{ ' ' }
					{ sprintf( __( 'Double-clicking a feature in the “%s” field adds the feature to its default position.', 'tablepress' ), __( 'Available features', 'tablepress' ) ) }
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
					( requirementsFulfilled && datatablesLayoutInCustomCommands ) && (
						<span>
							<em>
								{
									createInterpolateElement(
										sprintf(
											__( 'This feature is currently being controlled via the <codeLayout /> or <codeDom /> command in the “%1$s” text field in the “%2$s” section.', 'tablepress' ),
											__( 'Custom Commands', 'tablepress' ),
											__( 'Table Features for Site Visitors', 'tablepress' ),
										),
										{
											codeLayout: <code>&quot;layout&quot;</code>,
											codeDom: <code>&quot;dom&quot;</code>,
										},
									)
								}
							</em>
						</span>
					)
				}
				<VStack
					id="wrapper-datatables-layout"
					style={ {
						display: ( layoutEnabled ) ? 'block' : 'none',
					} }
				>
					<span>
						{
							__( 'Choose the desired features and drag them to the desired position around the table:', 'tablepress' )
							+ ' ' + __( 'Use drag and drop with your mouse or double-click to remove a feature.', 'tablepress' )
							+ ' ' + sprintf(
								__( 'Double-clicking a feature in the “%s” field adds the feature to its default position.', 'tablepress' ),
								__( 'Available features', 'tablepress' ),
							)
						}
					</span>
					<div
						ref={ dragBoxSectionRef }
						className="drag-box-section"
					></div>
					<span id="notice-datatables-layout-unavailable-features-notice">
						<em>
							{
								createInterpolateElement(
									__( 'These features are currently not available for positioning because they are deactivated: <span />.', 'tablepress' ),
									{
										span: <span id="notice-datatables-layout-unavailable-features-list"></span>,
									},
								)
							}
						</em>
					</span>
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
