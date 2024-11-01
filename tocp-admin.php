<?php
require_once( 'tweet-old-cpost.php' );
require_once( 'tocp-core.php' );
require_once( 'Include/tocp-oauth.php' );
require_once( 'xml.php' );
require_once( 'Include/tocp-debug.php' );



function tocp_admin() {
	if ( current_user_can( 'edit_plugins' ) ) {
		$message         = null;
		$message_updated = __( "Tweet Old Custom Post Options Updated.", 'TweetOldCustomPost' );
		$response        = null;
		$save            = true;
		$settings        = tocp_get_settings();
		 // echo '<pre>'. print_r($settings,true) . '</pre>';
		//on authorize
		if ( isset( $_GET['oauth_verifier'] ) ) {
			global $tocp_oauth;
			$result = $tocp_oauth->get_access_token( $settings['oauth_request_token'], $settings['oauth_request_token_secret'], $_GET['oauth_verifier'] );
			/// echo '<div class="update"><pre> resultssss'. print_r($result ,true) . '</pre></div>';
			if ( $result ) {
				$settings['oauth_request_token']        = $result['oauth_token'];
				$settings['oauth_request_token_secret'] = $result['oauth_token_secret'];
				$settings['oauth_verified'] = true;
				$settings['user_id']                   = $result['user_id'];
				$result = $tocp_oauth->get_user_info( $result['user_id'] );
				if ( $result ) {
					$settings['profile_image_url'] = $result['user']['profile_image_url'];
					$settings['screen_name']       = $result['user']['screen_name'];
					if ( isset( $result['user']['location'] ) ) {
						$settings['location'] = $result['user']['location'];
					} else {
						$settings['location'] = false;
					}
				}
				tocp_save_settings( $settings );
				echo '<script language="javascript">window.location.href= "' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=TweetOldCustomPost";</script>';
				die;
			}
		} //on deauthorize
		else if ( isset( $_GET['top'] ) && $_GET['top'] == 'removeapikeys' ) {
			$settings                              = tocp_get_settings();
			$settings['oauth_request_token']        = '';
			$settings['oauth_request_token_secret'] = '';
			$settings['user_id']                   = '';
			$settings['oauth_verified'] = false;
			$settings['tweet_queue']               = array();
			tocp_save_settings( $settings );
			delete_option( 'tocp_consumer_key' );
			delete_option( 'tocp_consumer_secret' );
			delete_option( 'tocp_api_connected' );
			echo '<script language="javascript">window.location.href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=TweetOldCustomPost";</script>';
			die;
		}
		else if ( isset( $_GET['top'] ) && $_GET['top'] == 'deauthorize' ) {
			$settings                              = tocp_get_settings();
			$settings['oauth_request_token']        = '';
			$settings['oauth_request_token_secret'] = '';
			$settings['user_id']                   = '';
			$settings['oauth_verified'] = false;
			$settings['tweet_queue']               = array();
			tocp_save_settings( $settings );
			echo '<script language="javascript">window.location.href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=TweetOldCustomPost";</script>';
			die;
		} else if ( isset( $_GET['top'] ) && $_GET['top'] == 'reset' ) {
			print( '
			<div id="message" class="updated fade">
				<p>' . __( "All settings have been reset. Kindly update the settings for Tweet Old Custom Post to start tweeting again.", 'TweetOldCustomPost' ) . '</p>
			</div>' );
		}
		//check if username and key provided if bitly selected
		if ( isset( $_POST['tocp_opt_url_shortener'] ) ) {
			if ( $_POST['tocp_opt_url_shortener'] == "bit.ly" ) {
				//check bitly username
				if ( ! isset( $_POST['tocp_opt_bitly_user'] ) ) {
					print( '
			<div id="message" class="updated fade">
				<p>' . __( 'Please enter bit.ly username.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
					$save = false;
				} //check bitly key
				elseif ( ! isset( $_POST['tocp_opt_bitly_key'] ) ) {
					print( '
			<div id="message" class="updated fade">
				<p>' . __( 'Please enter bit.ly API Key.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
					$save = false;
				} //if both the good to save
				else {
					$save = true;
				}
			}
		}

		//Forcing admin URL as https plugins could mess up th urls that are not https
		$myTopUrl = site_url() . "/wp-admin/admin.php?page=TweetOldCustomPost";
		update_option( 'tocp_opt_admin_url', $myTopUrl );

		//if submit and if bitly selected its fields are filled then save
		if ( isset( $_POST['submit'] ) && $save ) {
			$message = $message_updated;
			//TOP admin URL (current url)
			// $myTopUrl = $_POST['tocp_opt_admin_url'];
			//echo"tocp_opt_admin_url ln 95";


			/*
			if ( isset( $_POST['tocp_opt_admin_url'] ) ) {
				$myTopUrl = '';
				if ( strpos( $_POST['tocp_opt_admin_url'], 'TweetOldCustomPost' ) !== true ) {
					$myTopUrl = str_replace( 'TweetOldCustomPost', 'TweetOldCustomPost', $_POST['tocp_opt_admin_url'] );
				}
				update_option( 'tocp_opt_admin_url', $myTopUrl );
			}
			*/
			//what to tweet
			//set omitted categories
			$consumer_key = trim(filter_input( INPUT_POST, 'tocp_consumer_key' ));
			$consumer_secret = trim(filter_input( INPUT_POST, 'tocp_consumer_secret' ));
			//$consumer_api_connected = filter_input( INPUT_POST, 'tocp_api_connected' );

			if( ! empty( $consumer_key ) && ! empty( $consumer_secret ) ){

				update_option( 'tocp_consumer_key', $consumer_key );
				update_option( 'tocp_consumer_secret', $consumer_secret );
				update_option( 'tocp_api_connected', true );
				global $tocp_oauth;
				$tocp_oauth->refresh_api_tokens();
				$auth_url = tocp_get_auth_url();
			///	$tocp_oauth->output_api_tokens();
				if(! $auth_url){
					$twitter_api_connection_error =  get_option('tocp_api_connection_error');
					delete_option('tocp_api_connection_error');
					//delete_option( 'tocp_consumer_key' );
					//delete_option( 'tocp_consumer_secret' );
					delete_option( 'tocp_api_connected' );


				}
			}



			if ( isset( $_POST['tocp_opt_tweet_type'] ) ) {
				update_option( 'tocp_opt_tweet_type', $_POST['tocp_opt_tweet_type'] );
			}
			//additional data
			if ( isset( $_POST['tocp_opt_add_text'] ) ) {
				update_option( 'tocp_opt_add_text', $_POST['tocp_opt_add_text'] );
			}
			//place of additional data
			if ( isset( $_POST['tocp_opt_add_text_at'] ) ) {
				update_option( 'tocp_opt_add_text_at', $_POST['tocp_opt_add_text_at'] );
			}
			//include link
			if ( isset( $_POST['tocp_opt_include_link'] ) ) {
				update_option( 'tocp_opt_include_link', $_POST['tocp_opt_include_link'] );
			}
			//fetch url from custom field?
			if ( isset( $_POST['tocp_opt_custom_url_option'] ) ) {
				update_option( 'tocp_opt_custom_url_option', true );
			} else {
				update_option( 'tocp_opt_custom_url_option', false );
			}
			//custom field to fetch URL from
			if ( isset( $_POST['tocp_opt_custom_url_field'] ) ) {
				update_option( 'tocp_opt_custom_url_field', $_POST['tocp_opt_custom_url_field'] );
			} else {
				update_option( 'tocp_opt_custom_url_field', '' );
			}
			//use URL shortner?
			if ( isset( $_POST['tocp_opt_use_url_shortner'] ) ) {
				update_option( 'tocp_opt_use_url_shortner', true );
			} else {
				update_option( 'tocp_opt_use_url_shortner', false );
			}
			//url shortener to use
			if ( isset( $_POST['tocp_opt_url_shortener'] ) ) {
				update_option( 'tocp_opt_url_shortener', $_POST['tocp_opt_url_shortener'] );
				if ( $_POST['tocp_opt_url_shortener'] == "bit.ly" ) {
					if ( isset( $_POST['tocp_opt_bitly_user'] ) ) {
						update_option( 'tocp_opt_bitly_user', $_POST['tocp_opt_bitly_user'] );
					}
					if ( isset( $_POST['tocp_opt_bitly_key'] ) ) {
						update_option( 'tocp_opt_bitly_key', $_POST['tocp_opt_bitly_key'] );
					}
				}
			}
			//hashtags option
			if ( isset( $_POST['tocp_opt_custom_hashtag_option'] ) ) {
				update_option( 'tocp_opt_custom_hashtag_option', $_POST['tocp_opt_custom_hashtag_option'] );
			} else {
				update_option( 'tocp_opt_custom_hashtag_option', "nohashtag" );
			}
			//use inline hashtags
			if ( isset( $_POST['tocp_opt_use_inline_hashtags'] ) ) {
				update_option( 'tocp_opt_use_inline_hashtags', true );
			} else {
				update_option( 'tocp_opt_use_inline_hashtags', false );
			}
			//hashtag length
			if ( isset( $_POST['tocp_opt_hashtag_length'] ) ) {
				update_option( 'tocp_opt_hashtag_length', $_POST['tocp_opt_hashtag_length'] );
			} else {
				update_option( 'tocp_opt_hashtag_length', 0 );
			}
			//custom field name to fetch hashtag from
			if ( isset( $_POST['tocp_opt_custom_hashtag_field'] ) ) {
				update_option( 'tocp_opt_custom_hashtag_field', $_POST['tocp_opt_custom_hashtag_field'] );
			} else {
				update_option( 'tocp_opt_custom_hashtag_field', '' );
			}
			//default hashtags for tweets
			if ( isset( $_POST['tocp_opt_hashtags'] ) ) {
				update_option( 'tocp_opt_hashtags', $_POST['tocp_opt_hashtags'] );
			} else {
				update_option( 'tocp_opt_hashtags', '' );
			}
			//tweet interval
			if ( isset( $_POST['tocp_opt_interval'] ) ) {
				if ( is_numeric( $_POST['tocp_opt_interval'] ) && $_POST['tocp_opt_interval'] > 0 ) {
					update_option( 'tocp_opt_interval', $_POST['tocp_opt_interval'] );
				} else {
					update_option( 'tocp_opt_interval', "4" );
				}
			}
			//minimum post age to tweet
			if ( isset( $_POST['tocp_opt_age_limit'] ) ) {
				if ( is_numeric( $_POST['tocp_opt_age_limit'] ) && $_POST['tocp_opt_age_limit'] >= 0 ) {
					update_option( 'tocp_opt_age_limit', $_POST['tocp_opt_age_limit'] );
				} else {
					update_option( 'tocp_opt_age_limit', "30" );
				}
			}
			//maximum post age to tweet
			if ( isset( $_POST['tocp_opt_max_age_limit'] ) ) {
				if ( is_numeric( $_POST['tocp_opt_max_age_limit'] ) && $_POST['tocp_opt_max_age_limit'] > 0 ) {
					update_option( 'tocp_opt_max_age_limit', $_POST['tocp_opt_max_age_limit'] );
				} else {
					update_option( 'tocp_opt_max_age_limit', "0" );
				}
			}
			//number of posts to tweet
			if ( isset( $_POST['tocp_opt_no_of_tweet'] ) ) {
				if ( is_numeric( $_POST['tocp_opt_no_of_tweet'] ) && $_POST['tocp_opt_no_of_tweet'] > 0 ) {
					update_option( 'tocp_opt_no_of_tweet', $_POST['tocp_opt_no_of_tweet'] );
				} else {
					update_option( 'tocp_opt_no_of_tweet', "1" );
				}
			}
			//type of post to tweet
			if ( isset( $_POST['tocp_opt_post_type'] ) ) {
				update_option( 'tocp_opt_post_type', $_POST['tocp_opt_post_type'] );
			}
			//option to enable log
			if ( isset( $_POST['tocp_enable_log'] ) ) {
				update_option( 'tocp_enable_log', true );
				global $tocp_debug;
				$tocp_debug->enable( true );

			} else {
				update_option( 'tocp_enable_log', false );
				global $tocp_debug;
				$tocp_debug->enable( false );
			}
			//categories to omit from tweet
			if ( isset( $_POST['post_category'] ) ) {
				$imp = implode( ',', $_POST['post_category'] );
				update_option( 'tocp_opt_omit_cats', $imp );
			} else {
				update_option( 'tocp_opt_omit_cats', '' );
			}
			//categories from custom posts to omit from tweet
			if ( isset( $_POST['tax_input'] ) ) {
				$mimp = tocp_multi_implode( $_POST['tax_input'], ',' );
				update_option( 'tocp_opt_omit_cust_cats', $mimp );
			} else {
				update_option( 'tocp_opt_omit_cust_cats', '' );
			}
			//successful update message
			print( '
			<div id="message" class="updated fade">
				<p>' . __( 'Tweet Old Custom Post Options Updated.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
		} //tweet now clicked
		elseif ( isset( $_POST['tweet'] ) ) {
			$tweet_msg = tocp_opt_tweet_old_post();
			print( '
			<div id="message" class="updated fade">
				<p>' . __( $tweet_msg, 'TweetOldCustomPost' ) . '</p>
			</div>' );
		} elseif ( isset( $_POST['reset'] ) ) {
			tocp_reset_settings();
			echo '<script language="javascript">window.location.href= "' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=TweetOldCustomPost";</script>';
			die;
		}
		//Current URL
		//echo"tocp_opt_admin_url ln 299 <br />";
		//echo"tocp_opt_admin_url ln 299 <br />";


		$admin_url = get_option( 'tocp_opt_admin_url' );
		//var_dump($admin_url);
		// echo"admin_url $admin_url ln 299 <br />";
		//$admin_url = site_url('/wp-admin/admin.php?page=TweetOldPost');
		if ( ! isset( $admin_url ) ) {
			$admin_url = "";
			update_option( 'tocp_opt_admin_url', $admin_url );
		} else {
			if ( strpos( $admin_url, 'TweetOldCustomPost' ) !== true ) {
				$admin_url = str_replace( 'TweetOldPost', 'TweetOldCustomPost', $admin_url );
			}
			update_option( 'tocp_opt_admin_url', $admin_url );
			//echo"admin_url $admin_url ln 316 <br />";
		}


		//what to tweet?
		$tweet_type = get_option( 'tocp_opt_tweet_type' );
		if ( ! isset( $tweet_type ) ) {
			$tweet_type = "title";
		}
		//additional text
		$additional_text = get_option( 'tocp_opt_add_text' );
		if ( ! isset( $additional_text ) ) {
			$additional_text = "";
		}
		//position of additional text
		$additional_text_at = get_option( 'tocp_opt_add_text_at' );
		if ( ! isset( $additional_text_at ) ) {
			$additional_text_at = "beginning";
		}
		//include link in tweet
		$include_link = get_option( 'tocp_opt_include_link' );
		if ( ! isset( $include_link ) ) {
			$include_link = "no";
		}
		//use custom field to fetch url
		$custom_url_option = get_option( 'tocp_opt_custom_url_option' );
		if ( ! isset( $custom_url_option ) ) {
			$custom_url_option = "";
		} elseif ( $custom_url_option ) {
			$custom_url_option = "checked";
		} else {
			$custom_url_option = "";
		}
		//custom field name for url
		$custom_url_field = get_option( 'tocp_opt_custom_url_field' );
		if ( ! isset( $custom_url_field ) ) {
			$custom_url_field = "";
		}
		//use url shortner?
		$use_url_shortner = get_option( 'tocp_opt_use_url_shortner' );
		if ( ! isset( $use_url_shortner ) ) {
			$use_url_shortner = "";
		} elseif ( $use_url_shortner ) {
			$use_url_shortner = "checked";
		} else {
			$use_url_shortner = "";
		}
		//url shortner
		$url_shortener = get_option( 'tocp_opt_url_shortener' );
		if ( ! isset( $url_shortener ) ) {
			$url_shortener = tocp_opt_URL_SHORTENER;
		}
		//bitly key
		$bitly_api = get_option( 'tocp_opt_bitly_key' );
		if ( ! isset( $bitly_api ) ) {
			$bitly_api = "";
		}
		//bitly username
		$bitly_username = get_option( 'tocp_opt_bitly_user' );
		if ( ! isset( $bitly_username ) ) {
			$bitly_username = "";
		}
		//hashtag option
		$custom_hashtag_option = get_option( 'tocp_opt_custom_hashtag_option' );
		if ( ! isset( $custom_hashtag_option ) ) {
			$custom_hashtag_option = "nohashtag";
		}
		//use inline hashtag
		$use_inline_hashtags = get_option( 'tocp_opt_use_inline_hashtags' );
		if ( ! isset( $use_inline_hashtags ) ) {
			$use_inline_hashtags = "";
		} elseif ( $use_inline_hashtags ) {
			$use_inline_hashtags = "checked";
		} else {
			$use_inline_hashtags = "";
		}
		//hashtag length
		$hashtag_length = get_option( 'tocp_opt_hashtag_length' );
		if ( ! isset( $hashtag_length ) ) {
			$hashtag_length = "20";
		}
		//custom field
		$custom_hashtag_field = get_option( 'tocp_opt_custom_hashtag_field' );
		if ( ! isset( $custom_hashtag_field ) ) {
			$custom_hashtag_field = "";
		}
		//default hashtag
		$twitter_hashtags = get_option( 'tocp_opt_hashtags' );
		if ( ! isset( $twitter_hashtags ) ) {
			$twitter_hashtags = tocp_opt_HASHTAGS;
		}
		//interval
		$interval = get_option( 'tocp_opt_interval' );
		if ( ! ( isset( $interval ) && is_numeric( $interval ) ) ) {
			$interval = tocp_opt_INTERVAL;
		}
		//min age limit
		$ageLimit = get_option( 'tocp_opt_age_limit' );
		if ( ! ( isset( $ageLimit ) && is_numeric( $ageLimit ) ) ) {
			$ageLimit = tocp_opt_AGE_LIMIT;
		}
		//max age limit
		$maxAgeLimit = get_option( 'tocp_opt_max_age_limit' );
		if ( ! ( isset( $maxAgeLimit ) && is_numeric( $maxAgeLimit ) ) ) {
			$maxAgeLimit = tocp_opt_MAX_AGE_LIMIT;
		}
		//number of post to tweet
		$tocp_opt_no_of_tweet = get_option( 'tocp_opt_no_of_tweet' );
		if ( ! ( isset( $tocp_opt_no_of_tweet ) && is_numeric( $tocp_opt_no_of_tweet ) ) ) {
			$tocp_opt_no_of_tweet = "1";
		}
		//type of post to tweet
		$tocp_opt_post_type = get_option( 'tocp_opt_post_type' );
		if ( ! isset( $tocp_opt_post_type ) ) {
			$tocp_opt_post_type = "post";
		}
		//check enable log
		$tocp_enable_log = get_option( 'tocp_enable_log' );
		if ( ! isset( $tocp_enable_log ) ) {
			$tocp_enable_log = "";
		} elseif ( $tocp_enable_log ) {
			$tocp_enable_log = "checked";
		} else {
			$tocp_enable_log = "";
		}
		//set omitted categories
		$omitCats = get_option( 'tocp_opt_omit_cats' );


		if ( ! isset( $omitCats ) ) {
			$omitCats = tocp_opt_OMIT_CATS;
		}
		//set omitted categories from custom posts
		$omitCustCats = get_option( 'tocp_opt_omit_cust_cats' );
		if ( ! isset( $omitCustCats ) ) {
			$omitCustCats = tocp_opt_OMIT_CUSTOM_CATS;
		}
		$x = WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) );
		$settings = tocp_get_settings();
//		 echo '<pre>ssss'. print_r($settings,true) . '</pre>';

		//set omitted categories

		//$consumer_key = get_option( 'tocp_consumer_key' );
		//$consumer_secret = get_option( 'tocp_consumer_secret' );
		$consumer_api_connected = get_option( 'tocp_api_connected' );
		if ( !isset($settings["oauth_verified"]) || ! $settings["oauth_verified"] ) {
			if ( $consumer_api_connected ) {

				if(!isset($auth_url) || $auth_url === false ){

					$auth_url = tocp_get_auth_url();

				}
			}
		}

		$tocp_api_connection_error = get_option('tocp_api_connection_error');
		if( ! empty( $tocp_api_connection_error)){
		/*	echo 'it is something';
			exit;*/
			delete_option('tocp_api_connection_error');
		}
		?>

		<?php
		/*and don\'t forget to <a href="http://wordpress.org/support/view/plugin-reviews/tweet-old-post">leave a review</a>*/
		print( '
			<div class="wrap">
				<!-- <h2>' . 'Tweet Old <em>Custom Post</em> by - ' . ' <a href="//www.dejanmarkovic.com" target="_blank">Dejan Markovic</a> & <a target="_blank" href="//www.nytogroup.com/tweet-old-custom-post/">NYTO Group.com</a></h2> -->
<h3 style="color: darkorange;">Before changing any settings here please click on <em style="color: black;">"Update Tweet Old Custom Post Options"</em> button bellow!</h3>
<!-- <h3>Do you like this plugin? If yes please <a target="_blank" href="https://twitter.com/intent/tweet?text=Check-out%20this%20awesome%20plugin%20-%20&url=http%3A%2F%2Fwww.nytogroup.com%2Fproducts%2Fwordpress-plugins%2Ftweet-old-custom-post%2F&via=nytogroup">share your love</a> on Twitter!</h3> -->
<br /><br />


				<form id="tocp_opt" name="tocp_TweetOldCustomPost" action="" method="post">
					<input type="hidden" name="tocp_opt_action" value="tocp_opt_update_settings" />
					<fieldset class="options">
					
					<div id="profile-box">');
		print('<h3>' . __( 'Twitter API', 'TweetOldCustomPost' ) . '</h3>');
		if(!empty($tocp_api_connection_error)){
			print('<div style="color:red;">' . $tocp_api_connection_error . '</div>');
		}
				if(! $consumer_api_connected || empty( $consumer_api_connected )){
			?>
					<p><strong><?php echo __('Note: Before authorization, you need to create your Twitter application and insert API keys here', 'TweetOldCustomPost'); ?></strong></p>
					<ol>

						<li><?php echo __('Login to your Twitter account', 'TweetOldCustomPost'); ?></li>
						<li><?php echo __('go to https://apps.twitter.com/app/new', 'TweetOldCustomPost'); ?></li>
						<li><?php echo __('Enter name of your App and website', 'TweetOldCustomPost'); ?></li>
						<li><?php echo __('Add to Callback URL your Tweet Old Custom Post settings page URL', 'TweetOldCustomPost'); ?>: <strong> <?php echo $admin_url; ?></strong></li>
						<li><?php echo __('Submit and create your new application', 'TweetOldCustomPost'); ?></li>
						<li><?php echo __('Go to "keys and access tokens" tab and copy over Consumer Key and Consumer secret and submit', 'TweetOldCustomPost'); ?> </li>
					</ol>

			<?php
					print('<div class="option">
							<label for="tocp_consumer_key">' . __( 'Consumer Key', 'TweetOldCustomPost' ) . ':</label>
						    <input type="text" size="25" name="tocp_consumer_key" id="tocp_consumer_key" value="" autocomplete="off" />

						</div>
						<div class="option">
							<label for="tocp_consumer_secret">' . __( 'Consumer Secret', 'TweetOldCustomPost' ) . ':</label>
						    <input type="text" size="25" name="tocp_consumer_secret" id="tocp_consumer_secret" value="" autocomplete="off" />

						</div>');
					?>

					<input type="submit" name="submit" value="Save API Keys">


					<?php
				}else{
					echo '<p>

								You inserted your API keys . <a href="' . $_SERVER["REQUEST_URI"] . '&top=removeapikeys" onclick=\'return confirm("Are you sure you want to your Twitter API keys? This will remove your authorization as well.");\'>Click to remove API keys</a>.<br />

							</p>

							<div class="retweet-clear"></div>
					';
				}
		if( $consumer_api_connected ){
			print('<div class="option">
							<label for="tocp_opt_twitter_username">' . __( 'Account Login', 'TweetOldCustomPost' ) . ':</label>

' );


			if ( ! $settings["oauth_request_token"] || empty( $settings["oauth_request_token"] ) || !isset( $settings['oauth_verified']) || (int)$settings['oauth_verified'] == 0) {

				echo '<a href="' . $auth_url . '"><img src="' . $x . 'images/twitter.png" /></a>';
				$admin_url_new = get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php';
				print( '</div>
						
                                                <div class="option">
							<b>&nbsp;&nbsp;&nbsp;Note: </b>If you are not able to authorize? or Wordpress logs you out on any button click,<br/>

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Make sure that you added your Tweet Old Custom Post admin url ' .  $admin_url_new . ' to Callback URL in your Twitter application

						</div>
                    
						');
			} else{
               // echo  $settings["oauth_request_token"];
				echo '<p>

								Your account has  been authorized. <a href="' . $_SERVER["REQUEST_URI"] . '&top=deauthorize" onclick=\'return confirm("Are you sure you want to deauthorize your Twitter account?");\'>Click to deauthorize</a>.<br />

							</p>

							<div class="retweet-clear"></div>
					';
			}
		}


                         /*    <div class="option">
							<label for="tocp_opt_admin_url">' . __( 'Tweet Old Custom Post Admin URL <br/> <span class="desc">(Current URL)</span>', 'TweetOldCustomPost' ) . ':</label>
							<input type="text" style="width:500px" id="tocp_opt_admin_url" value="' . $admin_url . '" name="tocp_opt_admin_url" /><br/><b>(Note: If this does not show your current URL in this textbox, copy paste the current URL in this textbox, then click "Update Options")</b>
						</div>*/

		print('<br><br><br><div class="option">
							<label for="tocp_opt_tweet_type">' . __( 'Tweet Content:<br /><span class="desc">What do you want to share?<span>', 'TweetOldCustomPost' ) . ':</label>
							<select id="tocp_opt_tweet_type" name="tocp_opt_tweet_type" style="width:150px">
			$admin_url					<option value="title" ' . tocp_opt_optionselected( "title", $tweet_type ) . '>' . __( ' Title Only ', 'TweetOldCustomPost' ) . ' </option>
								<option value="body" ' . tocp_opt_optionselected( "body", $tweet_type ) . '>' . __( ' Body Only ', 'TweetOldCustomPost' ) . ' </option>
								<option value="titlenbody" ' . tocp_opt_optionselected( "titlenbody", $tweet_type ) . '>' . __( ' Title & Body ', 'TweetOldCustomPost' ) . ' </option>
							</select>

						</div>


						<div class="option">
							<label for="tocp_opt_add_text">' . __( 'Additional Text:<br /><span class="desc">Text added to your auto posts.<span>', 'TweetOldCustomPost' ) . ':</label>
							<input type="text" size="25" name="tocp_opt_add_text" id="tocp_opt_add_text" value="' . $additional_text . '" autocomplete="off" />
						</div>
						<div class="option">
							<label for="tocp_opt_add_text_at">' . __( 'Additional Text At:<br /><span class="desc">Where you want the added text.<span>', 'TweetOldCustomPost' ) . ':</label>
							<select id="tocp_opt_add_text_at" name="tocp_opt_add_text_at" style="width:150px">
								<option value="beginning" ' . tocp_opt_optionselected( "beginning", $additional_text_at ) . '>' . __( ' Beginning of tweet ', 'TweetOldCustomPost' ) . '</option>
								<option value="end" ' . tocp_opt_optionselected( "end", $additional_text_at ) . '>' . __( ' End of tweet ', 'TweetOldCustomPost' ) . '</option>
							</select>
						</div>

						<div class="option">
							<label for="tocp_opt_include_link">' . __( 'Include Link:<br /><span class="desc">Include a link to your post?<span>', 'TweetOldCustomPost' ) . ':</label>
							<select id="tocp_opt_include_link" name="tocp_opt_include_link" style="width:150px" onchange="javascript:showURLOptions()">
								<option value="false" ' . tocp_opt_optionselected( "false", $include_link ) . '>' . __( ' No ', 'TweetOldCustomPost' ) . '</option>
								<option value="true" ' . tocp_opt_optionselected( "true", $include_link ) . '>' . __( ' Yes ', 'TweetOldCustomPost' ) . '</option>
							</select>
						</div>

						<div id="urloptions" style="display:none">

                                                <div class="option">
							<label for="tocp_opt_custom_url_option">' . __( 'Fetch URL from custom field', 'TweetOldCustomPost' ) . ':</label>
							<input onchange="return showCustomField();" type="checkbox" name="tocp_opt_custom_url_option" ' . $custom_url_option . ' id="tocp_opt_custom_url_option" />
							<b>If checked URL will be fetched from custom field.</b>
						</div>



						<div id="customurl" style="display:none;">
						<div class="option">
							<label for="tocp_opt_custom_url_field">' . __( 'Custom field name to fetch URL to be tweeted with post', 'TweetOldCustomPost' ) . ':</label>
							<input type="text" size="25" name="tocp_opt_custom_url_field" id="tocp_opt_custom_url_field" value="' . $custom_url_field . '" autocomplete="off" />
							<b>If set this will fetch the URL from specified custom field</b>
						</div>

						</div>

						<div class="option">
							<label for="tocp_opt_use_url_shortner">' . __( 'Use URL shortner?:<br /><span class="desc">Shorten the link to your post.<span>', 'TweetOldCustomPost' ) . ':</label>
							<input onchange="return showshortener()" type="checkbox" name="tocp_opt_use_url_shortner" id="tocp_opt_use_url_shortner" ' . $use_url_shortner . ' />

						</div>

						<div  id="urlshortener">
						<div class="option">
							<label for="tocp_opt_url_shortener">' . __( 'URL Shortener Service', 'TweetOldCustomPost' ) . ':</label>
							<select name="tocp_opt_url_shortener" id="tocp_opt_url_shortener" onchange="javascript:showURLAPI()" style="width:100px;">
									<option value="is.gd" ' . tocp_opt_optionselected( 'is.gd', $url_shortener ) . '>' . __( 'is.gd', 'TweetOldCustomPost' ) . '</option>
									<option value="su.pr" ' . tocp_opt_optionselected( 'su.pr', $url_shortener ) . '>' . __( 'su.pr', 'TweetOldCustomPost' ) . '</option>
									<option value="bit.ly" ' . tocp_opt_optionselected( 'bit.ly', $url_shortener ) . '>' . __( 'bit.ly', 'TweetOldCustomPost' ) . '</option>
									<option value="tr.im" ' . tocp_opt_optionselected( 'tr.im', $url_shortener ) . '>' . __( 'tr.im', 'TweetOldCustomPost' ) . '</option>
									<option value="3.ly" ' . tocp_opt_optionselected( '3.ly', $url_shortener ) . '>' . __( '3.ly', 'TweetOldCustomPost' ) . '</option>
									<option value="u.nu" ' . tocp_opt_optionselected( 'u.nu', $url_shortener ) . '>' . __( 'u.nu', 'TweetOldCustomPost' ) . '</option>
									<option value="1click.at" ' . tocp_opt_optionselected( '1click.at', $url_shortener ) . '>' . __( '1click.at', 'TweetOldCustomPost' ) . '</option>
									<option value="tinyurl" ' . tocp_opt_optionselected( 'tinyurl', $url_shortener ) . '>' . __( 'tinyurl', 'TweetOldCustomPost' ) . '</option>
							</select>
						</div>
						<div id="showDetail" style="display:none">
							<div class="option">
								<label for="tocp_opt_bitly_user">' . __( 'bit.ly Username', 'TweetOldCustomPost' ) . ':</label>
								<input type="text" size="25" name="tocp_opt_bitly_user" id="tocp_opt_bitly_user" value="' . $bitly_username . '" autocomplete="off" />
							</div>

							<div class="option">
								<label for="tocp_opt_bitly_key">' . __( 'bit.ly API Key', 'TweetOldCustomPost' ) . ':</label>
								<input type="text" size="25" name="tocp_opt_bitly_key" id="tocp_opt_bitly_key" value="' . $bitly_api . '" autocomplete="off" />
							</div>
						</div>
                                                </div>
					</div>


                                                <div class="option">
							<label for="tocp_opt_custom_hashtag_option">' . __( '#Hashtags:<br /><span class="desc">Include #hashtags in your auto posts.<span>', 'TweetOldCustomPost' ) . ':</label>
                                                        <select name="tocp_opt_custom_hashtag_option" id="tocp_opt_custom_hashtag_option" onchange="javascript:return showHashtagCustomField()" style="width:250px;">
									<option value="nohashtag" ' . tocp_opt_optionselected( 'nohashtag', $custom_hashtag_option ) . '>' . __( 'Don`t add any hashtags', 'TweetOldCustomPost' ) . '</option>
                                                                        <option value="common" ' . tocp_opt_optionselected( 'common', $custom_hashtag_option ) . '>' . __( 'Common hashtag for all tweets', 'TweetOldCustomPost' ) . '</option>
									<option value="categories" ' . tocp_opt_optionselected( 'categories', $custom_hashtag_option ) . '>' . __( 'Create hashtags from categories', 'TweetOldCustomPost' ) . '</option>
									<option value="tags" ' . tocp_opt_optionselected( 'tags', $custom_hashtag_option ) . '>' . __( 'Create hashtags from tags', 'TweetOldCustomPost' ) . '</option>
									<option value="custom" ' . tocp_opt_optionselected( 'custom', $custom_hashtag_option ) . '>' . __( 'Get hashtags from custom fields', 'TweetOldCustomPost' ) . '</option>

							</select>


						</div>
						<div id="inlinehashtag" style="display:none;">
						<div class="option">
							<label for="tocp_opt_use_inline_hashtags">' . __( 'Use inline hashtags: ', 'TweetOldCustomPost' ) . '</label>
							<input type="checkbox" name="tocp_opt_use_inline_hashtags" id="tocp_opt_use_inline_hashtags" ' . $use_inline_hashtags . ' />

						</div>

                                                <div class="option">
							<label for="tocp_opt_hashtag_length">' . __( 'Maximum Hashtag length: ', 'TweetOldCustomPost' ) . '</label>
							<input type="text" size="25" name="tocp_opt_hashtag_length" id="tocp_opt_hashtag_length" value="' . $hashtag_length . '" />
                                                       <b>Set this to 0 to include all hashtags</b>
						</div>
						</div>
						<div id="customhashtag" style="display:none;">
						<div class="option">
							<label for="tocp_opt_custom_hashtag_field">' . __( 'Custom field name', 'TweetOldCustomPost' ) . ':</label>
							<input type="text" size="25" name="tocp_opt_custom_hashtag_field" id="tocp_opt_custom_hashtag_field" value="' . $custom_hashtag_field . '" autocomplete="off" />
							<b>fetch hashtags from this custom field</b>
						</div>

						</div>
                                                <div id="commonhashtag" style="display:none;">
						<div class="option">
							<label for="tocp_opt_hashtags">' . __( 'Common #hashtags for your tweets', 'TweetOldCustomPost' ) . ':</label>
							<input type="text" size="25" name="tocp_opt_hashtags" id="tocp_opt_hashtags" value="' . $twitter_hashtags . '" autocomplete="off" />
							<b>Include #, like #thoughts</b>
						</div>
						</div>
						<div class="option">
							<label for="tocp_opt_interval">' . __( 'Minimum interval between tweets: <br /><span class="desc">What should be minimum time between your tweets?<span> ', 'TweetOldCustomPost' ) . '</label>
							<input type="text" id="tocp_opt_interval" maxlength="5" value="' . $interval . '" name="tocp_opt_interval" /> Hour / Hours <b>(Note: If set to 0 it will take default as 4 hours)</b>

						</div>

						<div class="option">
							<label for="tocp_opt_age_limit">' . __( 'Minimum age of post to be eligible for tweet: <br /><span class="desc">Include post in tweets if at least this age.<span> ', 'TweetOldCustomPost' ) . '</label>
							<input type="text" id="tocp_opt_age_limit" maxlength="5" value="' . $ageLimit . '" name="tocp_opt_age_limit" /> Day / Days
							<b> (enter 0 for today)</b>

						</div>

						<div class="option">
							<label for="tocp_opt_max_age_limit">' . __( 'Maximum age of post to be eligible for tweet: <br /><span class="desc">Don\'t include posts older than this.<span>', 'TweetOldCustomPost' ) . '</label>
                                                        <input type="text" id="tocp_opt_max_age_limit" maxlength="5" value="' . $maxAgeLimit . '" name="tocp_opt_max_age_limit" /> Day / Days
                                                       <b>(If you dont want to use this option enter 0 or leave blank)</b><br/>
							<b>Post older than specified days will not be tweeted.</b>
						</div>


                                                <div class="option">
							<label for="tocp_opt_no_of_tweet">' . __( 'Number Of Posts To Tweet:<br/><span class="desc">Number of tweets to share each time.<span>', 'TweetOldCustomPost' ) . ':</label>
							<input type="text" style="width:30px" id="tocp_opt_no_of_tweet" value="' . $tocp_opt_no_of_tweet . '" name="tocp_opt_no_of_tweet" /></b>
						</div>



						<div class="option">
							<label for="tocp_opt_post_type">' . __( 'Post Type:<br/> <span class="desc">What type of items do you want to share?<span>', 'TweetOldCustomPost' ) . ':</label>
							<select id="tocp_opt_post_type" name="tocp_opt_post_type" style="width:150px">
								<option value="post" ' . tocp_opt_optionselected( "post", $tocp_opt_post_type ) . '>' . __( ' Post Only ', 'TweetOldCustomPost' ) . ' </option>
								<option value="page" ' . tocp_opt_optionselected( "page", $tocp_opt_post_type ) . '>' . __( ' Page Only ', 'TweetOldCustomPost' ) . ' </option>
								<option value="both" ' . tocp_opt_optionselected( "both", $tocp_opt_post_type ) . '>' . __( ' Post & Page ', 'TweetOldCustomPost' ) . ' </option>
							</select>

						</div>



                                                <div class="option">
							<label for="tocp_enable_log">' . __( 'Enable Log: ', 'TweetOldCustomPost' ) . '</label>
							<input type="checkbox" name="tocp_enable_log" id="tocp_enable_log" ' . $tocp_enable_log . ' />
                                                        <b>saves log in log folder</b>

						</div>


				    	<div class="option category">
				    	<div style="float:left">
						    	<label class="catlabel">' . __( 'Categories to Omit from tweets: <br/><span class="desc">Check categories not to share.<span> ', 'TweetOldCustomPost' ) . '</label> </div>
						    	<div style="float:left">
						    		<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
								' );

		//get the list of categories from "normal" posts
		wp_category_checklist( 0, 0, explode( ',', $omitCats ) );

		//get custom posts
		$custom_posts = tocp_get_custom_posts( 'objects' );
    // var_dump($custom_posts);
		if (!empty($custom_posts)) {
			$post_taxonomies = tocp_get_post_taxonomies( $custom_posts, 'object' );
			//get post name label array $post_labels
			$post_labels = tocp_get_post_labels( $custom_posts, 'object' );
			//get post names array
			$post_names = array_keys( $post_labels );
			//get post names from posts that have taxonomies
			$filtered_post_names = array_keys( $post_taxonomies );
			//compare post names and ger diff
			$diff_post_names = array_diff( $post_names, $filtered_post_names );
			//array of post names and labels that have taxonomies
			$filtered_post_labels = array_diff_key( $post_labels, array_flip( $diff_post_names ) );
			$category_taxonomies  = tocp_get_category_taxonomies( $post_taxonomies, $filtered_post_labels );

			if(!empty($category_taxonomies)){
			//get the list of categories from "custom" posts
                print'</ul>
                                  </div>
                                  </div>
                                  <div class="option category">
                                  <div style="float:left">
                                  <label class="catlabel">' . __( 'Categories from custom posts: <br/><span class="desc">Check categories not to share.<span> ', 'TweetOldCustomPost' ) . '</label> </div>
                                  <div style="float:left">
                                                    <ul id="categorychecklist" class="list:category categorychecklist form-no-clear">';
                foreach ( $category_taxonomies as $name => $value ) {
                    echo "<li style='padding-top: 30px;'><strong>" . $filtered_post_labels[ $name ] . "</strong><br /></li>";
                    //echo "<li style='padding-top: 30px;'><strong>".  $value . "</strong><br /></li>";
                    tocp_get_taxonomy_checklist( $value, explode( ',', $omitCustCats ) );
                    echo "<li>&nbsp; <br clear='both' /></li>";
                }
                print( '				    		</ul>' );
		    }//end if
		}
		print( '				    		</ul>
              <div style="clear:both;padding-top:20px;">');
                                                          /*<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=ExcludePosts">Exclude specific posts</a> from selected categories. */
       print( '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=ExcludePosts">Exclude specific posts</a></div>


								</div>

								</div>
					</fieldset>



                                                <h3>Note: Please click update to then click "Tweet Now" button to reflect the changes.</h3>
						<p class="submit"><input type="submit" name="submit" onclick="javascript:return validate()" value="' . __( 'Update Tweet Old Custom Post Options', 'TweetOldCustomPost' ) . '" />
						<input type="submit" name="tweet" value="' . __( 'Tweet Now', 'TweetOldCustomPost' ) . '" />
                                                <input type="submit" onclick=\'return resetSettings();\' name="reset" value="' . __( 'Reset Settings', 'TweetOldCustomPost' ) . '" />
					</p>

				</form><script language="javascript" type="text/javascript">
function showURLAPI()
{
	var urlShortener=document.getElementById("tocp_opt_url_shortener").value;
	if(urlShortener=="bit.ly")
	{
		document.getElementById("showDetail").style.display="block";

	}
	else
	{
		document.getElementById("showDetail").style.display="none";

	}

}

function validate()
{

	if(document.getElementById("showDetail").style.display=="block" && document.getElementById("tocp_opt_url_shortener").value=="bit.ly")
	{
		if(trim(document.getElementById("tocp_opt_bitly_user").value)=="")
		{
			alert("Please enter bit.ly username.");
			document.getElementById("tocp_opt_bitly_user").focus();
			return false;
		}

		if(trim(document.getElementById("tocp_opt_bitly_key").value)=="")
		{
			alert("Please enter bit.ly API key.");
			document.getElementById("tocp_opt_bitly_key").focus();
			return false;
		}
	}
 if(trim(document.getElementById("tocp_opt_interval").value) != "" && !isNumber(trim(document.getElementById("tocp_opt_interval").value)))
        {
            alert("Enter only numeric in Minimum interval between tweet");
		document.getElementById("tocp_opt_interval").focus();
		return false;
        }

 if(trim(document.getElementById("tocp_opt_no_of_tweet").value) != "" && !isNumber(trim(document.getElementById("tocp_opt_no_of_tweet").value)))
        {
            alert("Enter only numeric in Number Of Posts To Tweet");
		document.getElementById("tocp_opt_no_of_tweet").focus();
		return false;
        }

        if(trim(document.getElementById("tocp_opt_age_limit").value) != "" && !isNumber(trim(document.getElementById("tocp_opt_age_limit").value)))
        {
            alert("Enter only numeric in Minimum age of post");
		document.getElementById("tocp_opt_age_limit").focus();
		return false;
        }
 if(trim(document.getElementById("tocp_opt_max_age_limit").value) != "" && !isNumber(trim(document.getElementById("tocp_opt_max_age_limit").value)))
        {
            alert("Enter only numeric in Maximum age of post");
		document.getElementById("tocp_opt_max_age_limit").focus();
		return false;
        }
	if(trim(document.getElementById("tocp_opt_max_age_limit").value) != "" && trim(document.getElementById("tocp_opt_max_age_limit").value) != 0)
	{
	if(eval(document.getElementById("tocp_opt_age_limit").value) > eval(document.getElementById("tocp_opt_max_age_limit").value))
	{
		alert("Post max age limit cannot be less than Post min age iimit");
		document.getElementById("tocp_opt_age_limit").focus();
		return false;
	}
	}
}

function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}

function showCustomField()
{
	if(document.getElementById("tocp_opt_custom_url_option").checked)
	{
		document.getElementById("customurl").style.display="block";
	}
	else
	{
		document.getElementById("customurl").style.display="none";
	}
}

function showHashtagCustomField()
{
	if(document.getElementById("tocp_opt_custom_hashtag_option").value=="custom")
	{
		document.getElementById("customhashtag").style.display="block";
                document.getElementById("commonhashtag").style.display="none";
                 document.getElementById("inlinehashtag").style.display="block";
	}
        else if(document.getElementById("tocp_opt_custom_hashtag_option").value=="common")
	{
		document.getElementById("customhashtag").style.display="none";
                document.getElementById("commonhashtag").style.display="block";
                document.getElementById("inlinehashtag").style.display="block";
	}
        else if(document.getElementById("tocp_opt_custom_hashtag_option").value=="nohashtag")
	{
		document.getElementById("customhashtag").style.display="none";
                document.getElementById("commonhashtag").style.display="none";
                document.getElementById("inlinehashtag").style.display="none";
	}
	else
	{
                document.getElementById("inlinehashtag").style.display="block";
		document.getElementById("customhashtag").style.display="none";
                document.getElementById("commonhashtag").style.display="none";
	}
}

function showURLOptions()
{
    if(document.getElementById("tocp_opt_include_link").value=="true")
	{
		document.getElementById("urloptions").style.display="block";
	}
	else
	{
		document.getElementById("urloptions").style.display="none";
	}
}

function isNumber(val)
{
    if(isNaN(val)){
        return false;
    }
    else{
        return true;
    }
}

function showshortener()
{


	if((document.getElementById("tocp_opt_use_url_shortner").checked))
		{
			document.getElementById("urlshortener").style.display="block";
		}
		else
		{
			document.getElementById("urlshortener").style.display="none";
		}
}
function setFormAction()
{
    if(document.getElementById("tocp_opt_admin_url").value == "")
    {
        var loc=location.href;
        if(location.href.indexOf("&")>0)
        {
            location.href.substring(0,location.href.lastIndexOf("&"));
        }
        document.getElementById("tocp_opt_admin_url").value=loc;
        document.getElementById("tocp_opt").action=loc;

    }
    else
    {
        document.getElementById("tocp_opt").action=document.getElementById("tocp_opt_admin_url").value;
    }
 }

function resetSettings()
{
   var re = confirm("This will reset all the setting, including your account, omitted categories, and your excluded posts. Are you sure you want to reset all the settings?");
   if(re==true)
   {
        document.getElementById("tocp_opt").action=location.href;
        return true;
   }
   else
   {
        return false;
   }
}

setFormAction();
showURLAPI();
showshortener();
showCustomField();
showHashtagCustomField();
showURLOptions();

</script>' );
	} else {
		print( '
			<div id="message" class="updated fade">
				<p>' . __( 'You do not have enough permission to set the option. Please contact your admin.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
	}
}

function tocp_opt_optionselected( $opValue, $value ) {
	if ( $opValue == $value ) {
		return 'selected="selected"';
	}

	return '';
}

function tocp_opt_head_admin() {
	$path = plugins_url() . '/tweet-old-custom-post/css/tweet-old-post.css';
	echo( '<link rel="stylesheet" href="' . $path . '" type="text/css" media="screen" />' );
}

?>