/**
 * JavaScript code for the "Edit" section integration of the DataTables RowGroup feature.
 *
 * @package TablePress
 * @subpackage DataTables RowGroup
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
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import { $ } from '../../../admin/js/common/functions';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-rowgroup';

const Section = ( { tableOptions, updateTableOptions } ) => {
	const rowgroupEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_rowgroup && tableOptions.datatables_rowgroup_datasrc !== '1' ) { // 1 is the default Shortcode parameter value.
			$( '#tablepress-datatables_rowgroup-advanced-settings' ).open = true;
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

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
					! rowgroupEnabled && (
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
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Group rows that belong to the same category.', 'tablepress' ) }
					checked={ tableOptions.datatables_rowgroup }
					disabled={ ! rowgroupEnabled }
					onChange={ ( datatables_rowgroup ) => updateTableOptions( { datatables_rowgroup } ) }
				/>
				<details id="tablepress-datatables_rowgroup-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<HStack alignment="left">
							<label htmlFor="option-datatables_rowgroup_datasrc">
								<HStack>
									{
										createInterpolateElement(
											__( 'Use these columns as group categories: <input />', 'tablepress' ),
											{
												input: (
													<TextControl
														__nextHasNoMarginBottom
														__next40pxDefaultSize
														id="option-datatables_rowgroup_datasrc"
														title={ __( 'This field can only contain letters, numbers, commas, spaces, and hyphens (-).', 'tablepress' ) }
														pattern="[0-9A-Z, \-]+"
														value={ tableOptions.datatables_rowgroup_datasrc }
														disabled={ ! rowgroupEnabled || ! tableOptions.datatables_rowgroup }
														onChange={ ( datatables_rowgroup_datasrc ) => updateTableOptions( { datatables_rowgroup_datasrc: datatables_rowgroup_datasrc.replace( /[^0-9A-Z, -]/g, '' ) } ) }
														required={ true }
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
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);

addFilter( 'tablepress.optionsValidateFields', `tp/${ MODULE_SLUG }/validate-fields`, ( formValid, tableOptions ) => {
	// The "rowgroup datasrc" field must not be empty and it must start with a valid number or letter.
	if ( tableOptions.datatables_rowgroup && ! ( /^[1-9A-Z]/ ).test( tableOptions.datatables_rowgroup_datasrc ) ) {
		// This alert can not be replaced by the `Alert` component, as that does not stop the cmd+S keyboard shortcut from running.
		window.alert( sprintf( __( 'The entered value in the “%1$s” field is invalid.', 'tablepress' ), __( 'Row Grouping Column', 'tablepress' ) ) );
		const $field = $( '#option-datatables_rowgroup_datasrc' );
		$field.closest( 'details' ).open = true;
		$field.focus();
		$field.select();
		formValid = false;
	}

	return formValid;
} );
