<?php get_header(); ?>

    <?php get_template_part( 'parts/middle', 'single' ); ?>

    <?php get_template_part( 'parts/breadcrumbs', 'single' ); ?>

    <div class="content-wrapper">
    	<div class="grid--2 flex">
    		<!-- <div class="sidebar">
    			<?php dynamic_sidebar( 'blog' ); ?>
    		</div> -->
    		<div id="content" class="content">
    			<?php //back_to_blog_btn(); ?>
			    <?php if ( have_posts() ):?>
			        <?php while ( have_posts() ) : the_post(); ?>
			            <div class="article" <?php post_class() ?> id="post-<?php the_ID(); ?>">
			                <h2 class="article__title"><?php the_title(); ?></h2>
			                <?php get_template_part( 'parts/blog/metadata', 'single' ); ?>
			              	<?php the_post_thumbnail( $size = 'post-thumbnail', $attr = array('class' => 'article__thumbnail') ); ?>
			                <div class="article__entry">
			                    <?php the_content(); ?>
			                </div> 
			                <?php get_template_part( 'parts/blog/postmetadata', 'single' ); ?>
			            </div>
                		<?php //comments_template(); ?>
			        <?php endwhile; ?>
			    <?php else : ?>
			        <h2>Not found</h2>
			    <?php endif; ?> 
    		</div>
    	</div>
	</div>

<?php get_footer(); ?>