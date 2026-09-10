<div class="middle">
    <div class="diamond-hero">
        <div class="diamond-hero__content">
            <h2><?php 
                if (is_home() || is_archive() || is_singular( 'post' )) {
                    echo get_the_title(get_option( 'page_for_posts' ));
                    if (is_archive()) { ?>
                        <?php if (is_category()) {?>
                            <span>- Archive for the &#8216;<?php single_cat_title(); ?>&#8217; Category</span>
                        <?php } elseif (is_tag()) {?>
                            <span>- Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;</span>
                        <?php } elseif (is_day()) {?>
                            <span>- Archive for <?php the_time('F jS, Y'); ?></span>
                        <?php } elseif (is_month()) {?>
                            <span>- Archive for <?php the_time('F, Y'); ?></span>
                        <?php } elseif (is_year()) {?>
                            <span>- Archive for <?php the_time('Y'); ?></span>
                        <?php } elseif (is_author()) {?>
                            <span>- Author Archive</span>
                        <?php } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) {?>
                            <span>- Archives</span>
                        <?php } ?>
                    <?php }
                } elseif (is_search()) {
                    echo 'Search results for "' . get_search_query( $escaped = true ) . '"';
                } else {
                    the_title(); 
                }
            ?></h2>
        </div>
        <div class="diamond-grid">
            <div class="diamond diamond--large"></div>
            <div class="diamond diamond--1"></div>
            <div class="diamond diamond--2"></div>
            <div class="diamond diamond--3"></div>
            <div class="diamond diamond--4"></div>
            <div class="diamond diamond--5"></div>
            <div class="diamond diamond--6"></div>
            <div class="diamond diamond--7"></div>
            <div class="diamond diamond--8"></div>
            <div class="diamond diamond--9"></div>
            <div class="diamond diamond--10"></div>
            <div class="diamond diamond--11"></div>
            <div class="diamond diamond--12"></div>
            <div class="diamond diamond--13"></div>
            <div class="diamond diamond--14"></div>
            <div class="diamond diamond--15"></div>
        </div>
    </div>
</div>