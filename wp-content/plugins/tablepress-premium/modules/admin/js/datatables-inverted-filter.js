/**
 * JavaScript code for the "Edit" section integration of the DataTables Inverted Filter feature.
 *
 * @package TablePress
 * @subpackage DataTables Inverted Filter
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import {
	CheckboxControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-inverted-filter';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const invertedFilterEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );

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
					! invertedFilterEnabled && (
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
					label={ __( 'Turn the filtering into a search and hide the table if no search term is entered.', 'tablepress' ) }
					checked={ tableOptions.datatables_inverted_filter }
					disabled={ ! invertedFilterEnabled || serversideProcessingEnabled }
					onChange={ ( datatables_inverted_filter ) => {
						const updatedTableOptions = { datatables_inverted_filter };
						if ( datatables_inverted_filter ) {
							updatedTableOptions.datatables_alphabetsearch = false;
							updatedTableOptions.datatables_column_filter = '';
							updatedTableOptions.datatables_columnfilterwidgets = false;
							updatedTableOptions.datatables_fuzzysearch = false;
							updatedTableOptions.datatables_searchbuilder = false;
							updatedTableOptions.datatables_searchpanes = false;
						}
						updateTableOptions( updatedTableOptions );
					} }
				/>
				<span>
					<em>
						{ __( 'Please note that features like “Alphabet Search”, “Column Filter Dropdowns”, “Custom Search Builder”, “Fuzzy Search”, “Individual Column Filtering”, and “Search Panes” are not compatible with Inverted Filtering.', 'tablepress' ) }
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
