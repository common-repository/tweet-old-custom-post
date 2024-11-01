<?php

function tocp_DEBUG( $str ) {
	global $tocp_debug;
	$tocp_enable_log = get_option( 'tocp_enable_log' );
	if ( $tocp_enable_log ) {
		$tocp_debug->enable( true );
	}
	$tocp_debug->add_to_log( $str );
}

function tocp_is_debug_enabled() {
	global $tocp_debug;

	return $tocp_debug->is_enabled();
}

class topDebug {
	var $debug_file;
	var $log_messages;

	function topDebug() {
		$this->debug_file = false;
	}

	function is_enabled() {
		return ( $this->debug_file );
	}

	function enable( $enable_or_disable ) {
		if ( $enable_or_disable ) {
			//$this->debug_file   = fopen( WP_CONTENT_DIR . '/plugins/tweet-old-custom-post/log.txt', 'a+t' );
			$this->debug_file   = fopen( tocp_opt_plugin_dir . '/log.txt', 'a+t' );
			$this->log_messages = 0;
		} else if ( $this->debug_file ) {
			fclose( $this->debug_file );
			$this->debug_file = false;
		}
	}

	function add_to_log( $str ) {
		if ( $this->debug_file ) {
			$log_string = $str;
			// Write the data to the log file
			fwrite( $this->debug_file, sprintf( "%12s %s\n", time(), $log_string ) );
			fflush( $this->debug_file );
			$this->log_messages ++;
		}
	}
}

global $tocp_debug;
$tocp_debug = new topDebug();


?>
