/**
 * JavaScript code for the "Edit" section integration of the DataTables AlphabetSearch feature.
 *
 * @package TablePress
 * @subpackage DataTables AlphabetSearch
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
	RadioControl,
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';
import { $ } from '../../../admin/js/common/functions';

const MODULE_SLUG = 'datatables-alphabetsearch';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const alphabetSearchEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const invertedFilterEnabled = ( features.includes( 'datatables-inverted-filter' ) && tableOptions.datatables_inverted_filter );

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_alphabetsearch && (
			tableOptions.datatables_alphabetsearch_column !== '1' || // 1 is the default Shortcode parameter value.
			tableOptions.datatables_alphabetsearch_alphabet !== 'latin' ||
			tableOptions.datatables_alphabetsearch_numbers ||
			! tableOptions.datatables_alphabetsearch_letters ||
			tableOptions.datatables_alphabetsearch_case_sensitive
			) ) {
			$( '#tablepress-datatables_alphabetsearch-advanced-settings' ).open = true;
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
					! alphabetSearchEnabled && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s”, “%2$s”, and “%3$s” settings in the “%4$s” and “%5$s” sections are used.', 'tablepress' ),
										__( 'Table Header', 'tablepress' ),
										__( 'Enable Visitor Features', 'tablepress' ),
										__( 'Search/Filtering', 'tablepress' ),
										__( 'Table Options', 'tablepress' ),
										__( 'Table Features for Site Visitors', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				{
					( serversideProcessingEnabled || invertedFilterEnabled ) && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s” feature is turned off.', 'tablepress' ),
										serversideProcessingEnabled ? __( 'Server-side Processing', 'tablepress' ) : __( 'Inverted Filtering', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Show an alphabet for filtering the table by a chosen column’s first character.', 'tablepress' ) }
					checked={ tableOptions.datatables_alphabetsearch }
					disabled={ ! alphabetSearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
					onChange={ ( datatables_alphabetsearch ) => updateTableOptions( { datatables_alphabetsearch } ) }
				/>
				<details id="tablepress-datatables_alphabetsearch-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<table className="tablepress-postbox-table fixed">
							<tbody>
								<tr className="top-border">
									<th className="column-1" scope="row"><label htmlFor="option-datatables_alphabetsearch_column">{ __( 'Search column', 'tablepress' ) }:</label></th>
									<td className="column-2">
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											id="option-datatables_alphabetsearch_column"
											title={ __( 'This field can only contain a number or letters.', 'tablepress' ) }
											pattern="[1-9][0-9]*|[A-Z]+"
											value={ tableOptions.datatables_alphabetsearch_column }
											disabled={ ! tableOptions.datatables_alphabetsearch || ! alphabetSearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											onChange={ ( datatables_alphabetsearch_column ) => updateTableOptions( { datatables_alphabetsearch_column: datatables_alphabetsearch_column.replace( /[^0-9A-Z]/g, '' ) } ) }
											required={ true }
											style={ {
												width: '65px',
											} }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Alphabet', 'tablepress' ) }:</th>
									<td className="column-2">
										<RadioControl
											hideLabelFromVision={ true }
											selected={ tableOptions.datatables_alphabetsearch_alphabet }
											disabled={ ! tableOptions.datatables_alphabetsearch || ! alphabetSearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											onChange={ ( datatables_alphabetsearch_alphabet ) => updateTableOptions( { datatables_alphabetsearch_alphabet } ) }
											options={ [
												{ label: __( 'Latin alphabet (A-Z)', 'tablepress' ), value: 'latin' },
												{ label: __( 'Greek alphabet (Α-Ω)', 'tablepress' ), value: 'greek' },
											] }
										/>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Display', 'tablepress' ) }:</th>
									<td className="column-2">
										<HStack
											alignment="left"
											spacing="20px"
										>
											<CheckboxControl
												__nextHasNoMarginBottom
												label={ __( 'Show letters.', 'tablepress' ) }
												checked={ tableOptions.datatables_alphabetsearch_letters }
												disabled={ ! tableOptions.datatables_alphabetsearch || ! alphabetSearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
												onChange={ ( datatables_alphabetsearch_letters ) => updateTableOptions( { datatables_alphabetsearch_letters } ) }
											/>
											<CheckboxControl
												__nextHasNoMarginBottom
												label={ __( 'Show numbers.', 'tablepress' ) }
												checked={ tableOptions.datatables_alphabetsearch_numbers }
												disabled={ ! tableOptions.datatables_alphabetsearch || ! alphabetSearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
												onChange={ ( datatables_alphabetsearch_numbers ) => updateTableOptions( { datatables_alphabetsearch_numbers } ) }
											/>
										</HStack>
									</td>
								</tr>
								<tr className="top-border">
									<th className="column-1" scope="row">{ __( 'Case sensitivity', 'tablepress' ) }:</th>
									<td className="column-2">
										<CheckboxControl
											__nextHasNoMarginBottom
											label={ __( 'Treat upper and lower case letters separately.', 'tablepress' ) }
											checked={ tableOptions.datatables_alphabetsearch_case_sensitive }
											disabled={ ! tableOptions.datatables_alphabetsearch || ! tableOptions.datatables_alphabetsearch_letters || ! alphabetSearchEnabled || serversideProcessingEnabled || invertedFilterEnabled }
											onChange={ ( datatables_alphabetsearch_case_sensitive ) => updateTableOptions( { datatables_alphabetsearch_case_sensitive } ) }
										/>
									</td>
								</tr>
							</tbody>
						</table>
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
	// The "AlphabetSearch column" field must be a non-empty number or a letter sequence.
	if ( tableOptions.datatables_alphabetsearch && ! ( /^([1-9][0-9]*|[A-Z]+)$/ ).test( tableOptions.datatables_alphabetsearch_column ) ) {
		// This alert can not be replaced by the `Alert` component, as that does not stop the cmd+S keyboard shortcut from running.
		window.alert( sprintf( __( 'The entered value in the “%1$s” field is invalid.', 'tablepress' ), __( 'Search column', 'tablepress' ) ) );
		const $field = $( '#option-datatables_alphabetsearch_column' );
		$field.closest( 'details' ).open = true;
		$field.focus();
		$field.select();
		formValid = false;
	}

	return formValid;
} );

// Register the "AlphabetSearch" feature for the "DataTables Layout" feature module.
addFilter( 'tablepress.dataTablesLayoutFeatures', `tp/${ MODULE_SLUG }/add-datatables_layout-features`, ( features ) => {
	return {
		...features,
		alphabetSearch: {
			label: __( 'Alphabet Search', 'tablepress' ),
			defaultPosition: 'top',
			option: 'datatables_alphabetsearch',
			requirements: ( tableOptions ) => ( tableOptions.datatables_filter && tableOptions.datatables_alphabetsearch ),
		},
	};
} );
