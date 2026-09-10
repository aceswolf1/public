/**
 * JavaScript code for the "Edit" section integration of the DataTables Advanced Loading feature.
 *
 * @package TablePress
 * @subpackage DataTables Advanced Loading
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
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import ModuleHelp from './common/module-help';
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';

const MODULE_SLUG = 'datatables-advanced-loading';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const advancedLoadingEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );

	// Open the "Advanced Settings" if a different value than the default is set.
	useEffect( () => {
		if ( tableOptions.datatables_advanced_loading && tableOptions.datatables_advanced_loading_html_rows !== 10 ) { // 10 is the default Shortcode parameter value.
			document.getElementById( 'tablepress-datatables_advanced_loading-advanced-settings' ).open = true;
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
					! advancedLoadingEnabled && (
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
					serversideProcessingEnabled && (
						<span>
							<em>
								{
									sprintf(
										__( 'This feature is only available when the “%1$s” feature is turned off.', 'tablepress' ),
										__( 'Server-side Processing', 'tablepress' ),
									)
								}
							</em>
						</span>
					)
				}
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Load the table from a JavaScript array.', 'tablepress' ) }
					checked={ tableOptions.datatables_advanced_loading }
					disabled={ ! advancedLoadingEnabled || serversideProcessingEnabled }
					onChange={ ( datatables_advanced_loading ) => {
						const updatedTableOptions = { datatables_advanced_loading };
						if ( datatables_advanced_loading ) {
							updatedTableOptions.datatables_serverside_processing = false;
							updatedTableOptions.highlight = '';
							updatedTableOptions.row_highlight = '';
						}
						updateTableOptions( updatedTableOptions );
					} }
				/>
				<details id="tablepress-datatables_advanced_loading-advanced-settings">
					<summary>{ __( 'Advanced settings', 'tablepress' ) }</summary>
					<VStack>
						<HStack alignment="left">
							<label htmlFor="option-datatables_advanced_loading_html_rows">
								<HStack>
									{
										createInterpolateElement(
											__( 'Show <input /> rows as HTML.', 'tablepress' ),
											{
												input: (
													<NumberControl
														size="compact"
														id="option-datatables_advanced_loading_html_rows"
														title={ __( 'This field must contain a positive number.', 'tablepress' ) }
														isDragEnabled={ false }
														value={ tableOptions.datatables_advanced_loading_html_rows }
														disabled={ ! advancedLoadingEnabled || serversideProcessingEnabled || ! tableOptions.datatables_advanced_loading }
														onChange={ ( datatables_advanced_loading_html_rows ) => {
															datatables_advanced_loading_html_rows = '' !== datatables_advanced_loading_html_rows ? parseInt( datatables_advanced_loading_html_rows, 10 ) : 1;
															updateTableOptions( { datatables_advanced_loading_html_rows } );
														} }
														min={ 1 }
														required={ true }
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
				</details>
				<span>
					<em>
						{ __( 'Please note that features like “Cell Highlighting”, “Row Highlighting”, and “Server-side Processing” are not compatible with Advanced Loading.', 'tablepress' ) }
					</em>
				</span>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);
