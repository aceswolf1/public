<?php
/**
 * Plugin Name:       HR Healthcare Sample System
 * Plugin URI:        https://hrhealthcare.com
 * Description:       Sample request cart for HR Healthcare product pages (Elementor). Excel-driven SKUs, modal + cart, Gravity Forms checkout.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            HR Healthcare
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hr-healthcare-sample-system
 * Domain Path:       /languages
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HRH_SAMPLE_VERSION', '0.1.0' );
define( 'HRH_SAMPLE_FILE', __FILE__ );
define( 'HRH_SAMPLE_PATH', plugin_dir_path( __FILE__ ) );
define( 'HRH_SAMPLE_URL', plugin_dir_url( __FILE__ ) );

$hrh_sample_autoload = HRH_SAMPLE_PATH . 'vendor/autoload.php';

if ( ! is_readable( $hrh_sample_autoload ) ) {
	return;
}

require_once $hrh_sample_autoload;

use HR_Healthcare\Sample_System\Activation;
use HR_Healthcare\Sample_System\Plugin;

register_activation_hook(
	__FILE__,
	static function (): void {
		Activation::activate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->boot();
	}
);
