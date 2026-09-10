<div class="article__postmetadata flex">
	<div class="flex">
		<?php the_tags('<div class="icon-text"><i class="fas fa-tags"></i> ', ',&nbsp;', '</div>'); ?>
		<div class="icon-text"><i class="fas fa-briefcase"></i> <?php the_category(',&nbsp;') ?></div>
		<!-- <div class="icon-text"><i class="fas fa-comments"></i> <?php comments_popup_link('No Comments', '1 Comments', '% Comments'); ?></div> -->
	</div>
	<div>
        <?php 
	    	if (is_home() || is_archive()) {
	    		echo '<a class="icon-text icon-text--icon-after more-link" href="' . get_permalink($post->ID) . '">Read More <i class="fas fa-arrow-right"></i></a>';
	    	}
	    ?>
	</div>
</div>