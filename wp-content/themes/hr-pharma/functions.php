<?php

add_theme_support( 'post-thumbnails' );
add_theme_support( 'title-tag' );

add_image_size( 'post-thumbnail', 940, 300, true );
add_image_size( 'header', 1900, 350, true );

get_template_part( 'admin/autoload' );

// Enqueue scripts
function theme_scripts() {
	$ver = '1.0.7';	
	wp_enqueue_style( 'google-fonts-custom', 'https://fonts.googleapis.com/css2?family=Gentium+Basic:ital@1&family=Marcellus&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,300;1,400;1,500;1,700;1,900&display=swap', [], null);
	wp_enqueue_style( 'font-awesome-all', get_stylesheet_directory_uri() . '/css/font-awesome.all.min.css');
	wp_enqueue_style( 'theme-style', get_stylesheet_directory_uri() . '/css/main.css', null, $ver );
	wp_enqueue_style( 'slick-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css');
	wp_enqueue_style( 'slick-theme-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css');

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'font-awesome-scripts', get_stylesheet_directory_uri() . '/js/font-awesome.all.min.js', null, $ver = false, $in_footer = true );
	wp_enqueue_script( 'slick-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', array('jquery'), $ver = false, $in_footer = true );
	
}
add_action( 'wp_enqueue_scripts', 'theme_scripts' );


// Register primary navigation
if ( ! function_exists( 'theme_setup' ) ) {
	function theme_setup() {

		//navigations
		register_nav_menus( array(
			'top' => 'Top Menu',
			'primary' => 'Primary Menu',
			'footer' => 'Footer Menu',
			'contract_manufacturing_top' => 'Contract Manufacturing Top Menu',
			'contract_manufacturing_primary' => 'Contract Manufacturing Primary Menu'
		));

		//sidebars
		register_sidebar(array(
			'name' 	=> 'Blog Sidebar',
			'id'	=> 'blog',
			'description'	=> 'Widgets for the sidebar on each blog page.',
			'before_widget'	=> '<div class="widget %2$s" id="%1$s">',
			'after_widget'	=> '</div>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		));

		register_sidebar(array(
			'name' 	=> 'Footer Left',
			'id'	=> 'footer_left',
			'description'	=> 'Widgets for the left side footer on each page.',
			'before_widget'	=> '<div class="widget %2$s" id="%1$s">',
			'after_widget'	=> '</div>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		));

		register_sidebar(array(
			'name' 	=> 'Footer Middle',
			'id'	=> 'footer_middle',
			'description'	=> 'Widgets for the middle section footer on each page.',
			'before_widget'	=> '<div class="widget %2$s" id="%1$s">',
			'after_widget'	=> '</div>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		));

		register_sidebar(array(
			'name' 	=> 'Footer Right',
			'id'	=> 'footer_right',
			'description'	=> 'Widgets for the right side footer on each page.',
			'before_widget'	=> '<div class="widget %2$s" id="%1$s">',
			'after_widget'	=> '</div>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		));
	}
}
add_action( 'after_setup_theme', 'theme_setup' );

function new_excerpt_more($more) {
	global $post;
	return '...';
}
add_filter('excerpt_more', 'new_excerpt_more');

//edit user capibilities
function add_options_to_editor () {
	$role = get_role( 'editor' );
	$role->add_cap( 'edit_theme_options' ); 
	$role->add_cap( 'gravityforms_edit_forms' );
	$role->add_cap( 'gravityforms_delete_forms' );
	$role->add_cap( 'gravityforms_create_form' );
	$role->add_cap( 'gravityforms_view_entries' );
	$role->add_cap( 'gravityforms_edit_entries' );
	$role->add_cap( 'gravityforms_delete_entries' );
	$role->add_cap( 'gravityforms_view_settings' );
	$role->add_cap( 'gravityforms_edit_settings' );
	$role->add_cap( 'gravityforms_export_entries' );
	$role->add_cap( 'gravityforms_view_entry_notes' );
	$role->add_cap( 'gravityforms_edit_entry_notes' );
}
add_action( 'admin_init', 'add_options_to_editor' );


// mobile detection
function mobile_viewport() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
}
add_action('wp_head', 'mobile_viewport');

// for debuging
function debug($data) {
	echo '<pre>';
	var_dump($data);
	echo '</pre>';
}

// returns the top parent page id
function get_top_parent_page_id() {
	global $post;
	if (isset($post)) {
		if ($post->ancestors) {
			$post_ancestors = get_post_ancestors($post);
			return end( $post_ancestors );
		} else {
			return $post->ID;
		}
	}
}

if( function_exists('acf_add_options_page') ) {	
	acf_add_options_page(array(
		'page_title' 	=> 'Global Settings',
		'menu_title'	=> 'Global Settings',
		'menu_slug' 	=> 'global-settings',
		'capability'	=> 'edit_pages',
		'redirect'		=> false
	));
}

function field_exists($field) {
	if (isset($field) && strlen($field) > 0) {
		return true;
	} else {
		return false;
	}
}

// get video data given a vimeo or youtube url
function get_video_data($video_url) {
	// parse url
	$parse = parse_url($video_url);

	// extract video id
	if ($parse['query']) {	
		parse_str($parse['query'], $query);
		$data['id'] = $query['v'];
	} else {
		$video_array = explode('/', $video_url);
		$data['id'] = array_pop($video_array);
	}
	// extract video type
	$data['type'] = str_replace(array('.com', 'www.'), '', $parse['host']);

	return $data;
}

// echos a clean excerpt based on how many words
function get_clean_excerpt($post_id, $words, $link = false, $append = false) {
    $post = get_post($post_id);
	if ($link === true) {
		$append = ' <a href="' . get_permalink($post_id) . '">' . $append . '</a>';
	}
	$content = $post->post_content;
    $content = strip_shortcodes($content);
    return wp_trim_words(esc_html(strip_tags($content)), $words, $append);
}

// echo out a navigation in the content with shortcode
// ex: [nav name="primary"]
function nav_func( $atts = array(), $content = '' ) {
	$atts = shortcode_atts( array(
		'name' => 'primary',
	), $atts, 'nav' );

	return wp_nav_menu( array( 'theme_location' => $atts['name'], 'echo' => false ) );
}
add_shortcode( 'nav', 'nav_func' );

function back_to_blog_btn() {
	echo '<a class="button button--light icon-text back-to-blog-btn" href="' . get_permalink( get_option( 'page_for_posts' ) ) . '"><i class="fas fa-reply"></i> Back to blog</a>';
}

// Add CSS to WP backend
function admin_style() {
  wp_enqueue_style('admin-styles', get_template_directory_uri().'/css/admin.css');
}
add_action('admin_enqueue_scripts', 'admin_style');

// allow svg filetype in media uploads
function cc_mime_types($mimes) {
 	$mimes['svg'] = 'image/svg+xml';
 	return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');


// edit menu items
add_filter('wp_nav_menu_objects', 'my_wp_nav_menu_objects', 10, 2);
function my_wp_nav_menu_objects( $items, $args ) {
	// top menu items (Contract Manufacturing CTA button)
	if( $args->theme_location == 'top' || $args->theme_location == 'contract_manufacturing_top'  ) {
		// loop
		foreach( $items as &$item ) {
			// vars
			$icon = get_field('icon', $item);
			$description = get_field('description', $item);
			
			// append icon
			if( $icon && $description ) {			
				$item->title = '<div class="cta flex">' . $icon  . '<div><div class="cta__title">' . $item->title . '</div><div class="cta__description">' . $description . '</div></div></div>';	
			}
		}
	}
	if( $args->theme_location == 'primary' || $args->theme_location == 'contract_manufacturing_primary' ) {
		// loop
		foreach( $items as &$item ) {
			// vars
			$description = get_field('description', $item);
			
			// append icon
			if( $description ) {			
				$item->title = '<div class="menu-item__title">' . $item->title . '</div><div class="menu-item__description">' . $description . '</div>';	
			}
		}
	}
	// return
	return $items;
}


function is_contract_manufacturing() {
	global $post;

	$cm = get_page_by_path('contract-manufacturing');

	// debug($cm);

	if ($post->post_parent === $cm->ID || $post->ID === $cm->ID) {
	   	return true;
	} else {
		return false;
	}
}