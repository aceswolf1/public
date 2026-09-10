<?php 

// Enqueue files
function wdoy_slicknav_files() {	
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'slicknav', get_template_directory_uri() . '/modules/slicknav/js/jquery.slicknav.min.js', 'jquery', null, true );
}
add_action( 'wp_enqueue_scripts', 'wdoy_slicknav_files' );

// Register mobile navigation
if ( ! function_exists( 'wdoy_slicknav_nav_setup' ) ) {
	function wdoy_slicknav_nav_setup() {
		register_nav_menus( array(
			'mobile' => 'Mobile Menu'
		));
	}
}
add_action( 'after_setup_theme', 'wdoy_slicknav_nav_setup' );

// Footer scripts
function wdoy_slicknav_footer_script() { 
	$output = '<nav id="mobile-nav">';
		$output .= wp_nav_menu(array( 'theme_location' => 'mobile', 'echo' => false ));
	$output .= '</nav>';
	echo $output;
}
add_action( 'wp_footer', 'wdoy_slicknav_footer_script' );
