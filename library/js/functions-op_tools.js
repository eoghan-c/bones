/* All the functions to support the Online Placement Tools system */

// Tell JSHint that MyAjax is defined, honest - so CodeKit doesn't repeatedly throw warning.
/* global MyAjax:true */

jQuery(document).ready(function($) {
	
	/******************************************************
	** Handle actions in the Online Placement Tool frame **
	*******************************************************/

	// Hiding elements on 'page' holding 'rdsvs_op_tool_user_details' shortcode
	if ( ($("#rdsvs_opt_user_details_form").length !== 0) ||
			($("#rdsvs_opt_table_of_contents").length !== 0) ) {
		// If the 'Start' screen of the OPT is being displayed (embedded on page),
		//    then don't show the main nav bar.
		$("#rdsvs_opt_main_navigation").hide();
	}

	// Code to run if we're displaying an OPT post
	//var opt_post_pos = -1;
	var timeout_id = 0;
	var op_tool_content = $("#rdsvs_online_placement_tool_content");
	if ( op_tool_content.length !== 0 ) {
		// Get the 'delay' value for this post
		var opt_post_delay;

		// data("opt_post_delay") will hopefully always exist, but worth checking.
		if ( $.type(op_tool_content.data("opt_post_delay")) === "undefined" ) {
			// Should maybe draw the '5000' from somewhere - but hopefully this will never get called.
			opt_post_delay = 5000;
		} else {
			opt_post_delay = op_tool_content.data("opt_post_delay");
		}

		// Start the timer
		if ( opt_post_delay > 0 ) {
			timeout_id = window.setTimeout(function() { rdsvs_opt_timer_complete(); }, opt_post_delay);
		}
	}

	/******************************************************
	** Handle actions in the Online Placement Tool frame **
	*******************************************************/

	// If the user clicks the 'Start' button, begin the OPT
	$(".rdsvs_opt_start").click( function (event) {
		var start_button = $(this);

		// Get the rdsvs_online_placement_tool_frame div class that the start_button is within 
		//var op_tool_frame = $("#rdsvs_online_placement_tool_frame");

		var op_tool_username    = $("#rdsvs_opt_username").val();
		var op_tool_useremail   = $("#rdsvs_opt_useremail").val();
		var op_tool_notifyemail = $("#rdsvs_opt_notifyemail").val();

		// Get the link from the href attribute, so we can go to it.
		var opt_next_url = start_button.attr("href");

		$.post(MyAjax.ajaxurl, {
					action:"op_tool_start",
					username:op_tool_username,
					useremail:op_tool_useremail,
					notifyemail:op_tool_notifyemail
				}, function (response) {

					if (response.success) {
						// Success
						// If there is any text in 'response.data.message',
						// then we have a question to ask the user.
						if ( $.trim(response.data.message) ) {
							// If we have a message then ask the user to Ok or Cancel.
							var reply = window.confirm(response.data.message.replace(/\\n/g, "\n"));
							/* EC Hack: Try using jQuery Impromptu plugin?
							var reply = $.prompt ( response.data.message.replace(/\\n/g, "\n"),
												   { buttons: {Ok: true, Cancel: false}, focus: 1 } );*/
							if ( reply === true ) {
								// If the user clicked Ok, then continue with the program.
								window.location.href = opt_next_url;
							}
							// If the user clicked Cancel, then stay on page.

						} else {
							// Move the user to the next screen of the OPT
							// The PHP should have already dealt with the case that 
							//    there is no second OPT screen, so just load the URL.
							window.location.href = opt_next_url;
						}
					} else {
						// Error
						window.alert(response.data.message);
					}
				}
			);

		// We need to override the default href link behaviour,
		//    because Ajax will execute asynchronously, and could follow the href link
		//    before Ajax returns.
		event.preventDefault();
	});

	// If the user clicks the 'Next' button inside the OPT screens,
	//    check whether they have 'viewed' the screen, and make a note of it.
	$("#rdsvs_opt_navigation").find(".rdsvs-opt-next").click( function (event) {
		var next_button = $(this);
		
		// Get the rdsvs_online_placement_tool_frame div class that the start_button is within 
		var op_tool_content = $("#rdsvs_online_placement_tool_content");

		// Get the position of this OPT post in the chrono array
		var opt_posts_pos = op_tool_content.data("opt_post_pos");

		// Get the link from the href attribute, so we can go to it.
		var opt_next_url = next_button.attr("href");

		// EC Currently we only need to execute code if the timeout timer has fired
		//    (meaning we will mark this OPT post has having been read)
		if ( next_button.hasClass("rdsvs-opt-nav-button-skip") ) {
			// User skipped to the next OPT post page
			window.location.href = opt_next_url;
		} else {
			// User waited on post, so mark it as 'viewed'
			$.post(MyAjax.ajaxurl, {
						action:"opt_screen_viewed",
						opt_posts_pos:opt_posts_pos
					}, function (response) {
						if (response.success) {
							// Success
							// Go to the next OPT post page
							window.location.href = opt_next_url;
						} else {
							// Error message
							window.alert(response.data.error_msg);
						}
					}
				);
		}

		// We need to override the default href link behaviour,
		//    because Ajax will execute asynchronously, and could follow the href link
		//    before Ajax returns.
		event.preventDefault();
	});

	/******************************************************************
	 * Handle timer events in the Online Placement Tool frame
	 ******************************************************************/

	function rdsvs_opt_timer_complete() {
		var next_button = $(".rdsvs-opt-next");

		next_button.removeClass("rdsvs-opt-nav-button-skip");

		/* Hide the 'Skip' label, and show the 'Next' label */
		next_button.find(".rdsvs-button-skip-label").hide();
		next_button.find(".rdsvs-button-next-label").show();
	}

});