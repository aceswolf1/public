/**
 * JavaScript code for the "Email Notifications" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * Internal dependencies.
 */
import { initializeReactComponent } from '../../../admin/js/common/react-loader';
import Screen from './email-notifications/screen';

initializeReactComponent(
	'tablepress-email-notifications-screen',
	<Screen />
);
