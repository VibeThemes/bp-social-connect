<?php

 if ( ! defined( 'ABSPATH' ) ) exit;

class bp_social_connect_facebook extends bpc_config{

	var $fields = array(
		'id' => '',
		'email' =>'',
		'first_name' =>'',
		'gender' =>'',
		'last_name' =>'',
		'link' =>'',
		'locale' =>'',
		'name' =>'',
		'timezome' =>'',
		'updated_time' =>'',
		'verified' => '',
		);

	var $facebook_meta_key = 'facebook_id';
	function __construct(){
		
		add_action( 'login_enqueue_scripts',array($this,'login_enqueue'));
		add_action('bp_social_connect',array($this,'display_social_login'));
		add_action('wp_ajax_nopriv_bp_social_connect_facebook_login',array($this,'bp_social_connect_facebook_login'));
		add_action('wp_ajax_bp_social_connect_facebook_login',array($this,'bp_social_connect_facebook_login'));
		add_filter('bp_social_connect_facebook_fields',array($this,'map_fields'));
	}

	function login_enqueue(){
		wp_enqueue_script('jquery');
	}

	function get_settings(){
		if(empty($this->settings)){
			$this->settings = $this->get();
		}
	}

	function map_fields($settings){
		$settings[]= array(
					'label' => __('Map Fields','vibe-customtypes'),
					'name' => 'facebook_map_fields',
					'fields' => $this->fields,
					'type' => 'bp_fields',
					'desc' => __('Map Facebook fields with BuddyPress','vibe-customtypes')
				);

		return $settings;
	}

	function display_social_login(){
		$this->get_settings();

		if(empty($this->settings['facebook']))
			return;
		

		$fb_keys='';
		?>
		<style>
			a.bp_social_connect_facebook:before { content: ""!important; width: 16px; height: 16px; background: url(<?php echo plugins_url( '../../../assets/images/fb_logo.png',__FILE__ );?>); background-size: contain; opacity: 1 !important; }
		</style>
		<div id="fb-root" class="bp_social_connect_fb"></div>
		<script type="text/javascript">
		window.fbAsyncInit = function() {
			FB.init({
				appId      : "<?php echo $this->settings['facebook_app_id']; ?>", // replace your app id here
				status     : true, 
				cookie     : true, 
				xfbml      : true,
				version    : 'v2.0'  
			});
			FB.Event.subscribe('auth.authResponseChange', function(response){
				
				if (response.status === 'connected'){
					 console.log('success');
				}else if (response.status === 'not_authorized'){
					console.log('failed');
				} else{
					console.log('unknown error');
				}
			});
		};
		(function(d){
			var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement('script'); js.id = id; js.async = true;
			js.src = "//connect.facebook.net/en_US/all.js";
			ref.parentNode.insertBefore(js, ref);
		}(document));
		<?php
		if(isset($this->settings['facebook_map_fields']) && is_array($this->settings['facebook_map_fields'])){
			if(count($this->settings['facebook_map_fields']['field'])){
				$fields = array('email','link','first_name','name');
				foreach($this->settings['facebook_map_fields']['field'] as $field){
					if(!in_Array($field,$fields)){
						$fields[]=$field;
					}
				}
	   	  	    $fb_keys = '?fields='.implode(',',$fields);
	   	  	}else{
	   	  		$fb_keys = '?fields=email,link,first_name,name';
	   	  	}
   	  	}
		?>
		jQuery(document).ready(function($){
			$('.bp_social_connect_facebook').unbind('click');
			$('.bp_social_connect_facebook').on('click',function(){
					var $this = $(this);
					$this.addClass('loading');
					var security = $('#<?php echo $this->security_key; ?>').val();
					FB.login(function(res){
						if (res && res.authResponse){

							FB.api('/me<?php echo $fb_keys;?>', function(response) {
								$.ajax({
									url: ajaxurl,
									data: 'action=bp_social_connect_facebook_login&id='+response.id+'&email='+response.email+'&first_name='+response.first_name+'&last_name='+response.last_name+'&gender='+response.gender+'&name='+response.name+'&link='+response.link+'&locale='+response.locale+'&accessToken='+res.authResponse.accessToken+'&fbuid='+res.authResponse.id+'&security='+security,
									type: 'POST',
									dataType: 'JSON',
									success:function(data){
										$this.removeClass('loading');
										
										if (data.redirect_uri){
											if (data.redirect_uri =='refresh') {
												window.location.href =jQuery(location).attr('href');
											} else {
												window.location.href = data.redirect_uri;
											}
										}else{
											window.location.href = "<?php echo home_url();?>";
										}
									},
									error: function(xhr, ajaxOptions, thrownError) {
										$this.removeClass('loading');
										window.location.href = "<?php echo home_url();?>";
									}
								});
							
							});
						}else{

						}
					}, {scope: 'email,user_likes', return_scopes: true});
			});		
		});
		</script>
		<?php
		echo '<a class="bp_social_connect_facebook" href="javascript:void(0)">'.__('FACEBOOK','bp-social-connect').'</a>';	
	}


	function bp_social_connect_facebook_login(){

		$this->get_settings();
		if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],$this->settings['security']) ){
		    _e('Security check Failed. Contact Administrator.','bp-social-connect'); 
		    die();
		}

		if (!isset($_POST) || ($_POST['action'] != 'bp_social_connect_facebook_login') || !isset($_POST['id'])){
			_e('Invalid Post values','bp-social-connect'); 
			die();
		} 

		if(!empty($_POST['accessToken'])){

			https://graph.facebook.com/USER_ID/access_token=xxxxxxxxxxxxxxxxx
			$reverify = wp_remote_get(esc_url_raw('https://graph.facebook.com/'.$_POST['id'].'?fields=email&access_token='.$_POST['accessToken']));
			$body = wp_remote_retrieve_body($reverify);
			$body = json_decode($body,true);
			if(!empty($body['error']) || empty($body['email']) || !is_email($_POST['email']) || sanitize_email($body['email']) != sanitize_email($_POST['email'])){
				_e('Invalid access token','bp-social-connect'); 
				die();
			}
			if(empty($body['id']) || $body['id'] != $this->settings['facebook_app_id']){
				_e('Invalid access token','bp-social-connect'); 
				die();
			}
			
		}else{
			_e('Invalid access token','bp-social-connect'); 
			die();
		}

		//Verify if call is from Facebook !
		extract($_POST);
		
		$user_id = '';
		$return = array();

		//Check if facebook ID already connected to any User

		if (isset($id) && $id != '' && $id != 'undefined'){ 
			$users = get_users(array(
				'meta_key'     => $this->facebook_meta_key,
				'meta_value'   => $id,
				'meta_compare' => '='
			));
			if (isset($users[0]->ID) && is_numeric($users[0]->ID) ){ 
				$user_id = $users[0]->ID;
				$wpuser = $this->force_login($users[0]->user_email,false);
				if(is_wp_error($wpuser)){
					$message = $wpuser->get_error_message();
					$return = array('redirect_uri'=>wp_login_url(),'message'=>$message);
					echo json_encode($return);
					die();
				}else{
					//Redirect JSON
					$redirect_url = $this->settings['redirect_link'];
					echo $redirect_url;
					$url = apply_filters('login_redirect',$redirect_url,home_url(),$wpuser);
					$return=json_encode(array('redirect_uri'=>$url,'message'=>'success1'));
					if(is_array($return)){ print_r($return); }else{ echo $return; }
					die();
				}
			} 
			
		}


		if(!is_numeric($user_id)){ 
			//Check if facebook email is already being used by another user
			if( email_exists( $email )) { // user is a member 
				$user = $this->force_login($email ,false);
				if(is_wp_error($user)){
					$message = $user->get_error_message();
					$return = array('redirect_uri'=>wp_login_url(),'message'=>$message);
					echo json_encode($return);
					die();
				}else{
			  	  $user_id = $user->ID;
				  //Redirect JSON
				  $redirect_url = $this->settings['redirect_link'];
				  $url = apply_filters('login_redirect',$redirect_url,home_url(),$user);
				  $return=json_encode(array('redirect_uri'=>$url,'message'=>'success2'));
				  if(is_array($return)){ print_r($return); }else{ echo $return; }
				}
				die();
		    }else{ // Register this new user 
			    
			    $user_login = apply_filters( 'bp_social_connect_user_login_name', $email ,$_POST);
			    $user_login .=rand(0,999);
			    $user_id = register_new_user($user_login, $email);
		    	if ( !is_wp_error($user_id) && is_numeric($user_id)) {
					
				    if(empty($first_name)){
				    	$first_name = $email;
				    }
				    wp_update_user(
			    	array(
			    		'ID'=>$user_id,
			    		'user_url'=> $link,
			    		'display_name'=>$name,
			    		)
			    	);
				    //Add facebook user ID to User meta field
				    update_user_meta($user_id,$this->facebook_meta_key,$id);

					if(isset($this->settings['facebook_map_fields']) && is_array($this->settings['facebook_map_fields'])){
				   	    if(count($this->settings['facebook_map_fields']['field'])){
				   	  	   foreach($this->settings['facebook_map_fields']['field'] as $fb_key => $fb_field){
				   	  	 		xprofile_set_field_data($this->settings['facebook_map_fields']['bpfield'][$fb_key],$user_id,$$fb_field);
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
				  	$return=json_encode($return);
					if(is_array($return)){ print_r($return); }else{ echo $return; } die;
				  	
				  	die();
				}else{
					_e('User not created','bp-social-connect');
				}
		    }
		}
	}
}