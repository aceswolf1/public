<?php 

// Enqueue files
function wdoy_dropdown_files() {
	// wp_enqueue_style( 'google-icons', '//fonts.googleapis.com/icon?family=Material+Icons' );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'hoverIntent' );
}
add_action( 'wp_enqueue_scripts', 'wdoy_dropdown_files' );






