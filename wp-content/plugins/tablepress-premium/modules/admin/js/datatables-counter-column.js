/**
 * JavaScript code for the "Edit" section integration of the DataTables Index Column feature.
 *
 * @package TablePress
 * @subpackage DataTables Index Column
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

const MODULE_SLUG = 'datatables-counter-column';

const Section = ( { tableOptions, updateTableOptions } ) => {
	const counterColumnEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );

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
					! counterColumnEnabled && (
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
					label={ __( 'Make the first column a counter or index column.', 'tablepress' ) }
					checked={ tableOptions.datatables_counter_column }
					disabled={ ! counterColumnEnabled }
					onChange={ ( datatables_counter_column ) => updateTableOptions( { datatables_counter_column } ) }
				/>
				<span>
					{ __( 'If your table’s first column contains regular data, you should insert a new first column for this.', 'tablepress' ) }
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
