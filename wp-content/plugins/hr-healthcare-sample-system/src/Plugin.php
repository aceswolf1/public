<?php
/**
 * Singleton bootstrap for the plugin.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System;

use HR_Healthcare\Sample_System\Admin\Metabox;
use HR_Healthcare\Sample_System\Admin\PagesColumn;
use HR_Healthcare\Sample_System\Admin\SettingsPage;
use HR_Healthcare\Sample_System\Frontend\Assets;
use HR_Healthcare\Sample_System\Frontend\CartShortcodes;
use HR_Healthcare\Sample_System\Frontend\ProductPicker;
use HR_Healthcare\Sample_System\Integration\Elementor;
use HR_Healthcare\Sample_System\Integration\GravityForms;
use HR_Healthcare\Sample_System\Rest\GroupsController;
use HR_Healthcare\Sample_System\Rest\ResolveController;
use HR_Healthcare\Sample_System\Rest\Routes;
use HR_Healthcare\Sample_System\Support\ImageMap;
use HR_Healthcare\Sample_System\Support\Manifest;

/**
 * Instantiates and registers services on plugins_loaded.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Shared ImageMap service.
	 *
	 * @var ImageMap|null
	 */
	private ?ImageMap $image_map = null;

	/**
	 * Shared Manifest service.
	 *
	 * @var Manifest|null
	 */
	private ?Manifest $manifest = null;

	/**
	 * Shared Assets service.
	 *
	 * @var Assets|null
	 */
	private ?Assets $assets = null;

	/**
	 * Get the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use {@see instance()}.
	 */
	private function __construct() {}

	/**
	 * Boot the plugin: register all services.
	 */
	public function boot(): void {
		load_plugin_textdomain(
			'hr-healthcare-sample-system',
			false,
			dirname( plugin_basename( HRH_SAMPLE_FILE ) ) . '/languages'
		);
		$this->register_services();
	}

	/**
	 * ImageMap accessor for importers / REST.
	 */
	public function image_map(): ImageMap {
		if ( null === $this->image_map ) {
			$this->image_map = new ImageMap();
		}

		return $this->image_map;
	}

	/**
	 * Manifest accessor.
	 */
	public function manifest(): Manifest {
		if ( null === $this->manifest ) {
			$this->manifest = new Manifest( HRH_SAMPLE_PATH, HRH_SAMPLE_URL );
		}

		return $this->manifest;
	}

	/**
	 * Assets accessor.
	 */
	public function assets(): Assets {
		if ( null === $this->assets ) {
			$this->assets = new Assets( $this->manifest(), $this->image_map() );
		}

		return $this->assets;
	}

	/**
	 * Instantiate and hook services.
	 */
	private function register_services(): void {
		$this->image_map()->register();
		$this->assets()->register();

		( new ProductPicker( $this->assets() ) )->register();
		( new CartShortcodes( $this->assets() ) )->register();

		$groups  = new GroupsController( $this->image_map() );
		$resolve = new ResolveController();
		( new Routes( $groups, $resolve ) )->register();

		( new GravityForms() )->register();

		// Elementor Page-Settings tagging (hooks are Elementor-gated, safe when inactive).
		( new Elementor( $this->image_map() ) )->register();

		if ( is_admin() ) {
			( new Metabox() )->register();
			( new PagesColumn() )->register();
			( new SettingsPage( $this->image_map() ) )->register();
		}
	}
}
