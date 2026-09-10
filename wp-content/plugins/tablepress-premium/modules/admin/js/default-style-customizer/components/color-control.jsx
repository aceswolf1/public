/**
 * JavaScript code for the ColorControl component.
 *
 * @package TablePress
 * @subpackage Default Style Customizer Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import {
	Button,
	ColorIndicator,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from 'react';

/**
 * Internal dependencies.
 */
import ColorPicker from './color-picker';
import { useScreenSettings } from '../context/screen-settings';

/**
 * Returns the ColorControl component's JSX markup.
 *
 * @param {Object}   props             Function parameters.
 * @param {string}   props.cssProperty Custom CSS property for this ColorControl.
 * @param {string}   props.color       Current color value of the custom CSS property.
 * @param {string}   props.name        Readable name for the custom CSS property.
 * @param {Function} props.onChange    Callback for color value changes.
 * @return {Object} ColorControl component.
 */
const ColorControl = ( { cssProperty, color, name, onChange } ) => {
	const { currentStyle } = useScreenSettings();
	const [ isVisible, setIsVisible ] = useState( false );

	// Get HEX color value from CSS property.
	let colorHex = color;
	while ( colorHex.startsWith( 'var(' ) ) {
		const colorName = ( ( colorHex.match( /var\(([-a-z]+)\)/ ) )?.[1] ).trim();
		colorHex = currentStyle[ colorName ];
	}

	return (
		<HStack>
			<strong>
				{ `${ name }:` }
			</strong>
			<HStack
				alignment="left"
				style={ {
					width: '95px',
				} }
			>
				<ColorIndicator
					colorValue={ colorHex }
					onClick={ () => setIsVisible( ! isVisible ) }
				/>
				<Button
					variant="link"
					text={ colorHex.toUpperCase() }
					onClick={ () => setIsVisible( ! isVisible ) }
					style={ {
						color: '#000000',
						textDecoration: 'none',
					} }
				/>
				{ isVisible &&
					<ColorPicker
						cssProperty={ cssProperty }
						color={ color }
						onChange={ onChange }
						onClose={ () => setIsVisible( ! isVisible ) }
					/>
				}
			</HStack>
		</HStack>
	);
};

export default ColorControl;
