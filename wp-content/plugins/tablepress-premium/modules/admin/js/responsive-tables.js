/**
 * JavaScript code for the "Edit" section integration of the Responsive Tables feature.
 *
 * @package TablePress
 * @subpackage Responsive Tables
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import {
	CheckboxControl,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../../../admin/js/common/react-loader';
import ModuleHelp from './common/module-help';

const MODULE_SLUG = 'responsive-tables';

const Section = ( { tableOptions, updateTableOptions } ) => {
	const modesCollapseAndModalEnabled = ( tableOptions.use_datatables && tableOptions.table_head > 0 );

	const modesCollapseAndModalEnabledNotice = ! modesCollapseAndModalEnabled ? sprintf(
		__( 'The “Collapse” and “Modal” modes are only available when the “%1$s” and the “%2$s” settings in the “%3$s” and “%4$s” sections are used.', 'tablepress' ),
		__( 'Table Header', 'tablepress' ),
		__( 'Enable Visitor Features', 'tablepress' ),
		__( 'Table Options', 'tablepress' ),
		__( 'Table Features for Site Visitors', 'tablepress' ),
	) : '';

	return (
		<>
			<ModuleHelp
				slug={ MODULE_SLUG }
				modalProps={ {
					className: 'has-size-medium', // Using size: 'medium' is only possible in WP 6.5+.
				} }
			>
				<p>
					{ __( 'Tables on websites can not always adjust to the available space on the screen automatically. The reason is that their content requires a certain minimum space, and that’s what defines the minimum width of the table. If that minimum table width is bigger than the width of the available content area, the table will not fit. Unfortunately, this can lead to ugly behavior on small screens, like on mobile phones and tablets, where some parts of a table might then be cut-off on the right side.', 'tablepress' ) }
				</p>
				<p>
					{ __( 'The Responsive Tables module offers four approaches to get around this challenge:', 'tablepress' ) }
				</p>
				<ul style={ {
					listStyle: 'disc',
					marginLeft: '12px',
				} }>
					<li>
						<RawHTML>{ __( '<em>Scroll</em>: This mode will make a table that is too wide to be fully displayed horizontally scrollable. With that, the user can still reach all table data. This is usually a good approach for tables with images, if they don’t automatically resize.', 'tablepress' ) }</RawHTML>
					</li>
					<li>
						<RawHTML>{ __( '<em>Collapse</em>: The Collapse approach can add a hide/expand effect to a table. It will hide the data from those columns that would otherwise be cut-off and instead adds that data to a collapsible row that is inserted below each entry. That row can be shown and hidden with a “+” and “-” button. This mode is especially useful in tables that show additional information for some “main” columns, e.g. in a directory table.', 'tablepress' ) }</RawHTML>
					</li>
					<li>
						<RawHTML>{ __( '<em>Modal</em>: Similar to the Collapse mode, the Modal mode will only show columns that fit on the screen. The other data is then shown in a modal window when a row is clicked.', 'tablepress' ) }</RawHTML>
					</li>
					<li>
						<RawHTML>{ __( '<em>Stack</em>: The Stack mode will show the cells of a row on top of each other, instead of next to each other. This makes the table more narrow, as it will appear to have only two columns: One for the header cells and one for the original row’s data cells.', 'tablepress' ) }</RawHTML>
					</li>
					<li>
						<RawHTML>{ __( '<em>Flip</em>: This mode changes the layout of the table, by flipping it to the side (rows appear as columns and vice versa), and then makes the table horizontally scrollable. This mode is a good solution for plain data tables, but will usually not work nicely in tables with images, cells of different height, or with combined/merged cells.', 'tablepress' ) }</RawHTML>
					</li>
				</ul>
				<p>
					{ __( 'For all modes, filtering and pagination will continue to work. Sorting will be possible for all modes except the Stack mode.', 'tablepress' ) }
				</p>
			</ModuleHelp>
			<VStack
				spacing="16px"
				style={ {
					paddingTop: '6px',
				} }
			>
				<table className="tablepress-postbox-table fixed">
					<tbody>
						<tr>
							<th className="column-1 top-align" scope="row">{ __( 'Mode', 'tablepress' ) }:</th>
							<td className="column-2">
								<VStack>
									<span>{ __( 'Choose the desired behavior of the table on small screens:', 'tablepress' ) }</span>
									{
										! modesCollapseAndModalEnabled && (
											<span>
												<em>
													{ modesCollapseAndModalEnabledNotice }
												</em>
											</span>
										)
									}
									<div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-responsive-"
												className="control-input"
												checked={ '' === tableOptions.responsive }
												onChange={ () => updateTableOptions( { responsive: '' } ) }
											/>
											<label htmlFor="option-responsive-">
												<span className="box-title">{ __( 'None', 'tablepress' ) }</span>
												<p className="description">{ __( 'The table won’t show special behavior.', 'tablepress' ) }</p>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-responsive-scroll"
												className="control-input"
												checked={ 'scroll' === tableOptions.responsive }
												onChange={ () => updateTableOptions( { responsive: 'scroll' } ) }
											/>
											<label htmlFor="option-responsive-scroll">
												<span className="box-title">{ __( 'Scroll', 'tablepress' ) }</span>
												<p className="description">{ __( 'The table will scroll horizontally.', 'tablepress' ) }</p>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-responsive-collapse"
												className="control-input"
												checked={ 'collapse' === tableOptions.responsive }
												onChange={ () => updateTableOptions( { responsive: 'collapse' } ) }
												disabled={ ! modesCollapseAndModalEnabled }
											/>
											<label
												htmlFor="option-responsive-collapse"
												title={ ! modesCollapseAndModalEnabled ? modesCollapseAndModalEnabledNotice : undefined }
											>
												<span className="box-title">{ __( 'Collapse', 'tablepress' ) }</span>
												<p className="description">{ __( 'The table will have collapsible rows.', 'tablepress' ) }</p>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-responsive-modal"
												className="control-input"
												checked={ 'modal' === tableOptions.responsive }
												onChange={ () => updateTableOptions( { responsive: 'modal' } ) }
												disabled={ ! modesCollapseAndModalEnabled }
											/>
											<label
												htmlFor="option-responsive-modal"
												title={ ! modesCollapseAndModalEnabled ? modesCollapseAndModalEnabledNotice : undefined }
											>
												<span className="box-title">{ __( 'Modal', 'tablepress' ) }</span>
												<p className="description">{ __( 'The cells will be shown in a modal window.', 'tablepress' ) }</p>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-responsive-stack"
												className="control-input"
												checked={ 'stack' === tableOptions.responsive }
												onChange={ () => updateTableOptions( { responsive: 'stack' } ) }
											/>
											<label htmlFor="option-responsive-stack">
												<span className="box-title">{ __( 'Stack', 'tablepress' ) }</span>
												<p className="description">{ __( 'The table cells in a row will be stacked.', 'tablepress' ) }</p>
											</label>
										</div>
										<div className="input-field-box">
											<input
												type="radio"
												id="option-responsive-flip"
												className="control-input"
												checked={ 'flip' === tableOptions.responsive }
												onChange={ () => updateTableOptions( { responsive: 'flip' } ) }
											/>
											<label htmlFor="option-responsive-flip">
												<span className="box-title">{ __( 'Flip', 'tablepress' ) }</span>
												<p className="description">{ __( 'The table will flip to the side.', 'tablepress' ) }</p>
											</label>
										</div>
									</div>
								</VStack>
							</td>
						</tr>
						<tr>
							<th className="column-1 top-align" scope="row">{ __( 'Settings', 'tablepress' ) }:</th>
							<td className="column-2">
								<VStack spacing="32px">
									<VStack>
										<label htmlFor="option-responsive_breakpoint">{ __( 'Largest screen size (breakpoint) for which the Flip or Stack mode should be used:', 'tablepress' ) }</label>
										<HStack alignment="left">
											<SelectControl
												__nextHasNoMarginBottom
												__next40pxDefaultSize
												id="option-responsive_breakpoint"
												value={ tableOptions.responsive_breakpoint }
												onChange={ ( responsive_breakpoint ) => updateTableOptions( { responsive_breakpoint } ) }
												options={ [
													{ value: 'phone', label: __( 'Phone', 'tablepress' ) },
													{ value: 'tablet', label: __( 'Tablet', 'tablepress' ) },
													{ value: 'desktop', label: __( 'Desktop', 'tablepress' ) },
													{ value: 'all', label: __( 'All', 'tablepress' ) },
												] }
												disabled={ ! [ "flip", "stack" ].includes( tableOptions.responsive ) }
											/>
											{ ( ! [ "", "flip", "stack" ].includes( tableOptions.responsive ) ) && (
												<span>{ __( '(Only used by Flip and Stack modes.)', 'tablepress' ) }</span>
											) }
										</HStack>
									</VStack>
									<CheckboxControl
										__nextHasNoMarginBottom
										label={ __( 'Show left/right buttons for the Scroll mode.', 'tablepress' ) + ( ! [ "", "scroll" ].includes( tableOptions.responsive ) ? ' ' + __( '(Only used by the Scroll mode.)', 'tablepress' ) : '' ) }
										checked={ tableOptions.responsive_scroll_buttons }
										disabled={ 'scroll' !== tableOptions.responsive }
										onChange={ ( responsive_scroll_buttons ) => updateTableOptions( { responsive_scroll_buttons } ) }
									/>
								</VStack>
							</td>
						</tr>
					</tbody>
				</table>
			</VStack>
		</>
	);
};

initializeReactComponentInPortal(
	MODULE_SLUG,
	'edit',
	Section,
);
