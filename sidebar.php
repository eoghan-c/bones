				<div id="op_tool_sidebar" class="sidebar m-all t-1of3 d-2of7 last-col cf" role="complementary">

					<?php if ( is_active_sidebar( 'op_tool_sidebar' ) ) : ?>

						<?php dynamic_sidebar( 'op_tool_sidebar' ); ?>

					<?php else : ?>

						<?php
							/*
							 * This content shows up if there are no widgets defined in the backend.
							*/
						?>

						<div class="no-widgets">
							<p><?php _e( 'No widgets in sidebar.', 'bonestheme' );  ?></p>
						</div>

					<?php endif; ?>

				</div>
