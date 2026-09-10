<?php get_header(); ?>

    <div id="content" class="content">
        <?php while ( have_posts() ) : the_post(); ?>
            <div class="content__entry">
                <?php the_content(); ?>
            </div>
        <?php endwhile; ?>
    </div>

<?php get_footer(); ?>