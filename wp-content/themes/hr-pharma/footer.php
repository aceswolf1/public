        <div class="footer">
            <?php if(!is_contract_manufacturing()) { ?>
            	<div class="footer__top">
            		<div class="grid flex">
                        <div><?php dynamic_sidebar( 'footer_left' ); ?></div>
                        <div><?php dynamic_sidebar( 'footer_middle' ); ?></div>
                        <div><?php dynamic_sidebar( 'footer_right' ); ?></div>
            		</div>
            	</div>
            <?php } ?>
        	<div class="footer__bottom">
        		<div class="grid">
                    <p><a href="https://hrpharma.com/terms-conditions/" title="Terms & Conditions">Terms & Conditions</a> <a href="<?php echo get_the_permalink( 649 ); ?>" title="Legal Notice">Legal Notice</a> <a href="<?php echo get_the_permalink( 3 ); ?>" title="Privacy Policy">Privacy Policy</a>. &copy; <?php echo date('Y'); ?> HR Pharmaceuticals, Inc.
					</p>
        		</div>
        	</div>
        </div>
    </div> <?php // .wrapper ?>
<?php wp_footer(); ?>
</body>
</html>