<?php 

class bp_social_connect_linkedin extends bpc_config{

	var $fields = array( 
			'id' => '',
			'email' =>'',
			'first_name' =>'',
			'last_name' =>'',
			'name' =>'',
		);


	var $linkedin_meta_key = 'linkedin_id';
	var $debug 			  		  = false;
    var $debug_http 		      = true;
    var $redirect_uri 	  		  = '';
    var $server 			      = 'LinkedIn';
    var $client_id 		  		  = '';
    var $client_secret 	  		  = '';
    var $scope 			  		  = array(); 
    var $authorize_url 	  		  = '';
    var $token_url 		  		  = '';
    var $api_base_url 	  		  = '';

    //-
    var $sign_token_name          = "oauth2_access_token";
	var $decode_json              = false;
	var $curl_time_out            = 30;
	var $curl_connect_time_out    = 30;
	var $curl_ssl_verifypeer      = false;
	var $curl_header              = array();
	var $curl_useragent           = "OAuth/2 Simple PHP Client v0.1; HybridAuth http://hybridauth.sourceforge.net/";
	var $curl_authenticate_method = "POST";
    var $curl_proxy               = null;

	const _AUTHORIZE_URL = 'https://www.linkedin.com/uas/oauth2/authorization';

    const _TOKEN_URL = 'https://www.linkedin.com/uas/oauth2/accessToken';

    const _BASE_URL = 'https://api.linkedin.com/v2';

	function __construct(){
		$this->settings = $this->get();
		if($this->settings['linkedin']){

		    $this->redirect_uri 	  		  = wp_login_url().'?login=linkedin';
		    $this->client_id 		  		  = $this->settings['linkedin_consumer_key'];
		    $this->client_secret 	  		  = $this->settings['linkedin_consumer_secret'];
		    $this->scope 			  		  = ['r_liteprofile', 'r_emailaddress'];  //NEED TO CHECK
	        $this->authorize_url 	  		  = self::_AUTHORIZE_URL;
	        $this->token_url 		  		  = self::_TOKEN_URL;
	        $this->api_base_url 	  		  = self::_BASE_URL;
		}

		add_action('bp_social_connect',array($this,'display_social_login'));
		add_action('login_init',array($this,'process_login'));
		add_action('init', array($this, 'process_login'));
		add_action('template_redirect',array($this,'process_login'));
		add_filter('bp_social_connect_linkedin_fields',array($this,'map_fields'));
	}

	function map_fields($settings){
		$settings[]= array(
					'label' => __('Map Fields','bp-social-connect'),
					'name' => 'linkedin_map_fields',
					'fields' => $this->fields,
					'type' => 'bp_fields',
					'desc' => __('Map Linkedin fields with BuddyPress','bp-social-connect')
				);

		return $settings;
	}

	function authenticate( $code ){
		$params = array(
			"client_id"     => $this->client_id,
			"client_secret" => $this->client_secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->redirect_uri,
			"code"          => $code
		);
	
		$response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );
		
		$response = $this->parseRequestResult( $response );

		if( ! $response || ! isset( $response->access_token ) ){
			throw new Exception( "The Authorization Service has return: " . $response->error );
		}

		if( isset( $response->access_token  ) )  $this->access_token           = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->refresh_token           = $response->refresh_token; 
		if( isset( $response->expires_in    ) ) $this->access_token_expires_in = $response->expires_in; 
		
		if( isset($response->expires_in)) {
			$this->access_token_expires_at = time() + $response->expires_in;
		}

		return $response;  
	}

	// -- utilities
	function request( $url, $params=false, $type="GET" ){
        $args = array(
            'timeout'   => $this->curl_time_out,
            'user-agent' => $this->curl_useragent,
            'sslverify' => $this->curl_ssl_verifypeer,
            'headers' => $this->curl_header,
        );

        if ($type == "GET") {                
            $url = $url . ( strpos($url, '?') ? '&' : '?' ) . http_build_query($params);
            $request = wp_remote_get( $url, $args );
        }
        
        if ($type == "POST") {
            if($params){
                $args['body'] = $params;
            }

            $request = wp_remote_post( $url, $args );                
        }
        
        $response_code = wp_remote_retrieve_response_code( $request );
        
        if ( ! is_wp_error( $request ) && 200 == $response_code ) {                
            $response = wp_remote_retrieve_body( $request );

            if( ! $response ){
                    return new WP_Error( 'http_request_failed', 'Nodata!' );
            }
        }else{                
            $response_message = wp_remote_retrieve_response_message( $request );

            if ( ! empty( $response_message ) ){
                return new WP_Error( $response_code, $response_message );

            }else{
                return new WP_Error( $response_code, 'Unknown error!' );

            }
        }

		return $response; 
	}


	function parseRequestResult( $result ){
		if( json_decode( $result ) ) return json_decode( $result );

		parse_str( $result, $ouput ); 

		$result = new StdClass();

		foreach( $ouput as $k => $v )
			$result->$k = $v;

		return $result;
	}

	function api( $url, $method = "GET", $parameters = array() ){

		if ( strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0 ) {
			$url = $this->api_base_url . $url;
		}

		$parameters[$this->sign_token_name] = $this->access_token;
		$response = null;

		switch( $method ){
			case 'GET'  : $response = $this->request( $url, $parameters, "GET"  ); break; 
			case 'POST' : $response = $this->request( $url, $parameters, "POST" ); break;
		}

		if( $response && $this->decode_json ){
			$response = json_decode( $response ); 
		}

		return $response; 
	}
	// GET REQUEST
	function api_get( $url, $parameters = array() )
	{
		return $this->api( $url, 'GET', $parameters ); 
	} 

	//POST REQUEST
	function api_post( $url, $parameters = array() )
	{
		return $this->api( $url, 'POST', $parameters ); 
	}

	function display_social_login(){

		if($this->settings['linkedin']){

			echo '<style>
				a.bp_social_connect_linkedin {
				    background: #41b2f1;
				    padding: 15px 15px 5px 15px !important;
				    text-decoration: none;
				    color: #FFF;
				    margin-top: 20px;
				    display: inline-block;
				}

				a.bp_social_connect_linkedin img {
				     vertical-align: middle;
				     float: left;
				     margin-right:20px;
				}
				a.bp_social_connect_linkedin span{
					    background: none !important;
					    padding: 0px !important;
					    line-height: 2 !important;

				}
			</style>';
			echo '<a class="bp_social_connect_linkedin" href='.$this->get_linkedin_auth_url().'><img
	            src="'.plugins_url( 'linkedin-icon.png',__FILE__ ).'" /><span>'.__('Login with LinkedIn','bp-social-connect').'</span></a>';
        }
	}

	function get_linkedin_auth_url($redirect = false){
		$scope = implode(' ', $this->scope);
		$authorize_url = $this->linkedinAuthUrl(array('scope' => $scope));  
        return $authorize_url;
	}

	function linkedinAuthUrl( $extras = array() ){
		$params = array(
			"client_id"     => $this->client_id,
			"redirect_uri"  => $this->redirect_uri,
			"response_type" => "code"
		);

		if( count($extras) )
			foreach( $extras as $k=>$v )
				$params[$k] = $v;

		return $this->authorize_url . "?" . http_build_query( $params );
	}


	function process_login() {

// CHECK SIGN IN
        if (!$this->is_signin()) {
            return;
        }

// If USER DENIED ACCESS, REDIRECT TO HOME URL
        if (isset($_REQUEST['error']) && $_REQUEST['error'] == 'user_cancelled_login') {
            // REDIRECT TO LOGIN URL
            wp_redirect(wp_login_url());
        }

// GET PROFILE DATA
        $xml = $this->get_linkedin_data();
        $linkedin_id = (string) $xml->{'id'};
        $email = (string) $xml->{'email'};

//SET FIELD'S VALUE
        foreach($this->fields as $key => $value){
			$this->fields[$key] = $xml->{$key};
		}

		if(empty($this->fields['name'])){
	    	$this->fields['name'] = $xml->{'first_name'}.' '.$xml->{'last_name'} ;
	    }

//CHECKING FOR LINKEDIN ID, IF EXIST THEN FORCE LOGIN
        $users = get_users(array(
					'meta_key'     => $this->linkedin_meta_key,
					'meta_value'   => $linkedin_id,
					'meta_compare' => '='
				));

		if (isset($users[0]->ID) && is_numeric($users[0]->ID) ){
			$user_id = $users[0]->ID;
			$this->force_login($users[0]->user_email,false);
			$login_redirect = $this->get_login_redirect($user_id);
			wp_redirect($login_redirect);
			die();
		} 
//IF LINKEDIN ID DOESNT EXIST, CHECK FOR EMAIL AND FORCE LOGIN BY EMAIL

        if( email_exists( $email )) { // user is a member 
			$user = get_user_by('email',$email ); 
			//print_r($user->ID);
			if (is_numeric($user->ID)){
				$this->force_login($this->fields['email'] ,false);
				$login_redirect = $this->get_login_redirect($user->ID);
				wp_redirect($login_redirect);
				update_user_meta($user->ID,$this->linkedin_meta_key,$linkedin_id);
				die();
			}
	    }else{ 

// REGISTER NEW USER
		    $user_login = apply_filters( 'bp_social_connect_user_login_name', $email ,$this->fields);
		    $user_login .=rand(0,999);
		    $user_id = register_new_user($user_login, $email);

		    if ( !is_wp_error($user_id) && is_numeric($user_id)) {
			    update_user_meta($user_id,$this->linkedin_meta_key,$this->fields['id']);
			    wp_update_user(
			    	array(
			    		'ID' =>$user_id,
			    		'display_name'=>$this->fields['name'],
			    		)
			    	);
				if(isset($this->settings['linkedin_map_fields']) && is_array($this->settings['linkedin_map_fields'])){
			   	    if(count($this->settings['linkedin_map_fields']['field'])){ 
			   	  	   foreach($this->settings['linkedin_map_fields']['field'] as $l_key => $l_field){
			   	  	 		xprofile_set_field_data($this->settings['linkedin_map_fields']['bpfield'][$l_key],$user_id,$this->fields[$l_field]);
			   	  	   }
			   	    }
			    }

// SET PROFILE IMAGE
			    $thumb = $xml->{'picture_url'}.'&width='.BP_AVATAR_THUMB_WIDTH.'&height='.BP_AVATAR_THUMB_HEIGHT;
			    $full = $xml->{'picture_url'}.'&width='.BP_AVATAR_FULL_WIDTH.'&height='.BP_AVATAR_FULL_HEIGHT;

			  	$this->grab_avatar($thumb,'thumb',$user_id);
			  	$this->grab_avatar($full,'full',$user_id);

			  	$this->force_login($this->fields['email'],false);
				$login_redirect = $this->get_login_redirect($user_id);
				wp_redirect($login_redirect); 
				die();
			}
		    wp_redirect(home_url());
		    die();
	    }
    }	

    function get_login_redirect($user_id){
    	$redirect_array = apply_filters('wplms_redirect_location',array(
					'home' => $url,
					'profile' => bp_core_get_user_domain($user_id),
					'mycourses' => bp_core_get_user_domain($user_id).'/'.BP_COURSE_SLUG,
					'instructing_courses' => bp_core_get_user_domain($user_id).'/'.BP_COURSE_SLUG.'/'.BP_COURSE_INSTRUCTOR_SLUG,
					'dashboard' => bp_core_get_user_domain($user_id).'/'.(defined('WPLMS_DASHBOARD_SLUG')?WPLMS_DASHBOARD_SLUG:'dashboard'),
					'same' => '',
					));
	    $lms_settings = get_option('lms_settings');
	    $redirect_index = $lms_settings['general']['student_login_redirect'];
	    return $redirect_array[$redirect_index];
    }

	function is_signin() { 

        if (!isset($_REQUEST['login']) || ($_REQUEST['login'] != "linkedin")) {
            return false;
        }

//IF CODE AND ERROR IS NOT RETURN THEN OAUTH WILL NOT WORK PROPERLY
        if (!isset($_REQUEST['code']) && !isset($_REQUEST['error'])) {
            return false;
        }

        return true;

    }

    function get_linkedin_data() {
        $this->curl_authenticate_method = 'GET';

        if (isset($_REQUEST['error'])) {
            wp_redirect(wp_login_url());
        }
        
// GENERATE CODE AND REQUEST ACCESS TOKEN
        $response = $this->authenticate($_REQUEST['code']);
        $this->access_token = $response->{'access_token'};

// GETTING PROFILE DATA
        $xml = $this->api_get('https://api.linkedin.com/v2/me?projection=(id,firstName,lastName,positions,profilePicture(displayImage~:playableStreams))');

//GETTING EMAIL DATA
        $email_data = $this->api_get('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))');

		$email_data = json_decode($email_data);
		$xml = json_decode($xml);
		$locale_lastName = $xml->lastName->preferredLocale->language.'_'.$xml->lastName->preferredLocale->country;
		$lastName = $xml->lastName->localized->$locale_lastName;

		$locale_firstName = $xml->firstName->preferredLocale->language.'_'.$xml->firstName->preferredLocale->country;
		$firstName = $xml->firstName->localized->$locale_firstName;

		$data = [
			'id'=>$xml->id,
			'first_name'=>$firstName,
			'last_name'=>$lastName,
			'email'=>$email_data->elements[0]->{'handle~'}->{'emailAddress'},
			'picture_url'=>$xml->profilePicture->{'displayImage~'}->elements[0]->identifiers[0]->identifier,
		];

        return (object)$data;
    }	
						
}

