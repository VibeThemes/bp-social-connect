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
		add_filter('bp_social_connect_twitter_fields',array($this,'map_fields'));
	}

	function map_fields($settings){
		$settings[]= array(
					'label' => __('Map Fields','vibe-customtypes'),
					'name' => 'twitter_map_fields',
					'fields' => $this->fields,
					'type' => 'bp_fields',
					'desc' => __('Map Twitter fields with BuddyPress','vibe-customtypes')
				);

		return $settings;
	}
	function initialise(){
		if($this->settings['twitter']){
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
		//if (!$_SESSION['twitter_oauth_url']){

			$this->initialise();
			$request_token = $this->twitter->getRequestToken($this->settings['twitter_callback']);
			
			if(200 == $this->twitter->http_code){
				set_transient('twitter_oauth_token',$request_token['oauth_token'],time()+3600);
				set_transient('twitter_oauth_token_secret',$request_token['oauth_token_secret'],time()+3600);
				//$_SESSION['twitter_oauth_token']=$request_token['oauth_token'];
				//$_SESSION['twitter_oauth_token_secret']=$request_token['oauth_token'];
				$token = $request_token['oauth_token'];
				$this->twitter_url = $this->twitter->getAuthorizeURL( $token );
				set_transient('twitter_oauth_url',$this->twitter_url,time()+3600);
				//$_SESSION['twitter_oauth_url'] = $this->twitter_url;
			}else{
				$this->twitter_url = '';
			}
			return $this->twitter_url;
		//} else {
		//	return $_SESSION['twitter_oauth_url'];
		//}
	}

	function authorize(){ 

		if (isset($this->settings['twitter'])) { 
			
			if ( isset( $_REQUEST['oauth_verifier'] ) && isset( $_REQUEST['oauth_token'] ) ) { 

				$this->initialise();
				$oauth_token = get_transient('twitter_oauth_token');//$_SESSION['twitter_oauth_token'];
				$oauth_token_secret = get_transient('twitter_oauth_token_secret');//$_SESSION['twitter_oauth_token_secret'];

				if( isset( $oauth_token ) && $oauth_token == $_REQUEST['oauth_token'] ){ 
					$this->twitter = new TwitterOAuth( $this->settings['twitter_consumer_key'] ,$this->settings['twitter_consumer_secret'], $oauth_token, $oauth_token_secret );

					$twitter_access_token = $this->twitter->getAccessToken($_REQUEST['oauth_verifier']);
					
					set_transient('twitter_oauth_token',$twitter_access_token['oauth_token'],time()+3600);
					set_transient('twitter_oauth_token_secret',$twitter_access_token['oauth_token_secret'],time()+3600);
					//$_SESSION['twitter_oauth_token']=$twitter_access_token['oauth_token'];
					//$_SESSION['twitter_oauth_token_secret']=$twitter_access_token['oauth_token_secret'];
					
					//Data Capture from Twitter 
					$twitter_user = (array)$this->twitter->get('account/verify_credentials');

					//if user data get successfully
					if (isset($twitter_user['id'])){

						//all data will assign to a session
						//$_SESSION['twitter_user']=$twitter_user;

						foreach($this->fields as $key => $value){
							$this->fields[$key] = $twitter_user[$key];
						}
						$users = get_users(array(
							'meta_key'     => $this->twitter_meta_key,
							'meta_value'   => $twitter_user['id'],
							'meta_compare' => '='
						));
						

						if (isset($users[0]->ID) && is_numeric($users[0]->ID) ){	
							//$_SESSION['user_login'] = $users[0]->user_login;
							$this->force_login_user($users[0]->user_login,false);
							wp_safe_redirect(home_url());
							exit;
						} else {
							$user_login = $this->generate_username($twitter_user['screen_name']);
							$random_password = strtolower($user_login).'@123';
						    $user_id = wp_create_user($user_login, $random_password);
						    //Add twitter user ID to User meta field
						    update_user_meta($user_id,$this->twitter_meta_key,$twitter_user['id']);

							if(isset($this->settings['twitter_map_fields']) && is_array($this->settings['twitter_map_fields'])){
						   	    if(count($this->settings['twitter_map_fields']['field'])){
						   	  	   foreach($this->settings['twitter_map_fields']['field'] as $tw_key => $tw_field){
						   	  	 		xprofile_set_field_data($this->settings['twitter_map_fields']['bpfield'][$tw_key],$user_id,$this->fields[$tw_field]);
						   	  	   }
						   	    }
						    }
							// Grab Image and set as 
						    $thumb = str_replace('_normal', '_bigger',$twitter_user['profile_image_url']);
						    $full = str_replace('_normal', '',$twitter_user['profile_image_url']);
						  	$this->grab_avatar($thumb,'thumb',$user_id);
						  	$this->grab_avatar($full,'full',$user_id);
						  	wp_update_user(
					    	array(
					    		'ID'=>$user_id,
					    		'user_url'=> $this->fields['url'],
					    		'user_nicename'=>$this->fields['screen_name'],
					    		'display_name'=>$this->fields['name'],
					    		)
					    	);
						  	//Redirect JSON
						  	$this->force_login_user($user_login ,false);
							wp_safe_redirect(home_url());
							exit;
							
						}
					}
				}
			}
		}
	}					
						
}

