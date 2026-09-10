/**
 * JavaScript code for the "Default Style Customizer Screen" component.
 *
 * @package TablePress
 * @subpackage Default Style Customizer Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Button,
	Card,
	CardBody,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	Panel,
	PanelBody,
	PanelRow,
	TabPanel,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { info } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import ColorControl from './components/color-control';
import LengthControl from './components/length-control';
import SandBox from './components/sandbox';
import StyleVariation from './components/style-variation';
import { useScreenSettings } from './context/screen-settings';
import cssProperties from './data/options';
import styleVariations from './data/variations';
import { exportToCustomCss, saveCustomCss } from './functions/css';

/**
 * Returns the "Default Style Customizer Screen" component's JSX markup.
 *
 * @return {Object} Default Style Customizer Screen component.
 */
const Screen = () => {
	const { currentStyle, setCurrentStyle } = useScreenSettings();
	const [ styleVariationsPanelOpen, setStyleVariationsPanelOpen ] = useState( 'custom' !== currentStyle['--style-variation'] );

	/**
	 * Handles CSS property state changes.
	 *
	 * @param {string}      cssProperty Custom CSS property name.
	 * @param {string|null} value       Value for the CSS property.
	 */
	const handleCssPropertyChange = ( cssProperty, value ) => {
		if ( null === value ) {
			// Reset to default value.
			value = styleVariations.default.style[ cssProperty ];
		} else if ( value.startsWith( '--' ) ) {
			// Properly store CSS variable values.
			value = `var(${ value })`;
		}

		const newValues = {
			...currentStyle,
			[ cssProperty ]: value,
			'--style-variation': 'custom',
		};
		setCurrentStyle( newValues );
	};

	const tabs = [
		{
			name: 'background',
			title: __( 'Background colors', 'tablepress' ),
		},
		{
			name: 'text',
			title: __( 'Text colors', 'tablepress' ),
		},
		{
			name: 'ui',
			title: __( 'Control elements', 'tablepress' ),
		},
		{
			name: 'border',
			title: __( 'Border', 'tablepress' ),
		},
		{
			name: 'spacing',
			title: __( 'Spacing', 'tablepress' ),
		},
	];

	return (
		<>
			<p>
				{ __( 'To change the default styling of your tables, choose an existing style variation or customize individual colors or properties.', 'tablepress' ) }
				<br />
				{ __( 'Click the “Save to ‘Custom CSS’” button below to add the automatically generated code for your styling to the site’s “Custom CSS”.', 'tablepress' ) }
			</p>
			<Panel>
				<PanelBody
					title={ __( 'Style Variations', 'tablepress' ) }
					initialOpen={ styleVariationsPanelOpen }
				>
					<PanelRow>
						<HStack
							className="style-variation-wrapper"
							alignment="left"
							spacing="16px"
							wrap={ true }
						>
							{
								Object.entries( styleVariations ).map( ( [ variation, variationData ] ) => (
									<StyleVariation
										key={ variation }
										variation={ variation }
										variationName={ variationData.name }
										variationCss={ variationData.style }
										checked={ variation === currentStyle['--style-variation'] }
										onChange={ () => {
											setCurrentStyle( { ...variationData.style } );
										} }
									/>
								) )
							}
							<StyleVariation
								variation="custom"
								variationName={ __( 'Custom', 'tablepress' ) }
								variationCss={ currentStyle }
								checked={ 'custom' === currentStyle['--style-variation'] }
								onChange={ () => {
									handleCssPropertyChange( '--style-variation', 'custom' );
									setStyleVariationsPanelOpen( true ); // Keep "Style Variations" panel open.
								} }
							/>
						</HStack>
					</PanelRow>
				</PanelBody>
			</Panel>
			<div style={ {
				display: 'flex',
			} }>
				<div style={ {
					width: '480px',
					borderLeft: '1px solid #e0e0e0',
					borderRight: '1px solid #e0e0e0',
					borderBottom: '1px solid #e0e0e0',
				} }>
					<Card>
						<CardBody>
							<h2 className="card-title">
								{ __( 'Custom style values', 'tablepress' ) }
							</h2>
							<TabPanel tabs={ tabs }>
								{ ( tab ) => (
									<VStack
										spacing="12px"
										style={ {
											paddingTop: '24px',
										} }
									>
										{
											Object.entries( cssProperties )
												.filter( ( [ , propertyData ] ) => ( tab.name === propertyData.category ) )
												.map( ( [ cssProperty, propertyData ] ) => {
													if ( 'color' === propertyData.type ) {
														return (
															<ColorControl
																key={ cssProperty }
																cssProperty={ cssProperty }
																color={ currentStyle[ cssProperty ] }
																name={ propertyData.name }
																onChange={ handleCssPropertyChange }
															/>
														);
													} else if ( 'length' === propertyData.type ) {
														return (
															<LengthControl
																key={ cssProperty }
																cssProperty={ cssProperty }
																lengthValue={ currentStyle[ cssProperty ] }
																name={ propertyData.name }
																onChange={ handleCssPropertyChange }
															/>
														);
													}
													return <></>;
												} )
										}
									</VStack>
								) }
							</TabPanel>
						</CardBody>
					</Card>
				</div>
				<div style={ {
					width: 'calc(100% - 480px)',
					borderRight: '1px solid #e0e0e0',
					borderBottom: '1px solid #e0e0e0',
				} }>
					<Card>
						<CardBody>
							<h2 className="card-title">
								{ __( 'Preview', 'tablepress' ) }
							</h2>
							<VStack
								spacing="24px"
							>
								<SandBox />
								<HStack
									alignment="left"
								>
									<Icon icon={ info } />
									<span>
										{ __( 'Please note your site’s theme and plugins may also influence the table styling.', 'tablepress' ) } { __( 'Tables on your site might look different!', 'tablepress' ) }
										<br />
										{ __( 'To change the styling of individual tables, use “Custom CSS” code below.', 'tablepress' ) }
									</span>
								</HStack>
							</VStack>
						</CardBody>
					</Card>
				</div>
			</div>
			<Card>
				<CardBody>
					<Button
						variant="primary"
						text={ __( 'Save to “Custom CSS”', 'tablepress' ) }
						onClick={ () => {
							exportToCustomCss( currentStyle );
							saveCustomCss();
						} }
					/>
				</CardBody>
			</Card>
		</>
	);
};

export default Screen;
