<?php get_header(); ?>

    <?php get_template_part( 'parts/middle', 'page' ); ?>

    <?php get_template_part( 'parts/breadcrumbs', 'page' ); ?>

    <div id="content" class="content-wrapper">
    	<div class="grid flex">
		    <div class="content">
		        <?php while ( have_posts() ) : the_post(); ?>
		            <div class="content__entry">
		                <?php the_content(); ?>
		            </div>
		        <?php endwhile; ?>
		    </div>
    	</div>
	</div>

<?php get_footer(); ?>