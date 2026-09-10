/**
 * JavaScript code for the "Edit" section integration of the DataTables SearchHighlight feature.
 *
 * @package TablePress
 * @subpackage DataTables SearchHighlight
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
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'datatables-searchhighlight';

const Section = ( { tableOptions, updateTableOptions } ) => {
	const searchhighlightEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 && tableOptions.datatables_filter );

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
					! searchhighlightEnabled && (
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
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Highlight the current search term in the table.', 'tablepress' ) }
					checked={ tableOptions.datatables_searchhighlight }
					disabled={ ! searchhighlightEnabled }
					onChange={ ( datatables_searchhighlight ) => updateTableOptions( { datatables_searchhighlight } ) }
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
