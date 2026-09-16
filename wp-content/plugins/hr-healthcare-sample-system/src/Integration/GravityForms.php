<?php
/**
 * Gravity Forms checkout integration — validation, snapshot, entries column.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Integration;

use HR_Healthcare\Sample_System\Data\Groups;
use HR_Healthcare\Sample_System\Data\Options;
use HR_Healthcare\Sample_System\Data\Resolver;
use HR_Healthcare\Sample_System\Support\Settings;

/**
 * Form-ID gated hooks. Never trusts a posted SKU — resolves via Data\Resolver.
 *
 * Required marker CSS classes on the configured form (Appearance → Custom CSS Class):
 * - `hrh-sample-field-selections`  (Hidden; frontend-populated, cleared after snapshot)
 * - `hrh-sample-field-readable`    (Hidden/admin-only; hook-written fulfillment text)
 * - `hrh-sample-field-json`        (Hidden/admin-only; hook-written resolved snapshot)
 */
final class GravityForms {

	public const MARKER_SELECTIONS = 'hrh-sample-field-selections';
	public const MARKER_READABLE   = 'hrh-sample-field-readable';
	public const MARKER_JSON       = 'hrh-sample-field-json';

	public const COLUMN_KEY = 'hrh_sample_summary';

	/**
	 * Register GF hooks. No-ops until a checkout form ID is configured.
	 */
	public function register(): void {
		add_filter( 'gform_validation', array( $this, 'validate' ) );
		add_action( 'gform_pre_submission', array( $this, 'pre_submission' ) );
		add_action( 'gform_after_submission', array( $this, 'clear_cart_cookie' ), 10, 2 );
		add_filter( 'gform_entry_list_columns', array( $this, 'entry_list_columns' ), 10, 2 );
		add_filter( 'gform_entries_column_filter', array( $this, 'entries_column_filter' ), 10, 5 );
	}

	/**
	 * Empty the sample cart cookie after a successful checkout submission.
	 *
	 * Runs during submission processing (before output), so the expiring
	 * Set-Cookie reaches the browser for both standard and AJAX submits. The
	 * frontend also clears + refreshes the UI on the AJAX confirmation.
	 *
	 * @param array<string, mixed> $entry Created entry (unused).
	 * @param array<string, mixed> $form  GF form.
	 */
	public function clear_cart_cookie( $entry, $form ): void {
		unset( $entry );

		if ( ! is_array( $form ) || ! $this->is_checkout_form( $form ) ) {
			return;
		}

		if ( ! headers_sent() ) {
			setcookie(
				'hrh_sample_cart',
				'',
				array(
					'expires'  => time() - HOUR_IN_SECONDS,
					'path'     => '/',
					'samesite' => 'Lax',
				)
			);
		}

		unset( $_COOKIE['hrh_sample_cart'] );
	}

	/**
	 * Validate via gform_validation — cart non-empty + every selection still resolves.
	 *
	 * @param array<string, mixed> $validation_result GF validation payload.
	 * @return array<string, mixed>
	 */
	public function validate( array $validation_result ): array {
		$form = $validation_result['form'] ?? null;

		if ( ! is_array( $form ) || ! $this->is_checkout_form( $form ) ) {
			return $validation_result;
		}

		$fields     = $this->locate_marker_fields( $form );
		$selections = $this->read_selections( $fields['selections'] );

		if ( array() === $selections ) {
			return $this->fail_validation(
				$validation_result,
				$fields['selections'],
				__( 'Your cart is empty. Add at least one sample before submitting.', 'hr-healthcare-sample-system' )
			);
		}

		foreach ( $selections as $index => $line ) {
			$slug    = isset( $line['slug'] ) ? sanitize_title( (string) $line['slug'] ) : '';
			$options = isset( $line['options'] ) && is_array( $line['options'] ) ? $line['options'] : array();

			if ( '' === $slug ) {
				return $this->fail_validation(
					$validation_result,
					$fields['selections'],
					sprintf(
						/* translators: %d: 1-based cart line number */
						__( 'Sample request line %d is invalid. Please re-select it in your cart.', 'hr-healthcare-sample-system' ),
						$index + 1
					)
				);
			}

			$resolved = Resolver::resolve( $slug, $this->sanitize_options( $options ) );

			if ( empty( $resolved['valid'] ) ) {
				return $this->fail_validation(
					$validation_result,
					$fields['selections'],
					sprintf(
						/* translators: 1: 1-based cart line number, 2: group slug */
						__( 'Sample request line %1$d (%2$s) is no longer available. Please re-select or remove it in your cart.', 'hr-healthcare-sample-system' ),
						$index + 1,
						$slug
					)
				);
			}
		}

		return $validation_result;
	}

	/**
	 * Snapshot via gform_pre_submission — resolve live, write readable + JSON, blank selections.
	 *
	 * @param array<string, mixed> $form GF form.
	 */
	public function pre_submission( array $form ): void {
		if ( ! $this->is_checkout_form( $form ) ) {
			return;
		}

		$fields     = $this->locate_marker_fields( $form );
		$selections = $this->read_selections( $fields['selections'] );
		$resolved   = array();

		foreach ( $selections as $line ) {
			$slug    = isset( $line['slug'] ) ? sanitize_title( (string) $line['slug'] ) : '';
			$options = isset( $line['options'] ) && is_array( $line['options'] )
				? $this->sanitize_options( $line['options'] )
				: array();

			if ( '' === $slug ) {
				continue;
			}

			$result = Resolver::resolve( $slug, $options );

			if ( empty( $result['valid'] ) ) {
				// Validation should have blocked this; skip rather than invent data.
				continue;
			}

			$resolved[] = array(
				'slug'          => $slug,
				'options'       => $options,
				'sku'           => (string) $result['sku'],
				'product_name'  => (string) $result['product_name'],
				'hcpcs'         => (string) $result['hcpcs'],
				'sample_amount' => (string) $result['sample_amount'],
			);
		}

		$readable = $this->format_readable( $resolved );
		$json     = wp_json_encode( $resolved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( ! is_string( $json ) ) {
			$json = '[]';
		}

		$this->write_field_value( $fields['readable'], $readable );
		$this->write_field_value( $fields['json'], $json );
		$this->write_field_value( $fields['selections'], '' );
	}

	/**
	 * Add a scannable Samples column on the configured form's entries list.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @param int                   $form_id Form ID.
	 * @return array<string, string>
	 */
	public function entry_list_columns( array $columns, int $form_id ): array {
		if ( ! $this->is_checkout_form_id( $form_id ) ) {
			return $columns;
		}

		$columns[ self::COLUMN_KEY ] = __( 'Samples', 'hr-healthcare-sample-system' );

		return $columns;
	}

	/**
	 * Render the Samples column from the stored JSON snapshot.
	 *
	 * @param string               $value    Current cell value.
	 * @param int                  $form_id  Form ID.
	 * @param string|int           $field_id Column key / field ID.
	 * @param array<string, mixed> $entry    Entry row.
	 * @param string               $query_string Unused.
	 */
	public function entries_column_filter( string $value, int $form_id, $field_id, array $entry, string $query_string ): string {
		unset( $query_string );

		if ( self::COLUMN_KEY !== (string) $field_id || ! $this->is_checkout_form_id( $form_id ) ) {
			return $value;
		}

		$form   = class_exists( 'GFAPI' ) ? \GFAPI::get_form( $form_id ) : null;
		$fields = is_array( $form ) ? $this->locate_marker_fields( $form ) : array(
			'selections' => null,
			'readable'   => null,
			'json'       => null,
		);

		$raw = '';

		if ( null !== $fields['json'] ) {
			$fid = (string) $this->field_id( $fields['json'] );
			$raw = isset( $entry[ $fid ] ) ? (string) $entry[ $fid ] : '';
		}

		if ( '' === $raw ) {
			return '—';
		}

		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) || array() === $decoded ) {
			return '—';
		}

		return $this->format_summary( $decoded );
	}

	/**
	 * Whether this form is the configured checkout form.
	 *
	 * @param array<string, mixed> $form GF form.
	 */
	private function is_checkout_form( array $form ): bool {
		$id = isset( $form['id'] ) ? (int) $form['id'] : 0;

		return $this->is_checkout_form_id( $id );
	}

	/**
	 * Form-ID gate.
	 *
	 * @param int $form_id Candidate form ID.
	 */
	private function is_checkout_form_id( int $form_id ): bool {
		$configured = Settings::checkout_form_id();

		return $configured > 0 && $form_id === $configured;
	}

	/**
	 * Locate the three marker-class fields on a form.
	 *
	 * @param array<string, mixed> $form GF form.
	 * @return array{selections:?object|array, readable:?object|array, json:?object|array}
	 */
	private function locate_marker_fields( array $form ): array {
		$out = array(
			'selections' => null,
			'readable'   => null,
			'json'       => null,
		);

		$map = array(
			self::MARKER_SELECTIONS => 'selections',
			self::MARKER_READABLE   => 'readable',
			self::MARKER_JSON       => 'json',
		);

		$fields = $form['fields'] ?? array();

		if ( ! is_array( $fields ) ) {
			return $out;
		}

		foreach ( $fields as $field ) {
			$css = $this->field_css_class( $field );

			foreach ( $map as $marker => $key ) {
				if ( null !== $out[ $key ] ) {
					continue;
				}

				if ( preg_match( '/(?:^|\s)' . preg_quote( $marker, '/' ) . '(?:\s|$)/', $css ) ) {
					$out[ $key ] = $field;
				}
			}
		}

		return $out;
	}

	/**
	 * Read CSS class from a GF field object or array.
	 *
	 * @param mixed $field GF field.
	 */
	private function field_css_class( $field ): string {
		if ( is_object( $field ) && isset( $field->cssClass ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GF API.
			return (string) $field->cssClass; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GF API.
		}

		if ( is_array( $field ) && isset( $field['cssClass'] ) ) {
			return (string) $field['cssClass'];
		}

		return '';
	}

	/**
	 * Numeric field ID from a GF field object or array.
	 *
	 * @param mixed $field GF field.
	 */
	private function field_id( $field ): int {
		if ( is_object( $field ) && isset( $field->id ) ) {
			return (int) $field->id;
		}

		if ( is_array( $field ) && isset( $field['id'] ) ) {
			return (int) $field['id'];
		}

		return 0;
	}

	/**
	 * Decode the posted selections JSON from the marker field.
	 *
	 * @param mixed $field Selections field or null.
	 * @return list<array<string, mixed>>
	 */
	private function read_selections( $field ): array {
		if ( null === $field ) {
			return array();
		}

		$id = $this->field_id( $field );

		if ( $id <= 0 ) {
			return array();
		}

		$key = 'input_' . $id;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- GF owns the form nonce; we only read the selections payload it already accepted.
		if ( ! isset( $_POST[ $key ] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded + sanitized below.
		$raw = wp_unslash( (string) $_POST[ $key ] );

		if ( '' === trim( $raw ) ) {
			return array();
		}

		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$out = array();

		foreach ( $decoded as $line ) {
			if ( ! is_array( $line ) ) {
				continue;
			}

			$out[] = $line;
		}

		return $out;
	}

	/**
	 * Sanitize an options map to string→string.
	 *
	 * @param array<mixed, mixed> $options Raw options.
	 * @return array<string, string>
	 */
	private function sanitize_options( array $options ): array {
		$clean = array();

		foreach ( $options as $axis => $value ) {
			if ( ! is_string( $axis ) && ! is_int( $axis ) ) {
				continue;
			}

			$axis = sanitize_key( (string) $axis );

			if ( '' === $axis ) {
				continue;
			}

			if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
				continue;
			}

			$value = sanitize_text_field( (string) $value );

			if ( '' === $value ) {
				continue;
			}

			$clean[ $axis ] = $value;
		}

		return $clean;
	}

	/**
	 * Write a value into $_POST for a marker field so GF persists it on the entry.
	 *
	 * @param mixed  $field Field or null.
	 * @param string $value Value to store.
	 */
	private function write_field_value( $field, string $value ): void {
		if ( null === $field ) {
			return;
		}

		$id = $this->field_id( $field );

		if ( $id <= 0 ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- GF submission context; mutating values GF will persist.
		$_POST[ 'input_' . $id ] = $value;
	}

	/**
	 * Mark the form invalid and attach a message to the selections field (or form).
	 *
	 * @param array<string, mixed> $validation_result GF validation payload.
	 * @param mixed                $selections_field  Marker field or null.
	 * @param string               $message           User-facing message.
	 * @return array<string, mixed>
	 */
	private function fail_validation( array $validation_result, $selections_field, string $message ): array {
		$validation_result['is_valid'] = false;
		$form                          = $validation_result['form'] ?? array();

		if ( ! is_array( $form ) || empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			$validation_result['form'] = $form;
			return $validation_result;
		}

		$target_id = null !== $selections_field ? $this->field_id( $selections_field ) : 0;
		$applied   = false;

		foreach ( $form['fields'] as &$field ) {
			$fid = $this->field_id( $field );

			if ( $target_id > 0 && $fid === $target_id ) {
				if ( is_object( $field ) ) {
					$field->failed_validation  = true;
					$field->validation_message = $message;
				} elseif ( is_array( $field ) ) {
					$field['failed_validation']  = true;
					$field['validation_message'] = $message;
				}
				$applied = true;
				break;
			}
		}
		unset( $field );

		if ( ! $applied && isset( $form['fields'][0] ) ) {
			$field = &$form['fields'][0];
			if ( is_object( $field ) ) {
				$field->failed_validation  = true;
				$field->validation_message = $message;
			} elseif ( is_array( $field ) ) {
				$field['failed_validation']  = true;
				$field['validation_message'] = $message;
			}
			unset( $field );
		}

		$validation_result['form'] = $form;

		return $validation_result;
	}

	/**
	 * Human-readable multi-line fulfillment summary.
	 *
	 * @param list<array<string, mixed>> $resolved Resolved lines.
	 */
	private function format_readable( array $resolved ): string {
		if ( array() === $resolved ) {
			return '';
		}

		$blocks = array();
		$n      = 1;

		foreach ( $resolved as $line ) {
			$rows   = array();
			$rows[] = sprintf( '%d. %s', $n, (string) ( $line['product_name'] ?? '' ) );
			$rows[] = '   SKU: ' . (string) ( $line['sku'] ?? '' );
			$rows[] = '   HCPCS: ' . (string) ( $line['hcpcs'] ?? '' );

			$slug    = (string) ( $line['slug'] ?? '' );
			$options = isset( $line['options'] ) && is_array( $line['options'] ) ? $line['options'] : array();

			foreach ( $this->labeled_options( $slug, $options ) as $label_line ) {
				$rows[] = '   ' . $label_line;
			}

			$rows[]   = '   Quantity: ' . (string) ( $line['sample_amount'] ?? '' );
			$blocks[] = implode( "\n", $rows );
			++$n;
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Map selected options to "Label: Value" lines using live option metadata.
	 *
	 * @param string               $slug    Group slug.
	 * @param array<string, mixed> $options Axis → value.
	 * @return list<string>
	 */
	private function labeled_options( string $slug, array $options ): array {
		$out   = array();
		$group = Groups::find_by_slug( $slug );

		if ( null === $group ) {
			foreach ( $options as $axis => $value ) {
				$out[] = $axis . ': ' . (string) $value;
			}
			return $out;
		}

		$properties = Options::properties_for_group( (int) $group['id'] );
		$by_key     = array();

		foreach ( $properties as $prop ) {
			if ( isset( $prop['key'] ) ) {
				$by_key[ (string) $prop['key'] ] = $prop;
			}
		}

		foreach ( $options as $axis => $value ) {
			$axis   = (string) $axis;
			$value  = (string) $value;
			$prop   = $by_key[ $axis ] ?? null;
			$plabel = is_array( $prop ) ? (string) ( $prop['label'] ?? $axis ) : $axis;
			$vlabel = $value;

			if ( is_array( $prop ) && ! empty( $prop['selectable'] ) && isset( $prop['values'] ) && is_array( $prop['values'] ) ) {
				foreach ( $prop['values'] as $choice ) {
					if ( is_array( $choice ) && (string) ( $choice['key'] ?? '' ) === $value ) {
						$vlabel = (string) ( $choice['label'] ?? $value );
						break;
					}
				}
			}

			$out[] = $plabel . ': ' . $vlabel;
		}

		return $out;
	}

	/**
	 * Short entries-list summary, e.g. "3 items · TruCath Duo +2".
	 *
	 * @param list<array<string, mixed>> $resolved Decoded JSON lines.
	 */
	private function format_summary( array $resolved ): string {
		$count = count( $resolved );
		$first = (string) ( $resolved[0]['product_name'] ?? $resolved[0]['sku'] ?? '' );
		$short = $this->shorten_name( $first );

		if ( 1 === $count ) {
			return $short;
		}

		return sprintf(
			/* translators: 1: item count, 2: first product short name, 3: remaining count */
			__( '%1$d items · %2$s +%3$d', 'hr-healthcare-sample-system' ),
			$count,
			$short,
			$count - 1
		);
	}

	/**
	 * Trim a product name for the entries-list column.
	 *
	 * @param string $name Full product name.
	 */
	private function shorten_name( string $name ): string {
		$name = trim( $name );

		if ( '' === $name ) {
			return __( 'Sample', 'hr-healthcare-sample-system' );
		}

		// Prefer the brand-ish head before an em/en dash or " - ".
		if ( preg_match( '/^(.+?)\s+[–—-]\s+/u', $name, $m ) ) {
			$name = $m[1];
		}

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $name ) > 28 ) {
				return rtrim( mb_substr( $name, 0, 27 ) ) . '…';
			}
			return $name;
		}

		if ( strlen( $name ) > 28 ) {
			return rtrim( substr( $name, 0, 27 ) ) . '…';
		}

		return $name;
	}
}
