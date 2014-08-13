<?php

if ( ! defined( 'ABSPATH' ) ) exit('You\'re not allowed to see this page'); // Exit if accessed directly


/***** Adding javascript and css *****/

	add_action( 'wp_enqueue_scripts', 'yasr_add_scripts' );  
	add_action( 'admin_enqueue_scripts', 'yasr_add_admin_scripts' );

	function yasr_add_scripts () {

		wp_enqueue_style( 'yasrcss', YASR_CSS_DIR . 'yasr.css', FALSE, NULL, 'all' );


        //If choosen is light or not dark (force to be default)
        if (YASR_SCHEME_COLOR === 'light' || YASR_SCHEME_COLOR != 'dark' ) {
            wp_enqueue_style( 'yasrcsslightscheme', YASR_CSS_DIR . 'yasr-table-light.css', array('yasrcss'), NULL, 'all' );
        }

        elseif (YASR_SCHEME_COLOR === 'dark') {
            wp_enqueue_style( 'yasrcssdarkscheme', YASR_CSS_DIR . 'yasr-table-dark.css', array('yasrcss'), NULL, 'all' );
        }

        if (YASR_CUSTOM_CSS_RULES) {
            wp_add_inline_style( 'yasrcss', YASR_CUSTOM_CSS_RULES );
        }

		wp_enqueue_script( 'rateit', YASR_JS_DIR . 'jquery.rateit.min.js' , array('jquery'), '1.0.20', TRUE );
		wp_enqueue_script( 'cookie', YASR_JS_DIR . 'jquery-cookie.min.js' , array('jquery', 'rateit'), '1.4.0', TRUE );
	}

    function yasr_add_admin_scripts () {

        wp_enqueue_style( 'yasrcss', YASR_CSS_DIR . 'yasr.css', FALSE, NULL, 'all' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );

        wp_enqueue_script( 'rateit', YASR_JS_DIR . 'jquery.rateit.min.js' , array('jquery'), '1.0.20', TRUE );
        wp_enqueue_script( 'jquery-ui-dialog' );

    }



/****** Translating YASR ******/
	
	add_action('plugins_loaded', 'yasr_translate_option');

	function yasr_translate_option() {
		load_plugin_textdomain('yasr', FALSE, YASR_LANG_DIR); 
	}


/****** Create a new Page in Administration Menu ******/

	/* Hook to admin_menu the yasr_add_pages function above */
	add_action( 'admin_menu', 'yasr_add_pages' );

	function yasr_add_pages() {

    //Add Settings Page
    add_options_page(
        __( 'Yet Another Stars Rating: Settings', 'yasr' ), //Page Title
        __( 'Yet Another Stars Rating: Settings', 'yasr' ), //Menu Title
        'manage_options', //capability
        'yasr_settings_page', //menu slug
        'yasr_settings_page_callback' //The function to be called to output the content for this page.
    	);
	}


	// Settings Page Content 
	function yasr_settings_page_callback () {
    	if ( ! current_user_can( 'manage_options' ) ) {
        	wp_die( __( 'You do not have sufficient permissions to access this page.', 'yasr' ) );
    	}

	include(YASR_ABSOLUTE_PATH  . '/yasr-settings-page.php');

	} //End yasr_settings_page_content



/****** Create 2 metaboxes in post and pages ******/

	add_action( 'add_meta_boxes', 'yasr_add_metaboxes' );

	function yasr_add_metaboxes() {

        //Default post type where display metabox
        $post_type_where_display_metabox = array('post', 'page');

        //get the custom post type
        $custom_post_types = yasr_get_custom_post_type();

        if ($custom_post_types) {

            //First merge array then changes keys to int
            $post_type_where_display_metabox = array_values(array_merge($post_type_where_display_metabox, $custom_post_types));     

        }

		$multi_set=yasr_get_multi_set(); 
		//If multiset are used then add 2 metabox, 1 for overall rating and 1 for multiple rating 
		if ($multi_set) {
			foreach ($post_type_where_display_metabox as $post_type) {
				add_meta_box( 'yasr_metabox_overall_rating', __( 'Overall Rating', 'yasr' ), 'yasr_metabox_overall_rating_content', $post_type, 'side', 'high' );
				add_meta_box( 'yasr_metabox_multiple_rating', __( 'Yet Another Stars Rating: Multiple set', 'yasr' ), 'yasr_metabox_multiple_rating_content', $post_type, 'normal', 'high' );
			}
		}
		//else create just the overall rating one
		else {
			foreach ($post_type_where_display_metabox as $post_type) {
				add_meta_box( 'yasr_metabox_overall_rating', __( 'Overall Rating', 'yasr' ), 'yasr_metabox_overall_rating_content', $post_type, 'side', 'high' );
			}
		}
	}

	function yasr_metabox_overall_rating_content() {
		if ( current_user_can( 'publish_posts' ) )  {
			include (YASR_ABSOLUTE_PATH . '/yasr-metabox-overall-rating.php');
		}
		else {
            _e("You don't have enought privileges to insert overall rating");
        }

	}

	function yasr_metabox_multiple_rating_content() {
		if ( current_user_can( 'publish_posts' ) )  {
			include (YASR_ABSOLUTE_PATH . '/yasr-metabox-multiple-rating.php');
		}
        else {
            _e("You don't have enought privileges to insert multi-set");
        }
		
	}



/****** Return html code that will output overall rating stars. Used in auto insert overall rating ******/

function overall_rating_auto_insert_code () {

    $overall_rating=yasr_get_overall_rating();

    if (!$overall_rating) {
        $overall_rating = "-1";
    }

    $shortcode_html = '';

    if( YASR_TEXT_BEFORE_STARS == 1 && YASR_TEXT_BEFORE_STARS != '' ) {

        $shortcode_html = "<div class=\"yasr-container-custom-text-and-overall\">
                               <span id=\"yasr-custom-text-before-overall\">" . YASR_TEXT_BEFORE_OVERALL . "</span>";

    }

    switch (YASR_AUTO_INSERT_SIZE) {
        case 'small':
                    $shortcode_html .= "<div class=\"rateit\" id=\"yasr_rateit_overall\" data-rateit-value=\"$overall_rating\" data-rateit-step=\"0.1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>";
                    break;

        case 'medium':
                    $shortcode_html .= "<div class=\"rateit medium\" id=\"yasr_rateit_overall\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"$overall_rating\" data-rateit-step=\"0.1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>";
                    break;

        case 'large':
                    $shortcode_html .= "<div class=\"rateit bigstars\" id=\"yasr_rateit_overall\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"$overall_rating\" data-rateit-step=\"0.1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>"; 
                    break;
    }

    if( YASR_TEXT_BEFORE_STARS == 1 && YASR_TEXT_BEFORE_STARS != '' ) {

        $shortcode_html .= "</div>";

    }


    //IF show overall rating in loop is disabled use is_singular && is_main query
    if ( YASR_SHOW_OVERALL_IN_LOOP === 'disabled' ) {

        //If pages are not excluted
        if ( YASR_AUTO_INSERT_EXCLUDE_PAGES === 'no' ) {

            if( is_singular() && is_main_query() ) {

                return $shortcode_html;

            }

        }

        //If page are excluted
        else {

            if( is_singular() && is_main_query() && !is_page() )

                return $shortcode_html;

        }

    } // End if YASR_SHOW_OVERALL_IN_LOOP === 'disabled') {

    //If overall rating in loop is enabled don't use is_singular && is main_query
    elseif ( YASR_SHOW_OVERALL_IN_LOOP === 'enabled' ) {

        //If pages are not excluted return always
        if ( YASR_AUTO_INSERT_EXCLUDE_PAGES === 'no' ) {

            return $shortcode_html;

        }

        //Else if page are excluted return only if is not a page
        else {

            if ( !is_page() ) {

                return $shortcode_html;

            }

        }

    }

} //End function



/****** Return html code that will output visitor rating stars. It's almost the same 
than shortcode_visitor_votes_callback used in yasr-shortcode function, but work only when 
is called and have initial different conditions ******/

function visitor_votes_auto_insert_code () {

        $shortcode_html = ''; //Avoid undefined variable outside is_singular && is_main_query

        if( is_singular() && is_main_query() ) {

            $ajax_nonce_visitor = wp_create_nonce( "yasr_nonce_insert_visitor_rating" );

        	$votes=yasr_get_visitor_votes();

            $medium_rating=0;   //Avoid undefined variable

        	if (!$votes) {
        		$votes=0;         //Avoid undefined variable if there is not overall rating
        		$votes_number=0;  //Avoid undefined variable
        	}

            else {
        		foreach ($votes as $user_votes) {
        			$votes_number = $user_votes->number_of_votes;
                    if ($votes_number !=0 ) {
        			    $medium_rating = ($user_votes->sum_votes/$votes_number);
                    }
                }
            }


            $image = YASR_IMG_DIR . "/loader.gif";

            $loader_html = "<div id=\"loader-visitor-rating\" >&nbsp; " . __("Loading, please wait","yasr") . " <img src= \" $image \"></div>";

            $medium_rating=round($medium_rating, 1);

            if (YASR_AUTO_INSERT_SIZE == 'small') {

                //if anonymous are allowed to vote
                if (YASR_ALLOWED_USER === 'allow_anonymous') {

                    //I've to block a logged in user that has already rated
                    if ( is_user_logged_in() ) {

                        //Chek if a logged in user has already rated for this post
                        $vote_if_user_already_rated = yasr_check_if_user_already_voted();

                        //If user has already rated show readonly stars
                        if ($vote_if_user_already_rated) {

                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr-rateit-visitor-votes-logged-rated\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span> 
                            <strong>" . __("You've already voted this article with", "yasr") . " $vote_if_user_already_rated </strong></div>";

                        }

                        //else logged user can vote 
                        else {

                            $vote_if_user_already_rated = 0;

                            if ($votes_number>0) {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                            }

                            else {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                            }

                        } //End else

                    } //End if user is logged


                    //else if is not logged can vote
                    else {

                        if ($votes_number>0) {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                        }

                        else {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                        }

                    } //end else
              
                } //end if  ($allow_logged_option['allowed_user']==='allow_anonymous') {


                //If only logged in users can vote
                elseif (YASR_ALLOWED_USER === 'logged_only') {

                    //If user is logged in and can vote
                    if ( is_user_logged_in() ) {

                        //Chek if a logged in user has already rated for this post
                        $vote_if_user_already_rated = yasr_check_if_user_already_voted();

                        if ($vote_if_user_already_rated) {

                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr-rateit-visitor-votes-logged-rated\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            <strong>" . __("You've already voted this article with", "yasr") . " $vote_if_user_already_rated </strong></div>";

                        }

                        else {

                            if ($votes_number>0) {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                                </div>";
                            }

                            else {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                                </div>";
                            }

                        }

                    } //End if user is logged in

                    //Else mean user is not logged in
                    else {


                        if ($votes_number>0) {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>
                            <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            " . __("You must sign to vote", "yasr") . "</div>";
                        }

                        else {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"16\" data-rateit-starheight=\"16\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>
                            <span class=\"yasr-total-average-text-small\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            </div>";
                        }

                    }
      
                }

            } //End if $size == 'small'

            elseif (YASR_AUTO_INSERT_SIZE == 'medium') {

                //if anonymous are allowed to vote
                if (YASR_ALLOWED_USER === 'allow_anonymous') {

                    //I've to block a logged in user that has already rated
                    if ( is_user_logged_in() ) {

                        //Chek if a logged in user has already rated for this post
                        $vote_if_user_already_rated = yasr_check_if_user_already_voted();

                        //If user has already rated show readonly stars
                        if ($vote_if_user_already_rated) {

                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr-rateit-visitor-votes-logged-rated\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span> 
                            <strong>" . __("You've already voted this article with", "yasr") . " $vote_if_user_already_rated </strong></div>";

                        }

                        //else logged user can vote 
                        else {

                            $vote_if_user_already_rated = 0;

                            if ($votes_number>0) {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                            }

                            else {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                            }

                        } //End else

                    } //End if user is logged


                    //else if is not logged can vote
                    else {

                        if ($votes_number>0) {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                        }

                        else {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                        }

                    } //end else
              
                } //end if  ($allow_logged_option['allowed_user']==='allow_anonymous') {



                //If only logged in users can vote
                elseif (YASR_ALLOWED_USER === 'logged_only') {

                    //If user is logged in and can vote
                    if ( is_user_logged_in() ) {

                        //Chek if a logged in user has already rated for this post
                        $vote_if_user_already_rated = yasr_check_if_user_already_voted();

                        if ($vote_if_user_already_rated) {

                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr-rateit-visitor-votes-logged-rated\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            <strong>" . __("You've already voted this article with", "yasr") . " $vote_if_user_already_rated </strong></div>";

                        }

                        else {

                            if ($votes_number>0) {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                                </div>";
                            }

                            else {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                                </div>";
                            }

                        }

                    } //End if user is logged in

                  //Else mean user is not logged in
                    else {


                        if ($votes_number>0) {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>
                            <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            " . __("You must sign to vote", "yasr") . "</div>";
                        }

                        else {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit medium\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"24\" data-rateit-starheight=\"24\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>
                            <span class=\"yasr-total-average-text-medium\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            </div>";
                        }

                    }
      
                }

            } //End if $size == 'medium'

            elseif (YASR_AUTO_INSERT_SIZE == 'large') {

                //if anonymous are allowed to vote
                if (YASR_ALLOWED_USER === 'allow_anonymous') {

                    //I've to block a logged in user that has already rated
                    if ( is_user_logged_in() ) {

                        //Chek if a logged in user has already rated for this post
                        $vote_if_user_already_rated = yasr_check_if_user_already_voted();

                        //If user has already rated show readonly stars
                        if ($vote_if_user_already_rated) {

                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr-rateit-visitor-votes-logged-rated\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span> 
                            <strong>" . __("You've already voted this article with", "yasr") . " $vote_if_user_already_rated </strong></div>";

                        }

                        //else logged user can vote 
                        else {

                            $vote_if_user_already_rated = 0;

                            if ($votes_number>0) {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                            }

                            else {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                            }

                        } //End else

                    } //End if user is logged


                    //else if is not logged can vote
                    else {

                        if ($votes_number>0) {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                        }

                        else {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span></div>";
                        }

                    } //end else
              
                } //end if  ($allow_logged_option['allowed_user']==='allow_anonymous') {



                //If only logged in users can vote
                elseif (YASR_ALLOWED_USER === 'logged_only') {

                    //If user is logged in and can vote
                    if ( is_user_logged_in() ) {

                        //Chek if a logged in user has already rated for this post
                        $vote_if_user_already_rated = yasr_check_if_user_already_voted();

                        if ($vote_if_user_already_rated) {

                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr-rateit-visitor-votes-logged-rated\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            <strong>" . __("You've already voted this article with", "yasr") . " $vote_if_user_already_rated </strong></div>";

                        }

                        else {

                            if ($votes_number>0) {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                                </div>";
                            }

                            else {
                                $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"false\"></div>
                                <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                                </div>";
                            }

                        }

                    } //End if user is logged in

                  //Else mean user is not logged in
                    else {


                        if ($votes_number>0) {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"$medium_rating\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            " . __("You must sign to vote", "yasr") . "</div>";
                        }

                        else {
                            $shortcode_html="<div id=\"yasr_visitor_votes\"><div class=\"rateit bigstars\" id=\"yasr_rateit_visitor_votes\" data-rateit-starwidth=\"32\" data-rateit-starheight=\"32\" data-rateit-value=\"0\" data-rateit-step=\"1\" data-rateit-resetable=\"false\" data-rateit-readonly=\"true\"></div>
                            <span class=\"yasr-total-average-text\"> [" . __("Total: ", "yasr") . "$votes_number &nbsp; &nbsp;" .  __("Average $medium_rating/5" , "yasr") . "]</span>
                            </div>";
                        }

                    }
      
                }

            } //End if $size == 'large'


            if(YASR_TEXT_BEFORE_STARS == 1 && YASR_TEXT_BEFORE_VISITOR_RATING != '') {
            
                $shortcode_html_tmp = "<div class=\"yasr-container-custom-text-and-visitor-rating\">
                    <span id=\"yasr-custom-text-before-visitor-rating\">" . YASR_TEXT_BEFORE_VISITOR_RATING . "</span>" .  $shortcode_html . "</div>"; 

                    $shortcode_html = $shortcode_html_tmp;

            }


            ?>

            <script>
            jQuery(document).ready(function() {

                var tooltipvalues = ['bad', 'poor', 'ok', 'good', 'super'];
                jQuery("#yasr_rateit_visitor_votes").bind('over', function (event, value) { jQuery(this).attr('title', tooltipvalues[value-1]); });

                var postid = <?php the_ID(); ?>;
                var cookiename = "yasr_visitor_vote_" + postid;
                var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

                var size = "<?php echo YASR_AUTO_INSERT_SIZE ?>";

                //json encode convert php type to javascript type
                var logged_user = <?php echo json_encode(is_user_logged_in()); ?>

                //On click Insert visitor votes
                jQuery('#yasr_rateit_visitor_votes').on('rated', function() { 
                    var el = jQuery(this);
                    var value = el.rateit('value');
                    var value = value.toFixed(1); //

                    jQuery('#yasr_visitor_votes').html( ' <?php echo "$loader_html" ?> ');

                    var data = {
                        action: 'yasr_send_visitor_rating',
                        rating: value,
                        post_id: postid,
                        size: size,
                        nonce_visitor: "<?php echo "$ajax_nonce_visitor"; ?>"
                    };

                    //Send value to the Server
                    jQuery.post(ajaxurl, data, function(response) {
                        //response
                        jQuery('#yasr_visitor_votes').html(response); 
                        jQuery('.rateit').rateit();
                        //Create a cookie to disable double vote
                        jQuery.cookie(cookiename, value, { expires : 360 }); 
                    }) ;          
                });
                //} //End if (!jQuery.cookie(cookiename))


                //If user is not logged in
                if (! logged_user) {

                    //Check if has cookie
                    if (jQuery.cookie(cookiename)) {                

                        var cookievote=jQuery.cookie(cookiename);
                        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

                        var data = {
                            action: 'yasr_readonly_visitor_shortcode',
                            size: size,
                            rating: cookievote,
                            votes: <?php echo $medium_rating ?>,
                            votes_number: <?php echo $votes_number ?>,
                            post_id: postid
                        }

                        jQuery.post(ajaxurl, data, function(response) {
                            jQuery('#yasr_visitor_votes').html(response);
                            jQuery('.rateit').rateit();
                        });

                    } //End if jquery cookie

                }

                //If a logged in user has already voted, he/she can update the vote
                else {

                    jQuery('#yasr-rateit-visitor-votes-logged-rated').on('rated', function() {

                        var el = jQuery(this);
                        var value = el.rateit('value');
                        var value = value.toFixed(1); //

                        jQuery('#yasr_visitor_votes').html( ' <?php echo "$loader_html" ?> ');

                        var data = {
                                action: 'yasr_update_visitor_rating',
                                rating: value,
                                post_id: postid,
                                size: size,
                                nonce_visitor: "<?php echo "$ajax_nonce_visitor"; ?>"
                            };

                        //Send value to the Server
                        jQuery.post(ajaxurl, data, function(response) {
                            //response
                            jQuery('#yasr_visitor_votes').html(response); 
                            jQuery('.rateit').rateit();
                            //Create a cookie to disable double vote
                            jQuery.cookie(cookiename, value, { expires : 360 }); 
                        }) ;      

                    });//End function update vote

                }

            });

        </script>

     	    <?php

            if (YASR_AUTO_INSERT_EXCLUDE_PAGES === 'no') {

                return $shortcode_html;

            }

            else {

                if ( !is_page() ) {

                    return $shortcode_html;

                }

            }

        } //End if is singular

} //End function shortcode_visitor_votes_callback



/****** Auto insert overall rating and visitor rating  ******/

	add_filter('the_content', 'yasr_auto_insert_shortcode_callback');

	function yasr_auto_insert_shortcode_callback($content) {

		if (YASR_AUTO_INSERT_ENABLED == 1) {

			$auto_insert_shortcode=NULL; //To avoid undefined variable notice outside the loop (if (is_singular) )

				$overall_rating_code = overall_rating_auto_insert_code();
				$visitor_votes_code = visitor_votes_auto_insert_code();

				if (YASR_AUTO_INSERT_WHAT==='overall_rating') {
					switch (YASR_AUTO_INSERT_WHERE) {
						case 'top':
							return $overall_rating_code . $content;
							break;
					
						case 'bottom':
							return $content . $overall_rating_code;
							break;
					} //End Switch
				} //end YASR_AUTO_INSERT_WHAT overall rating

				elseif (YASR_AUTO_INSERT_WHAT==='visitor_rating') {
					switch (YASR_AUTO_INSERT_WHERE) {
						case 'top':
							return $visitor_votes_code . $content;
							break;
					
						case 'bottom':
							return $content . $visitor_votes_code;
							break;
					} //End Switch
				}

				elseif (YASR_AUTO_INSERT_WHAT==='both') {
					switch (YASR_AUTO_INSERT_WHERE) {
						case 'top':
							return $overall_rating_code . $visitor_votes_code . $content;
							break;
					
						case 'bottom':
							return $content . $overall_rating_code . $visitor_votes_code;
							break;
					} //End Switch
				}

			return $content;

		} //End  if (YASR_AUTO_INSERT_ENABLED

		else {

			return $content;

		}


	} //End function yasr_auto_insert_shortcode_callback


/****** Add review schema data at the end of the post *******/

	add_filter('the_content', 'yasr_add_overall_rating_schema');

	function yasr_add_overall_rating_schema($content) {

		$schema=NULL; //To avoid undefined variable notice outside the loop

		if (YASR_SNIPPET == 'overall_rating') {

			$overall_rating=yasr_get_overall_rating();

			if($overall_rating && $overall_rating != '-1' && $overall_rating != '0.0') {

				if(is_singular() && is_main_query() ) {
					global $post;

					$div = "<div class=\"yasr_schema\" itemprop=\"review\" itemscope itemtype=\"http://schema.org/Review\">";
					$title = "<span itemprop=\"about\">". get_the_title() ."</span>";
					$author = __(' reviewed by ', 'yasr') . "<span itemprop=\"author\">" . get_the_author() . "</span>";
					$date = __(' on ', 'yasr') . "<meta itemprop=\"datePublished\" content=\"" . get_the_date('c') . "\"> " .  get_the_date();
					$rating = __( ' rated ' , 'yasr' ) . "<span itemprop=\"reviewRating\">" . $overall_rating . "</span>" . __(' on 5.0' , 'yasr');
					$end_div= "</div>";

					$schema = $div . $title . $author . $date . $rating . $end_div;
				}

			} //END id if $overall_rating != '-1'

			if( is_singular() && is_main_query() ) {
				return $content . $schema;
			}

			else {
				return $content;
			}

		}  //end if ($choosen_snippet['snippet'] == 'overall_rating')

		if (YASR_SNIPPET == 'visitor_rating') {

			$visitor_votes = yasr_get_visitor_votes ();

            if ($visitor_votes) {

                foreach ($visitor_votes as $rating) {
                    $visitor_rating['votes_number']=$rating->number_of_votes;
                    $visitor_rating['sum']=$rating->sum_votes;
                }

            }

            else {
                $visitor_rating = NULL;
            }

			if ($visitor_rating['sum'] != 0) {

				$average_rating = $visitor_rating['sum'] / $visitor_rating['votes_number'];

				$average_rating=round($average_rating, 1);

				$div_1 = "<div class=\"yasr_schema\" itemscope itemtype=\"http://schema.org/Product\">";
				$title = "<span itemprop=\"name\">". get_the_title() ."</span>";
				$span_1 = "<span itemprop=\"aggregateRating\" itemscope itemtype=\"http://schema.org/AggregateRating\">";
				$rating = __( ' rated ' , 'yasr' ) . "<span itemprop=\"ratingValue\">" . $average_rating . "</span>" . __(' out of ' ,'yasr') . "<span itemprop=\"bestRating\">5</span>";
				$n_ratings = __(' based on ', 'yasr') . "<span itemprop=\"ratingCount\">" . $visitor_rating['votes_number'] . "</span>" . __(' user ratings', 'yasr');
				$end_span_1 = "</span>";
				$end_div_1 = "</div>";

				$schema = $div_1 . $title . $span_1 . $rating . $n_ratings . $end_span_1 . $end_div_1;

			}

			if( is_singular() && is_main_query() ) {
					return $content . $schema;
			}

			else {
					return $content;
			}

		}

	} //End function


/****** Create a new button in Tinymce for use shortag
(Thanks to wordpress.stackexchange) ******/

// init process for registering our button
add_action('admin_init', 'yasr_shortcode_button_init');
    function yasr_shortcode_button_init() {

        //Abort early if the user will never see TinyMCE
        if ( ! current_user_can('publish_posts') && ! current_user_can('publish_posts') && get_user_option('rich_editing') == 'true')
           return;

        //Add a callback to regiser our tinymce plugin   
        add_filter("mce_external_plugins", "yasr_register_tinymce_plugin"); 

        // Add a callback to add our button to the TinyMCE toolbar
        add_filter('mce_buttons', 'yasr_add_tinymce_button');

    }


    //This callback registers our plug-in
    function yasr_register_tinymce_plugin($plugin_array) {
        $plugin_array['yasr_button'] = YASR_JS_DIR . 'addButton_tinymcs.js';
        return $plugin_array;
    }

    //This callback adds our button to the toolbar
    function yasr_add_tinymce_button($buttons) {
                //Add the button ID to the $button array
        $buttons[] = "yasr_button";
        return $buttons;
    }


/****** Return the custom post type if exists ******/

add_action( 'admin_init', 'yasr_get_custom_post_type');
    function yasr_get_custom_post_type() {

        $args = array(
            'public'   => true,
            '_builtin' => false
        );

        $output = 'names'; // names or objects, note names is the default
        $operator = 'and'; // 'and' or 'or'

        $post_types = get_post_types( $args, $output, $operator ); 

        if ($post_types) {
            return ($post_types);
        }

        else {
            return FALSE;
        }

    }
