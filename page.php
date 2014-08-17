<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

						<?php /* EC Don't show sidebar on 'pages' replacing classes 't-2of3 d-5of7' */ ?>
						<div id="main" class="m-all t-all d-all cf" role="main">

							<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

							<article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">

								<header class="article-header">

									<h1 class="page-title" itemprop="headline"><?php the_title(); ?></h1>

								</header> <?php // end article header ?>

								<section class="entry-content cf" itemprop="articleBody">
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
								</section> <?php // end article section ?>

								<footer class="article-footer cf">

									<?php
										$pagelist = get_pages('sort_column=menu_order&sort_order=asc');
										$pages = array();
										foreach ($pagelist as $page) {
										   $pages[] += $page->ID;
										}

										$current = array_search(get_the_ID(), $pages);
										$prevID = $pages[$current-1];
										$nextID = $pages[$current+1];
									?>

									<div id="rdsvs_opt_main_navigation">
									<?php if (!empty($prevID)) { ?>
											<a class="rdsvs-opt-previous rdsvs-opt-nav-button" 
												href="<?php echo get_permalink($prevID); ?>"
												data-opt_post_id="<?php echo $prevID; ?>"
									  			title="<?php echo get_the_title($prevID); ?>">Previous</a>
									<?php }
									if (!empty($nextID)) { ?>
											<a class="rdsvs-opt-next rdsvs-opt-nav-button" 
												href="<?php echo get_permalink($nextID); ?>" 
												data-opt_post_id="<?php echo $nextID; ?>"
										 		title="<?php echo get_the_title($nextID); ?>">Next</a>
									<?php } ?>
									</div><!-- #rdsvs_opt_main_navigation -->

								</footer>

								<?php comments_template(); ?>

							</article>

							<?php endwhile; else : ?>

									<article id="post-not-found" class="hentry cf">
										<header class="article-header">
											<h1><?php _e( 'Oops, Post Not Found!', 'bonestheme' ); ?></h1>
										</header>
										<section class="entry-content">
											<p><?php _e( 'Uh Oh. Something is missing. Try double checking things.', 'bonestheme' ); ?></p>
										</section>
										<footer class="article-footer">
												<p><?php _e( 'This is the error message in the page.php template.', 'bonestheme' ); ?></p>
										</footer>
									</article>

							<?php endif; ?>

						</div>

						<?php /*get_sidebar(); */ /* Do not show the sidebar on 'pages' */?>

				</div>

			</div>

<?php get_footer(); ?>
