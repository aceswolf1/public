<?php 

// Enqueue files
function wdoy_scrolltop_files() {
	wp_enqueue_style( 'google-icons', '//fonts.googleapis.com/icon?family=Material+Icons' );
	wp_enqueue_script( 'jquery' );	
}
add_action( 'wp_enqueue_scripts', 'wdoy_scrolltop_files' );


// Footer scripts
function wdoy_scrolltop_footer_script() {	
	$output = '<div class="arrow-up grid">';
	    $output .= '<div class="arrow-up__wrapper">';
	        $output .= '<i class="arrow-up__btn fas fa-arrow-up"></i>';
	    $output .= '</div>';
	$output .= '</div>';

	echo $output;
}
add_action( 'wp_footer', 'wdoy_scrolltop_footer_script' );
