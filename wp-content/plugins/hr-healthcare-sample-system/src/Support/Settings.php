<?php
/**
 * Plugin option accessors (configuration values).
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Support;

/**
 * Single place for option keys and typed getters/setters.
 */
final class Settings {

	public const OPT_CHECKOUT_FORM_ID = 'hrh_sample_checkout_form_id';
	public const OPT_CHECKOUT_PAGE_ID = 'hrh_sample_checkout_page_id';
	public const OPT_CART_CAP         = 'hrh_sample_cart_cap';

	public const DEFAULT_CART_CAP = 10;

	/**
	 * Configured Gravity Forms form ID (0 = unset).
	 */
	public static function checkout_form_id(): int {
		return (int) get_option( self::OPT_CHECKOUT_FORM_ID, 0 );
	}

	/**
	 * Persist checkout form ID.
	 *
	 * @param int $form_id Form ID.
	 */
	public static function set_checkout_form_id( int $form_id ): void {
		update_option( self::OPT_CHECKOUT_FORM_ID, max( 0, $form_id ), false );
	}

	/**
	 * Configured checkout page ID (0 = unset).
	 */
	public static function checkout_page_id(): int {
		return (int) get_option( self::OPT_CHECKOUT_PAGE_ID, 0 );
	}

	/**
	 * Persist checkout page ID.
	 *
	 * @param int $page_id Page ID.
	 */
	public static function set_checkout_page_id( int $page_id ): void {
		update_option( self::OPT_CHECKOUT_PAGE_ID, max( 0, $page_id ), false );
	}

	/**
	 * Checkout permalink, or empty if unset/trashed.
	 */
	public static function checkout_url(): string {
		$page_id = self::checkout_page_id();

		if ( $page_id <= 0 ) {
			return '';
		}

		$status = get_post_status( $page_id );

		if ( ! is_string( $status ) || 'publish' !== $status ) {
			return '';
		}

		$url = get_permalink( $page_id );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Cart item cap (default 10).
	 */
	public static function cart_cap(): int {
		$cap = (int) get_option( self::OPT_CART_CAP, self::DEFAULT_CART_CAP );

		return $cap > 0 ? $cap : self::DEFAULT_CART_CAP;
	}

	/**
	 * Persist cart item cap.
	 *
	 * @param int $cap Max items.
	 */
	public static function set_cart_cap( int $cap ): void {
		update_option( self::OPT_CART_CAP, max( 1, $cap ), false );
	}
}
