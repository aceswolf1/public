/**
 * JavaScript code for the Row Highlighting module's controls in the TablePress table editor block.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import shortcode from '@wordpress/shortcode';

/**
 * Get the block name from the block.json.
 */
import block from '../../../blocks/table/block.json';

/**
 * Internal dependencies.
 */
import { TableOptionCheckboxControl, TableOptionTextControl } from './common/components';

/**
 * Add custom controls to the sidebar.
 */
const addTableBlockSidebarControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		// Return early if we are not dealing with the TablePress table block.
		if ( block.name !== props.name ) {
			return (
				<BlockEdit { ...props } />
			);
		}

		const { attributes, setAttributes } = props;

		// Don't show the extra sidebar panel if no existing table has been chosen.
		if ( ! attributes.id || ! tp.tables.hasOwnProperty( attributes.id ) ) {
			return (
				<BlockEdit { ...props } />
			);
		}

		let shortcodeAttrs = shortcode.attrs( attributes.parameters );
		shortcodeAttrs = { named: { ...shortcodeAttrs.named }, numeric: [ ...shortcodeAttrs.numeric ] }; // Use object destructuring to get a clone of the object.

		const tableOptionProps = {
			shortcodeAttrs,
			setAttributes,
		};

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody
						title={ __( 'Row Highlighting', 'tablepress' ) }
						initialOpen={ false }
						className={ 'wp-block-tablepress-table-inspector-panel' }
					>
						{ ( ! shortcodeAttrs.named.row_highlight_expression || shortcodeAttrs.named.row_highlight ) &&
							<>
								<TableOptionTextControl
									label={ __( 'Row Highlight term', 'tablepress' ) + ':' }
									help={ __( 'Rows that contain this term will be highlighted.', 'tablepress' ) + ' ' + __( 'You can combine multiple highlight terms with an OR operator, e.g. “term1||term2”.', 'tablepress' ) }
									tableOption={ 'row_highlight' }
									{ ...tableOptionProps }
								/>
								{ shortcodeAttrs.named.row_highlight &&
									<>
										<TableOptionCheckboxControl
											label={ __( 'Full cell matching', 'tablepress' ) }
											help={ __( 'If this is turned on, the full cell content has to match the highlight term.', 'tablepress' ) }
											tableOption={ 'row_highlight_full_cell_match' }
											{ ...tableOptionProps }
										/>
										<TableOptionCheckboxControl
											label={ __( 'Case-sensitive matching', 'tablepress' ) }
											help={ __( 'If this is turned on, the case sensitivity of the highlight term has to match the content in the cell.', 'tablepress' ) }
											tableOption={ 'row_highlight_case_sensitive' }
											{ ...tableOptionProps }
										/>
										<TableOptionTextControl
											label={ __( 'Highlight columns:', 'tablepress' ) }
											help={ __( 'Enter a comma-separated list of the columns which should be searched for the highlight terms, e.g. “1,3-5,7”.', 'tablepress' ) }
											tableOption={ 'row_highlight_columns' }
											{ ...tableOptionProps }
										/>
										<TableOptionTextControl
											label={ __( 'Highlight rows:', 'tablepress' ) }
											help={ __( 'Enter a comma-separated list of the rows which should be searched for the highlight terms, e.g. “1,3-5,7”.', 'tablepress' ) }
											tableOption={ 'row_highlight_rows' }
											{ ...tableOptionProps }
										/>
									</>
								}
							</>
						}
						{ ( ! shortcodeAttrs.named.row_highlight || shortcodeAttrs.named.row_highlight_expression ) &&
							<>
								<TableOptionTextControl
									label={ __( 'Row Highlight expression', 'tablepress' ) + ':' }
									help={
										createInterpolateElement(
											__( 'For more complex row highlighting, e.g. based on comparisons, enter a logic expression to add custom CSS classes to rows that fulfill the expression.', 'tablepress' )
											+ ' '
											+ __( 'For details and examples see the <a>logic expression documentation</a>.', 'tablepress' )
											+ ' '
											+ __( 'For advanced use only.', 'tablepress' ),
											{
												a: <a href="https://tablepress.org/modules/row-highlighting/#logic-expressions" />, // eslint-disable-line jsx-a11y/anchor-has-content
											},
										)
									}
									tableOption={ 'row_highlight_expression' }
									{ ...tableOptionProps }
								/>
							</>
						}
					</PanelBody>
				</InspectorControls>
			</>
		);
	};
}, 'addTableBlockSidebarControls' );

addFilter(
	'editor.BlockEdit',
	'tp/row-highlighting/add-table-block-sidebar-controls',
	addTableBlockSidebarControls
);
