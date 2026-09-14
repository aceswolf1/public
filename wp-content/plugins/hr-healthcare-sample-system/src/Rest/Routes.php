<?php
/**
 * REST route registration for hrh-sample/v1.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Rest;

/**
 * Registers nonce-protected sample-system endpoints.
 */
final class Routes {

	public const NAMESPACE = 'hrh-sample/v1';

	/**
	 * Groups controller.
	 *
	 * @var GroupsController
	 */
	private GroupsController $groups;

	/**
	 * Resolve controller.
	 *
	 * @var ResolveController
	 */
	private ResolveController $resolve;

	/**
	 * Constructor.
	 *
	 * @param GroupsController  $groups  Groups controller.
	 * @param ResolveController $resolve Resolve controller.
	 */
	public function __construct( GroupsController $groups, ResolveController $resolve ) {
		$this->groups  = $groups;
		$this->resolve = $resolve;
	}

	/**
	 * Hook into rest_api_init.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/groups',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->groups, 'batched' ),
				'permission_callback' => array( self::class, 'permission_check' ),
				'args'                => array(
					'slugs' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/group/(?P<slug>[a-z0-9\-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->groups, 'single' ),
				'permission_callback' => array( self::class, 'permission_check' ),
				'args'                => array(
					'slug' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/resolve',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this->resolve, 'resolve' ),
				'permission_callback' => array( self::class, 'permission_check' ),
			)
		);
	}

	/**
	 * Require a valid wp_rest nonce (works for anonymous cart users).
	 */
	public static function permission_check(): bool {
		$nonce = '';

		if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_X_WP_NONCE'] ) );
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
