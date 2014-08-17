<?php
/*
 * CUSTOM POST TYPE ARCHIVE TEMPLATE
 *
 * This is the custom post type archive template. If you edit the custom post type name,
 * you've got to change the name of this template to reflect that name change.
 *
 * For Example, if your custom post type is called "register_post_type( 'bookmarks')",
 * then your template name should be archive-bookmarks.php
 *
 * For more info: http://codex.wordpress.org/Post_Type_Templates
*/
?>

<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

						<div id="main" class="m-all t-all d-all cf" role="main">

						<h1 class="archive-title h2">Full notes<?php /*post_type_archive_title(); */ ?></h1>

							<p>Notes for <?php bloginfo('name') ?> program:</p>

							<?php /* First display the 'Front Page' */
								$frontpage_id = get_option ( 'page_on_front');
								$frontpage_post = get_post ( $frontpage_id );
								$frontpage_content = $frontpage_post->post_content;
								$frontpage_content = apply_filters ( 'the_content', $frontpage_content );
							?>

							<article id="post-<?php echo($frontpage_id); ?>" <?php post_class( 'cf page-break-on-print' ); ?> role="article">

								<header class="article-header">

									<h2 class="h2"><?php bloginfo('name'); ?></h2>

								</header>

								<section class="entry-content cf">

									<?php echo($frontpage_content); ?>

								</section>

								<footer class="article-footer">

								</footer>

							</article>

							<?php if (have_posts()) : 

							/* EC Sort the posts into menu_order for display */
							query_posts ( "post_type=op_tool_type&posts_per_page=-1&orderby=menu_order&order=asc" );

							while (have_posts()) : the_post(); ?>

							<article id="post-<?php the_ID(); ?>" <?php post_class( 'cf page-break-on-print' ); ?> role="article">

								<header class="article-header">

									<h2 class="h2"><?php the_title(); ?></h2>

								</header>

								<section class="entry-content cf">

									<?php the_content(); ?>

								</section>

								<footer class="article-footer">

								</footer>

							</article>

							<?php endwhile; ?>

									<?php bones_page_navi(); ?>

							<?php else : ?>

									<article id="post-not-found" class="hentry cf">
										<header class="article-header">
											<h1><?php _e( 'Oops, Post Not Found!', 'bonestheme' ); ?></h1>
										</header>
										<section class="entry-content">
											<p><?php _e( 'Uh Oh. Something is missing. Try double checking things.', 'bonestheme' ); ?></p>
										</section>
										<footer class="article-footer">
												<p><?php _e( 'This is the error message in the custom posty type archive template.', 'bonestheme' ); ?></p>
										</footer>
									</article>

							<?php endif; ?>

						</div>

					<?php /* get_sidebar(); */ ?>

				</div>

			</div>

<?php get_footer(); ?>
