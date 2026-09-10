<?php
/**
 * Rest API for Magic Login
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;
use function MagicLogin\Utils\sanitize_phone_number;
use const MagicLogin\Constants\RATE_LIMIT_TRANSIENT_PREFIX;

/**
 * Class API
 */
class API {
	/**
	 * MagicLogin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = \MagicLogin\Utils\get_settings();

		if ( ! empty( $this->settings['enable_rest_api'] ) && $this->settings['enable_rest_api'] ) {
			add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
		}
	}

	/**
	 * Return an instance of the current class.
	 *
	 * @return self
	 */
	public static function setup() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Initialize the REST API routes.
	 */
	public function rest_api_init() {
		register_rest_route(
			'magic-login/v1',
			'/token',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_api_request' ],
					'args'                => array(
						'user'        => array(
							'required' => true,
						),
						'redirect_to' => array(
							'required' => false,
						),
						'send'        => array(
							'required' => false,
						),
						'qr'          => array(
							'required' => false,
							'type'     => 'boolean',
						),
						'qr_img'      => array(
							'required' => false,
							'type'     => 'boolean',
						),
					),
					'permission_callback' => [ $this, 'permission_callback' ],
				),
			)
		);
	}

	/**
	 * Handle the API request.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function handle_api_request( WP_REST_Request $request ) {
		$user_param = $request->get_param( 'user' );
		$user       = $this->get_user_by_param( $user_param );

		if ( is_wp_error( $user ) ) {
			return new WP_REST_Response(
				[
					'code'    => $user->get_error_code(),
					'message' => $user->get_error_message(),
				],
				422
			);
		}

		if ( ! empty( $request->get_param( 'redirect_to' ) ) ) {
			$_POST['redirect_to'] = $request->get_param( 'redirect_to' );
		}

		$login_url = \MagicLogin\Utils\create_login_link( $user );
		$mail_sent = false;

		if ( ! empty( $request->get_param( 'send' ) ) ) {
			$mail_sent = LoginManager::send_login_link( $user, $login_url );
		}

		$response = [
			'link'      => $login_url,
			'mail_sent' => $mail_sent,
		];

		if ( $request->get_param( 'qr' ) ) {
			$response['qr'] = \MagicLogin\QR::get_image_src( $login_url );
		}

		if ( $request->get_param( 'qr_img' ) ) {
			$response['qr_img'] = \MagicLogin\QR::get_img_tag( $login_url );
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Permission callback for the API request.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool|WP_Error
	 */
	public function permission_callback( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user ) || 0 === $current_user->ID ) {
			return false;
		}

		if ( is_wp_error( $current_user ) ) {
			return false;
		}

		// Check rate limit if enabled
		if ( ! empty( $this->settings['enable_api_rate_limit'] ) && $this->settings['enable_api_rate_limit'] ) {
			$rate_limit_check = $this->check_rate_limit();
			if ( is_wp_error( $rate_limit_check ) ) {
				return $rate_limit_check;
			}
		}

		$user_param = $request['user'];
		$user       = $this->get_user_by_param( $user_param );

		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'missing_user', esc_html__( 'No account matches the given user.', 'magic-login' ), array( 'status' => 422 ) );
		}

		return current_user_can( 'edit_user', $user->ID );
	}

	/**
	 * Get user by login or email, respecting MAGIC_LOGIN_USERNAME_ONLY definition.
	 *
	 * @param string $user_param User login or email.
	 *
	 * @return \WP_User|WP_Error
	 */
	private function get_user_by_param( $user_param ) {
		if ( is_numeric( $user_param ) ) {
			$user = get_user_by( 'id', $user_param );
		} else {
			$user = get_user_by( 'login', $user_param );
		}

		if ( ! $user && ( ! defined( 'MAGIC_LOGIN_USERNAME_ONLY' ) || false === MAGIC_LOGIN_USERNAME_ONLY ) ) {
			if ( strpos( $user_param, '@' ) !== false ) {
				$user = get_user_by( 'email', $user_param );
			}
		}

		if ( SmsService::is_sms_login_enabled() ) {
			// Check if the input is a phone number
			if ( ! $user && preg_match( '/^\+?[1-9]\d{1,14}$/', $user_param ) ) {
				$phone_number = sanitize_phone_number( $user_param );
				$user         = SmsService::get_user_by_phone_number( $phone_number );
			}
		}

		if ( ! $user ) {
			return new WP_Error( 'missing_user', esc_html__( 'No account matches the given user.', 'magic-login' ) );
		}

		return $user;
	}

	/**
	 * Check if the current IP has exceeded the rate limit.
	 *
	 * @return true|WP_Error True if rate limit is not exceeded, WP_Error otherwise.
	 */
	private function check_rate_limit() {
		$ip_address = \MagicLogin\Utils\get_client_ip();

		/**
		 * Filter to bypass rate limiting for specific IP addresses.
		 *
		 * @hook   magic_login_rate_limit_allowlist
		 *
		 * @param bool   $is_allowlisted Whether the IP should bypass rate limiting. Default false.
		 * @param string $ip_address     The IP address being checked.
		 *
		 * @return bool True to bypass rate limiting, false to apply rate limiting.
		 * @since  2.6.2
		 */
		$is_allowlisted = apply_filters( 'magic_login_rate_limit_allowlist', false, $ip_address );
		if ( $is_allowlisted ) {
			return true;
		}

		if ( empty( $ip_address ) ) {
			// If we can't get IP, block the request for security
			// Allow exception for CLI/WP-CLI usage
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return true;
			}

			return new WP_Error(
				'rate_limit_no_ip',
				esc_html__( 'Unable to determine IP address for rate limiting.', 'magic-login' ),
				array( 'status' => 403 )
			);
		}

		$max_requests = isset( $this->settings['rate_limit_max_requests'] )
			? absint( $this->settings['rate_limit_max_requests'] )
			: 60;

		if ( $max_requests <= 0 ) {
			$max_requests = 60;
		}

		// Create a unique transient key for this IP (keep under 45 chars)
		$transient_key = RATE_LIMIT_TRANSIENT_PREFIX . md5( $ip_address );
		$request_data  = get_transient( $transient_key );

		if ( false === $request_data ) {
			// First request in this time window
			$request_data = array(
				'count'      => 1,
				'start_time' => time(),
			);
			set_transient( $transient_key, $request_data, 60 ); // 60 seconds

			return true;
		}

		// Check if we're still within the same minute
		$elapsed_time = time() - $request_data['start_time'];

		if ( $elapsed_time >= 60 ) {
			// New time window, reset counter
			$request_data = array(
				'count'      => 1,
				'start_time' => time(),
			);
			set_transient( $transient_key, $request_data, 60 );

			return true;
		}

		if ( $request_data['count'] > $max_requests ) {
			// Rate limit exceeded
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
				/* translators: %d: maximum number of requests allowed per minute */
					esc_html__( 'Rate limit exceeded. Maximum %d requests per minute allowed.', 'magic-login' ),
					$max_requests
				),
				array( 'status' => 429 )
			);
		}

		// Increment request count
		$request_data['count'] ++;

		// Update transient with new count
		set_transient( $transient_key, $request_data, 60 );

		return true;
	}
}
