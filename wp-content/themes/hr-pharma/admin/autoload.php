<?php

// include all custom post types
function get_cpts() {
	$path = get_template_directory() . '/admin/cpt/';
	$files = scandir($path);
	foreach ($files as $file) {
		if (strpos($file, '.php') !== false) {
			$file_name = str_replace('.php', '', $file);
			get_template_part( 'admin/cpt/' . $file_name );
		}
	}
} get_cpts();

// include all modules
function get_modules() {
	$path = get_template_directory() . '/modules/*';
	$dirs = array_filter(glob($path), 'is_dir');
	foreach ($dirs as $dir) {
		$dir_name = basename($dir);
		get_template_part( 'modules/' . $dir_name . '/' . $dir_name );
	}
	function all_js() {
		wp_enqueue_script( 'all-js', get_template_directory_uri() . '/js/all.js', 'jquery', null, true );
	}
	if (!empty($dirs)) {
		add_action( 'wp_enqueue_scripts', 'all_js' );
	}
} get_modules();