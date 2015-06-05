<?php

class bp_social_connect_twitter extends bpc_config{

	var $fields = array(
		'id' => '',
		'name'=>'',
		'screen_name'=>'',
		'location'=>'',
		'description'=>'',
		'url'=>'',
		'followers_count'=>'',
		'friends_count'=>'',
		'time_zone'=>'',
		'lang'=>'',
		);
	var $twitter;
	var $twitter_url;
	var $twitter_meta_key = 'twitter_id';

	function __construct(){
		$this->settings = $this->get();
		add_action('bp_social_connect',array($this,'display_social_login'));
		add_action('init',array($this,'initialise'));
		add_action('login_init',array($this,'authorize'));
		add_action('template_redirect',array($this,'authorize'));
	}

	function initialise(){
		if($this->settings['twitter']){
			if (!session_id()){
				session_start();
			}
			require_once "twitteroauth.php";
			$this->twitter = new TwitterOAuth( $this->settings['twitter_consumer_key'] ,$this->settings['twitter_consumer_secret']);
		}
	}
	function simple_url(){
		$url = remove_query_arg( array( 'oauth_token', 'oauth_verifier' ), $this->get_current_url() );
		return $url;
	}
	function display_social_login(){
		if($this->settings['twitter']){
			$url = $this->settings['callback'];
			echo '<a id="bp_social_connect_twitter" href="'.$this->get_twitter_auth_url().'">'.__('TWITTER','bp-social-connect').'</a><br />';	
		}
	}

	function get_twitter_auth_url() {
			
		//if (!get_option('twitter_oauth_url')){
			$this->initialise();
			$request_token = $this->twitter->getRequestToken($this->settings['twitter_callback']);
			print_r($request_token);
			if(200 == $this->twitter->http_code){
				$_SESSION['twitter_oauth_token']=$request_token['oauth_token'];
				$_SESSION['twitter_oauth_token_secret']=$request_token['oauth_token'];
				$token = $request_token['oauth_token'];
				$this->twitter_url = $this->twitter->getAuthorizeURL( $token );
			}else{
				$this->twitter_url = '';
			}
			update_option('twitter_oauth_url', $this->twitter_url);
			return $this->twitter_url;
		//} else {
		//	return get_option('twitter_oauth_url');
		//}
	}

	function authorize(){ 

		if (isset($this->settings['twitter'])) { 
			
			if ( isset( $_REQUEST['oauth_verifier'] ) && isset( $_REQUEST['oauth_token'] ) ) { 
				
				$this->initialise();
				$oauth_token = $_SESSION['twitter_oauth_token'];
				$oauth_token_secret = $_SESSION['twitter_oauth_token_secret'];
				if(!isset($oauth_token)){
					$oauth_token = $_REQUEST['oauth_token'];
				}
				if( isset( $oauth_token ) && $oauth_token == $_REQUEST['oauth_token'] ){
				
					$this->twitter = new TwitterOAuth( $this->settings['twitter_consumer_key'] ,$this->settings['twitter_consumer_secret'], $oauth_token, $oauth_token_secret );
					$twitter_access_token = $this->twitter->getAccessToken($_REQUEST['oauth_verifier']);
					
					$_SESSION['twitter_oauth_token']=$twitter_access_token['oauth_token'];
					$_SESSION['twitter_oauth_token_secret']=$twitter_access_token['oauth_token_secret'];
					
					//Data Capture from Twitter 
					$twitter_user = (array)$this->twitter->get('account/verify_credentials');
					
					//if user data get successfully
					if (isset($twitter_user['id'])){

						//all data will assign to a session
						$_SESSION['twitter_user']=$twitter_user;

						foreach($this->fields as $key => $value){
							$this->fields[$key] = $twitter_user[$key];
						}
						$users = get_users(array(
							'meta_key'     => $this->twitter_meta_key,
							'meta_value'   => $twitter_user['id'],
							'meta_compare' => '='
						));
						if (isset($users[0]->ID) && is_numeric($users[0]->ID) ){
							$this->force_login($users[0]->user_email,false);
							die();
						} else {
							
							/*
							$random_password = wp_generate_password( 10, false );
						    $user_id = wp_create_user( $twitter_user['email'] , $random_password, $twitter_user['email'] );
						    //Add twitter user ID to User meta field
						    update_user_meta($user_id,$this->twitter_meta_key,$twitter_user['id']);

							if(isset($this->settings['twitter_map_fields']) && is_array($this->settings['twitter_map_fields'])){
						   	    if(count($this->settings['twitter_map_fields']['field'])){
						   	  	   foreach($this->settings['twitter_map_fields']['field'] as $fb_key => $fb_field){
						   	  	 		xprofile_set_field_data($this->settings['twitter_map_fields']['bpfield'][$fb_key],$user_id,$$fb_field);
						   	  	   }
						   	    }
						    }
							// Grab Image and set as 
						    $thumb = 'http://graph.facebook.com/'.$id.'/picture?width='.BP_AVATAR_THUMB_WIDTH.'&height='.BP_AVATAR_THUMB_HEIGHT;
						    $full = 'http://graph.facebook.com/'.$id.'/picture?width='.BP_AVATAR_FULL_WIDTH.'&height='.BP_AVATAR_FULL_HEIGHT;
						  	$this->grab_avatar($thumb,'thumb',$user_id);
						  	$this->grab_avatar($full,'full',$user_id);
						  	//Redirect JSON
						  	$this->force_login($email,false);
							update_user_meta($user_id,$this->twitter_meta_key,$twitter_user['id']);
							wp_redirect(site_url(),200);
							exit;
							*/
						}
					}
				}
			}
		}
	}					
						
}

