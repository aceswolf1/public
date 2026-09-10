<div class="comments-wrapper">
	<ul class="comments">
		<?php wp_list_comments( array(
		    'callback'          => 'barebones_comment',
		    'type'              => 'comment',
		    'reply_text'        => '<div class="icon-text"><i class="fas fa-reply"></i> ' . __('Reply', 'barebones') . '</div>',
		    'avatar_size'       => 62
		), $comments ); ?>
	</ul>

	<?php the_comments_navigation(); ?>

	<?php
		comment_form( array(
		    'comment_notes_after' => '',
		    'title_reply' => __('Leave a Comment', 'barebones')
		) ); 
	?>
</div>


<?php
// Custom comments loop
function barebones_comment($comment, $args, $depth) {
	extract($args, EXTR_SKIP);

	if ( 'div' == $args['style'] ) {
		$tag = 'div';
		$add_below = 'comment';
	} else {
		$tag = 'li';
		$add_below = 'div-comment';
	} ?>
	<<?php echo $tag ?> <?php comment_class(empty( $args['has_children'] ) ? '' : 'parent') ?> id="comment-<?php comment_ID() ?>">
	<?php if ( 'div' != $args['style'] ) : ?>
		<div id="div-comment-<?php comment_ID() ?>" class="comment-body">
	<?php endif; ?>
		<div class="comment-table">
			<div class="comment-author vcard">
				<?php if ($args['avatar_size'] != 0) echo get_avatar( $comment, $args['avatar_size'] ); ?>
				<?php printf(__('<cite class="fn">%s</cite>', 'barebones'), get_comment_author_link()) ?>
			</div>
			<div class="spacer"></div>
			<div class="block content comment-box">
				<div class="comment-triangle"></div>
				<?php if ($comment->comment_approved == '0') : ?>
					<em class="comment-awaiting-moderation"><?php _e('Your comment is awaiting moderation.', 'barebones') ?></em>
					<br />
				<?php endif; ?>
				<div class="comment-meta commentmetadata"><a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>">
					<?php
						/* translators: 1: date, 2: time */
						printf( __('%1$s at %2$s', 'barebones'), get_comment_date(),  get_comment_time()) ?></a><?php edit_comment_link(__('(Edit)', 'barebones'),'  ','' );
					?>
				</div>

				<?php comment_text() ?>
			</div>
		</div>

		<div class="reply">
			<?php comment_reply_link(array_merge( $args, array('add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
		</div>
	<?php if ( 'div' != $args['style'] ) : ?>
		</div>
	<?php endif; ?>
<?php }