<?php

//require_once "facebook.php";

class bp_social_connect_google extends bpc_config{

	var $settings;
	var $fields = array(
		'id' => '',
		'email' =>'',
		'name' =>'',
		'given_name' =>'',
		'family_name' =>'',
		'gender' =>'',
		'link' =>'',
		);
	var $google_meta_key = 'google_id';
	var $google;
	var $googleplus;
	var $googleoauth2;

	function __construct(){
		$this->settings = $this->get();
		add_action('bp_social_connect',array($this,'display_social_login'));
		add_action('template_redirect', array($this, 'google_authorize'));
		add_filter('bp_social_connect_google_fields',array($this,'map_fields'));
	}

	function verify(){
		if ( $this->settings['google'] && isset($this->settings['google_client_id']) && isset($this->settings['google_client_secret']) && isset($this->settings['google_redirect_uri']) ) {
			return true;
			if(!session_id())
				session_start();
		}else
			return false;
	}

	function map_fields($settings){
		$settings[]= array(
					'label' => __('Map Fields','vibe-customtypes'),
					'name' => 'google_map_fields',
					'fields' => $this->fields,
					'type' => 'bp_fields',
					'desc' => __('Map Google fields with BuddyPress','vibe-customtypes')
				);

		return $settings;
	}
	function display_social_login(){
		if(!$this->verify())
			return;
		
		echo '<a id="bp_social_connect_google" href="'.$this->get_google_auth_url().'">'.__('GOOGLE','bp-social-connect').'</a>';	
	}

	function load_google(){
			if(!$this->verify())
				return;

			if( !class_exists( 'apiClient' ) ) { // loads the Google class
				require_once ( 'src/apiClient.php' ); 
			}
			if( !class_exists( 'apiPlusService' ) ) { // Loads the google plus service class for user data
				require_once ( 'src/contrib/apiPlusService.php' ); 
			}
			if( !class_exists( 'apiOauth2Service' ) ) { // loads the google plus service class for user email
				require_once ( 'src/contrib/apiOauth2Service.php' ); 
			}
			
			// Google Objects
			$this->google = new apiClient();
			$this->google->setApplicationName( "Google+ PHP Starter Application" );
			$this->google->setClientId( $this->settings['google_client_id'] );
			$this->google->setClientSecret( $this->settings['google_client_secret'] );
			$this->google->setRedirectUri( $this->settings['google_redirect_uri'] );
			$this->google->setScopes( array( 'https://www.googleapis.com/auth/plus.me','https://www.googleapis.com/auth/userinfo.email' ) );
			
			$this->googleplus = new apiPlusService( $this->google ); // For getting user detail from google
			$this->googleoauth2 = new apiOauth2Service( $this->google ); // For gettting user email from google
			
			if (isset($_SESSION['google_token'])) {
				$this->google->setAccessToken($_SESSION['google_token']);
			}
		
	}

	function get_google_auth_url(){
			//load google class
			$google = $this->load_google();
			
			$url = $this->google->createAuthUrl();
			$authurl = isset( $url ) ? $url : '';
			
			return $authurl;
	}

	function google_authorize(){ 
		if(!$this->verify())
				return;


		if( isset( $_GET['code'] ) ) {
		
			//load google class
			$google = $this->load_google();

			if (isset($_SESSION['google_token'])) {
				$gplus_access_token = $_SESSION['google_token'];
			} else {
				$google_token = $this->google->authenticate();
				$_SESSION['google_token'] = $google_token;
				$gplus_access_token = $_SESSION['google_token'];
			}
			//check access token is set or not
			if ( !empty( $gplus_access_token ) ) {
				// capture data
				$user_info = $this->googleplus->people->get('me');
				$user_email = $this->googleoauth2->userinfo->get(); // to get email

				$user_info['email'] = $user_email['email'];
				foreach($this->fields as $key => $value){
					$this->fields[$key] = $user_email[$key];
				}

				//if user data get successfully
				if (isset($user_info['id'])){
					
					//all data will assign to a session
					$_SESSION['google_user_cache'] = $user_info;

					$users = get_users(array(
						'meta_key'     => $this->google_meta_key,
						'meta_value'   => $user_info['id'],
						'meta_compare' => '='
					));

					if (isset($users[0]->ID) && is_numeric($users[0]->ID) ){
						$user_id = $users[0]->ID;
						$this->force_login($users[0]->user_email,false);
						wp_redirect(site_url());
						die();
					} 

					$email = $this->fields['email'];

					if( email_exists( $email )) { // user is a member 
						  $user = get_user_by('email',$email ); 

						 //print_r($user->ID);
						  if (is_numeric($user->ID)){
						  $this->force_login($this->fields['email'] ,false);
						  wp_redirect(site_url());
						  update_user_meta($user->ID,$this->google_meta_key,$user_info['id']);
						  die();
						}
				    }else{ // Register this new user
					    $random_password = wp_generate_password( 10, false );
					    $user_id = wp_create_user( $email , $random_password, $email );
					    update_user_meta($user_id,$this->google_meta_key,$this->fields['id']);
					    wp_update_user(
					    	array(
					    		'ID' =>$user_id,
					    		'user_url'=> $this->fields['link'],
					    		'user_nicename'=>$this->fields['given_name'],
					    		'display_name'=>$this->fields['name'],
					    		)
					    	);
						if(isset($this->settings['google_map_fields']) && is_array($this->settings['google_map_fields'])){
					   	    if(count($this->settings['google_map_fields']['field'])){ 
					   	  	   foreach($this->settings['google_map_fields']['field'] as $g_key => $g_field){
					   	  	 		xprofile_set_field_data($this->settings['google_map_fields']['bpfield'][$g_key],$user_id,$this->fields[$g_field]);
					   	  	   }
					   	    }
					    }
						// Grab Image and set as 
					    $thumb = $user_email['picture'].'?sz='.BP_AVATAR_THUMB_WIDTH;
					    $full = $user_email['picture'].'?sz='.BP_AVATAR_FULL_WIDTH;
					  	$this->grab_avatar($thumb,'thumb',$user_id);
					  	$this->grab_avatar($full,'full',$user_id);
					  	//Redirect JSON
					  	$this->force_login($this->fields['email'],false);
					    wp_redirect(home_url());
					    die();
				    }
					
				}
			}
				
		}
	}//End Google authorise
}


