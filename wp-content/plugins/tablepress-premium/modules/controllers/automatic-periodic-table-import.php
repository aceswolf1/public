<?php
/**
 * TablePress Automatic Periodic Table Import.
 *
 * @package TablePress
 * @subpackage Automatic Periodic Table Import
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Class that contains the logic for the Automatic Periodic Table Import feature for TablePress.
 *
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Module_Automatic_Periodic_Table_Import {
	use TablePress_Module; // Use properties and methods from trait.

	/**
	 * Automatic Periodic Table Import configuration option.
	 *
	 * @since 2.0.0
	 */
	protected static \TablePress_WP_Option $auto_import_config;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Ensure that TablePress' copy of Action Scheduler (or a newer version in another plugin) is loaded on the next page view.
		if ( 'true' !== get_option( 'tablepress_load_action_scheduler', 'false' ) ) {
			// `update_option()` only writes to the database if a value is changed, so this does not incur a performance penalty.
			update_option( 'tablepress_load_action_scheduler', 'true', true );
		}

		// Hook into the action that is periodically executed by Action Scheduler.
		add_action( 'tablepress_automatic_periodic_table_import_action', array( __CLASS__, 'perform_automatic_import' ), 10, 2 );

		// Catch failed ActionScheduler actions and reschedule them or notify the site admin via email.
		add_action( 'action_scheduler_failed_execution', array( __CLASS__, 'handle_action_failure' ), 10, 2 );
		add_action( 'action_scheduler_failed_action', array( __CLASS__, 'handle_action_failure' ), 10, 2 );
		add_action( 'action_scheduler_unexpected_shutdown', array( __CLASS__, 'handle_action_failure' ), 10, 2 );

		/*
		 * Maybe migrate the configuration to the new format and re-schedule the cron jobs.
		 * This action will only be called when a legacy WP Cron hook for it is registered.
		 * This will be unregistered during the migration (which can also be triggered by e.g.
		 * saving the automatic import configuration on the "Import" screen), so that this action
		 * will only be executed once.
		 */
		add_action( 'tablepress_table_auto_import_hook', array( __CLASS__, 'load_configuration' ) );

		// Adjust the Automatic Import configuration when a table is deleted or its ID is changed.
		add_action( 'tablepress_event_deleted_table', array( __CLASS__, 'deleted_table_handler' ) );
		add_action( 'tablepress_event_changed_table_id', array( __CLASS__, 'changed_table_id_handler' ), 10, 2 );

		if ( is_admin() ) {
			self::init_admin();
		}
	}

	/**
	 * Limits the number of revisions to store for a table that is imported automatically to 2 (or a lower value if configure site-wide).
	 *
	 * @since 3.2.0
	 *
	 * @param int     $num  Number of revisions to store.
	 * @param WP_Post $post Post object.
	 */
	public static function limit_number_of_revisions( int $num, WP_Post $post ): int {
		$new_num = 2;
		if ( -1 < $num && $num < $new_num ) {
			$new_num = $num;
		}
		return $new_num;
	}

	/**
	 * Sends an email notification, e.g. about a failed automatic periodic table import.
	 *
	 * @since 3.0.0
	 *
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 * @param string $level   Importance level of the message ("warning" or "error").
	 */
	protected static function mail( string $subject, string $message, string $level ): void {
		if ( ! isset( self::$auto_import_config ) ) {
			self::load_configuration();
		}
		$notifications = self::$auto_import_config->get( 'notifications' );

		/**
		 * Filters the email notifications configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $notifications Email notifications configuration.
		 */
		$notifications = apply_filters( 'tablepress_auto_import_failure_email_notifications', $notifications );

		if ( ! $notifications['active'] ) {
			return;
		}
		if ( ! in_array( $level, $notifications['levels'], true ) ) {
			return;
		}
		if ( empty( $notifications['recipients'] ) ) {
			return;
		}

		wp_mail( $notifications['recipients'], $subject, $message );
	}

	/**
	 * Writes a message to the error log.
	 *
	 * @since 2.2.4
	 *
	 * @param mixed[]|WP_Error|object|string $data Data to log.
	 */
	protected static function log( /* array|WP_Error|object|string */ $data ): void {
		if ( ! defined( 'TABLEPRESS_DEBUG' ) || true !== TABLEPRESS_DEBUG ) {
			return;
		}

		$prefix = 'TablePress # ';

		if ( is_wp_error( $data ) ) {
			error_log( $prefix . TablePress::get_wp_error_string( $data ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		} elseif ( is_array( $data ) || is_object( $data ) ) {
			error_log( $prefix . var_export( $data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_var_export
		} else {
			error_log( $prefix . $data ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Catches failed ActionScheduler actions and reschedules them or notifies the site admin via email.
	 *
	 * @since 3.0.0
	 *
	 * @param string                             $action_id Action ID.
	 * @param int|array<string, mixed>|Exception $details   Details about the failure: a timeout integer, an error array, or an exception.
	 */
	public static function handle_action_failure( string $action_id, /* int|array|Exception */ $details ): void {
		$action = ActionScheduler::store()->fetch_action( $action_id );

		$hook = $action->get_hook();
		if ( 'tablepress_automatic_periodic_table_import_action' !== $hook ) {
			return;
		}

		// Don't do anything if the action was triggered manually via `as_enqueue_async_action()`.
		if ( $action->get_schedule() instanceof ActionScheduler_NullSchedule ) {
			return;
		}

		$current_time = time();

		$args = $action->get_args();
		$table_id = (string) $args[0];
		$import_source = $args[1];
		$last_failure_time = $args[2] ?? 0; // Default to 0 if no last failure timestamp is set, so that the failure is considered to have occurred "a long time ago".

		if ( is_int( $details ) ) {
			$details = "Timeout: {$details} s";
		} elseif ( is_array( $details ) ) {
			$details = sprintf( 'PHP Fatal error %1$s in %2$s on line %3$s', $details['message'], $details['file'], $details['line'] );
		} elseif ( $details instanceof Exception ) { // @phpstan-ignore instanceof.alwaysTrue (As `int` and `array` have been checked, the type of `$details` should be Exception here. To be sure, check it anyways.)
			$details = 'Exception: ' . $details->getMessage();
		} else {
			// The else-case should never happen.
			$details = '';
		}

		self::log(
			<<<MESSAGE
				#######################################################
				### Failed ActionScheduler action {$action_id} at {$current_time} ###
				### Table ID: {$table_id}, Import source: {$import_source} ###
				### Details: {$details} ###
				### Last failure: {$last_failure_time} ###
				MESSAGE
		);

		self::load_configuration();
		$tables = self::$auto_import_config->get( 'tables', array() );
		if ( ! isset( $tables[ $table_id ] ) ) {
			// Bail if the table is no longer in the configuration -- in which case this action should not even have been scheduled anymore.
			self::log(
				'### Error: The table is no longer in the configuration! ###' .
				'#######################################################'
			);
			return;
		}

		$table = $tables[ $table_id ];
		if ( ! $table['active'] ) {
			// Bail if the automatic import for this table is inactive -- in which case this action should not even have been scheduled anymore.
			self::log(
				'### Error: The automatic import for this table is inactive! ###' .
				'#######################################################'
			);
			return;
		}

		/*
		 * Get the last successful import time. Default to 1 if the table has not been imported before,
		 * so that a failure on first import is considered a first failure, as the last failure time default is 0.
		 */
		$last_import_time = $table['last_import']['time'] ?? 1;

		$home_url = home_url();

		if ( $last_import_time > $last_failure_time ) {
			// There was a successful import after the last action failure, so chances are that the import works in principle. Thus, schedule it again, with the current time as the last failure time.
			self::schedule_single_periodic_action( $current_time, $table['interval'], array( $table_id, $table['location'], $current_time ) );

			self::log(
				'### Rescheduled the ActionScheduler action. ###' .
				'#######################################################'
			);

			self::mail(
				'[TablePress] [Notice] An Automatic Periodic Table Import failed and was rescheduled',
				<<<MESSAGE
					This is an automatic notification from TablePress at {$home_url}:
					A configured Automatic Periodic Table Import failed and was rescheduled.

					Table ID: {$table_id}
					Import Source: {$import_source}
					Details: {$details}

					The import was rescheduled and will be tried again.
					To check the configuration, please visit the Automatic Periodic Table Import settings at {$home_url}.
					MESSAGE,
				'warning',
			);
		} else {
			// There was no successful run of the action after the last failure, so this is a repeated failure. User intervention is likely needed.
			self::log(
				'### Repeated failure! User intervention needed! ###' .
				'#######################################################'
			);

			self::mail(
				'[TablePress] [ERROR] [Action Needed!] An Automatic Periodic Table Import failed permanently',
				<<<MESSAGE
					This is an automatic notification about a critical error from TablePress at {$home_url}:
					A configured Automatic Periodic Table Import failed repeatedly. User intervervention is needed.

					Table ID: {$table_id}
					Import Source: {$import_source}
					Details: {$details}

					The import failed twice in a row and was not rescheduled.
					Please check the error details and re-save the configuration to reschedule the import.
					To check the configuration, please visit the Automatic Periodic Table Import settings at {$home_url}.
					MESSAGE,
				'error',
			);
		}
	}

	/**
	 * Loads the import configuration, i.e. the list of tables that are to be imported.
	 *
	 * @since 2.0.0
	 */
	public static function load_configuration(): void {
		$params = array(
			'option_name'   => 'tablepress_auto_import_config',
			'default_value' => array(),
		);
		self::$auto_import_config = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );

		/*
		 * Maybe migrate the configuration to the new format and re-schedule the cron jobs.
		 */
		$config = self::$auto_import_config->get();
		$update_config = false;

		if ( isset( $config['schedule'] ) && is_string( $config['schedule'] ) ) {
			/*
			* Check for string, which is the data type for `schedule` in the previous configuration scheme.
			* Before TP 3.0, it would not exist, or be an array if `schedule` is set (it would then be a table).
			* After TP 3.0, it would not exist.
			*/

			// Get global import interval from previously configured schedule.
			$schedules = wp_get_schedules(); // Schedules from WordPress and other plugins.
			// Add custom schedules that the Automatic Periodic Table Import used previously.
			$schedules['every_minute'] = array(
				'interval' => MINUTE_IN_SECONDS,
			);
			$schedules['quarterhourly'] = array(
				'interval' => 15 * MINUTE_IN_SECONDS,
			);
			$interval = isset( $schedules[ $config['schedule'] ] ) ? $schedules[ $config['schedule'] ]['interval'] : DAY_IN_SECONDS;
			unset( $config['schedule'] ); // Remove the no longer used `schedule` key.

			// Add the previous schedule interval to each table separately and remove the no longer used source key.
			if ( ! isset( $config['tables'] ) ) {
				$config['tables'] = array();
			}
			foreach ( $config['tables'] as &$table ) {
				unset( $table['source'] );
				$table['interval'] = $interval;
			}
			unset( $table ); // Unset use-by-reference parameter of foreach loop.

			$config['notifications'] = array(
				'active'     => true,
				'levels'     => array( 'warning', 'error' ),
				'recipients' => array( get_option( 'admin_email' ) ),
			);

			wp_unschedule_hook( 'tablepress_table_auto_import_hook' ); // Unschedule the old cron job.

			$update_config = true;
		} elseif ( ! isset( $config['notifications'] ) || isset( $config['notifications']['location'] ) ) {
			/*
			 * If the `notifications` key does not exist, the config is an array of tables.
			 * If the `notifications` key exists, it could be a table, so check for the `location` key. If so, the config is an array of tables.
			 * In both cases, the tables need to be moved to the `tables` key.
			 */
			$tables = $config;

			$config = array(
				'tables'        => $tables,
				'notifications' => array(
					'active'     => true,
					'levels'     => array( 'warning', 'error' ),
					'recipients' => array( get_option( 'admin_email' ) ),
				),
			);

			$update_config = true;
		}

		if ( $update_config ) {
			self::$auto_import_config->update( $config );
			self::schedule_periodic_actions(); // Schedule the periodic import actions via Action Scheduler.
		}
	}

	/**
	 * Schedules a single periodic import action via Action Scheduler.
	 *
	 * @since 2.3.0
	 *
	 * @param int                         $timestamp Timestamp for the first execution of the action.
	 * @param int|string                  $interval  Interval in seconds or cron schedule for the action to be repeated.
	 * @param array{0: string, 1: string} $args      Data to pass to the action.
	 */
	public static function schedule_single_periodic_action( int $timestamp, /* int|string */ $interval, array $args ): void {
		if ( is_string( $interval ) ) {
			// Schedule the import action with a cron-like schedule.
			as_schedule_cron_action( $timestamp, $interval, 'tablepress_automatic_periodic_table_import_action', $args );
		} else {
			// Schedule the import action with a fixed integer interval.
			as_schedule_recurring_action( $timestamp, $interval, 'tablepress_automatic_periodic_table_import_action', $args );
		}
	}

	/**
	 * Clears and re-schedules the periodic import actions via Action Scheduler.
	 *
	 * @since 2.3.0
	 */
	public static function schedule_periodic_actions(): void {
		as_unschedule_all_actions( 'tablepress_automatic_periodic_table_import_action' );

		$timestamp = time();

		$tables = self::$auto_import_config->get( 'tables', array() );
		foreach ( $tables as $table_id => $table ) {
			if ( $table['active'] ) {
				$table_id = (string) $table_id; // Ensure that the table ID is a string, as it comes from an array key where numeric strings are converted to integers.
				self::schedule_single_periodic_action( $timestamp, $table['interval'], array( $table_id, $table['location'] ) );
			}
		}
	}

	/**
	 * Triggers an automatic import of specified tables "now".
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, string> $tables Array of table IDs and their import locations.
	 */
	public static function trigger_automatic_table_import( array $tables ): void {
		foreach ( $tables as $table_id => $location ) {
			$location = trim( $location );
			if ( '' === $location || 'https://' === $location ) {
				continue;
			}

			$table_id = (string) $table_id; // Ensure that the table ID is a string, as it comes from an array key where numeric strings are converted to integers.
			as_enqueue_async_action( 'tablepress_automatic_periodic_table_import_action', array( $table_id, $location ) );
		}
	}

	/**
	 * Imports a given import location and replaces an existing table with the new data.
	 *
	 * @since 2.0.0
	 *
	 * @param string $table_id Table ID of the table to replace.
	 * @param string $location Location of the data to import.
	 */
	public static function perform_automatic_import( string $table_id, string $location ): void {
		static $function_call = 0;
		++$function_call;

		// Initiate logging.
		if ( defined( 'TABLEPRESS_DEBUG' ) && true === TABLEPRESS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
			error_reporting( E_ALL ); // Equals WP_DEBUG true.
			// phpcs:ignore WordPress.PHP.IniSet.log_errors_Disallowed
			ini_set( 'log_errors', 1 ); // Equals WP_LOG_ERRORS true.
			// phpcs:ignore WordPress.PHP.IniSet.Risky
			ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' ); // Moves error log file to WP_CONTENT_DIR/debug.log.
		}

		self::log( '#######################################################' );
		self::log( "### Importing table {$table_id} ({$location}), Function call {$function_call} ###" );

		if ( ! TablePress::$model_table->table_exists( $table_id ) ) {
			self::log( 'Start script execution time (WP_START_TIMESTAMP): ' . WP_START_TIMESTAMP . ' s' );
			self::log( "### Table {$table_id} in the automatic import configuration does not exist!" );
			return;
		}

		$start_time = microtime( true );
		$start_memory = memory_get_peak_usage();
		self::log( "Starting periodic automatic table import at: {$start_time} s" );
		self::log( "Start PHP memory: {$start_memory} bytes (limit " . ini_get( 'memory_limit' ) . ')' );

		$import_config = array();
		$import_config['legacy_import'] = false;
		$import_config['type'] = 'replace';
		$import_config['existing_table'] = $table_id;
		$import_config['source'] = str_starts_with( $location, 'http' ) ? 'url' : 'server';
		$import_config[ $import_config['source'] ] = $location; // Store the import URL or server path in either 'url' or 'server'.

		// Use a static variable to keep a reference to the importer, as multiple tables will need to be imported, most likely.
		static $importer = null;
		if ( is_null( $importer ) ) {
			$importer = TablePress::load_class( 'TablePress_Import', 'class-import.php', 'classes' );
			add_filter( 'wp_tablepress_table_revisions_to_keep', array( __CLASS__, 'limit_number_of_revisions' ), 9, 2 ); // Run at priority 9 so that overriding is easier on default priority.
		}
		$import = $importer->run( $import_config );

		if ( is_wp_error( $import ) ) {
			$success = false;
			$error = TablePress::get_wp_error_string( $import );
		} elseif ( 0 < count( $import['errors'] ) ) {
			$success = false;
			$wp_error_strings = array();
			foreach ( $import['errors'] as $file ) {
				$wp_error_strings[] = TablePress::get_wp_error_string( $file->error );
			}
			$error = implode( ', ', $wp_error_strings );
		} else {
			$success = true;
			$error = '';
		}

		$import_message = current_time( 'mysql' );
		if ( ! $success ) {
			self::log( "### Import failed: {$error}" );

			$home_url = home_url();

			self::mail(
				'[TablePress] [Notice] An Automatic Periodic Table Import failed',
				<<<MESSAGE
					This is an automatic notification from TablePress at {$home_url}:
					A configured Automatic Periodic Table Import failed, likely due to a temporary connection issue.

					Table ID: {$table_id}
					Import Source: {$location}
					Details: {$error}

					The import will be tried again at the next scheduled time.
					To check the configuration, please visit the Automatic Periodic Table Import settings at {$home_url}.
					MESSAGE,
				'warning',
			);

			$import_message = '<strong>' . __( 'Failed', 'tablepress' ) . '</strong> @ ' . $import_message;
		}
		if ( '' !== $error ) {
			$import_message .= '<br><em>' . esc_html( $error ) . '</em>';
		}

		if ( ! isset( self::$auto_import_config ) ) {
			self::load_configuration();
		}

		$config = self::$auto_import_config->get();
		$config['tables'][ $table_id ]['last_import'] = array(
			'time'    => time(),
			'message' => $import_message,
		);
		self::$auto_import_config->update( $config );

		$end_time = microtime( true );
		$duration = $end_time - $start_time;
		self::log( "Finished periodic automatic table import at: {$end_time} s, duration {$duration} seconds" );
		$end_memory = memory_get_peak_usage();
		$memory_usage = $end_memory - $start_memory;
		self::log( "Memory usage during the import: {$memory_usage} bytes (total {$end_memory} bytes)" );
	}

	/**
	 * Removes an entry from the Automatic Import configuration after a table was deleted.
	 *
	 * @since 2.3.0
	 *
	 * @param string $table_id ID of the deleted table.
	 */
	public static function deleted_table_handler( string $table_id ): void {
		self::load_configuration();
		$config = self::$auto_import_config->get();
		if ( isset( $config['tables'][ $table_id ] ) ) {
			if ( $config['tables'][ $table_id ]['active'] ) {
				as_unschedule_action( 'tablepress_automatic_periodic_table_import_action', array( $table_id, $config['tables'][ $table_id ]['location'] ) );
			}

			unset( $config['tables'][ $table_id ] );
			self::$auto_import_config->update( $config );
		}
	}

	/**
	 * Updates an entry in the Automatic Import configuration after a table ID was changed.
	 *
	 * @since 2.3.0
	 *
	 * @param string $new_id New ID of the table.
	 * @param string $old_id Old ID of the table.
	 */
	public static function changed_table_id_handler( string $new_id, string $old_id ): void {
		self::load_configuration();
		$config = self::$auto_import_config->get();
		if ( isset( $config['tables'][ $old_id ] ) ) {
			if ( $config['tables'][ $old_id ]['active'] ) {
				// Get the next timestamp for the scheduled action, so that it can be used for rescheduling, or the current time if it is not scheduled.
				$timestamp = as_next_scheduled_action( 'tablepress_automatic_periodic_table_import_action', array( $old_id, $config['tables'][ $old_id ]['location'] ) );
				if ( false === $timestamp ) {
					$timestamp = time();
				} else {
					// Handle the special case of in-progress actions.
					if ( true === $timestamp ) {
						$timestamp = time();
					}
					as_unschedule_action( 'tablepress_automatic_periodic_table_import_action', array( $old_id, $config['tables'][ $old_id ]['location'] ) );
				}

				self::schedule_single_periodic_action( $timestamp, $config['tables'][ $old_id ]['interval'], array( $new_id, $config['tables'][ $old_id ]['location'] ) );
			}

			$config['tables'][ $new_id ] = $config['tables'][ $old_id ];
			unset( $config['tables'][ $old_id ] );
			self::$auto_import_config->update( $config );
		}
	}

	/**
	 * Initializes the admin screens of the Automatic Periodic Table Import module.
	 *
	 * @since 2.0.0
	 */
	public static function init_admin(): void {
		if ( current_user_can( 'tablepress_import_tables' ) ) {
			add_filter( 'tablepress_load_file_full_path', array( __CLASS__, 'change_import_view_full_path' ), 10, 3 );
			add_filter( 'tablepress_load_class_name', array( __CLASS__, 'change_view_import_class_name' ) );
			add_filter( 'tablepress_view_data', array( __CLASS__, 'add_automatic_periodic_table_import_view_data' ), 10, 2 );
			add_action( 'wp_ajax_tablepress_import', array( __CLASS__, 'handle_ajax_action_automatic_periodic_import' ) );
		}
	}

	/**
	 * Loads the Automatic Periodic Table Import view class when the TablePress Import view class is loaded.
	 *
	 * @since 2.0.0
	 *
	 * @param string $full_path Full path of the class file.
	 * @param string $file      File name of the class file.
	 * @param string $folder    Folder name of the class file.
	 * @return string Modified full path.
	 */
	public static function change_import_view_full_path( string $full_path, string $file, string $folder ): string {
		if ( 'view-import.php' === $file ) {
			require_once $full_path; // Load desired file first, as we inherit from it in the new $full_path file.
			$full_path = TABLEPRESS_ABSPATH . 'modules/views/view-automatic_periodic_table_import.php';
		}
		return $full_path;
	}

	/**
	 * Changes Import View class name, to load extended view.
	 *
	 * @since 2.0.0
	 *
	 * @param string $class_name Name of the class that shall be loaded.
	 * @return string Changed class name.
	 */
	public static function change_view_import_class_name( string $class_name ): string {
		if ( 'TablePress_Import_View' === $class_name ) {
			$class_name = 'TablePress_Automatic_Periodic_Table_Import_View';
		}
		return $class_name;
	}

	/**
	 * Adds the view data for the Automatic Periodic Table Import view.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $data   Data for this screen.
	 * @param string               $action Action for this screen.
	 * @return array<string, mixed> Modified data for this screen.
	 */
	public static function add_automatic_periodic_table_import_view_data( array $data, string $action ): array {
		if ( 'import' !== $action ) {
			return $data;
		}

		self::load_configuration();
		$config = self::$auto_import_config->get();
		$data['notifications'] = $config['notifications'];
		$data['auto_import_tables'] = $config['tables'];
		$data['default_emails'] = array_merge( array_unique( array(
			get_option( 'admin_email' ),
			wp_get_current_user()->user_email,
		) ) );
		return $data;
	}

	/**
	 * Saves the Automatic Periodic Table Import configuration.
	 *
	 * @since 2.0.0
	 */
	public static function handle_ajax_action_automatic_periodic_import(): void {
		if ( empty( $_POST['tablepress'] ) ) {
			wp_die( '-1' );
		}

		// Check if the submitted nonce matches the generated nonce we created earlier, dies -1 on failure.
		TablePress::check_nonce( 'import', false, '_ajax_nonce', true );

		// Ignore the request if the current user doesn't have sufficient permissions.
		if ( ! current_user_can( 'tablepress_import_tables' ) ) {
			wp_die( '-1' );
		}

		$data = wp_unslash( $_POST['tablepress'] );
		$data = json_decode( $data, true );
		if ( is_null( $data ) ) {
			wp_die( '-1' );
		}

		$tables = (array) $data['tables'];

		// Immediately trigger the automatic import for given tables.
		if ( isset( $_POST['immediate_import'] ) && 'true' === $_POST['immediate_import'] ) {
			self::trigger_automatic_table_import( $tables );

			ob_get_clean(); // Flush all outputs, to prevent errors/warnings being printed that make the JSON invalid.
			wp_send_json( array(
				'success' => true,
				'message' => 'success_trigger',
			) );
		}

		// Validate the tables configuration.
		foreach ( $tables as $table_id => $table ) {
			$table_id = (string) $table_id; // Ensure that the table ID is a string, as it comes from an array key where numeric strings are converted to integers.
			$table['active'] = ( isset( $table['active'] ) && $table['active'] );
			if ( ! isset( $table['location'] ) ) {
				$table['location'] = 'https://';
			} else {
				$table['location'] = trim( $table['location'] );
				if ( '' === $table['location'] ) {
					$table['location'] = 'https://';
				}
			}
			if ( ! isset( $table['interval'] ) ) {
				$table['interval'] = DAY_IN_SECONDS;
			} elseif ( is_numeric( $table['interval'] ) ) {
				$table['interval'] = absint( $table['interval'] );
				$table['interval'] = max( $table['interval'], MINUTE_IN_SECONDS ); // Minimum interval is 1 minute.
			} elseif ( is_string( $table['interval'] ) ) {
				$table['interval'] = $table['interval']; // Cron schedule. @todo Check format.
			} else {
				$table['interval'] = DAY_IN_SECONDS;
			}
			$table['last_import'] = array(
				// 'time' => 1, // Don't add the last import time, but treat it as 1 in the action failure handling.
				'message' => '-',
			);

			// Only save tables to the configuration that have other than just the default settings.
			if ( $table['active'] || 'https://' !== $table['location'] || DAY_IN_SECONDS !== $table['interval'] ) {
				$tables[ $table_id ] = $table;
			} else {
				unset( $tables[ $table_id ] );
			}
		}

		// Validate the notifications configuration.
		$notifications = (array) $data['notifications'];
		$notifications['active'] = ( isset( $notifications['active'] ) && $notifications['active'] );
		$notifications['levels'] = (array) $notifications['levels'];
		foreach ( $notifications['levels'] as $key => $level ) {
			if ( ! in_array( $level, array( 'warning', 'error' ), true ) ) {
				unset( $notifications['levels'][ $key ] );
			}
		}
		$notifications['recipients'] = (array) $notifications['recipients'];
		foreach ( $notifications['recipients'] as $key => $recipient ) {
			$recipient = trim( $recipient );
			if ( '' === $recipient || ! is_email( $recipient ) ) {
				unset( $notifications['recipients'][ $key ] );
			}
		}

		self::load_configuration();
		$config = self::$auto_import_config->get();
		$config['notifications'] = $notifications;
		$config['tables'] = $tables;
		self::$auto_import_config->update( $config );

		self::schedule_periodic_actions(); // Schedule the periodic import actions via Action Scheduler.

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

} // class TablePress_Module_Automatic_Periodic_Table_Import
