<?php
/**
 * Conditional register/enqueue via Vite manifest + HRH_SAMPLE_CONFIG localize.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Frontend;

use HR_Healthcare\Sample_System\Data\Groups;
use HR_Healthcare\Sample_System\Data\Resolver;
use HR_Healthcare\Sample_System\Support\ImageMap;
use HR_Healthcare\Sample_System\Support\Manifest;
use HR_Healthcare\Sample_System\Support\Settings;

/**
 * Register-once / enqueue-if-flagged for the frontend (and admin) bundles.
 */
final class Assets {

	public const HANDLE_FRONTEND = 'hrh-sample-frontend';
	public const HANDLE_ADMIN    = 'hrh-sample-admin';

	/**
	 * Manifest helper.
	 *
	 * @var Manifest
	 */
	private Manifest $manifest;

	/**
	 * Image map service.
	 *
	 * @var ImageMap
	 */
	private ImageMap $image_map;

	/**
	 * Whether the frontend bundle has been registered.
	 *
	 * @var bool
	 */
	private bool $frontend_registered = false;

	/**
	 * Whether the frontend bundle has been enqueued this request.
	 *
	 * @var bool
	 */
	private bool $frontend_enqueued = false;

	/**
	 * Current page group slug (null = untagged / not a singular page).
	 *
	 * @var string|null
	 */
	private ?string $page_slug = null;

	/**
	 * Constructor.
	 *
	 * @param Manifest $manifest Manifest helper.
	 * @param ImageMap $image_map Image map.
	 */
	public function __construct( Manifest $manifest, ImageMap $image_map ) {
		$this->manifest  = $manifest;
		$this->image_map = $image_map;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp', array( $this, 'detect_page_tag' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_for_tagged_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );

		// Vite emits ES modules (they use `import.meta`), so our bundles must be
		// loaded with `type="module"` — a classic <script> tag throws
		// "Cannot use 'import.meta' outside a module". Applies to front + admin.
		add_filter( 'script_loader_tag', array( $this, 'filter_module_type' ), 10, 2 );
	}

	/**
	 * Mark our two bundles as ES modules in the printed <script> tag.
	 *
	 * Only the main (src) tag is passed through this filter; the localized
	 * `-js-extra` data script stays classic, so the HRH_SAMPLE_CONFIG /
	 * HRH_SAMPLE_BOOT globals are set before the deferred module executes.
	 *
	 * @param string $tag    The full <script> HTML tag.
	 * @param string $handle The script handle being printed.
	 * @return string Filtered tag.
	 */
	public function filter_module_type( string $tag, string $handle ): string {
		if ( self::HANDLE_FRONTEND !== $handle && self::HANDLE_ADMIN !== $handle ) {
			return $tag;
		}

		// Drop any existing type attribute, then declare the module type.
		$tag = (string) preg_replace( '/\stype=([\'"])[^\'"]*\1/', '', $tag );
		$tag = str_replace( '<script ', '<script type="module" ', $tag );

		return $tag;
	}

	/**
	 * Detect `_hrh_sample_group_tag` once the queried object is known.
	 *
	 * Edge guards (§10):
	 * - No tag → leave page_slug null (no modal/cart UI, no errors).
	 * - Tag whose group was removed / tables empty → no UI; log a notice.
	 */
	public function detect_page_tag(): void {
		if ( ! is_singular( 'page' ) ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( $post_id <= 0 ) {
			return;
		}

		$slug = get_post_meta( $post_id, ImageMap::META_KEY, true );

		if ( ! is_string( $slug ) || '' === $slug ) {
			return;
		}

		$slug = sanitize_title( $slug );

		if ( '' === $slug ) {
			return;
		}

		// Removed group or fresh-migrated empty tables → degrade silently.
		if ( null === Groups::find_by_slug( $slug ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional §10 notice when debugging.
				error_log(
					sprintf(
						'[hrh-sample] Page %d tagged with removed/unknown group "%s"; cart UI suppressed.',
						$post_id,
						$slug
					)
				);
			}
			return;
		}

		$this->page_slug = $slug;
	}

	/**
	 * Current page slug if tagged.
	 */
	public function page_slug(): ?string {
		return $this->page_slug;
	}

	/**
	 * Register (don't enqueue) the frontend bundle.
	 */
	public function register_frontend(): void {
		if ( $this->frontend_registered ) {
			return;
		}

		$js = $this->manifest->url( 'frontend' );

		if ( null === $js ) {
			return;
		}

		wp_register_script(
			self::HANDLE_FRONTEND,
			$js,
			array(),
			HRH_SAMPLE_VERSION,
			true
		);

		foreach ( $this->manifest->css_urls( 'frontend' ) as $i => $css_url ) {
			wp_register_style(
				self::HANDLE_FRONTEND . ( 0 === $i ? '' : '-' . $i ),
				$css_url,
				array(),
				HRH_SAMPLE_VERSION
			);
		}

		$this->frontend_registered = true;
	}

	/**
	 * Enqueue frontend on tagged product pages and localize config.
	 */
	public function maybe_enqueue_for_tagged_page(): void {
		if ( null === $this->page_slug ) {
			return;
		}

		$this->enqueue_frontend();
		$this->localize_page_config( $this->page_slug );
	}

	/**
	 * Idempotent enqueue of the frontend bundle (called from shortcodes too).
	 */
	public function enqueue_frontend(): void {
		$this->register_frontend();

		if ( ! $this->frontend_registered || $this->frontend_enqueued ) {
			return;
		}

		wp_enqueue_script( self::HANDLE_FRONTEND );

		foreach ( $this->manifest->css_urls( 'frontend' ) as $i => $_css ) {
			wp_enqueue_style( self::HANDLE_FRONTEND . ( 0 === $i ? '' : '-' . $i ) );
		}

		wp_localize_script(
			self::HANDLE_FRONTEND,
			'HRH_SAMPLE_BOOT',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'hrh-sample/v1' ) ),
				'restNonce'   => wp_create_nonce( 'wp_rest' ),
				'cartCap'     => Settings::cart_cap(),
				'checkoutUrl' => Settings::checkout_url(),
				'placeholder' => $this->image_map->placeholder_url(),
				'cookieName'  => 'hrh_sample_cart',
			)
		);

		$this->frontend_enqueued = true;
	}

	/**
	 * Localize window.HRH_SAMPLE_CONFIG for a tagged product page.
	 *
	 * @param string $slug Group slug.
	 */
	public function localize_page_config( string $slug ): void {
		$config = Resolver::group_config( $slug, $this->image_map->image_url( $slug ) );

		if ( null === $config ) {
			return;
		}

		wp_localize_script( self::HANDLE_FRONTEND, 'HRH_SAMPLE_CONFIG', $config );
	}

	/**
	 * Enqueue admin bundle on the plugin settings screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin( string $hook ): void {
		if ( ! str_contains( $hook, 'hrh-sample' ) ) {
			return;
		}

		$js = $this->manifest->url( 'admin' );

		if ( null === $js ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE_ADMIN,
			$js,
			array(),
			HRH_SAMPLE_VERSION,
			true
		);

		foreach ( $this->manifest->css_urls( 'admin' ) as $i => $css_url ) {
			wp_enqueue_style(
				self::HANDLE_ADMIN . ( 0 === $i ? '' : '-' . $i ),
				$css_url,
				array(),
				HRH_SAMPLE_VERSION
			);
		}
	}

	/**
	 * Whether frontend assets are enqueued this request.
	 */
	public function is_frontend_enqueued(): bool {
		return $this->frontend_enqueued;
	}
}
