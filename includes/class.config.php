<?php

abstract class bpc_config{

	var $version = '1.0';
	var $option = 'bp_social_connect';
	var $security_key = 'bp_social_connect_security';
	var $social_options = array(
		'facebook',
		'google',
		'twitter',
		'linkedin'
		);
	function get_version(){
		return $this->version;
	}
	function get_social_options(){
		return $social_options;
	}
	function get(){
		return get_option($this->option);
	}

	function put($value){
		update_option($this->option,$value);
	}

	function get_current_url(){
    	global $post;
		if ( is_front_page() ) :
			$page_url = home_url();
			else :
			$page_url = 'http';
		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" )
			$page_url .= "s";
				$page_url .= "://";
				if ( $_SERVER["SERVER_PORT"] != "80" )
			$page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
				else
			$page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			endif;
			
		return esc_url( $page_url );
	}
	
	function grab_avatar($link,$type,$user_id){
		$base_dir=wp_upload_dir();
		$dir = $base_dir['basedir'].'/avatars/'.$user_id;
		is_dir($dir) || @mkdir($dir) || die(__("Can't Create folder","bp_social_connect"));
		if($type=='thumb'){
			copy($link, $dir .'/'.$user_id.'-bpthumb.jpg');	
		}else if($type=='full'){
			copy($link, $dir .'/'.$user_id.'-bpfull.jpg');
		}
	}
	function generate_username($username){
		if(username_exists($username)){
			$rand = rand(1,9);
			$username .= $rand;
			if(username_exists($username)){
				$rand = rand(1,9);
				$username .= $rand;
				if(username_exists($username)){
					$rand = rand(1,9);
					$username .= $rand;
				}
			}
		}
		return $username;
	}
	function force_login( $user_email, $remember=true ) { 
		ob_start();
		if ( !is_user_logged_in() ) {
			$user = get_user_by('email', $user_email );
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID, $remember );
			do_action( 'wp_login', $user->user_login );
		} else {
			wp_logout();
			$user = get_user_by('email', $user_email );
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID, $remember );
			do_action( 'wp_login', $user->user_login );
		}
		ob_end_clean();
	}
	function force_login_user( $username, $remember=true ) { 
		ob_start();
		if ( !is_user_logged_in() ) {
			$user = get_user_by('login', $username );
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID, $remember );
			do_action( 'wp_login', $user->user_login );
		} else {
			wp_logout();
			$user = get_user_by('login', $username );
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID, $remember );
			do_action( 'wp_login', $user->user_login );
		}
		ob_end_clean();
		return;
	}
}