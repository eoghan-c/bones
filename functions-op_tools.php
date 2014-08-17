<?php

/* All the functions to support the Online Placement Tools system */

abstract class EMS_OP_Tool_Base {
	/* Define object variables */
    private static $reg = array();
    protected static $opt_post_default_delay;

    public static function init() {
        add_action ( 'after_setup_theme', array(static::instance(), '_setup') );
    }

    public static function instance () {
        $cls = get_called_class();
        !isset(self::$reg[$cls]) && self::$reg[$cls] = new $cls;
        return self::$reg[$cls];
    }

    abstract public function _setup ();

	abstract public function start_session ();

	/* Deal with object variables */
	abstract public static function get_opt_post_default_delay ();
	abstract public static function op_tool_get_option ( $key = '' );

	/* Register scrips, sidebars etc. */
	abstract public function register_scripts ();
	abstract public function enqueue_scripts ();
	abstract public function register_sidebars ();

	/* Define Shortcodes */
	abstract public function user_details_body_creator ( $atts, $content="" );
	abstract public function table_of_contents_body_creator ( $atts, $content="" );
	abstract public function certificate_screen_body_creator ( $atts, $content="" );
	abstract public function table_of_contents_button_creator ( $atts, $content="" );	
	abstract public function sections_nav_body_creator ( $atts, $content="" );
	abstract public function test_code_body_creator ( $atts, $content="" );

	abstract public static function is_valid_email ( $string );
	abstract public static function store_session_array ( $session_array );
	abstract public static function read_session_array ();
	abstract public function collect_op_tool_posts ();
	abstract public static function get_chrono_array_pos ( $chrono_array, $opt_post_id );
	abstract public static function get_pos_of_post_in_section ( $chrono_array, $curr_post_pos );

	abstract public static function get_section_pos_of_post ( $chrono_array, $curr_post_pos );
	abstract public function update_section_viewed_status ( $chrono_array );
	abstract public function get_number_sections_viewed ( $chrono_array );

	/* AJAX Functions */
	abstract public function op_tool_start ();
	abstract public function opt_screen_viewed ();

	abstract public static function mozilla_persona_valid ( $email_address, $audience );
	abstract public function award_certificate ( &$session_array );
	abstract public function replace_placeholder_tokens ( $the_text, $session_array );
	abstract public function wpbadger_award_badges ( $badge, $emails, $evidence, $expires );
	abstract public static function wpbadger_award_choose_badge ();
}

class EMS_OP_Tool extends EMS_OP_Tool_Base {

    public function _setup () {
    	add_action ( 'init', array($this, 'start_session'), 1 );

   		add_action ('init', array($this, 'register_scripts') );
		add_action ( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
 
 		add_action ( 'widgets_init', array($this, 'register_sidebars') );
		// Also allow shortcodes to be used in widgets
		add_filter ( 'widget_text', 'do_shortcode' );

		add_shortcode ( 'rdsvs_op_tool_user_details', array($this, 'user_details_body_creator') );
		add_shortcode ( 'rdsvs_op_tool_table_of_contents', array($this, 'table_of_contents_body_creator') );
		add_shortcode ( 'rdsvs_op_tool_certificate_screen', array($this, 'certificate_screen_body_creator') );
		add_shortcode ( 'rdsvs_op_tool_sections_nav', array($this, 'sections_nav_body_creator') );
		add_shortcode ( 'rdsvs_op_tool_table_of_contents_button', 
							array($this, 'table_of_contents_button_creator') );
		add_shortcode ( 'rdsvs_op_tool_test_code', array($this, 'test_code_body_creator') );

		add_action ( 'wp_ajax_op_tool_start', array($this, 'op_tool_start') );
		add_action ( 'wp_ajax_nopriv_op_tool_start', array($this, 'op_tool_start') );
		add_action ( 'wp_ajax_opt_screen_viewed', array($this, 'opt_screen_viewed') );
		add_action ( 'wp_ajax_nopriv_opt_screen_viewed', array($this, 'opt_screen_viewed') );

		/* Setup default values */
		/* Setup default post delay
		(number of seconds the OPT post stays on the screen before the 'Skip' button changes to 'Next') */
		self::$opt_post_default_delay = 5;

		/* Setup OP Tool post meta boxes */
		require_once 'cmb-functions.php';
	}


	/*************************************************
	** Prepare a session for us to use with the OPT **
	**************************************************/
	// Thanks http://silvermapleweb.com/using-the-php-session-in-wordpress/
	public function start_session () {
	    if ( !session_id() ) {
	        session_start();
	    }
	}

	/******************************************************
	** Functions to 'get' the values of object variables **
	*******************************************************/
	public static function get_opt_post_default_delay () {
		return EMS_OP_Tool::$opt_post_default_delay;
	}

	/**
	 * Wrapper function around cmb_get_option
	 * @since  0.1.0
	 * @param  string  $key Options array key
	 * @return mixed        Option value
	 */
	public static function op_tool_get_option ( $key = '' ) {
	    global $op_tool_Admin;
	    return cmb_get_option( $op_tool_Admin->key, $key );
	}

	/*************************
	** Register our scripts **
	**************************/

	/* With thanks to SSVadim for the development of his SSQuiz plugin
	   http://100vadim.com/ssquiz/ */
	public function register_scripts () {
		wp_register_script ( 'rdsvs_opt_js', 
			get_stylesheet_directory_uri() . '/library/js/min/functions-op_tools.min.js', 
			array( 'jquery', 'jquery-ui-sortable' ),
			'',
			true );
	}


	/******************
	** Queue Scripts **
	*******************/

	public function enqueue_scripts () {
		wp_enqueue_script ( 'rdsvs_opt_js' );
		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		wp_localize_script ( 'rdsvs_opt_js', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}


	/**************************************
	** Register sidebars used in the OPT **
	***************************************/

	public function register_sidebars () {
		register_sidebar ( array(
			'id'            => 'op_tool_sidebar',
			'name'          => __( 'OP Tool Sidebar', 'bonestheme' ),
			'description'   => __( 'The first (primary) sidebar.', 'bonestheme' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widgettitle">',
			'after_title'   => '</h4>',
		));
	}


	/********************************************************
	** Shortcode definitions for the Online Placement Tool **
	*********************************************************/

	/**** 'rdsvs_op_tool_user_details' shortcode setup ****/
	public function user_details_body_creator ( $atts, $content="" ) {	
		// Provide default values for shortcode [rdsvs_op_tool_user_details], using method from:
		//    http://codex.wordpress.org/Shortcode_API
		/*extract ( shortcode_atts( array(
			'parent_category' => '',
			'question_prompt' => '',
		), $atts ) );*/

		$session_array = array();

	    // Collect information about all the available OP Tool posts.
		$session_array['chrono_array'] = self::collect_op_tool_posts();

		// Store the info about OPT posts, for use throughout the resource
		self::store_session_array ( $session_array );

		$pagelist = get_posts('post_type=page&posts_per_page=-1&orderby=menu_order&order=asc');
		$pages = array();
		foreach ($pagelist as $page) {
		   $pages[] += $page->ID;
		}

		// Get the IDs of the posts before and after the current one
		$current = array_search(get_the_ID(), $pages);
		$nextID  = $pages[$current+1];

		ob_start();

	?>
		<div id="rdsvs_opt_user_details_form">
			<p>
				<strong>Full name</strong>
				<input type="text" id="rdsvs_opt_username"/>
			</p>
			<p>
				<strong>Mozilla Backpack account email address</strong><br />
				<span class="user_note">
					Note: This is <strong>case-sensitive</strong>, 
					so enter it exactly as it appears in the header of your 
					<a href="http://backpack.openbadges.org/" target="_blank">Mozilla Backpack</a> screen
				</span>
				<input type="text" id="rdsvs_opt_useremail"/>
			</p>
			<p>
				<strong>EMS Admin notification email address</strong><br />
				<span class="user_note">
					If you would like to automatically notify your EMS Administrator when
					you complete this program, enter their email address here
				</span>
				<input type="text" id="rdsvs_opt_notifyemail"/>
			</p>
			<a id="rdsvs_opt_frame_start" 
				class="rdsvs_opt_start rdsvs-opt-nav-button"
				href="<?php echo(get_permalink($nextID)); ?>">
				Begin
			</a>
		</div>
	<?php

		$return_content = ob_get_contents();
		ob_end_clean();

		return $return_content;
	}

	/**** 'rdsvs_op_tool_overall_navigation' shortcode setup ****/
	public function table_of_contents_body_creator ( $atts, $content="" ) {	
		// Provide default values for shortcode [rdsvs_op_tool_overall_navigation], using method from:
		//    http://codex.wordpress.org/Shortcode_API
		/*extract ( shortcode_atts( array(
			'parent_category' => '',
			'question_prompt' => '',
		), $atts ) );*/

		$session_array = self::read_session_array();

		ob_start();

		if ( empty($session_array) ) {
			// We don't have a valid session, invite the user to start the resource from the beginning.

	?>

		<a class="rdsvs-opt-nav-button" href=<?php echo (site_url()); ?>>Start At The Beginning</a>

	<?php

		} else {
			// We do have a valid session, so display the table of contents

			// Store the ID of this TOC page, for use in the 'Main Menu' buttons of OPT posts
			$session_array['table_of_contents_page_id'] = get_the_ID();

			// Make sure we store the TOC page ID in the user's session variable
			self::store_session_array ( $session_array );

			// Scan through, and work out the current 'viewed' status of each section
			$session_array['chrono_array'] = 
									self::update_section_viewed_status ( $session_array['chrono_array'] );
	?>

		<div id="rdsvs_opt_table_of_contents">

	<?php 
		$curr_section_num = 1;
		foreach ( $session_array['chrono_array'] as $curr_post ): 
			if ( $curr_post['section'] ):

				$curr_section_class = '';
				// Set its class, depending on whether the section has been viewed or not
				if ( $curr_post['section_viewed'] ) {
					$curr_section_class .= ' rdsvs-section-viewed';
				}

				// Call get_number_sections_viewed() to find out whether any full sections
				//    have been viewed yet
				list ( $total_num_sections, $num_sections_viewed ) = 
									self::get_number_sections_viewed ( $session_array['chrono_array'] );

				// If no sections have been viewed yet, add a 'Start Here' note to the first button
				$long_button_label  = $curr_section_num . '. ' . get_the_title($curr_post['post_id']);
				$short_button_label = $curr_section_num;
				if ( ($num_sections_viewed < 1) && ($curr_section_num == 1) )
					$long_button_label .= '<br /><br /><span class="rdsvs-start-here-label">Start Here</span>';
	?>

			<span class="rdsvs-toc-button<?php echo($curr_section_class); ?>">
				<a class="rdsvs-opt-nav-button"
					href=<?php echo(get_permalink($curr_post['post_id'])); ?>>
					<span class="hide-on-small-screens"><?php echo($long_button_label); ?></span>
					<span class="show-on-small-screens"><?php echo($short_button_label); ?></span>
				</a>
			</span>

	<?php 
				$curr_section_num++;
			endif;
		endforeach; 
	?>

		</div>
		
	<?php

		}

		$return_content = ob_get_contents();
		ob_end_clean();
		
		return $return_content;
	}

	/**** 'rdsvs_op_tool_certificate_screen' shortcode setup ****/
	public function certificate_screen_body_creator ( $atts, $content="" ) {	
		// Provide default values for shortcode [rdsvs_op_tool_user_details], using method from:
		//    http://codex.wordpress.org/Shortcode_API
		extract ( shortcode_atts( array(
			'completed' => '',
			'uncompleted' => ''
		), $atts ) );

		// Get the session array
		$session_array = self::read_session_array ();

		// Record the finishing time (but guard against page reloads)
		if ( !isset($session_array['session']['end_time']) ) {
			$session_array['session']['end_time'] = time();
			$session_array['session']['minutes_taken'] = 
						ceil(($session_array['session']['end_time'] - 
							$session_array['session']['start_time']) / 60);
		}

		list ( $total_num_sections, $num_sections_viewed ) = 
									self::get_number_sections_viewed ( $session_array['chrono_array'] );

		$additional_msg = $uncompleted;
		if ( $num_sections_viewed >= $total_num_sections ) {
			$additional_msg = $completed;
		}

		// Get the version number of this theme, and treat it as the Engine Version Number
		$opt_engine_version = 0;
		$theme_data = wp_get_theme();
		if ( $theme_data->exists() )
			$opt_engine_version = $theme_data->get( 'Version' );

		ob_start();

	?>

		<div class="rdsvs-sections-report no-print">
			<p>
				<strong>
					You have viewed
					<?php echo($num_sections_viewed); ?>
					out of
					<?php echo($total_num_sections); ?>
					sections of this program.
				</strong>
			</p>
			<p>
				<?php echo($additional_msg); ?>
			</p>

	<?php if ( $num_sections_viewed >= $total_num_sections ): ?>
			<p>
				Notification email sent to:
				&lt;<?php echo($session_array['user_details']['email']); ?>&gt;
			</p>
	<?php

			// Check whether we have already awarded a certificate in this session
			if ( !isset($session_array['session']['badge_id']) ) {

				// The badge hasn't been awarded yet
				$award_post_ids = self::award_certificate ( $session_array );

				if ( empty($award_post_ids) ) {
					echo '<p>Unfortunately there was a problem awarding your certificate, 
								please refresh this page.</p>';
				}/* else {
					$session_array['session']['badge_id'] = $award_post_ids[0];
				}*/
			}

		endif; ?>

		</div>

	<?php

		// We've set $session_array['session']['end_time'] & ...['badge_id'], so make sure we save them
		self::store_session_array ( $session_array );

		$return_content = ob_get_contents();
		ob_end_clean();
		
		return $return_content;
	}

	/**** 'rdsvs_op_tool_table_of_contents_button' shortcode setup ****/
	public function table_of_contents_button_creator ( $atts, $content="" ) {	
		// Provide default values for shortcode [rdsvs_op_tool_sections_nav], using method from:
		//    http://codex.wordpress.org/Shortcode_API
		extract ( shortcode_atts( array(
			'button_label' => ''
		), $atts ) );

		if ( empty($button_label) )
			$button_label = "Main Menu";

		// Get the session array
		$session_array = self::read_session_array ();

		if ( empty($session_array) )
			return;

		$table_of_contents_url = get_permalink ( $session_array['table_of_contents_page_id'] );

		ob_start();

	?>

		<div class="rdsvs-opt-section">
			<a class="rdsvs-opt-nav-button"
				href="<?php echo ($table_of_contents_url); ?>">
				<?php echo($button_label); ?>
			</a>
		</div>

	<?php

		$return_content = ob_get_contents();
		ob_end_clean();
		
		return $return_content;
	}

	/**** 'rdsvs_op_tool_sections_nav' shortcode setup ****/
	public function sections_nav_body_creator ( $atts, $content="" ) {	
		// Provide default values for shortcode [rdsvs_op_tool_sections_nav], using method from:
		//    http://codex.wordpress.org/Shortcode_API
		/*extract ( shortcode_atts( array(
			'parent_category' => '',
			'question_prompt' => '',
		), $atts ) );*/

		// Get the session array
		$session_array = self::read_session_array ();

		// Get the position of the currently displayed post in the chrono_array
		$op_tool_post_pos = self::get_chrono_array_pos ( $session_array['chrono_array'], get_the_ID() );
		// Find the position of the 'section' that this post is in
		$op_tool_section_pos = self::get_section_pos_of_post ( $session_array['chrono_array'], $op_tool_post_pos );

		// Scan through, and work out the current 'viewed' status of each section
		$session_array['chrono_array'] = self::update_section_viewed_status ( $session_array['chrono_array'] );

		ob_start();

		foreach ($session_array['chrono_array'] as $curr_post_pos => $curr_post) {
			if ( $curr_post['section'] ) {
				// Get the section title
				$curr_section_title = get_the_title ( $curr_post['post_id'] );

				// Get the weblink of the section post
				$curr_section_url = get_post_permalink ( $curr_post['post_id'] );

				$curr_section_class = "";
				// Set its class, depending on whether the current post is in this section
				if ( $curr_post_pos == $op_tool_section_pos )
					$curr_section_class .= " rdsvs-current-section";

				// Set its class, depending on whether the section has been viewed or not
				if ( $curr_post['section_viewed'] ) {
					$curr_section_class .= ' rdsvs-section-viewed';
				}
	?>

		<div class="rdsvs-opt-section<?php echo($curr_section_class); ?>">
			<a class="rdsvs-opt-nav-button"
				href="<?php echo ($curr_section_url); ?>">
				<?php echo($curr_section_title); ?>
			</a>
		</div>

	<?php

			}
		}

		$return_content = ob_get_contents();
		ob_end_clean();
		
		return $return_content;
	}

	/**** 'rdsvs_op_tool_test_code' shortcode setup ****/
	public function test_code_body_creator ( $atts, $content="" ) {
		// Provide default values for shortcode [rdsvs_op_tool_sections_nav], using method from:
		//    http://codex.wordpress.org/Shortcode_API
		/*extract ( shortcode_atts( array(
			'parent_category' => '',
			'question_prompt' => '',
		), $atts ) );*/

		ob_start();

		echo ("bloginfo(name): "); bloginfo('name');
		echo ("<br />bloginfo(description): "); bloginfo('description');
		echo ("<br />bloginfo(wpurl): "); bloginfo('wpurl');
		echo ("<br />bloginfo(url): "); bloginfo('url');
		echo ("<br />bloginfo(admin_email): "); bloginfo('admin_email');
		echo ("<br />bloginfo(charset): "); bloginfo('charset');
		echo ("<br />bloginfo(version): "); bloginfo('version');
		echo ("<br />bloginfo(html_type): "); bloginfo('html_type');
		echo ("<br />bloginfo(text_direction): "); bloginfo('text_direction');
		echo ("<br />bloginfo(language): "); bloginfo('language');
		echo ("<br />bloginfo(stylesheet_url): "); bloginfo('stylesheet_url');
		echo ("<br />bloginfo(stylesheet_directory): "); bloginfo('stylesheet_directory');
		echo ("<br />bloginfo(template_url): "); bloginfo('template_url');
		echo ("<br />bloginfo(home_url): "); echo esc_url( home_url( '/wp-admin/edit.php?post_type=award' ) );

		echo ("<br />bloginfo(home_url): "); echo home_url();
		echo("<br />parse_url(home_url()) ");
		print_r(parse_url(home_url()));

		echo("<br />network_home_url() ");
		echo network_home_url();

		echo("<br />getrandmax() ");
		echo getrandmax();

		echo("<br />mt_getrandmax() ");
		echo mt_getrandmax();

		$temp_array = array ();
		$temp_array['user_details']['name'] = 'Eoghan Mouse';
		$temp_array['user_details']['email'] = 'Eoghan.Mouse@the.house';
		$temp_array['session']['minutes_taken'] = '30';
		$evidence = self::op_tool_get_option ( 'badge_evidence_text' );
		$evidence = self::replace_placeholder_tokens ( $evidence, $temp_array );
		echo("<br /><br />" . $evidence);

		$return_content = ob_get_contents();

		ob_end_clean();
		
		return $return_content;
	}

	/*************************
	** Supporting functions **
	**************************/

	// Get the HTML content of the given $post_id
	/*function rdsvs_get_post_content ($post_id) {
		$screen_content_raw = get_post ($post_id) -> post_content;
		$screen_content     = do_shortcode( $screen_content_raw );

		return $screen_content;
	}*/

	// Get the HTML content of the given $post_id
	/*function rdsvs_get_post_content ($post_id) {
		$post_data = get_post ($post_id);
		setup_postdata ( $post_data );

		return the_content();
	}*/

	/*public function rdsvs_get_post_content ( $post_id ) {
		$post_content_raw = get_post ($post_id) -> post_content;
		$post_content     = apply_filters ( 'the_content', $post_content_raw );

		return $post_content;
	}*/

	// Thanks to  pzb at novell dot com - http://php.net/manual/en/function.count-chars.php
	// Return the number of email addresses found (a simple count of the number of '@' chars)
	public static function is_valid_email( $string ) {
		// First check that the $string contains valid 7-bit ASCII characters
		// empty strings are 7-bit clean
		if ( !strlen($string) ) {
			return 0;
		}

		// count_chars returns the characters in ascending octet order
		$str3 = count_chars( $string, 3 );
		// Check for null character
		if ( !ord($str3[0]) ) {
			return 0;
		}

		// Check for 8-bit character
		if ( ord($str3[strlen($str3)-1]) & 128 ) {
			return 0;
		}

		$str1 = count_chars( $string, 1 );
		// If any spaces are detected, then assume more than one email address added.
		if ( $str1[ord(' ')] > 0 ) {
			return 2;
		}

		// If any commas are detected, then assume more than one email address added.
		if ( $str1[ord(',')] > 0 ) {
			return 2;
		}

		// Finally:
		// Return the number of '@' chars in $string (assuming each '@' char represents an email address)
		return $str1[ord('@')];
	}

	/*****************************
	** Handle the session array **
	******************************/

	/* Store session array in the $_SESSION variable */
	public static function store_session_array ( $session_array ) {
		// Create a unique-ish $session_var_name
		// (avoid session variable clash when different OPT tools are run in same browser)
		$session_var_name = 'opt_tools_' . get_current_blog_id();

		$_SESSION[$session_var_name] = maybe_serialize ( $session_array );
	}

	/* Read the session array back from the $_SESSION variable */
	public static function read_session_array () {
		$session_array = array();

		// Create a unique-ish $session_var_name
		// (avoid session variable clash when different OPT tools are run in same browser)
		$session_var_name = 'opt_tools_' . get_current_blog_id();

		if ( isset($_SESSION[$session_var_name]) ) {
			$session_array = maybe_unserialize ( $_SESSION[$session_var_name] );
		}

		// Now sanity-check the result.
		if ( is_array($session_array) ) {
			// At least the 'chrono_array' should be present.
			if ( !is_array($session_array['chrono_array']) ) {
				unset ( $session_array );
				$session_array = array();
			}
		} else {
			unset ( $session_array );
			$session_array = array();
		}

		return $session_array;
	}

	/* Collect up information about all the Online Placement Tool screens in this OPT */
	public function collect_op_tool_posts () {
		$op_tool_posts_chronological = array();
		$opt_posts_with_children = array();
		$is_top_level = false;
		$is_section   = false;

		$args = array(
			'post_type'      => 'op_tool_type',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC'
		);

		$op_tool_posts_full_info = get_posts ( $args );

		if ( !empty ($op_tool_posts_full_info) ) {

			// First find out which posts have children posts
			foreach ( $op_tool_posts_full_info as $curr_op_tool_post ) {
				$parent_of_curr_post = $curr_op_tool_post -> post_parent;

				if ( $parent_of_curr_post != 0 ) {
					$opt_posts_with_children[$parent_of_curr_post] = true;
				}
			}

			// Now collect the rest of the info we need about the posts
			foreach ( $op_tool_posts_full_info as $curr_op_tool_post ) {
				$curr_post_id = $curr_op_tool_post -> ID;

				// If the post has no parents, then it is a 'top_level' post
				$is_top_level = $curr_op_tool_post -> post_parent == 0;

				// If the post has no parents but has children, then it's a 'section' post
				$is_section = false;
				if ( $is_top_level ) {
					if ( array_key_exists($curr_post_id, $opt_posts_with_children) ) 
						$is_section = true;
				}

				array_push ( $op_tool_posts_chronological, array (
					'post_id'   => $curr_post_id,
					'top_level' => $is_top_level,
					'section'   => $is_section,
					'viewed'    => false
					) );
			}
		}

		return $op_tool_posts_chronological;
	}

	/***************************************************************
	*** Handlers to process the chronological array of OPT posts ***
	****************************************************************/

	// Given a $opt_post_id and a chronological array of posts, work out which position (index)
	//    in the array contains this $opt_post_id
	public static function get_chrono_array_pos ( $chrono_array, $opt_post_id ) {
		foreach ( $chrono_array as $curr_post_pos => $curr_post ) {
			if ( $curr_post['post_id'] === $opt_post_id ) return $curr_post_pos;
		}

		// If we get this far, then we didn't find our value.
		return -1;
	}

	// Find what position this post is in, within its section.
	// Return an array of:
	//   $num_prev - Number of posts before, and including, this one in the current section.
	//   $num_next - Number of posts left in the section, after this one.
	public static function get_pos_of_post_in_section ( $chrono_array, $curr_post_pos ) {
		$num_prev = 0;
		$num_next = 0;

		// If the $curr_post_pos is a top_level post, then don't return progress.
		//    (all 'section' posts are also 'top_level' posts)
		if ( $chrono_array[$curr_post_pos]['top_level'] == false ) {

			for ( $curr_index = $curr_post_pos; $curr_index >= 0; $curr_index-- ) {
				if ( $chrono_array[$curr_index]['section'] ) break;
				$num_prev++;
			}

			for ( $curr_index = $curr_post_pos+1; $curr_index < count($chrono_array); $curr_index++ ) {
				if ( array_key_exists($curr_index, $chrono_array) == false ) break;
				if ( $chrono_array[$curr_index]['section'] ) break;
				$num_next++;
			}
		}

		return array ( $num_prev, $num_next );
	}

	// Get the position in the chrono array of the next post following the given $curr_post_pos
	// If the $curr_post_pos is the last position in the array, then return -2
	/*public function rdsvs_opt_get_next_post ( $chrono_array, $curr_post_pos ) {
		$new_post_pos = $curr_post_pos + 1;

		if ( array_key_exists($new_post_pos, $chrono_array) == false ) {
			$new_post_pos = -2;
		}

		return $new_post_pos;
	}*/

	// Get the position in the chrono array of the previous post following the given $curr_post_pos
	// If the $curr_post_pos is the first position in the array, then return -1
	/*public function rdsvs_opt_get_prev_post ( $chrono_array, $curr_post_pos ) {
		$new_post_pos = $curr_post_pos - 1;

		if ( array_key_exists($new_post_pos, $chrono_array) == false ) {
			$new_post_pos = -1;
		}

		return $new_post_pos;
	}*/

	// Return the postion in the $chrono_array of the start of the section that contains the $curr_post_pos
	public static function get_section_pos_of_post ( $chrono_array, $curr_post_pos ) {
		$section_pos = -1;

		if ( $chrono_array[$curr_post_pos]['section'] ) {
			$section_pos = $curr_post_pos;
		} else {
			for ( $curr_index = $curr_post_pos; $curr_index >= 0; $curr_index-- ) {
				if ( $chrono_array[$curr_index]['section'] ) {
					$section_pos = $curr_index;
					break;
				}
			}
		}

		return $section_pos;
	}

	// Work out which sections have been completely viewed
	public function update_section_viewed_status ( $chrono_array ) {
		$all_posts_viewed = false;
		$curr_section_post_pos = -1;
		$num_posts = count($chrono_array);

		// The final OPT post will be the Certificate screen, assume this has been viewed
		//    (currently the only way the user could have marked the penultimate post as 'viewed'
		//     would be if they had clicked 'Next' and therefore shown the final post)
		$chrono_array[$num_posts - 1]['viewed'] = true;

		for ( $curr_post_pos = 0; $curr_post_pos < $num_posts; $curr_post_pos++ ) {
			if ( $chrono_array[$curr_post_pos]['section'] ) {
				// If this post is the start of a section ...
				if ( $curr_section_post_pos > -1 ) {
					// If we have already been tracking pages in a section,
					//   we now know whether all the posts it contains have been viewed ...
					$chrono_array[$curr_section_post_pos]['section_viewed'] = $all_posts_viewed;
				}

				// Now prepare to start collecting info about the next section
				$curr_section_post_pos = $curr_post_pos;
				$all_posts_viewed      = $chrono_array[$curr_post_pos]['viewed'];
			} elseif ( !$chrono_array[$curr_post_pos]['top_level'] ) {
				// We won't count non-section top-level posts
				$all_posts_viewed = $all_posts_viewed && $chrono_array[$curr_post_pos]['viewed'];
			}
		}

		// Make sure we update the final section in $chrono_array
		if ( $curr_section_post_pos > -1 ) {
			// If we have already been tracking pages in a section,
			//   we now know whether all the posts it contains have been viewed ...
			$chrono_array[$curr_section_post_pos]['section_viewed'] = $all_posts_viewed;
		}

		return $chrono_array;
	}

	// Get the total number of sections, and the number of sections viewed
	// Return array ($num_sections_viewed, $total_num_sections)
	public function get_number_sections_viewed ( $chrono_array ) {
		$total_num_sections  = 0;
		$num_sections_viewed = 0;

		$chrono_array = self::update_section_viewed_status ( $chrono_array );

		foreach ( $chrono_array as $curr_post ) {
			if ( $curr_post['section'] ) {
				$total_num_sections++;

				if ( $curr_post['section_viewed'] )
					$num_sections_viewed++;
			}
		}

		return array( $total_num_sections, $num_sections_viewed );
	}

	/********************
	*** AJAX Handlers ***
	*********************/

	public function op_tool_start () {
		$username    = trim($_POST['username']);
		$useremail   = trim($_POST['useremail']);
		$notifyemail = trim($_POST['notifyemail']);

		$success       = true;
		$message       = "";
		$output        = "";
		
		// Check that the user has entered their (or at least, a) name.
		if ( empty($username) ) {
			// If the uers didn't enter their name, then prompt them.
			$message = __( 'Please enter your full name', 'bonestheme' );
			$success = false;
		}

		if ( $success ) {
			// Check the Mozilla Backpack account email address was entered.
			if ( empty($useremail) ) {
			// If the uers didn't enter an email address, check whether they really want to continue
			//    without being awarded an open badge at the end.
			$message =  __( 'If you do not enter your Mozilla Backpack email username, then this program ' .
							'cannot award you an open badge to prove you completed it.\n\n' .
							'Do you want to continue without getting a certificate of completion?', 
							'bonestheme' );

			// If we return a '$message' and '$success==true', 
			//    then we will ask the user to confirm that they want to continue.
			$success = true;

			} else {
				// Check that the user hasn't entered more than one email address
				//   (sufficient to check that there is only one '@' symbol?)
				if ( $success ) {
					$is_valid_email = self::is_valid_email ( $useremail );

					if ( $is_valid_email < 1 ) {
						$message = __( 'Please enter your Mozilla Backpack account email address', 
											'bonestheme' );
						$success = false;
					}
					if ( $is_valid_email > 1 ) {
						$message = __( 'Please enter only one Mozilla Backpack account email address', 
											'bonestheme' );
						$success = false;
					}
				}

				if ( $success ) {
					// Now check the notificaiton email address
					if ( empty($notifyemail) ) {
						// If the uers didn't enter an email address, check whether they really want to continue
						//    without being awarded an open badge at the end.
						$message =  __( 'If you do not enter an email address for your EMS Administrator ' .
										'then they will not be automatically notified when you complete ' .
										'this program.\n\n' .
										'Do you want to continue without notifying your EMS Administrator?', 
										'bonestheme' );

						// If we return a '$message' and '$success==true', 
						//    then we will ask the user to confirm that they want to continue.
						$success = true;

					} else {

						// If the start form is in order, begin the Online Placement Preparation Tool
						if ( $success ) {
							// If the user did enter their name, 
							//    (and optionally email, and optionally notification email),
							//    then save that data, and proceed.

							// Get hold of the session array
							$session_array = self::read_session_array();

							// Sanity-check our chronological array
							if ( empty($session_array) )
								$message = "Error: Chrono Array Invalid.";

							if ( $message === "" ) {

								// Store session data for this user's go at the OPT
								$session_array['user_details']['name']        = $username;
								$session_array['user_details']['email']       = $useremail;
								$session_array['user_details']['notifyemail'] = $notifyemail;
								$session_array['session']['start_time']       = time();

								self::store_session_array ( $session_array );
							}
						}
					}
				}
			}
		}

		$output = array ( 
							'message' => $message
						);

		if ( $success )
			wp_send_json_success ( $output );
		else
			wp_send_json_error ( $output );
	}

	public function opt_screen_viewed () {
		$curr_opt_post_pos = intval ( $_POST['opt_posts_pos'] );

		$error_msg = "";
		$output    = "";

		// Get the chronological array
		$session_array = self::read_session_array();
		//$chrono_array = get_transient('opt_screens_chro_'.$session);

		// Sanity-check our chronological array
		if ( empty($session_array) )
			$error_msg = "Error: Chrono Array Invalid.";

		if ( $error_msg === "" ) {
			// Mark this OP Tools post as 'viewed'
			$session_array['chrono_array'][$curr_opt_post_pos]['viewed'] = true;

			self::store_session_array ( $session_array );
		}

		$output = array ( 
				'error_msg' => $error_msg
			);

		if ( $error_msg === "" )
			wp_send_json_success ( $output );
		else
			wp_send_json_error ( $output );
	}

	/***************************
	*** Open Badges Handlers ***
	****************************/

	// Check that Mozilla Persona is valid
	// HACK WARNING: UNTESTED
	public static function mozilla_persona_valid ( $email_address, $audience ) {
		$url = 'https://verifier.login.persona.org/verify';
		$assert = filter_input (
			INPUT_POST,
			'assertion',
			FILTER_UNSAFE_RAW,
			FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH
		);

		//Use the $_POST superglobal array for PHP < 5.2 and write your own filter 
		$params = 'assertion=' . urlencode($email_address) . '&audience=' . urlencode($audience);
		$ch = curl_init ();
		$options = array (
			CURLOPT_URL				=> $url,
			CURLOPT_RETURNTRANSFER	=> TRUE,
			CURLOPT_POSTFIELDS		=> 2,
			//CURLOPT_SSL_VERIFYPEER => true,		// This currently blocks connection to 
													// 'https://verifier.login.persona.org/verify'
			CURLOPT_SSL_VERIFYPEER	=> 0,

			CURLOPT_SSL_VERIFYHOST	=> 2,
			CURLOPT_POSTFIELDS		=> $params
		);

		curl_setopt_array ( $ch, $options );
		$result = curl_exec ( $ch );
		curl_close ( $ch );

		return ( json_decode($result) );
	}

	// Award the certificate (open badge)
	public function award_certificate ( &$session_array ) {
		$award_post_ids = array();

		// Make sure we have an email address to send the badge to
		if ( !empty($session_array['user_details']['email']) ) {

			// Get the 'badge evidence' text ready
			$evidence = self::op_tool_get_option ( 'badge_evidence_text' );
			$evidence = self::replace_placeholder_tokens ( $evidence, $session_array );

			// Get the 'id' of the badge we want to issue
			$badge_id = self::op_tool_get_option ( 'completion_badge_id' );

			$award_post_ids = self::wpbadger_award_badges ( 
									$badge_id, 
									$session_array['user_details']['email'],
									$evidence,
									'' );

			// We are only using wpbadger_award_badges to award a single badge,
			//    so we can use $award_post_ids[0] safely ... hopefully.
			$session_array['session']['badge_id'] = $award_post_ids[0];

			// Notify the EMS Admin person (if the user entered an address for them)
			if ( !empty($session_array['user_details']['notifyemail']) ) {
				// Get the 'badge notification' text ready to send to the EMS Admin
				$notifyemail_text = self::op_tool_get_option ( 'badge_notify_admin_text' );
				$notifyemail_text = self::replace_placeholder_tokens ( $notifyemail_text, $session_array );

				$subject = get_bloginfo( 'name' ) . ' completion';

				// Send and HTML mail
				add_filter ( 'wp_mail_content_type', 'set_html_content_type' );
				// Set the 'From' name
				add_filter ( 'wp_mail_from_name', 'opt_wp_mail_from_name' );
				// Set the 'From email address'
				add_filter ( 'wp_mail_from', 'opt_wp_mail_from' );

				// Send a mail to the EMS Admin person
				wp_mail( $session_array['user_details']['notifyemail'], $subject, $notifyemail_text );

				// Reset the wp_mail filters we set
				remove_filter ( 'wp_mail_content_type', 'set_html_content_type' );
				remove_filter ( 'wp_mail_from_name', 'opt_wp_mail_from_name' );
				remove_filter ( 'wp_mail_from', 'opt_wp_mail_from' );
			}
		}

		return $session_array;
	}

	public function replace_placeholder_tokens ( $the_text, $session_array ) {
		// Replace the 'user_name' token
		$the_text = str_replace ( "%%user_name%%", $session_array['user_details']['name'], $the_text );

		// Replace the 'end_date' token
		$end_date = date("j F, Y", $session_array['session']['end_time']);
		$the_text = str_replace ( "%%end_date%%", $end_date, $the_text );

		// Replace the 'end_time' token
		$end_date = date("g.i a", $session_array['session']['end_time']);
		$the_text = str_replace ( "%%end_time%%", $end_date, $the_text );

		// Replace the 'minutes_taken' token
		$the_text = str_replace ( "%%minutes_taken%%", $session_array['session']['minutes_taken'], $the_text );

		$op_tool_title = get_bloginfo( 'name' );
		$op_tool_url   = home_url( '/' );

		$op_tool_title_url = '<a href="'.$op_tool_url.'">'.$op_tool_title.'</a>';

		// Replace the 'op_tool_title' token
		$the_text = str_replace ( "%%op_tool_title%%", $op_tool_title, $the_text );

		// Replace the 'op_tool_title_and_weblink' token
		$the_text = str_replace ( "%%op_tool_title_and_weblink%%", $op_tool_title_url, $the_text );

		// Replace the 'badge_evidence_weblink' token
		$evidence_url = get_permalink( $session_array['session']['badge_id'] );
		$the_text = str_replace ( "%%badge_evidence_weblink%%", $evidence_url, $the_text );

		// Replace the 'badge_evidence_label_and_weblink' token
		$evidence_name_url = '<a href="'.$evidence_url.'">The evidence</a>';
		$the_text = str_replace ( "%%badge_evidence_label_and_weblink%%", $evidence_name_url, $the_text );

		// Replace the 'op_tool_content_version' token
		$content_version = opt_get_content_version ();
		$the_text = str_replace ( "%%op_tool_content_version%%", $content_version, $the_text );

		// Replace the 'op_tool_engine_version' token
		$engine_version = opt_get_engine_version ();
		$the_text = str_replace ( "%%op_tool_engine_version%%", $engine_version, $the_text );

		// Replace the 'op_tool_badge_title' token
		$badge_id = self::op_tool_get_option ( 'completion_badge_id' );
		$badge_title = get_the_title( $badge_id );
		$the_text = str_replace ( "%%op_tool_badge_title%%", $badge_title, $the_text );

		// Replace the 'op_tool_badge_img' token
		$badge_img = get_the_post_thumbnail( $badge_id );
		$the_text = str_replace ( "%%op_tool_badge_img%%", $badge_img, $the_text );

		// Replace the 'op_tool_home_url' token
		$the_text = str_replace ( "%%op_tool_home_url%%", esc_url( home_url('/') ), $the_text );
		return $the_text;
	}

	// Code written by davelester, in his plugin WPBadger:
	//  https://wordpress.org/plugins/wpbadger/
	public function wpbadger_award_badges ( $badge, $emails, $evidence, $expires ) {
		global $wpdb;
		$award_post_ids = array();

		if ( $badge && $emails ) {

			$badge_id        = $badge;
			$email_addresses = $emails;
			$evidence        = $evidence;
			$expires         = $expires;

			$email_addresses = split ( ',', $email_addresses );

			foreach ( $email_addresses as $email ) {
				$email = trim ( $email );

				// Insert a new post for each award
				$post = array(
					'post_content' => $evidence,
					'post_status'  => 'publish',
					'post_type'    => 'award',
					'post_title'   => 'Badge Awarded: ' . get_the_title ( $badge_id ),
					'post_name'    => wpbadger_award_generate_slug()
				);

				$post_id = wp_insert_post( $post, $wp_error );

				update_post_meta ( $post_id, 'wpbadger-award-email-address', $email );
				update_post_meta ( $post_id, 'wpbadger-award-choose-badge', $badge_id );
				update_post_meta ( $post_id, 'wpbadger-award-expires', $expires );
				update_post_meta ( $post_id, 'wpbadger-award-status','Awarded' );
				
				// Send award email
				wpbadger_award_send_email ( $post_id );

				$award_post_ids[] = $post_id;
			}
		}

		return $award_post_ids;
	}

	public static function wpbadger_award_choose_badge () {
		//$chosen_badge_id = self::op_tool_get_option ( 'completion_badge_id' );

		$badge_list_array = array();

		$query = new WP_Query( array( 'post_type' => 'badge' ) );
		
		while ( $query->have_posts() ) : $query->the_post();
			$badge_title_version = the_title(null, null, false) . " (" . 
										get_post_meta(get_the_ID(), 'wpbadger-badge-version', true) . ")";

			$badge_list_array[get_the_ID()] = $badge_title_version;
		endwhile;

		return $badge_list_array;
	}

}

/* Finally, initialise the class */
EMS_OP_Tool::init();


/*********************
*** Misc functions ***
**********************/

// Get the 'Path' name of a specific site (as set in the Newtork Sites panel - but without the slashes)
// EC Hack: Note, this will only work in a Network of Sites WordPress install
function opt_get_site_path () {
	$site_path = "";

	// get_home_url() gives us e.g. 'http://www.ems.vet.ed.ac.uk/test'
	$full_url  = parse_url ( get_home_url() );

	// ['path'] give us '/path'
	// Strip all slashes out of path
	$site_path = str_replace ( "/", "", $full_url['path'] );

	return ( $site_path );
}

function opt_get_content_version () {
	$opt_content_version = EMS_OP_Tool::op_tool_get_option ( 'content_version' );

	return ( $opt_content_version );
}

function opt_get_engine_version () {
	$opt_engine_version = 0;
	$theme_data = wp_get_theme();

	if ( $theme_data->exists() )
		$opt_engine_version = $theme_data->get ( 'Version' );

	return ( $opt_engine_version );
}

/****************************
*** wp_mail Email Filters ***
*****************************/

function set_html_content_type ( $content_type ) {
	return 'text/html';
}

function opt_wp_mail_from ( $original_email_address ) {
	$new_email = $original_email_address;

	// Strip the username off the original_email_address
	$separator_pos = strpos ( $original_email_address, '@' );

	if ( $separator_pos !== false )
		$new_email = opt_get_site_path () . substr ( $original_email_address, $separator_pos );

	return ( $new_email );
}

function opt_wp_mail_from_name ( $original_email_from ) {
	$new_email_from = get_bloginfo( 'name' );

	if ( empty ($new_email_from) )
		$new_email_from = $original_email_from;

	return ( $new_email_from );
}