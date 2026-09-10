/**
 * JavaScript code for the "Edit" section integration of the DataTables SearchBuilder feature.
 *
 * @package TablePress
 * @subpackage DataTables SearchBuilder
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import {
	CheckboxControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-searchbuilder';

const Section = ( { tableOptions, updateTableOptions, features } ) => {
	const searchbuilderEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );
	const serversideProcessingEnabled = ( features.includes( 'datatables-serverside-processing' ) && tableOptions.datatables_serverside_processing );
	const invertedFilterEnabled = ( features.includes( 'datatables-inverted-filter' ) && tableOptions.datatables_inverted_filter );

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
					! searchbuilderEnabled && (
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
					label={ __( 'Show a search builder interface for filtering from groups and using conditions.', 'tablepress' ) }
					checked={ tableOptions.datatables_searchbuilder }
					disabled={ ! searchbuilderEnabled || serversideProcessingEnabled || invertedFilterEnabled }
					onChange={ ( datatables_searchbuilder ) => updateTableOptions( { datatables_searchbuilder } ) }
				/>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);

// Register the "Custom Search Builder" feature for the "DataTables Layout" feature module.
addFilter( 'tablepress.dataTablesLayoutFeatures', `tp/${ MODULE_SLUG }/add-datatables_layout-features`, ( features ) => {
	return {
		...features,
		searchBuilder: {
			label: __( 'Custom Search Builder', 'tablepress' ),
			defaultPosition: 'top',
			option: 'datatables_searchbuilder',
			requirements: ( tableOptions ) => ( tableOptions.datatables_filter && tableOptions.datatables_searchbuilder ),
		},
	};
} );
