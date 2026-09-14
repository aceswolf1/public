<?php
/**
 * REST controller — batched + single group configs.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Rest;

use HR_Healthcare\Sample_System\Data\Resolver;
use HR_Healthcare\Sample_System\Support\ImageMap;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Returns group config payloads including resolved image_url.
 */
final class GroupsController {

	/**
	 * Image map service.
	 *
	 * @var ImageMap
	 */
	private ImageMap $image_map;

	/**
	 * Constructor.
	 *
	 * @param ImageMap $image_map Image map service.
	 */
	public function __construct( ImageMap $image_map ) {
		$this->image_map = $image_map;
	}

	/**
	 * GET /groups?slugs=a,b,c
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function batched( WP_REST_Request $request ): WP_REST_Response {
		$raw   = (string) $request->get_param( 'slugs' );
		$slugs = array_filter(
			array_map(
				static function ( string $slug ): string {
					return sanitize_title( trim( $slug ) );
				},
				explode( ',', $raw )
			)
		);

		$out = array();

		foreach ( array_unique( $slugs ) as $slug ) {
			$config = Resolver::group_config( $slug, $this->image_map->image_url( $slug ) );

			if ( null !== $config ) {
				$out[ $slug ] = $config;
			}
		}

		return new WP_REST_Response( $out, 200 );
	}

	/**
	 * GET /group/{slug}
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function single( WP_REST_Request $request ): WP_REST_Response {
		$slug   = sanitize_title( (string) $request->get_param( 'slug' ) );
		$config = Resolver::group_config( $slug, $this->image_map->image_url( $slug ) );

		if ( null === $config ) {
			return new WP_REST_Response(
				array(
					'code'    => 'hrh_sample_group_not_found',
					'message' => 'Group not found.',
				),
				404
			);
		}

		return new WP_REST_Response( $config, 200 );
	}
}
