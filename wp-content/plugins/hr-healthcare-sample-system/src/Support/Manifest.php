<?php
/**
 * Vite manifest reader — logical entry name → hashed asset path.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Support;

/**
 * Reads `assets/dist/.vite/manifest.json` and resolves logical bundle names
 * (`frontend` / `admin`) to hashed filenames. Missing manifest degrades
 * gracefully (returns null / empty — never fatals).
 */
final class Manifest {

	/**
	 * Logical entry name → source path as emitted in Vite manifest keys.
	 *
	 * @var array<string, string>
	 */
	private const ENTRY_SOURCES = array(
		'frontend' => 'assets/src/js/frontend.js',
		'admin'    => 'assets/src/js/admin.js',
	);

	/**
	 * Absolute path to the Vite manifest file.
	 *
	 * @var string
	 */
	private string $manifest_path;

	/**
	 * Absolute filesystem path to the dist directory (trailing slash).
	 *
	 * @var string
	 */
	private string $dist_path;

	/**
	 * URL to the dist directory (trailing slash).
	 *
	 * @var string
	 */
	private string $dist_url;

	/**
	 * Decoded manifest contents, or null if unavailable.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $manifest = null;

	/**
	 * Whether the manifest has been loaded (successfully or not).
	 *
	 * @var bool
	 */
	private bool $loaded = false;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_path Absolute plugin root path (trailing slash).
	 * @param string $plugin_url  Absolute plugin root URL (trailing slash).
	 */
	public function __construct( string $plugin_path, string $plugin_url ) {
		$this->dist_path     = trailingslashit( $plugin_path ) . 'assets/dist/';
		$this->dist_url      = trailingslashit( $plugin_url ) . 'assets/dist/';
		$this->manifest_path = $this->dist_path . '.vite/manifest.json';
	}

	/**
	 * Resolve a logical entry name to its hashed JS file URL.
	 *
	 * @param string $logical_name Entry name (`frontend` or `admin`).
	 * @return string|null Absolute URL, or null if unresolved / missing.
	 */
	public function url( string $logical_name ): ?string {
		$entry = $this->entry( $logical_name );

		if ( null === $entry || empty( $entry['file'] ) || ! is_string( $entry['file'] ) ) {
			return null;
		}

		return $this->dist_url . ltrim( $entry['file'], '/' );
	}

	/**
	 * Absolute filesystem path for a logical entry's hashed JS file.
	 *
	 * @param string $logical_name Entry name (`frontend` or `admin`).
	 * @return string|null Absolute path, or null if unresolved / missing.
	 */
	public function path( string $logical_name ): ?string {
		$entry = $this->entry( $logical_name );

		if ( null === $entry || empty( $entry['file'] ) || ! is_string( $entry['file'] ) ) {
			return null;
		}

		return $this->dist_path . ltrim( $entry['file'], '/' );
	}

	/**
	 * Resolve CSS files associated with a logical entry.
	 *
	 * @param string $logical_name Entry name (`frontend` or `admin`).
	 * @return list<string> Absolute URLs (may be empty).
	 */
	public function css_urls( string $logical_name ): array {
		$entry = $this->entry( $logical_name );

		if ( null === $entry || empty( $entry['css'] ) || ! is_array( $entry['css'] ) ) {
			return array();
		}

		$urls = array();

		foreach ( $entry['css'] as $css_file ) {
			if ( is_string( $css_file ) && '' !== $css_file ) {
				$urls[] = $this->dist_url . ltrim( $css_file, '/' );
			}
		}

		return $urls;
	}

	/**
	 * Tiny self-check: known logical names resolve when the manifest exists.
	 *
	 * Safe to call when dist is absent — reports missing_manifest instead of throwing.
	 *
	 * @return array{ok: bool, missing_manifest: bool, unresolved: list<string>}
	 */
	public function self_check(): array {
		$this->ensure_loaded();

		if ( null === $this->manifest ) {
			return array(
				'ok'               => false,
				'missing_manifest' => true,
				'unresolved'       => array_keys( self::ENTRY_SOURCES ),
			);
		}

		$unresolved = array();

		foreach ( array_keys( self::ENTRY_SOURCES ) as $name ) {
			if ( null === $this->entry( $name ) ) {
				$unresolved[] = $name;
			}
		}

		return array(
			'ok'               => array() === $unresolved,
			'missing_manifest' => false,
			'unresolved'       => $unresolved,
		);
	}

	/**
	 * Look up a manifest entry by logical name.
	 *
	 * @param string $logical_name Entry name.
	 * @return array<string, mixed>|null
	 */
	private function entry( string $logical_name ): ?array {
		$this->ensure_loaded();

		if ( null === $this->manifest ) {
			return null;
		}

		$source = self::ENTRY_SOURCES[ $logical_name ] ?? null;

		if ( null !== $source && isset( $this->manifest[ $source ] ) && is_array( $this->manifest[ $source ] ) ) {
			return $this->manifest[ $source ];
		}

		// Fallback: Vite may key by basename or include a `name` field.
		foreach ( $this->manifest as $key => $candidate ) {
			if ( ! is_array( $candidate ) ) {
				continue;
			}

			if ( isset( $candidate['name'] ) && $candidate['name'] === $logical_name ) {
				return $candidate;
			}

			if ( is_string( $key ) && ( $key === $logical_name || str_ends_with( $key, '/' . $logical_name . '.js' ) ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Load and decode the manifest once.
	 */
	private function ensure_loaded(): void {
		if ( $this->loaded ) {
			return;
		}

		$this->loaded = true;

		if ( ! is_readable( $this->manifest_path ) ) {
			$this->manifest = null;
			return;
		}

		// Local plugin file on disk — not a remote URL.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $this->manifest_path );

		if ( false === $raw || '' === $raw ) {
			$this->manifest = null;
			return;
		}

		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			$this->manifest = null;
			return;
		}

		$this->manifest = $decoded;
	}
}
