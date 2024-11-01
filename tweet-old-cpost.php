<?php
/*
Plugin Name: Tweet Old Custom Post
Plugin URI: https://dejanmarkovic.com/plugins/tweet-old-custom-post/
Description: WordPress Plugin that automatically tweets your old posts (including custom posts or CPT's) in order to get more traffic and keep the old posts alive.
It also helps you to promote your content. You can set time and number of tweets to post to drive more traffic.

For questions, comments, or feature requests, contact me at: <a href="http://dejanmarkovic.com/contact/">Contact</a>.
Author:  Dejan Markovic, NYTO Group,HYPEStudio
Version: 19.0.1
Author URI: https://dejanmarkovic.com/plugins/tweet-old-custom-post/
*/

/*
 Plugin Name: Tweet old post
 Plugin URI: http://www.readythemes.com/tweet-old-post-lite/
 Description: Wordpress plugin that helps you to keeps your old posts alive by tweeting about them and driving more traffic to them from twitter. It also helps you to promote your content. You can set time and no of tweets to post to drive more traffic.For questions, comments, or feature requests, contact me! <a href="http://www.readythemes.com/?r=top">Ionut Neagu</a>.
 Author: ReadyThemes
 Version: 4.0.11
 Author URI: http://www.readythemes.com/
 */
include( 'lib/helpers.php' );
require_once( 'tocp-admin.php' );
require_once( 'tocp-core.php' );
require_once( 'tocp-excludepost.php' );
require_once( 'Include/tocp-oauth.php' );
require_once( 'xml.php' );
require_once( 'Include/tocp-debug.php' );

//update_option('tocp_enable_log', true);
//global $tocp_debug;
//tocp_is_debug_enabled();
//$tocp_debug->enable( true );
define( 'tocp_opt_1_HOUR', 60 * 60 );
define( 'tocp_opt_2_HOURS', 2 * tocp_opt_1_HOUR );
define( 'tocp_opt_4_HOURS', 4 * tocp_opt_1_HOUR );
define( 'tocp_opt_8_HOURS', 8 * tocp_opt_1_HOUR );
define( 'tocp_opt_6_HOURS', 6 * tocp_opt_1_HOUR );
define( 'tocp_opt_12_HOURS', 12 * tocp_opt_1_HOUR );
define( 'tocp_opt_24_HOURS', 24 * tocp_opt_1_HOUR );
define( 'tocp_opt_48_HOURS', 48 * tocp_opt_1_HOUR );
define( 'tocp_opt_72_HOURS', 72 * tocp_opt_1_HOUR );
define( 'tocp_opt_168_HOURS', 168 * tocp_opt_1_HOUR );
define( 'tocp_opt_INTERVAL', 4 );
define( 'tocp_opt_AGE_LIMIT', 30 ); // 120 days
define( 'tocp_opt_MAX_AGE_LIMIT', 60 ); // 120 days
define( 'tocp_opt_OMIT_CATS', "" );
define( 'tocp_opt_OMIT_CUSTOM_CATS', "" );
define( 'tocp_opt_TWEET_PREFIX', "" );
define( 'tocp_opt_ADD_DATA', "false" );
define( 'tocp_opt_URL_SHORTENER', "is.gd" );
define( 'tocp_opt_HASHTAGS', "" );
define( 'tocp_opt_no_of_tweet', "1" );
define( 'tocp_opt_post_type', "post" );
define( 'tocp_opt_plugin_dir', plugin_dir_path( __FILE__ ) );
function tocp_admin_actions() {
	add_menu_page( "Tweet Old Custom Post", "Tweet Old Custom Post", 'manage_options', "TweetOldCustomPost", "tocp_admin" );
	add_submenu_page( "TweetOldCustomPost", __( 'Exclude Posts', 'TweetOldCustomPost' ), __( 'Exclude Posts', 'TweetOldCustomPost' ), 'manage_options', __( 'ExcludePosts', 'TweetOldCustomPost' ), 'tocp_exclude' );
}

add_action( 'admin_menu', 'tocp_admin_actions' );
add_action( 'admin_head', 'tocp_opt_head_admin' );
add_action( 'init', 'tocp_tweet_old_post' );
add_action( 'admin_init', 'tocp_authorize', 1 );
//add_action( 'admin_init', 'tocp_get_auth_url', 1 );


// Create a helper function for easy SDK access.
function tocp_fs() {
	global $tocp_fs;

	if ( ! isset( $tocp_fs ) ) {
		// Include Freemius SDK.
		require_once dirname(__FILE__) . '/freemius/start.php';

		$tocp_fs = fs_dynamic_init( array(
			'id'                => '297',
			'slug'              => 'tweet-old-custom-post',
			'public_key'        => 'pk_47f5a490529093c368d84badec470',
			'is_premium'        => false,
			'has_addons'        => false,
			'has_paid_plans'    => false,
			'menu'              => array(
				'slug'       => 'TweetOldCustomPost',
				'account'    => false,
			),
		) );
	}

	return $tocp_fs;
}

// Init Freemius.
tocp_fs();

function tocp_fs_uninstall_cleanup() {
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit();
	}
//delete options from tocp
	delete_option( 'top_settings' );
	delete_option( 'top_opt_admin_url' );
	delete_option( 'top_opt_last_update' );
	delete_option( 'top_opt_omit_cats' );
	delete_option( 'top_opt_omit_cust_cats' );
	delete_option( 'top_opt_max_age_limit' );
	delete_option( 'top_opt_age_limit' );
	delete_option( 'top_opt_excluded_post' );
	delete_option( 'top_opt_post_type' );
	delete_option( 'top_opt_no_of_tweet' );
	delete_option( 'top_opt_tweeted_posts' );
	delete_option( 'top_opt_tweet_type' );
	delete_option( 'top_opt_add_text' );
	delete_option( 'top_opt_add_text_at' );
	delete_option( 'top_opt_include_link' );
	delete_option( 'top_opt_custom_hashtag_option' );
	delete_option( 'top_opt_custom_hashtag_field' );
	delete_option( 'top_opt_hashtags' );
	delete_option( 'top_opt_url_shortener' );
	delete_option( 'top_opt_custom_url_option' );
	delete_option( 'top_opt_use_url_shortner' );
	delete_option( 'top_opt_use_inline_hashtags' );
	delete_option( 'top_opt_hashtag_length' );
	delete_option( 'top_opt_custom_url_field' );
	delete_option( 'top_opt_bitly_key' );
	delete_option( 'top_opt_bitly_user' );
	delete_option( 'top_opt_interval' );
	delete_option( 'top_settings' );
	delete_option( 'top_enable_log' );
	delete_option( 'top_opt_add_text' );
	delete_option( 'top_opt_add_text_at' );
	delete_option( 'top_opt_use_inline_hashtags' );
	delete_option( 'top_opt_use_url_shortner' );
	delete_option( 'top_reauthorize' );
//delete options from tocp
	delete_option( 'tocp_settings' );
	delete_option( 'tocp_opt_admin_url' );
	delete_option( 'tocp_opt_last_update' );
	delete_option( 'tocp_opt_omit_cats' );
	delete_option( 'tocp_opt_omit_cust_cats' );
	delete_option( 'tocp_opt_max_age_limit' );
	delete_option( 'tocp_opt_age_limit' );
	delete_option( 'tocp_opt_excluded_post' );
	delete_option( 'tocp_opt_post_type' );
	delete_option( 'tocp_opt_no_of_tweet' );
	delete_option( 'tocp_opt_tweeted_posts' );
	delete_option( 'tocp_opt_tweet_type' );
	delete_option( 'tocp_opt_add_text' );
	delete_option( 'tocp_opt_add_text_at' );
	delete_option( 'tocp_opt_include_link' );
	delete_option( 'tocp_opt_custom_hashtag_option' );
	delete_option( 'tocp_opt_custom_hashtag_field' );
	delete_option( 'tocp_opt_hashtags' );
	delete_option( 'tocp_opt_url_shortener' );
	delete_option( 'tocp_opt_custom_url_option' );
	delete_option( 'tocp_opt_use_url_shortner' );
	delete_option( 'tocp_opt_use_inline_hashtags' );
	delete_option( 'tocp_opt_hashtag_length' );
	delete_option( 'tocp_opt_custom_url_field' );
	delete_option( 'tocp_opt_bitly_key' );
	delete_option( 'tocp_opt_bitly_user' );
	delete_option( 'tocp_opt_interval' );
	delete_option( 'tocp_settings' );
	delete_option( 'tocp_enable_log' );
	delete_option( 'tocp_opt_add_text' );
	delete_option( 'tocp_opt_add_text_at' );
	delete_option( 'tocp_opt_use_inline_hashtags' );
	delete_option( 'tocp_opt_use_url_shortner' );
	delete_option( 'tocp_reauthorize' );
}

add_action( 'after_uninstall', 'tocp_fs_uninstall_cleanup' );


function tocp_authorize() {
	if ( isset ( $_GET['page'] ) ) {
		if ( $_GET['page'] == 'TweetOldCustomPost' ) {
			if ( isset( $_REQUEST['oauth_token'] ) ) {
				$auth_url = str_replace( 'oauth_token', 'oauth_token1', tocp_currentPageURL() );
				//	echo 'current page url '. tocp_currentPageURL() . "<br/><br/>";
				//
				/*	$top_url  = get_option( 'tocp_opt_admin_url' ) . substr( $auth_url, strrpos( $auth_url, "page=TweetOldCustomPost" ) + strlen( "page=TweetOldCustomPost" ) );
					echo "REQUEST_URI " . $_SERVER["REQUEST_URI"] . '<br/>';
				echo "self " . $_SERVER["PHP_SELF"] . '<br/>'; */
				//echo 'admin url '	. tocp_adminURL() . $_SERVER["PHP_SELF"]. '<br/>';
				$top_url = tocp_adminURL() . $_SERVER["PHP_SELF"] . '?page=TweetOldCustomPost' . substr( $auth_url, strrpos( $auth_url, "page=TweetOldCustomPost" ) + strlen( "page=TweetOldCustomPost" ) );
				//	echo "auth_url " . $auth_url . '<br/>';
				//	echo "auth substr url " . substr( $auth_url, strrpos( $auth_url, "page=TweetOldCustomPost" )) . '<br/>';
				//echo "top_url " . $top_url . '<br/>';
				//die;
				echo '<script language="javascript">window.location.href="' . $top_url . '";</script>';
				die;
			}
		}
	}
}

add_filter( 'plugin_action_links', 'tocp_plugin_action_links', 10, 2 );
function tocp_plugin_action_links( $links, $file ) {
	static $this_plugin;
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}
	if ( $file == $this_plugin ) {
		// The "page" query string value must be equal to the slug
		// of the Settings admin page we defined earlier, which in
		// this case equals "myplugin-settings".
		$settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=TweetOldCustomPost">Settings</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;
}

require_once dirname( __FILE__ ) . '/Include/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'tocp_plugin_lite_register_required_plugins1' );

function tocp_plugin_lite_register_required_plugins1() {
	$plugins = array (
		array(
			'name' => __( 'Social Web Suite - Social Media Auto Post, Auto Publish and Schedule', 'topcat-lite' ),
			'slug' => 'social-web-suite',
			'required' => false,
		),
	);

	$config = array (
		'id' => 'tweet-old-cpost',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'has_notices'  => true,
		'dismissable'  => true,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',

		'strings'      => array(
			'page_title'                      => __( 'Install Required Plugins', 'buffer-my-post' ),
			'menu_title'                      => __( 'Install Plugins', 'buffer-my-post' ),

			'installing'                      => __( 'Installing Plugin: %s', 'buffer-my-post' ),

			'updating'                        => __( 'Updating Plugin: %s', 'buffer-my-post' ),
			'oops'                            => __( 'Something went wrong with the plugin API.', 'buffer-my-post' ),
			'notice_can_install_required'     => _n_noop(
				'This plugin requires the following plugin: %1$s.',
				'This plugin requires the following plugins: %1$s.',
				'buffer-my-post'
			),
			'notice_can_install_recommended'  => _n_noop(
				'This plugin recommends the following plugin: %1$s.',
				'This plugin recommends the following plugins: %1$s.',
				'buffer-my-post'
			),
			'notice_ask_to_update'            => _n_noop(
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
				'buffer-my-post'
			),
			'notice_ask_to_update_maybe'      => _n_noop(
				'There is an update available for: %1$s.',
				'There are updates available for the following plugins: %1$s.',
				'buffer-my-post'
			),
			'notice_can_activate_required'    => _n_noop(
				'The following required plugin is currently inactive: %1$s.',
				'The following required plugins are currently inactive: %1$s.',
				'buffer-my-post'
			),
			'notice_can_activate_recommended' => _n_noop(
				'The following recommended plugin is currently inactive: %1$s.',
				'The following recommended plugins are currently inactive: %1$s.',
				'buffer-my-post'
			),
			'install_link'                    => _n_noop(
				'Begin installing plugin',
				'Begin installing plugins',
				'buffer-my-post'
			),
			'update_link' 					  => _n_noop(
				'Begin updating plugin',
				'Begin updating plugins',
				'buffer-my-post'
			),
			'activate_link'                   => _n_noop(
				'Begin activating plugin',
				'Begin activating plugins',
				'buffer-my-post'
			),
			'return'                          => __( 'Return to Required Plugins Installer', 'buffer-my-post' ),
			'plugin_activated'                => __( 'Plugin activated successfully.', 'buffer-my-post' ),
			'activated_successfully'          => __( 'The following plugin was activated successfully:', 'buffer-my-post' ),
		),

	);
	tgmpa( $plugins, $config );
}

?>
