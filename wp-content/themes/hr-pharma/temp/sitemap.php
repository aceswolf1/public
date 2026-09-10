<?php 

// Template Name: Sitemap


get_header(); ?>

    <?php get_template_part( 'parts/middle', 'page' ); ?>

    <?php get_template_part( 'parts/breadcrumbs', 'page' ); ?>

    <div id="content" class="content-wrapper">
    	<div class="grid flex">
		    <div class="content">
		        <?php while ( have_posts() ) : the_post(); ?>
		            <div class="content__entry">
		                <h3>Pages</h3>
		                <ul>
		                    <?php wp_list_pages(array( 
		                        'post_type' => 'page', 
		                        'title_li' => '',
		                        'sort_column' => 'post_title',
		                        'post_status' => 'publish'
		                    )); ?>
		                </ul>
		                <h3>Posts</h3>
		                <ul>
		                    <?php
		                        $args = array( 'posts_per_page' => 999 );
		                
		                        $myposts = get_posts( $args );
		                        foreach ( $myposts as $apost ) : setup_postdata( $apost ); ?>
		                            <li>
		                                <a href="<?php echo get_permalink($apost->ID); ?>"><?php echo $apost->post_title; ?></a>
		                            </li>
		                        <?php endforeach; 
		                        wp_reset_postdata();
		                    ?>
		                </ul>
		            </div>
		        <?php endwhile; ?>
		    </div>
    	</div>
	</div>

<?php get_footer(); ?>