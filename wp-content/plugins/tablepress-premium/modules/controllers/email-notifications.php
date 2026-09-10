<?php
/**
 * TablePress Email Notifications.
 *
 * @package TablePress
 * @subpackage Email Notifications
 * @author Tobias Bäthge
 * @since 3.1.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the Email Notifications feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 3.1.0
 */
class TablePress_Module_Email_Notifications {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Email Notifications configuration option.
	 *
	 * @since 3.1.0
	 */
	protected static \TablePress_WP_Option $email_notifications_config;

	/**
	 * Previous table revision, from before saving or deleting a table, to make data available for the notification email.
	 *
	 * @since 3.1.0
	 * @var ?array<string, mixed>
	 */
	protected static ?array $previous_table_revision = null;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 */
	public function __construct() {
		// Ensure that TablePress' copy of Action Scheduler (or a newer version in another plugin) is loaded on the next page view.
		if ( 'true' !== get_option( 'tablepress_load_action_scheduler', 'false' ) ) {
			// `update_option()` only writes to the database if a value is changed, so this does not incur a performance penalty.
			update_option( 'tablepress_load_action_scheduler', 'true', true );
		}

		// Register the custom post type for TablePress notification emails.
		register_post_type(
			'tablepress_email',
			array(
				'public'  => false,
				'rewrite' => false,
			),
		);

		// Hook into the Action Scheduler action that sends the scheduled emails.
		add_action( 'tablepress_email_notifications_send_email_action', array( __CLASS__, 'send_email' ), 10, 2 );

		// Hook into the TablePress events for which a notification email should be sent.
		add_action( 'tablepress_event_pre_save_table', array( __CLASS__, 'handle_event_pre_save_table' ) );
		add_action( 'tablepress_event_added_table', array( __CLASS__, 'handle_event_added_table' ) );
		add_action( 'tablepress_event_pre_delete_table', array( __CLASS__, 'handle_event_pre_delete_table' ) );
		add_action( 'tablepress_event_copied_table', array( __CLASS__, 'handle_event_copied_table' ), 10, 2 );
		add_action( 'tablepress_event_changed_table_id', array( __CLASS__, 'handle_event_changed_table_id' ), 10, 2 );

		if ( is_admin() ) {
			self::init_admin();
		}
	}

	/**
	 * Loads the email notifications configuration.
	 *
	 * @since 3.1.0
	 */
	protected static function load_configuration(): void {
		$default_recipients = array( get_option( 'admin_email' ) );
		// phpcs:disable WordPress.WP.I18n.UnorderedPlaceholdersText
		$params = array(
			'option_name'   => 'tablepress_email_notifications_config',
			'default_value' => array(
				'saved_table'      => array(
					'active'     => false,
					'recipients' => $default_recipients,
					'subject'    => __( '[TablePress] Table “%old_table_name%” (ID %table_id%) edited', 'tablepress' ),
					'message'    => __( 'The TablePress table “%old_table_name%” (ID %table_id%) on %site_url% was edited.', 'tablepress' )
									. "\n\n" . __( 'Last edited by %user% at %date_time%.', 'tablepress' )
									. "\n\n" . __( 'You can view the table at %edit_url%.', 'tablepress' ),
				),
				'added_table'      => array(
					'active'     => false,
					'recipients' => $default_recipients,
					'subject'    => __( '[TablePress] Table “%table_name%” (ID %table_id%) added', 'tablepress' ),
					'message'    => __( 'The TablePress table “%table_name%” (ID %table_id%) on %site_url% was added as a new table.', 'tablepress' )
									. "\n\n" . __( 'Last edited by %user% at %date_time%.', 'tablepress' )
									. "\n\n" . __( 'You can view the table at %edit_url%.', 'tablepress' ),
				),
				'deleted_table'    => array(
					'active'     => false,
					'recipients' => $default_recipients,
					'subject'    => __( '[TablePress] Table “%old_table_name%” (ID %table_id%) deleted', 'tablepress' ),
					'message'    => __( 'The TablePress table “%old_table_name%” (ID %table_id%) on %site_url% was deleted.', 'tablepress' )
									. "\n\n" . __( 'Deleted by %user% at %date_time%.', 'tablepress' )
									. "\n\n" . __( 'You can view your tables at %list_url%.', 'tablepress' ),
				),
				'copied_table'     => array(
					'active'     => false,
					'recipients' => $default_recipients,
					'subject'    => __( '[TablePress] Table “%table_name%” (ID %table_id%) copied', 'tablepress' ),
					'message'    => __( 'The TablePress table “%table_name%” (ID %table_id%) on %site_url% was copied to a new table.', 'tablepress' )
									. "\n\n" . __( 'Last edited by %user% at %date_time%.', 'tablepress' )
									. "\n\n" . __( 'You can view the new table at %edit_url%.', 'tablepress' ),
				),
				'changed_table_id' => array(
					'active'     => false,
					'recipients' => $default_recipients,
					'subject'    => __( '[TablePress] Table ID changed from %old_table_id% to %table_id%', 'tablepress' ),
					'message'    => __( 'The TablePress table with the ID %old_table_id% on %site_url% was changed to the new ID %table_id%.', 'tablepress' )
									. "\n\n" . __( 'Last edited by %user% at %date_time%.', 'tablepress' )
									. "\n\n" . __( 'You can view the table at %edit_url%.', 'tablepress' ),
				),
			),
		);
		// phpcs:enable WordPress.WP.I18n.UnorderedPlaceholdersText
		self::$email_notifications_config = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );
	}

	/**
	 * Schedules an asynchronous email via Action Scheduler.
	 *
	 * @since 3.1.0
	 *
	 * @param string[] $recipients List of email recipients.
	 * @param string   $subject    Email subject.
	 * @param string   $message    Email message.
	 */
	protected static function schedule_async_email( array $recipients, string $subject, string $message ): void {
		// Save the email to the database.
		$email_id = wp_insert_post( array(
			'post_title'   => $subject,
			'post_content' => $message,
			'post_type'    => 'tablepress_email',
		) );

		// Bail, if the email could not be saved as a custom post type.
		if ( 0 === $email_id ) {
			return;
		}

		as_enqueue_async_action( 'tablepress_email_notifications_send_email_action', array( $email_id, $recipients ) );
	}

	/**
	 * Sends an email notification.
	 *
	 * @since 3.1.0
	 *
	 * @param int      $email_id   Post ID for the email.
	 * @param string[] $recipients List of email recipients.
	 */
	public static function send_email( int $email_id, array $recipients ): void {
		$email = get_post( $email_id );
		if ( is_null( $email ) ) {
			return;
		}

		wp_mail( $recipients, $email->post_title, $email->post_content );

		// Delete the email post after sending the email.
		wp_delete_post( $email_id, true );
	}

	/**
	 * Schedules an email notification for a table, if the notification configuration is active.
	 *
	 * @since 3.1.0
	 *
	 * @param string               $table_id            Table ID.
	 * @param array<string, mixed> $notification_config Notification configuration.
	 * @param array<string, mixed> $additional_data     Additional data for the email. Optional. Default empty array.
	 */
	protected static function maybe_schedule_table_notification( string $table_id, array $notification_config, array $additional_data = array() ): void {
		// Bail, if this email notification type for the table is inactive.
		if ( empty( $notification_config['active'] ) ) {
			return;
		}

		if ( isset( $additional_data['previous_table_revision'] ) ) {
			$old_table = $additional_data['previous_table_revision'];
		} else {
			$old_table = array(
				'id'          => $table_id,
				'name'        => __( 'unknown', 'tablepress' ),
				'description' => __( 'unknown', 'tablepress' ),
			);
		}

		$current_table = TablePress::$model_table->load( $table_id, true, true );
		if ( is_wp_error( $current_table ) || ( isset( $current_table['is_corrupted'] ) && $current_table['is_corrupted'] ) ) {
			$current_table = $old_table;
		}

		// Prepare the email.
		$recipients = $notification_config['recipients'];
		$subject = $notification_config['subject'];
		$message = $notification_config['message'];

		// Replace placeholders in the email subject and message.
		$home_url = home_url();
		$edit_url = TablePress::url( array( 'action' => 'edit', 'table_id' => $current_table['id'] ) );
		$list_url = TablePress::url( array( 'action' => 'list' ) );
		$last_editor = TablePress::get_user_display_name( get_current_user_id() );
		$last_modified = TablePress::format_datetime( wp_date( 'Y-m-d H:i:s' ) ); // @phpstan-ignore argument.type (This will always be a string.)
		foreach ( array( 'subject', 'message' ) as $variable ) {
			${$variable} = str_replace( '%site_url%', $home_url, ${$variable} );
			${$variable} = str_replace( '%edit_url%', $edit_url, ${$variable} );
			${$variable} = str_replace( '%list_url%', $list_url, ${$variable} );
			${$variable} = str_replace( '%table_id%', $current_table['id'], ${$variable} );
			if ( isset( $additional_data['old_table_id'] ) ) {
				${$variable} = str_replace( '%old_table_id%', $additional_data['old_table_id'], ${$variable} );
			}
			${$variable} = str_replace( '%table_name%', $current_table['name'], ${$variable} );
			${$variable} = str_replace( '%table_description%', $current_table['description'], ${$variable} );
			${$variable} = str_replace( '%old_table_name%', $old_table['name'], ${$variable} );
			${$variable} = str_replace( '%old_table_description%', $old_table['description'], ${$variable} );
			${$variable} = str_replace( '%user%', $last_editor, ${$variable} );
			${$variable} = str_replace( '%date_time%', $last_modified, ${$variable} );
		}
		// Schedule the email notification.
		self::schedule_async_email( $recipients, $subject, $message );
	}

	/**
	 * Saves the previous table revision before saving a table.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_id Table ID.
	 */
	public static function handle_event_pre_save_table( string $table_id ): void {
		self::load_configuration();
		$notification_config = self::$email_notifications_config->get( 'saved_table', array() );
		if ( empty( $notification_config['active'] ) ) {
			return;
		}

		add_action( 'tablepress_event_saved_table', array( __CLASS__, 'handle_event_saved_table' ) );

		// Store current table data and options, so that it can be used in the notification.
		$table = TablePress::$model_table->load( $table_id, true, true );
		if ( is_wp_error( $table ) || ( isset( $table['is_corrupted'] ) && $table['is_corrupted'] ) ) {
			return;
		}
		self::$previous_table_revision = $table;
	}

	/**
	 * Schedules an email notification when a table is saved.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_id Table ID.
	 */
	public static function handle_event_saved_table( string $table_id ): void {
		self::load_configuration();
		$notification_config = self::$email_notifications_config->get( 'saved_table', array() );
		self::maybe_schedule_table_notification( $table_id, $notification_config, array( 'previous_table_revision' => self::$previous_table_revision ) );

		// Remove the action from the hook, so that it is only executed once.
		remove_action( 'tablepress_event_saved_table', array( __CLASS__, 'handle_event_saved_table' ) );
		self::$previous_table_revision = null;
	}

	/**
	 * Schedules an email notification when a table is added.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_id Table ID.
	 */
	public static function handle_event_added_table( string $table_id ): void {
		self::load_configuration();
		$notification_config = self::$email_notifications_config->get( 'added_table', array() );
		self::maybe_schedule_table_notification( $table_id, $notification_config );
	}

	/**
	 * Saves the previous table revision before deleting a table.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_id Table ID.
	 */
	public static function handle_event_pre_delete_table( string $table_id ): void {
		self::load_configuration();
		$notification_config = self::$email_notifications_config->get( 'deleted_table', array() );
		if ( empty( $notification_config['active'] ) ) {
			return;
		}

		add_action( 'tablepress_event_deleted_table', array( __CLASS__, 'handle_event_deleted_table' ) );

		// Store current table data and options, so that it can be used in the notification.
		$table = TablePress::$model_table->load( $table_id, true, true );
		if ( is_wp_error( $table ) || ( isset( $table['is_corrupted'] ) && $table['is_corrupted'] ) ) {
			return;
		}
		self::$previous_table_revision = $table;
	}

	/**
	 * Schedules an email notification when a table is deleted.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_id Table ID.
	 */
	public static function handle_event_deleted_table( string $table_id ): void {
		self::load_configuration();
		$notification_config = self::$email_notifications_config->get( 'deleted_table', array() );
		self::maybe_schedule_table_notification( $table_id, $notification_config, array( 'previous_table_revision' => self::$previous_table_revision ) );

		// Remove the action from the hook, so that it is only executed once.
		remove_action( 'tablepress_event_deleted_table', array( __CLASS__, 'handle_event_deleted_table' ) );
		self::$previous_table_revision = null;
	}

	/**
	 * Schedules an email notification when a table is copied.
	 *
	 * @since 3.1.0
	 *
	 * @param string $new_table_id New Table ID.
	 * @param string $table_id     Table ID.
	 */
	public static function handle_event_copied_table( string $new_table_id, string $table_id ): void {
		self::load_configuration();
		$notification_config = self::$email_notifications_config->get( 'copied_table', array() );
		self::maybe_schedule_table_notification( $new_table_id, $notification_config, array( 'old_table_id' => $table_id ) );
	}

	/**
	 * Schedules an email notification when a table ID is changed.
	 *
	 * @since 3.1.0
	 *
	 * @param string $new_table_id New Table ID.
	 * @param string $old_table_id Old Table ID.
	 */
	public static function handle_event_changed_table_id( string $new_table_id, string $old_table_id ): void {
		self::load_configuration();
		$notification_config = self::$email_notifications_config->get( 'changed_table_id', array() );
		self::maybe_schedule_table_notification( $new_table_id, $notification_config, array( 'old_table_id' => $old_table_id ) );
	}

	/**
	 * Initializes the admin screens of the Email Notifications module.
	 *
	 * @since 3.1.0
	 */
	public static function init_admin(): void {
		if ( current_user_can( 'tablepress_edit_options' ) ) {
			add_filter( 'tablepress_load_file_full_path', array( __CLASS__, 'change_email_notifications_view_full_path' ), 10, 3 );
			add_filter( 'tablepress_admin_view_actions', array( __CLASS__, 'add_view_action_email_notifications' ) );
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_email_notifications_view_data' ), 10, 2 );
			add_action( 'wp_ajax_tablepress_email_notifications', array( __CLASS__, 'handle_ajax_action_email_notifications' ) );
		}
	}

	/**
	 * Adjusts the path from which the Email Notifications class file is loaded.
	 *
	 * @since 3.1.0
	 *
	 * @param string $full_path Full path of the class file.
	 * @param string $file      File name of the class file.
	 * @param string $folder    Folder name of the class file.
	 * @return string Modified full path.
	 */
	public static function change_email_notifications_view_full_path( string $full_path, string $file, string $folder ): string {
		if ( 'view-email_notifications.php' === $file ) {
			$full_path = TABLEPRESS_ABSPATH . "modules/views/{$file}";
		}
		return $full_path;
	}

	/**
	 * Adds the Email Notifications view to the list of views in TablePress.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, array<string, bool|string>> $view_actions List of views.
	 * @return array<string, array<string, bool|string>> Modified list of views.
	 */
	public static function add_view_action_email_notifications( array $view_actions ): array {
		$view_email_notifications = array(
			'email_notifications' => array(
				'show_entry'       => true,
				'page_title'       => __( 'Email Notifications', 'tablepress' ),
				'admin_menu_title' => __( 'Email Notifications', 'tablepress' ),
				'nav_tab_title'    => __( 'Email Notifications', 'tablepress' ),
				'required_cap'     => 'tablepress_access_options_screen', // Only grant access to the Email Notifications area for admins.
			),
		);
		// Insert the Email Notifications view before the About view.
		return array_slice( $view_actions, 0, -1 ) + $view_email_notifications + array_slice( $view_actions, -1, null );
	}

	/**
	 * Adds the view data for the Email Notifications view.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, mixed> $data   Data for this screen.
	 * @param string               $action Action for this screen.
	 * @return array<string, mixed> Modified data for this screen.
	 */
	public static function add_email_notifications_view_data( array $data, string $action ): array {
		if ( 'email_notifications' !== $action ) {
			return $data;
		}

		self::load_configuration();
		$data['config'] = self::$email_notifications_config->get();
		$data['default_emails'] = array_merge( array_unique( array(
			get_option( 'admin_email' ),
			wp_get_current_user()->user_email,
		) ) );
		return $data;
	}

	/**
	 * Saves the Email Notifications configuration.
	 *
	 * @since 3.1.0
	 */
	public static function handle_ajax_action_email_notifications(): void {
		if ( empty( $_POST['tablepress'] ) ) {
			wp_die( '-1' );
		}

		// Check if the submitted nonce matches the generated nonce we created earlier, dies -1 on failure.
		TablePress::check_nonce( 'email_notifications', false, '_ajax_nonce', true );

		// Ignore the request if the current user doesn't have sufficient permissions.
		if ( ! current_user_can( 'tablepress_edit_options' ) ) {
			wp_die( '-1' );
		}

		$data = wp_unslash( $_POST['tablepress'] );
		$data = json_decode( $data, true );
		if ( is_null( $data ) ) {
			wp_die( '-1' );
		}

		// Validate the Email Notifications configuration.
		$config = (array) $data['config'];
		foreach ( $config as $event => $notifications ) {
			if ( ! is_array( $notifications ) ) {
				unset( $config[ $event ] );
				continue;
			}

			$notifications = wp_parse_args(
				$notifications,
				array(
					'active'     => false,
					'recipients' => array(),
					'subject'    => '',
					'message'    => '',
				),
			);
			$notifications['active'] = (bool) $notifications['active'];
			$notifications['subject'] = trim( $notifications['subject'] );
			$notifications['message'] = trim( $notifications['message'] );
			$notifications['recipients'] = (array) $notifications['recipients'];
			foreach ( $notifications['recipients'] as $key => $recipient ) {
				$recipient = trim( $recipient );
				if ( '' === $recipient || ! is_email( $recipient ) ) {
					unset( $notifications['recipients'][ $key ] );
				}
			}

			$config[ $event ] = $notifications;
		}

		self::load_configuration();
		self::$email_notifications_config->update( $config );

		$response = array(
			'success' => true,
			'message' => 'success_save',
		);
		// Buffer all outputs, to prevent errors/warnings being printed that make the JSON invalid.
		$output_buffer = ob_get_clean();
		if ( ! empty( $output_buffer ) ) {
			$response['output_buffer'] = $output_buffer;
		}

		// Send the response.
		wp_send_json( $response );
	}

} // class TablePress_Module_Email_Notifications
