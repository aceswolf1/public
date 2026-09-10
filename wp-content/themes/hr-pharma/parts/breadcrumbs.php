<?php

// options
$use_links 	= true;
$arrow 		= '<span class="breadcrumbs__arrow"><i class="fas fa-chevron-right"></i></span>';


// global vars
$posts_page = get_option( 'page_for_posts' );

if (!is_home() && !is_search()) {
	$parents = array_reverse(get_post_ancestors( $post->ID ));
}
echo '<div class="breadcrumbs">';
	echo '<div class="grid flex">';
		echo '<a class="breadcrumbs__home" href="' . esc_url( home_url( '/' ) ) . '"><i class="fas fa-home"></i></a>' . $arrow;
		if (is_search()) {
			echo 'Search results';
		}
		// display the blog title if home.php or archive.php
		elseif (is_home() || is_archive()) {
			if (is_home()) {
				echo '<span class="breadcrumbs__current-page">' . get_the_title($posts_page) . '</span>';
			} else {
				echo '<a href="' . get_the_permalink($posts_page) . '">' . get_the_title($posts_page) . '</a>';
				echo $arrow . 'Archive';
			}
		} else {	
			// display the blog title first if single.php			
			if (is_singular('post')) {
				$parents = array($posts_page);
			}
			
			foreach ($parents as $parent_id) {
				$title = get_the_title( $parent_id );
				if ($use_links === true) {
					$url = get_permalink( $parent_id );
					echo '<a href="' . $url . '" title="' . $title . '">' . $title . '</a>' . $arrow;
				} else {
					echo $title . $arrow;
				}
			}			

			if ($use_links === true ) {
				echo '<a class="breadcrumbs__current-page" href="' . get_the_permalink() . '" title="' . get_the_title() . '">' . get_the_title() . '</a>';
			} else {
				echo '<span class="breadcrumbs__current-page">' . get_the_title() . '</span>';
			}
		}
	echo '</div>';
echo '</div>';
