<?php
require_once( 'Include/tocp-oauth.php' );
global $tocp_oauth;

$tocp_oauth = new TOPOAuth;
function add_quotes( $str ) {
	return sprintf( "'%s'", $str );
}

if ( function_exists( 'w3tc_pgcache_flush' ) ) {
	w3tc_pgcache_flush();
	w3tc_dbcache_flush();
	w3tc_minify_flush();
	w3tc_objectcache_flush();
	$cache = ' and W3TC Caches cleared';
}

add_action('init','tocp_init_test');

function tocp_init_test(){
	$debug = filter_input(INPUT_GET, 'tocp_debug');

	if($debug == 'debug'){
//		tocp_generate_query();
//		exit;
	}


}
function tocp_tweet_old_post() {
//check last tweet time against set interval and span
	if ( tocp_opt_update_time() ) {
		update_option( 'tocp_opt_last_update', time() );
		tocp_opt_tweet_old_post();
		$ready = false;
	}
}

function tocp_currentPageURL() {
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		$serverrequri = $_SERVER['PHP_SELF'];
	} else {
		$serverrequri = $_SERVER['REQUEST_URI'];
	}
	$s        = empty( $_SERVER["HTTPS"] ) ? '' : ( $_SERVER["HTTPS"] == "on" ) ? "s" : "";
	$protocol = tocp_strleft( strtolower( $_SERVER["SERVER_PROTOCOL"] ), "/" ) . $s;
	$port     = ( $_SERVER["SERVER_PORT"] == "80" ) ? "" : ( ":" . $_SERVER["SERVER_PORT"] );

	return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $serverrequri;

}

function tocp_adminURL() {
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		$serverrequri = $_SERVER['PHP_SELF'];
	} else {
		$serverrequri = $_SERVER['REQUEST_URI'];
	}
	$s        = empty( $_SERVER["HTTPS"] ) ? '' : ( $_SERVER["HTTPS"] == "on" ) ? "s" : "";
	$protocol = tocp_strleft( strtolower( $_SERVER["SERVER_PROTOCOL"] ), "/" ) . $s;
	$port     = ( $_SERVER["SERVER_PORT"] == "80" ) ? "" : ( ":" . $_SERVER["SERVER_PORT"] );

	return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;

}

function tocp_strleft( $s1, $s2 ) {
	return substr( $s1, 0, strpos( $s1, $s2 ) );
}

//get random post and tweet
function tocp_opt_tweet_old_post() {
	return tocp_generate_query();
}

function tocp_generate_query( $can_requery = true ) {
	global $wpdb;
	$rtrn_msg               = "";
	$omitCats               = get_option( 'tocp_opt_omit_cats' );
	$omitCustCats           = get_option( 'tocp_opt_omit_cust_cats' );
	$maxAgeLimit            = get_option( 'tocp_opt_max_age_limit' );
	$ageLimit               = get_option( 'tocp_opt_age_limit' );
	$exposts                = get_option( 'tocp_opt_excluded_post' );
	$exposts                = preg_replace( '/,,+/', ',', $exposts );
	$tocp_opt_post_type     = get_option( 'tocp_opt_post_type' );
	$tocp_opt_no_of_tweet   = get_option( 'tocp_opt_no_of_tweet' );
	$tocp_opt_tweeted_posts = array();
	$tocp_opt_tweeted_posts = get_option( 'tocp_opt_tweeted_posts' );
	if ( ! $tocp_opt_tweeted_posts ) {
		$tocp_opt_tweeted_posts = array();
	}
	if ( $tocp_opt_tweeted_posts != null ) {
		$already_tweeted = implode( ",", $tocp_opt_tweeted_posts );
	} else {
		$already_tweeted = "";
	}
	if ( substr( $exposts, 0, 1 ) == "," ) {
		$exposts = substr( $exposts, 1, strlen( $exposts ) );
	}
	if ( substr( $exposts, - 1, 1 ) == "," ) {
		$exposts = substr( $exposts, 0, strlen( $exposts ) - 1 );
	}
	if ( ! ( isset( $ageLimit ) && is_numeric( $ageLimit ) ) ) {
		$ageLimit = tocp_opt_AGE_LIMIT;
	}
	if ( ! ( isset( $maxAgeLimit ) && is_numeric( $maxAgeLimit ) ) ) {
		$maxAgeLimit = tocp_opt_MAX_AGE_LIMIT;
	}
	if ( ! isset( $omitCats ) ) {
		$omitCats = tocp_opt_OMIT_CATS;
	}
	if ( ! isset( $omitCustCats ) ) {
		$omitCustCats = tocp_opt_OMIT_CUSTOM_CATS;
	}
	if ( $tocp_opt_no_of_tweet <= 0 ) {
		$tocp_opt_no_of_tweet = 1;
	}
	if ( $tocp_opt_no_of_tweet > 10 ) {
		$tocp_opt_no_of_tweet = 10;
	}

	//TODO pull all post types that are non public
	$non_public_posts = tocp_get_custom_posts( 'objects', false );
	$non_public_post_labels = tocp_get_post_labels( $non_public_posts, 'object' );
	$non_public_post_names = array_keys( $non_public_post_labels );
	$non_public_post_names = implode(',', array_map( 'add_quotes',  $non_public_post_names ) );
	if( empty( $tocp_opt_post_type )) {
		$tocp_opt_post_type = 'post';
	}
	if ( $tocp_opt_post_type != 'both' ) {
		if ( $tocp_opt_post_type == 'post' ) {

			$post_type = "post_type NOT IN('page','attachment','revision', 'nav_menu_item' " . $non_public_post_names . ") AND ";
		} else {
			$custom_posts = tocp_get_custom_posts( 'objects' );

			//print_r($custom_posts);
			//get post taxonomies
			$post_taxonomies = tocp_get_post_taxonomies( $custom_posts, 'object' );
			//get post name label array $post_labels
			$post_labels = tocp_get_post_labels( $custom_posts, 'object' );
			//get post names array
			$post_names = array_keys( $post_labels );
			//get post names from posts that have taxonomies
			$filtered_post_names = array_keys( $post_taxonomies );
			// $gcp = getCustomPostNames();
			$customPostNames = implode( ',', array_map( 'add_quotes', $filtered_post_names ) );
			$postnames       = '';
			if ( $customPostNames != '' ) {
				$postnames = $customPostNames . ',' . '\'post\',';
			//	$postnames = $customPostNames . ',';
			} else {
				$postnames = "";
				$postnames = "'post',";
			}
			$post_type = "post_type NOT IN(" . $postnames . "'attachment','revision', 'nav_menu_item' " . $non_public_post_names . ") AND ";
		}

	} else {
		//$post_type="(post_type = 'post' OR post_type = 'page') AND";
		$post_type = "post_type NOT IN('attachment','revision', 'nav_menu_item' " . $non_public_post_names . ") AND ";
	}
	//$post_type_not = "post_type NOT IN('attachment','revision', 'nav_menu_item') AND ";
	$sql = "SELECT ID,POST_TITLE
            FROM $wpdb->posts
            WHERE $post_type post_status = 'publish' ";
	if ( is_numeric( $ageLimit ) ) {
		if ( $ageLimit > 0 ) {
			$sql = $sql . " AND post_date <= curdate( ) - INTERVAL " . $ageLimit . " day";
		}
	}
	if ( $maxAgeLimit != 0 ) {
		$sql = $sql . " AND post_date >= curdate( ) - INTERVAL " . $maxAgeLimit . " day";
	}
	if ( isset( $exposts ) ) {
		if ( trim( $exposts ) != '' ) {
			$sql = $sql . " AND ID Not IN (" . $exposts . ") ";
		}
	}
	if ( isset( $already_tweeted ) ) {
		if ( trim( $already_tweeted ) != "" ) {
			$sql = $sql . " AND ID Not IN (" . $already_tweeted . ") ";
		}
	}
	$omitCats = trim($omitCats); 
	$omitCustCats = trim($omitCustCats); 
	
	if ( $omitCats != '' && $omitCustCats != '' ) {
		$sql = $sql . " AND NOT (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.term_id IN (" . $omitCats . ',' . $omitCustCats . ")))";
	} elseif ( $omitCats != '' ) {
		$sql = $sql . " AND NOT (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.term_id IN (" . $omitCats . ")))";
	} elseif ( $omitCustCats != '' ) {
		$sql = $sql . " AND NOT (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.term_id IN (" . $omitCustCats . ")))";
	} else {
		//$sql = $sql . " AND (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id))";
	}
	$sql         = $sql . "
            ORDER BY RAND()
            LIMIT $tocp_opt_no_of_tweet ";
	//$debug = filter_input(INPUT_GET, 'tocp_debug');
	//if($debug == 'debug'){
		/*echo $sql;
		exit;*/
	//}


	$oldest_post = $wpdb->get_results( $sql );
	if ( $oldest_post == null ) {
		if ( $can_requery ) {
			$tocp_opt_tweeted_posts = array();
			update_option( 'tocp_opt_tweeted_posts', $tocp_opt_tweeted_posts );

			return tocp_generate_query( false );
		} else {
			return "No post found to tweet. Please check your settings and try again.";
		}
	}
	if ( isset( $oldest_post ) ) {
		$ret = '';
		foreach ( $oldest_post as $k => $odp ) {
			array_push( $tocp_opt_tweeted_posts, $odp->ID );
			$ret .= 'Tweet ' . ( $k + 1 ) . ' ( ' . $odp->POST_TITLE . ' )' . ' : ' . tocp_opt_tweet_post( $odp->ID ) . '<br/>';
		}
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
			w3tc_dbcache_flush();
			w3tc_minify_flush();
			w3tc_objectcache_flush();
			$cache = ' and W3TC Caches cleared';
		}
		update_option( 'tocp_opt_tweeted_posts', $tocp_opt_tweeted_posts );

		return $ret;
	}

	return $rtrn_msg;
}

//tweet for the passed random post
function tocp_opt_tweet_post( $oldest_post ) {
	global $wpdb;
	$post                  = get_post( $oldest_post );
	$content               = "";
	$to_short_url          = true;
	$shorturl              = "";
	$tweet_type            = get_option( 'tocp_opt_tweet_type' );
	$additional_text       = get_option( 'tocp_opt_add_text' );
	$additional_text_at    = get_option( 'tocp_opt_add_text_at' );
	$include_link          = get_option( 'tocp_opt_include_link' );
	$custom_hashtag_option = get_option( 'tocp_opt_custom_hashtag_option' );
	$custom_hashtag_field  = get_option( 'tocp_opt_custom_hashtag_field' );
	$twitter_hashtags      = get_option( 'tocp_opt_hashtags' );
	$url_shortener         = get_option( 'tocp_opt_url_shortener' );
	$custom_url_option     = get_option( 'tocp_opt_custom_url_option' );
	$to_short_url          = get_option( 'tocp_opt_use_url_shortner' );
	$use_inline_hashtags   = get_option( 'tocp_opt_use_inline_hashtags' );
	$hashtag_length        = get_option( 'tocp_opt_hashtag_length' );
	if ( $include_link != "false" ) {
		$permalink = get_permalink( $oldest_post );
		if ( $custom_url_option ) {
			$custom_url_field = get_option( 'tocp_opt_custom_url_field' );
			if ( trim( $custom_url_field ) != "" ) {
				$permalink = trim( get_post_meta( $post->ID, $custom_url_field, true ) );
			}
		}
		if ( $to_short_url ) {
			if ( $url_shortener == "bit.ly" ) {
				$bitly_key  = get_option( 'tocp_opt_bitly_key' );
				$bitly_user = get_option( 'tocp_opt_bitly_user' );
				$shorturl   = shorten_url( $permalink, $url_shortener, $bitly_key, $bitly_user );
			} else {
				$shorturl = shorten_url( $permalink, $url_shortener );
			}
		} else {
			$shorturl = $permalink;
		}
	}
	if ( $tweet_type == "title" || $tweet_type == "titlenbody" ) {
		$title = stripslashes( $post->post_title );
		$title = strip_tags( $title );
		$title = preg_replace( '/\s\s+/', ' ', $title );
	} else {
		$title = "";
	}
	if ( $tweet_type == "body" || $tweet_type == "titlenbody" ) {
		$body = stripslashes( $post->post_content );
		$body = strip_tags( $body );
		$body = preg_replace( '/\s\s+/', ' ', $body );
	} else {
		$body = "";
	}
	if ( $tweet_type == "titlenbody" ) {
		if ( $title == null ) {
			$content = $body;
		} elseif ( $body == null ) {
			$content = $title;
		} else {
			$content = $title . " - " . $body;
		}
	} elseif ( $tweet_type == "title" ) {
		$content = $title;
	} elseif ( $tweet_type == "body" ) {
		$content = $body;
	}
	if ( $additional_text != "" ) {
		if ( $additional_text_at == "end" ) {
			$content = $content . " - " . $additional_text;
		} elseif ( $additional_text_at == "beginning" ) {
			$content = $additional_text . ": " . $content;
		}
	}
	$hashtags   = "";
	$newcontent = "";
	if ( $custom_hashtag_option != "nohashtag" ) {
		if ( $custom_hashtag_option == "common" ) {
//common hashtag
			$hashtags = $twitter_hashtags;
		} //post custom field hashtag
		elseif ( $custom_hashtag_option == "custom" ) {
			if ( trim( $custom_hashtag_field ) != "" ) {
				$hashtags = trim( get_post_meta( $post->ID, $custom_hashtag_field, true ) );
			}
		} elseif ( $custom_hashtag_option == "categories" ) {
			$post_categories = get_the_category( $post->ID );
			if ( $post_categories ) {
				foreach ( $post_categories as $category ) {
					$tagname = str_replace( ".", "", str_replace( " ", "", $category->cat_name ) );
					if ( $use_inline_hashtags ) {
						if ( strrpos( $content, $tagname ) === false ) {
							$hashtags = $hashtags . "#" . $tagname . " ";
						} else {
							$newcontent = preg_replace( '/\b' . $tagname . '\b/i', "#" . $tagname, $content, 1 );
						}
					} else {
						$hashtags = $hashtags . "#" . $tagname . " ";
					}
				}
			}
		} elseif ( $custom_hashtag_option == "tags" ) {
			$post_tags = get_the_tags( $post->ID );
			if ( $post_tags ) {
				foreach ( $post_tags as $tag ) {
					$tagname = str_replace( ".", "", str_replace( " ", "", $tag->name ) );
					if ( $use_inline_hashtags ) {
						if ( strrpos( $content, $tagname ) === false ) {
							$hashtags = $hashtags . "#" . $tagname . " ";
						} else {
							$newcontent = preg_replace( '/\b' . $tagname . '\b/i', "#" . $tagname, $content, 1 );
						}
					} else {
						$hashtags = $hashtags . "#" . $tagname . " ";
					}
				}
			}
		}
		if ( $newcontent != "" ) {
			$content = $newcontent;
		}
	}
	if ( $include_link != "false" ) {
		if ( ! is_numeric( $shorturl ) && ( strncmp( $shorturl, "http", strlen( "http" ) ) == 0 ) ) {

		} else {
			return "OOPS!!! problem with your URL shortning service. Some signs of error " . $shorturl . ".";
		}
	}
	$message = set_tweet_length( $content, $shorturl, $hashtags, $hashtag_length );
	$status  = urlencode( stripslashes( urldecode( $message ) ) );
	if ( $status ) {
		$poststatus = tocp_update_status( $message );
		// echo '<pre>'. print_r($poststatus,true) . '</pre>';
		// exit;
		if ( $poststatus == true ) {
			return "Whoopie!!! Posted Successfully";
		} else {
			return "OOPS!!! there seems to be some problem while tweeting. Please try again.";
		}
	}

	return "OOPS!!! there seems to be some problem while tweeting. Try again. If problem is persistent mail the problem at support@nytogroup.com";
}

//send request to passed url and return the response
function send_request( $url, $method = 'GET', $data = '', $auth_user = '', $auth_pass = '' ) {
	$ch = curl_init( $url );
	if ( strtoupper( $method ) == "POST" ) {
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	}
	if ( ini_get( 'open_basedir' ) == '' && ini_get( 'safe_mode' ) == 'Off' ) {
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
	}
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	if ( $auth_user != '' && $auth_pass != '' ) {
		curl_setopt( $ch, CURLOPT_USERPWD, "{$auth_user}:{$auth_pass}" );
	}
	$response = curl_exec( $ch );
	$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	if ( $httpcode != 200 ) {
		return $httpcode;
	}

	return $response;
}

/* returns a result form url */
function tocp_curl_get_result( $url ) {
	$ch      = curl_init();
	$timeout = 5;
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	$data = curl_exec( $ch );
	curl_close( $ch );

	return $data;
}

function tocp_get_bitly_short_url( $url, $login, $appkey, $format = 'txt' ) {
	$connectURL = 'http://api.bit.ly/v3/shorten?login=' . $login . '&apiKey=' . $appkey . '&uri=' . urlencode( $url ) . '&format=' . $format;

	return tocp_curl_get_result( $connectURL );
}

//Shorten long URLs with is.gd or bit.ly.
function shorten_url( $the_url, $shortener = 'is.gd', $api_key = '', $user = '' ) {
	if ( ( $shortener == "bit.ly" ) && isset( $api_key ) && isset( $user ) ) {
		$response = tocp_get_bitly_short_url( $the_url, $user, $api_key );
	} elseif ( $shortener == "su.pr" ) {
		$url      = "http://su.pr/api/simpleshorten?url={$the_url}";
		$response = send_request( $url, 'GET' );
	} elseif ( $shortener == "tr.im" ) {
		$url      = "http://api.tr.im/api/trim_simple?url={$the_url}";
		$response = send_request( $url, 'GET' );
	} elseif ( $shortener == "3.ly" ) {
		$url      = "http://3.ly/?api=em5893833&u={$the_url}";
		$response = send_request( $url, 'GET' );
	} elseif ( $shortener == "tinyurl" ) {
		$url      = "http://tinyurl.com/api-create.php?url={$the_url}";
		$response = send_request( $url, 'GET' );
	} elseif ( $shortener == "u.nu" ) {
		$url      = "http://u.nu/unu-api-simple?url={$the_url}";
		$response = send_request( $url, 'GET' );
	} elseif ( $shortener == "1click.at" ) {
		$url      = "http://1click.at/api.php?action=shorturl&url={$the_url}&format=simple";
		$response = send_request( $url, 'GET' );
	} else {
		$url      = "http://is.gd/api.php?longurl={$the_url}";
		$response = send_request( $url, 'GET' );
	}

	return $response;
}

//Shrink a tweet and accompanying URL down to 140 chars.
function set_tweet_length( $message, $url, $twitter_hashtags = "", $hashtag_length = 0 ) {
	$tags           = $twitter_hashtags;
	$message_length = strlen( $message );
	$url_length     = strlen( $url );
	//$cur_length = strlen($tags);
	if ( $hashtag_length == 0 ) {
		$hashtag_length = strlen( $tags );
	}
	if ( $twitter_hashtags != "" ) {
		if ( strlen( $tags ) > $hashtag_length ) {
			$tags = substr( $tags, 0, $hashtag_length );
			$tags = substr( $tags, 0, strrpos( $tags, ' ' ) );
		}
		$hashtag_length = strlen( $tags );
	}
	if ( $message_length + $url_length + $hashtag_length > 140 ) {
		$shorten_message_to = 140 - $url_length - $hashtag_length;
		$shorten_message_to = $shorten_message_to - 4;
//$message = $message." ";
		if ( strlen( $message ) > $shorten_message_to ) {
			$message = substr( $message, 0, $shorten_message_to );
			$message = substr( $message, 0, strrpos( $message, ' ' ) );
		}
		$message = $message . "...";
	}

	return $message . " " . $url . " " . $tags;
}

//check time and update the last tweet time
function tocp_opt_update_time() {
	return tocp_to_update();

}

function tocp_to_update() {
	global $wpdb;
	$ret = 0;
	//prevention from caching
	$last = $wpdb->get_var( "select SQL_NO_CACHE option_value from $wpdb->options where option_name = 'tocp_opt_last_update';" );
	//$last = get_option('tocp_opt_last_update');
	$interval = get_option( 'tocp_opt_interval' );
	if ( ( trim( $last ) == '' ) || ! ( isset( $last ) ) ) {
		$last = 0;
	}
	if ( ! ( isset( $interval ) ) ) {
		$interval = tocp_opt_INTERVAL;
	} else if ( ! ( is_numeric( $interval ) ) ) {
		$interval = tocp_opt_INTERVAL;
	}
	$interval = $interval * 60 * 60;
	/*
	if (false === $last) {
		$ret = 1;
	} else if (is_numeric($last)) {
		$ret = ( (time() - $last) > ($interval ));
	}

	 */
	if ( is_numeric( $last ) ) {
		$ret = ( ( time() - $last ) > ( $interval ) );
	} else {
		$ret = 0;
	}

	return $ret;
}

function tocp_get_auth_url() {
	global $tocp_oauth;

	$tocp_oauth->refresh_api_tokens();
	$settings = tocp_get_settings();
	$token    = $tocp_oauth->get_request_token();
	if ( $token && isset($token['oauth_callback_confirmed']) && $token['oauth_callback_confirmed'] == true ) {

		$settings['oauth_request_token']        = $token['oauth_token'];
		$settings['oauth_request_token_secret'] = $token['oauth_token_secret'];
	    tocp_save_settings( $settings );

		//return 'https://api.twitter.com/oauth/authorize';
		/*echo $tocp_oauth->get_auth_url( $token['oauth_token'] );
		exit;*/
		return $tocp_oauth->get_auth_url( $token['oauth_token'] );
		
	
	}else{
		return false;
	}
	/*echo 'no token';
	exit;*/
}

function tocp_update_status( $new_status ) {
	global $tocp_oauth;
	$settings = tocp_get_settings();
	if ( isset( $settings['oauth_request_token'] ) && isset( $settings['oauth_request_token_secret'] ) ) {
		
		return $tocp_oauth->update_status( $settings['oauth_request_token'], $settings['oauth_request_token_secret'], $new_status );
	}

	return false;
}

function tocp_has_tokens() {
	$settings = tocp_get_settings();

	return ( $settings['oauth_request_token'] && $settings['oauth_request_token_secret'] );
}

function tocp_is_valid() {
return tocp_has_tokens();
}

function tocp_do_tweet( $post_id ) {
	$settings = tocp_get_settings();
	$message  = tocp_get_message( $post_id );
// If we have a valid message, Tweet it
// this will fail if the Tiny URL service is done
	if ( $message ) {
// If we successfully posted this to Twitter, then we can remove it from the queue eventually
		if ( tocp_update_status( $message ) ) {
			return true;
		}
	}

	return false;
}

function tocp_get_settings() {
	global $tocp_defaults;
	$settings           = $tocp_defaults;
	$wordpress_settings = get_option( 'tocp_settings' );
 
	if ( is_array($wordpress_settings) && count($wordpress_settings) > 1 ) {
		foreach ( $wordpress_settings as $key => $value ) {
			$settings[ $key ] = $value;
		}
	}

	return $settings;
}

function tocp_save_settings( $settings ) {
	update_option( 'tocp_settings', $settings );
}

function tocp_reset_settings() {
	delete_option( 'tocp_settings' );
	update_option( 'tocp_enable_log', '' );
	update_option( 'tocp_opt_add_text', '' );
	update_option( 'tocp_opt_add_text_at', 'beginning' );
	update_option( 'tocp_opt_age_limit', 30 );
	update_option( 'tocp_opt_bitly_key', '' );
	update_option( 'tocp_opt_bitly_user', '' );
	update_option( 'tocp_opt_custom_hashtag_field', '' );
	update_option( 'tocp_opt_custom_hashtag_option', 'nohashtag' );
	update_option( 'tocp_opt_custom_url_field', '' );
	update_option( 'tocp_opt_custom_url_option', '' );
//update_option('tocp_opt_excluded_post','');
	update_option( 'tocp_opt_hashtags', '' );
	update_option( 'tocp_opt_hashtag_length', '20' );
	update_option( 'tocp_opt_include_link', 'no' );
	update_option( 'tocp_opt_interval', 4 );
	delete_option( 'tocp_opt_last_update' );
	update_option( 'tocp_opt_max_age_limit', 60 );
	update_option( 'tocp_opt_omit_cats', '' );
	update_option( 'tocp_opt_tweet_type', 'title' );
	delete_option( 'tocp_opt_tweeted_posts' );
	update_option( 'tocp_opt_url_shortener', 'is.gd' );
	update_option( 'tocp_opt_use_inline_hashtags', '' );
	update_option( 'tocp_opt_use_url_shortner', '' );
	update_option( 'tocp_opt_admin_url', '' );
//wp_redirect(tocp_currentPageURL());
}

?>