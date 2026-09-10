<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

use Elementor\WPNotificationsPackage\V110\Notifications as ThemeNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '3.3.0' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);
			add_theme_support( 'align-wide' );
			add_theme_support( 'responsive-embeds' );

			/*
			 * Editor Styles
			 */
			add_theme_support( 'editor-styles' );
			add_editor_style( 'editor-styles.css' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		$min_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				get_template_directory_uri() . '/header-footer' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

// Admin notice
if ( is_admin() ) {
	require get_template_directory() . '/includes/admin-functions.php';
}

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

function hello_elementor_get_theme_notifications(): ThemeNotifications {
	static $notifications = null;

	if ( null === $notifications ) {
		require get_template_directory() . '/vendor/autoload.php';

		$notifications = new ThemeNotifications(
			'hello-elementor',
			HELLO_ELEMENTOR_VERSION,
			'theme'
		);
	}

	return $notifications;
}

hello_elementor_get_theme_notifications();

function display_product_resources() {
    $product_resources = get_field('product_resources');
    if (!$product_resources) return '';
    
    $count = count($product_resources);
    $container_class = ($count === 3) ? 'product-container justify-between' : 'product-container';

    ob_start();
    echo "<div class='$container_class'>";
    
    foreach ($product_resources as $post):
        setup_postdata($post);
        
        $post_url = get_permalink($post->ID);
        $image = get_field('product_image', $post->ID);
        $video = get_field('product_video', $post->ID);
        $media_type = get_field('product_media_type', $post->ID);
        $category = get_field('product_category', $post->ID);

        // Map media_type to readable text
        $type_map = [
            'product-sheet' => 'Product Sheet',
            'video' => 'Video',
            'post' => 'Blog Post'
        ];
        $type = $type_map[$media_type] ?? 'Unknown';

        // Prepare media content
        $media = $video ? "<video controls><source src='".esc_url($video)."' type='video/mp4'></video>" :
                 ($image ? "<img src='".esc_url($image['url'])."' alt=''>" : '');

        // Conditional styling for product-sheet
        $style = ($media_type === 'product-sheet') ? "background: rgba(217, 217, 217, 0.5); padding: 20px;" : "";

        echo "<div class='product-card $media_type'>
                <div class='product-media' style='$style'>$media</div>
                <div class='product-info'>
                    <p class='product-category'><a href='".esc_url($post_url)."'>".esc_html($category)."</a></p>
                    <p class='product-type'>".esc_html($type)."</p>
                </div>
              </div>";
    endforeach;
    
    wp_reset_postdata();
    echo "</div>";
    
    return ob_get_clean();
}
add_shortcode('acf_product_repeater', 'display_product_resources');

function display_related_products() {
    $related_products = get_field('related_products');
    if (!$related_products) return '';

    $count = count($related_products);
    $container_class = ($count === 4) ? 'product-container related-product-container justify-between' : 'product-container';

    ob_start();
    echo "<div class='$container_class'>";

    foreach ($related_products as $post):
        setup_postdata($post);

        $post_url = get_permalink($post->ID);
        
        $gallery_images = get_field('product_carousel', $post->ID);
        $image_url = '';

        if ($gallery_images && is_array($gallery_images) && isset($gallery_images[0]['url'])) {
            $image_url = $gallery_images[0]['url']; // first image in the gallery
        } else {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full'); // fallback to featured image
        }

        $title = get_field('product_title', $post->ID);
        $subtitle = get_field('sub-title', $post->ID);

        echo "<div class='product-card related-product-card'>
                <a href='".esc_url($post_url)."' class='product-media'>
                    " . ($image_url ? "<img src='".esc_url($image_url)."' alt='' />" : "") . "
                </a>
                <div class='product-info'>
                    <p class='product-category'><a href='".esc_url($post_url)."'>".esc_html($title)."</a></p>
                    <p class='product-type'>".esc_html($subtitle)."</p>
                </div>
              </div>";
    endforeach;

    wp_reset_postdata();
    echo "</div>";

    return ob_get_clean();
}

add_shortcode('acf_related_products', 'display_related_products');


function query_by_matching_fields( $query ) {
    // Retrieve the custom field value from the current page (assumes you're using it on a page template)
    $current_page_id = get_queried_object_id(); // Get the current page ID
    $page_custom_field_value = get_post_meta( $current_page_id, 'product_category', true );

    // If the custom field value on the page is empty, return no posts
    if ( empty( $page_custom_field_value ) ) {
        // Return an empty array by setting 'post__in' to an empty array
        $query->set( 'post__in', array( 0 ) );
        return;
    }

    // Proceed only if the custom field value is set and valid
    $meta_query = array(
        array(
            'key'     => 'product_category',  // The custom field key for posts
            'value'   => $page_custom_field_value, // The value must match both post and page fields
            'compare' => '=',  // Ensure exact match comparison
        ),
    );

    // Apply both meta query and taxonomy query to the query
    $query->set( 'meta_query', $meta_query );

    // If the query still returns posts when no matches are found, ensure no posts are returned
    if ( empty( $meta_query ) ) {
        // Set post__in to an empty array if no matching custom field is found
        $query->set( 'post__in', array( 0 ) );
    }
}

// Hook the custom query to the specific Elementor Query ID (matching_product_asset_custom_fields_query)
add_action( 'elementor/query/matching_custom_fields_query', 'query_by_matching_fields' );


function redirect_logged_in_users() {
    if ( is_user_logged_in() && is_page('login') ) {
        wp_redirect( home_url('/media-center/corporate'), 302 );
        exit;
    }
}
add_action('template_redirect', 'redirect_logged_in_users');

function media_center_logout_shortcode() {
    $redirect_url = home_url( '/login' ); // Redirect to /login
    return '<a href="' . wp_logout_url( $redirect_url ) . '" class="logout-link">LOGOUT</a>';
}
add_shortcode('media-center-logout', 'media_center_logout_shortcode');




// -------- Enqueue custom JS --------
function enqueue_custom_checkbox_search_script() {
    wp_enqueue_script(
        'custom-checkbox-search',
        get_template_directory_uri() . '/js/custom-checkbox-search.js',
        [],
        '1.0',
        true
    );

    wp_localize_script(
        'custom-checkbox-search',
        'ajaxurl',
        admin_url('admin-ajax.php')
    );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_checkbox_search_script');


// -------- AJAX handlers --------
add_action('wp_ajax_custom_checkbox_search', 'custom_checkbox_search_callback');
add_action('wp_ajax_nopriv_custom_checkbox_search', 'custom_checkbox_search_callback');

function custom_checkbox_search_callback() {
    global $wpdb;

    $keywords = [];
    if (!empty($_POST['keywords']) && is_array($_POST['keywords'])) {
        $keywords = array_map('sanitize_text_field', $_POST['keywords']);
        $keywords = array_filter($keywords);
    }

    $paged = !empty($_POST['paged']) ? intval($_POST['paged']) : 1;
    $posts_per_page = 5; // Adjust as needed

    // === CASE 1: No keywords → default taxonomy filter ===
    if (empty($keywords)) {
        $args = [
            'post_type'      => 'page',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
            'tax_query'      => [
				'relation' => 'AND', // Both conditions must be met (change to 'OR' if you want either)
				[
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => ['hcp', 'pc', 'l1-hcp', 'li-pc'],
					'operator' => 'IN',
				],
				[
					'taxonomy' => 'product_category',
					'field'    => 'slug',
					'terms'    => ['antiseptic', 'urology', 'general', 'wound'],
					'operator' => 'IN',
				],
			],
        ];

        $query = new WP_Query($args);

        $output = '';
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $output .= custom_checkbox_search_render_post();
            }
        } else {
            $output = '<p>No results found in default categories.</p>';
        }

        if ($query->max_num_pages > 1) {
            $output .= custom_checkbox_search_render_pagination($paged, $query->max_num_pages);
        }

        wp_reset_postdata();
        echo $output;
        wp_die();
    }

    // === CASE 2: Keywords exist → OR search ===
    $or_clauses = [];
    foreach ($keywords as $word) {
        $like = '%' . $wpdb->esc_like($word) . '%';
        $or_clauses[] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s", $like, $like);
    }
    $custom_where = '(' . implode(' OR ', $or_clauses) . ')';

    $args = [
        'post_type'        => ['post','page'],
        'posts_per_page'   => $posts_per_page,
        'paged'            => $paged,
        'suppress_filters' => false,
		'tax_query'      => [
			[
				'taxonomy' => 'post_tag',      // Use the post_tag taxonomy
				'field'    => 'slug',          // We're using the slug to match
				'terms'    => ['hcp', 'pc', 'l1-hcp', 'li-pc'], // Tags to filter
				'operator' => 'IN',            // Match any of these tags
			],
		],
    ];

    add_filter('posts_where', function($where_sql) use ($custom_where) {
        return $where_sql . " AND $custom_where ";
    });

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $output = '';
        while ($query->have_posts()) {
            $query->the_post();
            $output .= custom_checkbox_search_render_post();
        }

        if ($query->max_num_pages > 1) {
            $output .= custom_checkbox_search_render_pagination($paged, $query->max_num_pages);
        }

        echo $output;
    } else {
        echo 'null';
    }

    wp_reset_postdata();
    wp_die();
}

/**
 * Render Elementor-style post block
 */
function custom_checkbox_search_render_post() {
    $post_id   = get_the_ID();
    $title     = get_the_title();
    $excerpt   = get_the_excerpt();
    $permalink = get_permalink();
    $thumbnail = get_the_post_thumbnail_url($post_id, 'large');
    $categories = get_the_category();

    ob_start();
    ?>
    <div data-elementor-type="loop-item" data-elementor-id="<?php echo esc_attr($post_id); ?>" 
         class="elementor elementor-<?php echo esc_attr($post_id); ?> e-loop-item post-<?php echo esc_attr($post_id); ?> page" 
         style="padding:20px 0;border-bottom:1px solid #eee;">
        <div class="elementor-element e-con-full e-flex e-con e-parent" 
             style="display:flex;flex-direction:column;gap:20px;align-items:flex-start;justify-content:space-between;">
            <div class="e-con-inner" 
                 style="display:flex;flex-direction:row;gap:30px;align-items:center;justify-content:space-between;width:100%;">

                <!-- Left Content -->
                <div style="flex:1;">
                    <div style="margin-bottom:8px;font-size:14px;color:#555;">
                        <?php echo (!empty($categories) ? esc_html($categories[0]->name).' | ' : ''); ?>
                        <?php echo get_post_type_object(get_post_type())->labels->singular_name; ?>
                    </div>
                    <div style="margin-bottom:8px;">
                        <h2 style="font-size:20px;font-weight:600;margin:0;"><?php echo esc_html($title); ?></h2>
                    </div>
                    <div style="margin-bottom:16px;font-size:16px;color:#444;line-height:1.5;">
                        <?php echo esc_html($excerpt); ?>
                    </div>
                    <div>
                        <a href="<?php echo esc_url($permalink); ?>" 
                           style="display:inline-block;background:none;border:none;color:#000;font-size:15px;font-weight:500;text-decoration:underline;cursor:pointer;">
                            Learn More
                        </a>
                    </div>
                </div>

                <!-- Right Image -->
                <div style="flex-shrink:0;">
                    <?php if ($thumbnail): ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>" style="max-width:200px;height:auto;"/>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render simple pagination buttons
 */
/**
 * Render Elementor-style pagination
 */
function custom_checkbox_search_render_pagination($current, $total) {
    if ($total <= 1) return '';

    $output = '<nav class="elementor-pagination" aria-label="Pagination">';

    // Previous button
    if ($current > 1) {
        $prev_page = $current - 1;
        $output .= '<span class="page-numbers prev" data-page="'. $prev_page .'">Previous</span>';
    } else {
        $output .= '<span class="page-numbers prev disabled">Previous</span>';
    }

    // Page numbers
    for ($i = 1; $i <= $total; $i++) {
        if ($i == $current) {
            $output .= '<span aria-current="page" class="page-numbers current">'
                        .'<span class="elementor-screen-only">Page</span>'. $i .'</span>';
        } else {
            $output .= '<a class="page-numbers" href="#" data-page="'. $i .'">'
                        .'<span class="elementor-screen-only">Page</span>'. $i .'</a>';
        }
    }

    // Next button
    if ($current < $total) {
        $next_page = $current + 1;
        $output .= '<span class="page-numbers next" data-page="'. $next_page .'">Next</span>';
    } else {
        $output .= '<span class="page-numbers next disabled">Next</span>';
    }

    $output .= '</nav>';
    return $output;
}

// Enable default post tags for Pages
function add_tags_to_pages() {
    register_taxonomy_for_object_type('post_tag', 'page');
}
add_action('init', 'add_tags_to_pages');

// Add a new column for Tags
function add_page_tags_column($columns) {
    $columns['tags'] = 'Tags';
    return $columns;
}
add_filter('manage_pages_columns', 'add_page_tags_column');

// Populate the Tags column
function show_page_tags_column($column, $post_id) {
    if ($column === 'tags') {
        $tags = get_the_terms($post_id, 'post_tag');
        if ($tags && !is_wp_error($tags)) {
            $tag_links = array();
            foreach ($tags as $tag) {
                $tag_links[] = '<a href="' . esc_url(get_edit_term_link($tag->term_id, 'post_tag')) . '">' . esc_html($tag->name) . '</a>';
            }
            echo implode(', ', $tag_links);
        } else {
            echo '-';
        }
    }
}
add_action('manage_pages_custom_column', 'show_page_tags_column', 10, 2);

add_action('elementor/query/acf_product_spec', function($query) {
    // Get the current page ID
    $page_id = get_the_ID();

    // Get the related post ID from ACF
    $related_post_id = get_field('product_spec', $page_id);

    if ($related_post_id) {
        // Only return this post
        $query->set('post__in', [$related_post_id]);
    } else {
        // No post selected, return nothing
        $query->set('post__in', [0]);
    }

    // Ensure only one post is returned
    $query->set('posts_per_page', 1);
});

add_action('elementor/query/acf_4p', function($query) {
    // Get the current page ID
    $page_id = get_the_ID();

    // Get the related post ID from ACF
    $related_post_id = get_field('4p', $page_id);

    if ($related_post_id) {
        // Only return this post
        $query->set('post__in', [$related_post_id]);
    } else {
        // No post selected, return nothing
        $query->set('post__in', [0]);
    }

    // Ensure only one post is returned
    $query->set('posts_per_page', 1);
});

add_action('elementor/query/acf_table_spec', function($query) {
    // Get the current page ID
    $page_id = get_the_ID();

    // Get the related post ID from ACF
    $related_post_id = get_field('table_spec', $page_id);

    if ($related_post_id) {
        // Only return this post
        $query->set('post__in', [$related_post_id]);
    } else {
        // No post selected, return nothing
        $query->set('post__in', [0]);
    }

    // Ensure only one post is returned
    $query->set('posts_per_page', 1);
});

?>


