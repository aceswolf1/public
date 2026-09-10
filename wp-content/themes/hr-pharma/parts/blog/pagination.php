<?php
	if (is_search()) {
		$type = 'Pages';
	} else {
		$type = 'Posts';
	}
?>

<div id="pagination" class="clear">
    <div id="prev-posts" class="alignleft"><?php previous_posts_link( '&laquo; Previous ' . $type ); ?></div>
    <div id="next-posts" class="alignright"><?php echo get_next_posts_link( 'More ' . $type . ' &raquo;', $wp_query->max_num_pages ); ?></div>
</div>