<?php get_header(); ?>

    <?php get_template_part( 'parts/middle', 'search' ); ?>

    <?php get_template_part( 'parts/breadcrumbs', 'search' ); ?>

    <div class="content-wrapper">
    	<div class="grid--2 flex">
    		<div class="content">
			    <?php if ( have_posts() ):?>
			        <?php while ( have_posts() ) : the_post(); ?>
			            <div class="article" <?php post_class() ?> id="post-<?php the_ID(); ?>">
			                <h2 class="article__title"><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
			                <?php get_template_part( 'parts/blog/metadata', 'search' ); ?>
			              	<?php the_post_thumbnail( $size = 'post-thumbnail', $attr = array('class' => 'article__thumbnail') ); ?>
			                <div class="article__entry">
			                    <?php the_excerpt(); ?>
			                </div> 
			                <?php get_template_part( 'parts/blog/postmetadata', 'search' ); ?>
			            </div>
			        <?php endwhile; ?>
			    <?php else : ?>
			        <h2>Not found</h2>
			    <?php endif; ?> 
    		</div>
    	</div>
	</div>

<?php get_footer(); ?>