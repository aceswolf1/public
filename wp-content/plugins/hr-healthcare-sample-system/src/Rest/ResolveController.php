<?php
/**
 * REST controller — POST /resolve.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Rest;

use HR_Healthcare\Sample_System\Data\Resolver;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Delegates to Data\Resolver — the ONE resolver.
 */
final class ResolveController {

	/**
	 * POST /resolve  body: { slug, options }
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function resolve( WP_REST_Request $request ): WP_REST_Response {
		$slug    = sanitize_title( (string) $request->get_param( 'slug' ) );
		$options = $request->get_param( 'options' );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$clean = array();

		foreach ( $options as $axis => $value ) {
			if ( ! is_string( $axis ) ) {
				continue;
			}

			$axis = sanitize_key( $axis );

			if ( '' === $axis ) {
				continue;
			}

			if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
				continue;
			}

			$clean[ $axis ] = sanitize_text_field( (string) $value );
		}

		return new WP_REST_Response( Resolver::resolve( $slug, $clean ), 200 );
	}
}
