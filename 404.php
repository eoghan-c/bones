<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

					<div id="main" class="m-all t-2of3 d-5of7 cf" role="main">

						<article id="post-not-found" class="hentry cf">

							<header class="article-header">

								<h1><?php _e( 'Page not found', 'bonestheme' ); ?></h1>

							</header>

							<section class="entry-content">

								<p><?php _e( 'The page you requested could not be found, please click below to return to the homepage:', 'bonestheme' ); ?></p>
								<ul>
									<li>
										<a href="<?php echo home_url( '/' ); ?>">
											<?php echo esc_url( home_url( '/' ) ); ?>
										</a>
									</li>
								</ul>
								
							</section>

							<footer class="article-footer">

									<p></p>

							</footer>

						</article>

					</div>

				</div>

			</div>

<?php get_footer(); ?>
