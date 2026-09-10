/**
 * JavaScript code for the IntervalControl component.
 *
 * @package TablePress
 * @subpackage Automatic Periodic Table Import Screen
 * @author Tobias Bäthge
 * @since 2.3.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
	Icon,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
	TextControl,
	Tooltip,
} from '@wordpress/components';
import { info } from '@wordpress/icons';

const availableIntervals = {
	60: __( 'Once per minute', 'tablepress' ),
	900: __( 'Once per 15 minutes', 'tablepress' ),
	3600: __( 'Once per hour', 'tablepress' ),
	43200: __( 'Twice per day', 'tablepress' ),
	86400: __( 'Once per day', 'tablepress' ),
	604800: __( 'Once per week', 'tablepress' ),
};

const availableIntervalsOptions = Object.entries( availableIntervals ).map( ( [ interval, intervalLabel ] ) => ( { value: interval, label: intervalLabel } ) );
availableIntervalsOptions.push( { value: 'custom', label: __( 'Custom', 'tablepress' ) } );

/**
 * Returns the IntervalControl component's JSX markup.
 *
 * @param {Object}        props          Function parameters.
 * @param {boolean}       props.disabled Whether the control is disabled. Default false.
 * @param {number|string} props.value    Current interval value.
 * @param {Function}      props.onChange Callback for interval value changes.
 * @return {Object} IntervalControl component.
 */
const IntervalControl = ( { disabled = false, value, onChange } ) => {
	const [ customIntervalSelected, setCustomIntervalSelected ] = useState( false );

	const customIntervalConfigured = ! availableIntervals.hasOwnProperty( value );

	return (
		<>
			<div
				className={ ( customIntervalSelected || customIntervalConfigured ) ? 'interval-control-custom-visible' : value }
			>
				<SelectControl
					__nextHasNoMarginBottom
					size="compact"
					value={ ( customIntervalSelected || customIntervalConfigured ) ? 'custom' : value }
					label={ __( 'Select import interval', 'tablepress' ) }
					hideLabelFromVision={ true }
					onChange={ ( selectedValue ) => {
						if ( 'custom' === selectedValue ) {
							setCustomIntervalSelected( true );
						} else {
							setCustomIntervalSelected( false );
							onChange( selectedValue );
						}
					} }
					options={ availableIntervalsOptions }
					disabled={ disabled }
				/>
			</div>
			{ ( customIntervalSelected || customIntervalConfigured ) &&
				<HStack style={ {
					width: 'auto',
				} }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						className="code"
						label={ __( 'Custom interval', 'tablepress' ) }
						hideLabelFromVision={ true }
						disabled={ disabled }
						value={ value }
						onChange={ ( enteredValue ) => {
							onChange( /^\d+$/.test( enteredValue ) ? parseInt( enteredValue, 10 ) : enteredValue );
						} }
					/>
					{ ! disabled ? (
						<Tooltip text={ __( 'Interval in seconds or Cron-like schedule', 'tablepress' ) }>
							{ /* @todo Remove <span> when the required WordPress version is 6.5. */ }
							<span>
								<Icon
									icon={ info }
									style={ {
										verticalAlign: 'middle',
									} }
								/>
							</span>
						</Tooltip>
					) : (
						<Icon
							icon={ info }
							style={ {
								fill: 'rgba(44, 51, 56, 0.5)',
								verticalAlign: 'middle',
							} }
						/>
					) }
				</HStack>
			}
		</>
	);
};

export default IntervalControl;
