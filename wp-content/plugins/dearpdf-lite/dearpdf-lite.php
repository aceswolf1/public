<?php
// @formatter:off
/**
 * Plugin Name: DearPDF Lite
 * Description: PDF Viewer for WordPress
 * Version: 1.2.62
 *
 * Text Domain: dearpdf
 * Author: DearHive
 * Author URI: http://dearhive.com/
 *
 */
// @formatter:on

// Do not allow direct file access
if (!defined('ABSPATH')) {
  exit('Direct script access denied.');
}

require_once dirname(__FILE__) . '/dearpdf.php';

//Load the DearPDF Plugin Class
$dearpdf = DearPDF::get_instance();

function dearpdf_lite_plugin_activated() {
  
  deactivate_plugins( 'dearpdf-pro/dearpdf-pro.php' );
  
}

register_activation_hook( __FILE__, 'dearpdf_lite_plugin_activated' );
