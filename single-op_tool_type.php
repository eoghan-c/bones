<?php
/*
 * CUSTOM POST TYPE TEMPLATE
 *
 * This is the custom post type post template. If you edit the post type name, you've got
 * to change the name of this template to reflect that name change.
 *
 * For Example, if your custom post type is "register_post_type( 'bookmarks')",
 * then your single template should be single-bookmarks.php
 *
 * Be aware that you should rename 'custom_cat' and 'custom_tag' to the appropiate custom
 * category and taxonomy slugs, or this template will not finish to load properly.
 *
 * For more info: http://codex.wordpress.org/Post_Type_Templates
*/
?>

<?php 
	// Only let users view this OPT post if the URL has a valid session ID
	$session_array = EMS_OP_Tool::read_session_array();

	if ( empty($session_array) ) {
		// We don't have a valid array of OPT posts, so bail out.
?>
<script>
	window.location.href = "<?php echo site_url(); ?>";
</script>
<?php

	}

	// If we've got this far, then $session_array is valid - collect other info we need

	// Find in which position within the chrono_array this post sits
	$op_tool_post_pos = EMS_OP_Tool::get_chrono_array_pos ( $session_array['chrono_array'], get_the_ID() );

	// Get info for the OPT progress bar
	list ( $num_prev, $num_next ) = 
		EMS_OP_Tool::get_pos_of_post_in_section ( $session_array['chrono_array'], $op_tool_post_pos );

	// Decide whether we should show the progress bar (not for Section posts)
	$progress_display_style = '';
	if ( $num_prev + $num_next == 0 ) {
		$progress_display_style = 'style="display: none;"';
	}

	// Set up the Previous and Next navigation buttons
	$pagelist = get_posts('post_type=op_tool_type&posts_per_page=-1&orderby=menu_order&order=asc');
	$pages = array();
	foreach ($pagelist as $page) {
	   $pages[] += $page->ID;
	}

	// Get the IDs of the posts before and after the current one
	$current = array_search ( get_the_ID(), $pages );
	$prevID  = $pages[$current-1];
	$nextID  = $pages[$current+1];

	// Decide whether we should show the Previous button
	//    (don't show it on posts [0])
	$prevStyle = '';
	if ( empty($prevID) || ($op_tool_post_pos < 1) ) {
		$prevStyle = 'visibility:hidden ';
	}
	$nextStyle = '';
	if ( empty($nextID) ) {
		$nextStyle = 'visibility:hidden ';
	}

	// Setup the 'delay' value for this post
	// (i.e. the number of seconds we wait before changing the 'Skip' button to 'Next')
	// Get the 'delay' value for this post
	$delay_value = get_post_meta ( get_the_ID(), '_opt_read_delay', true );

	// If this post doesn't have its delay set, use the default number of seconds
	if ( $delay_value === "" )
		$delay_value = EMS_OP_Tool::get_opt_post_default_delay();

	// $delay_value is in seconds, so convert to milliseconds
	$delay_value = intval ($delay_value) * 1000;

	$nextClass = '';
	if ( $delay_value <= 0 ) {
		// If the post has a delay value of '0', then don't show 'Skip' button 
		// Just consider posts with '0' delay value to be 'viewed' and show the 'Next' button straight away
		$nextClass = '';
		$nextSkipLabelStyle = 'display: none;';
		$nextLabelStyle = '';
	} else {
		// If this isn't a Section post, then show the button 'Skip'
		// (we will deal with posts that have already been viewed in the next 'if' statement)
		$nextClass = 'rdsvs-opt-nav-button-skip';
		$nextSkipLabelStyle = '';
		$nextLabelStyle = 'display: none;';
	}

	// If the post has already been viewed, then show the 'Next' button straight away
	// EC Hack: We could combine this check with the 'if [section]' check above, 
	//          but we might want to deal with them separately in the future.
	if ( $session_array['chrono_array'][$op_tool_post_pos]['viewed'] ) {
		$nextClass = '';
		$nextSkipLabelStyle = 'display: none;';
		$nextLabelStyle = '';
	}

	// Label the 'Previous' button, depending on whether we are showing a section
	$prevLabel  = "Previous";
	if ( $session_array['chrono_array'][$op_tool_post_pos]['section'] ) {
		// There are no more posts in this 'Section'
		$prevLabel = 'Previous Section';

		// If this button will lead to the previous section, then make sure we jump to the start of that section
		if ( $op_tool_post_pos > 0 ) {
			$prev_section_pos = EMS_OP_Tool::get_section_pos_of_post ( $session_array['chrono_array'], $op_tool_post_pos-1 );
			$prevID_url = get_permalink ( $session_array['chrono_array'][$prev_section_pos]['post_id'] );
		}

	} else {
		$prevID_url = get_permalink ( $prevID );
	}

	// Label the 'Next' button, depending on whether we're showing the last post in a section
	$nextLabel  = 'Next';
	$nextID_url = get_permalink ( $nextID );
	if ( !$session_array['chrono_array'][$op_tool_post_pos]['section'] & ($num_next == 0) ) {
		// There are no more posts in this 'Section'
		$nextLabel = 'Next Section';
	}

?>

<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

						<div id="main" class="m-all t-2of3 d-5of7 cf" role="main">

							<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

								<article id="post-<?php the_ID(); ?>" <?php post_class('cf'); ?> role="article">

									<header class="article-header">

										<h1 class="single-title custom-post-type-title"><?php the_title(); ?></h1>

									</header>

									<section class="entry-content cf">
										<div id="rdsvs_online_placement_tool_content" 
											data-opt_post_pos="<?php echo ($op_tool_post_pos); ?>" 
											data-opt_post_delay="<?php echo $delay_value; ?>">
										<?php
											// the content (pretty self explanatory huh)
											the_content();

											/*
											 * Link Pages is used in case you have posts that are set to break into
											 * multiple pages. You can remove this if you don't plan on doing that.
											 *
											 * Also, breaking content up into multiple pages is a horrible experience,
											 * so don't do it. While there are SOME edge cases where this is useful, it's
											 * mostly used for people to get more ad views. It's up to you but if you want
											 * to do it, you're wrong and I hate you. (Ok, I still love you but just not as much)
											 *
											 * http://gizmodo.com/5841121/google-wants-to-help-you-avoid-stupid-annoying-multiple-page-articles
											 *
											*/
											/*wp_link_pages( array(
												'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'bonestheme' ) . '</span>',
												'after'       => '</div>',
												'link_before' => '<span>',
												'link_after'  => '</span>',
											) );*/
										?>
										</div>
									</section> <!-- end article section -->

									<footer class="article-footer">

										<div id="rdsvs_opt_navigation">
											<a class="rdsvs-opt-previous rdsvs-opt-nav-button" 
												href="<?php echo $prevID_url; ?>"
												data-opt_post_id="<?php echo $prevID; ?>"
									  			title="<?php echo get_the_title($prevID); ?>"
									  			style="<?php echo $prevStyle; ?>">
									  			<span class="hide-on-small-screens">
									  				<?php echo ($prevLabel); ?>
									  			</span>
									  			<span class="show-on-small-screens">
									  				Prev
									  			</span>
									  		</a>

											<a class="rdsvs-opt-next rdsvs-opt-nav-button <?php echo($nextClass); ?>" 
												href="<?php echo $nextID_url; ?>" 
												data-opt_post_id="<?php echo $nextID; ?>"
										 		title="<?php echo get_the_title($nextID); ?>"
										 		style="<?php echo $nextStyle; ?>">
										 		<span class="rdsvs-button-skip-label" style="<?php echo ($nextSkipLabelStyle); ?>">
										  			<span class="hide-on-small-screens">
										  				Wait<!--Skip-->
										  			</span>
										  			<span class="show-on-small-screens">
										  				Wait<!--Skip-->
										  			</span>
										 		</span>
										 		<span class="rdsvs-button-next-label" style="<?php echo ($nextLabelStyle); ?>">
										  			<span class="hide-on-small-screens">
										  				<?php echo ($nextLabel); ?>
										  			</span>
										  			<span class="show-on-small-screens">
										  				Next
										  			</span>
										 		</span>
										 	</a>

											<div id="rdsvs-opt-frame-progress" class="rdsvs-opt-frame-progress" <?php echo($progress_display_style); ?>>
												Screen 
												<span class="rdsvs_opt_current_screen"><?php echo ($num_prev); ?></span>
												of 
												<span class="rdsvs_opt_total_screens"><?php echo ($num_prev+$num_next); ?></span>
											</div>
										</div><!-- #rdsvs_opt_navigation -->

										<p class="tags"><?php echo get_the_term_list( get_the_ID(), 'custom_tag', '<span class="tags-title">' . __( 'Custom Tags:', 'bonestheme' ) . '</span> ', ', ' ) ?></p>

									</footer>

									<?php comments_template(); ?>

								</article>

							<?php endwhile; ?>

							<?php else : ?>

									<article id="post-not-found" class="hentry cf">
										<header class="article-header">
											<h1><?php _e( 'Oops, Post Not Found!', 'bonestheme' ); ?></h1>
										</header>
										<section class="entry-content">
											<p><?php _e( 'Uh Oh. Something is missing. Try double checking things.', 'bonestheme' ); ?></p>
										</section>
										<footer class="article-footer">
											<p><?php _e( 'This is the error message in the single-op_tool_type.php template.', 'bonestheme' ); ?></p>
										</footer>
									</article>

							<?php endif; ?>

						</div>

						<?php get_sidebar(); ?>

				</div>

			</div>

<?php get_footer(); ?>
