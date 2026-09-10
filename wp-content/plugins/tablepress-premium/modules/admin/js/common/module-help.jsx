/**
 * JavaScript code for the ModuleHelp component.
 *
 * @package TablePress
 * @subpackage Edit Screen
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { Help } from '../../../../admin/js/common/help';

/**
 * Returns the ModuleHelp component's JSX markup.
 *
 * @param {Object} props             Function parameters.
 * @param {string} props.slug        The slug of the module.
 * @param {Object} props.buttonProps Additional props for the Button.
 * @param {Object} props.modalProps  Additional props for the Modal.
 * @param {Object} props.children    The Help content.
 * @return {Object} ModuleHelp component.
 */
const ModuleHelp = ( { slug, buttonProps = {}, modalProps = {}, children } ) => {
	const title = tp.modules[ slug ].name;
	const description = tp.modules[ slug ].description;

	return (
		<Help
			section={ slug }
			title={ title }
			buttonProps={ {
				label: sprintf( __( 'Help on the “%s” module', 'tablepress' ), title ),
				...buttonProps,
			} }
			modalProps={ modalProps }
		>
			<p><strong>{ description }</strong></p>
			{ children }
			<p>
				{ /* eslint-disable-next-line react/jsx-no-target-blank */ }
				<a
					href={ `https://tablepress.org/modules/${ slug }/?utm_source=plugin&utm_medium=textlink&utm_content=edit-screen-help-box` }
					className="module-link"
					target="_blank"
				>
					{ __( 'More Details, Examples, and Documentation', 'tablepress' ) }
				</a>
			</p>
		</Help>
	);
};

export default ModuleHelp;
