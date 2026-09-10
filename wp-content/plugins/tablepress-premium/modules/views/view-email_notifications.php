<?php
/**
 * Email Notifications View.
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 3.1.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Email Notifications View class.
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 3.1.0
 */
class TablePress_Email_Notifications_View extends TablePress_View {

	/**
	 * Sets up the view with data and do things that are specific for this view.
	 *
	 * @since 3.1.0
	 *
	 * @param string               $action Action for this view.
	 * @param array<string, mixed> $data   Data for this view.
	 */
	#[\Override]
	public function setup( /* string */ $action, array $data ) /* : void */ {
		// Don't use type hints in the method declaration to prevent PHP errors, as the method is inherited.

		parent::setup( $action, $data );

		$this->add_text_box( 'no-javascript', array( $this, 'textbox_no_javascript' ), 'header' );

		TablePress_Modules_Helper::enqueue_style( 'email-notifications' );
		TablePress_Modules_Helper::enqueue_script( 'email-notifications' );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'email_notifications', array( $this, 'textbox_email_notifications' ), 'normal' );
	}

	/**
	 * Prints the screen head text.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ): void {
		?>
		<div class="hide-if-no-js">
			<p>
				<?php _e( 'To configure email notifications for various TablePress events, use the configuration sections below.', 'tablepress' ); ?>
			</p>
			<p>
				<?php _e( 'You can receive an email notifications when tables are saved, added, deleted, or copied, or when the ID of a table was changed.', 'tablepress' ); ?>
				<?php _e( 'For each of these events, it is possible to configure the email recipients, subject, and body.', 'tablepress' ); ?>
				<?php _e( 'In the email subject and content, you can use various placeholders, like the table ID, table name, username, and more.', 'tablepress' ); ?>
				<?php printf( __( 'For more details, visit the module’s web page on the <a href="%s">TablePress website</a>.', 'tablepress' ), 'https://tablepress.org/modules/email-notifications/' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Prints the content of the "Email Notifications" text box.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_email_notifications( array $data, array $box ): void {
		$this->print_script_data_json(
			'emailNotifications',
			array(
				'config'        => $data['config'],
				'defaultEmails' => $data['default_emails'],
			),
		);

		echo '<div id="tablepress-email-notifications-screen"></div>';
	}

} // class TablePress_Email_Notifications_View
