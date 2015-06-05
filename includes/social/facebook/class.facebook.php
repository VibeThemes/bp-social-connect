<?php

//require_once "facebook.php";

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
		$this->settings = $this->get();
		add_action('bp_social_connect',array($this,'display_social_login'));
		add_action('wp_ajax_nopriv_bp_social_connect_facebook_login',array($this,'bp_social_connect_facebook_login'));
		add_action('wp_ajax_bp_social_connect_facebook_login',array($this,'bp_social_connect_facebook_login'));
		add_filter('bp_social_connect_facebook_fields',array($this,'map_fields'));
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
		if(!$this->settings['facebook'])
			return;
		?>
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

		jQuery(document).ready(function($){
			$('#bp_social_connect_facebook').on('click',function(){

				var $this = $(this);
				$this.addClass('loading');
				var security = $('#<?php echo $this->security_key; ?>').val();
				FB.login(function(response){
					if (response.authResponse){
						FB.api('/me', function(response) {
							console.log(response);
							jQuery.ajax({
								url: ajaxurl,
								data: 'action=bp_social_connect_facebook_login&id='+response.id+'&username='+response.username+'&email='+response.email+'&first_name='+response.first_name+'&last_name='+response.last_name+'&gender='+response.gender+'&name='+response.name+'&link='+response.link+'&locale='+response.locale+'&security='+security,
								dataType: 'JSON',
								type: 'POST',
								success:function(data){
									$this.removeClass('loading');
									if(typeof data =='object'){
									  /* redirect after form */
									  window.location.href = "<?php echo site_url();?>";
									}else{
										console.log(data);
									}
								},
								error: function(){
									alert('Unable to process login');
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
		echo '<a id="bp_social_connect_facebook" href="javascript:void(0)">'.__('FACEBOOK','bp-social-connect').'</a><br />';	
	}


	function bp_social_connect_facebook_login(){


		if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],$this->settings['security']) ){
		    _e('Security check Failed. Contact Administrator.','vibe'); 
		    die();
		}

		if (!isset($_POST) || ($_POST['action'] != 'bp_social_connect_facebook_login') || !isset($_POST['id'])){
			_e('Invalid Post values','vibe'); 
			die();
		} 

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
				$this->force_login($users[0]->user_email,false);
				//Redirect JSON
				$return=json_encode($return);
				if(is_array($return)){ print_r($return); }else{ echo $return; }
				die();
			} 
		}


		if(!is_numeric($user_id)){
			//Check if facebook email is already being used by another user
			if( email_exists( $email )) { // user is a member 
				  $user = get_user_by('email',$email );
				  $user_id = $user->ID;
				  $this->force_login($user->user_email,false);
				  //Redirect JSON
				  $return=json_encode($return);
				  if(is_array($return)){ print_r($return); }else{ echo $return; } die;
				  die();
		    }else{ // Register this new user
			    $random_password = wp_generate_password( 10, false );
			    $user_id = wp_create_user( $email , $random_password, $email );
			    //Add facebook user ID to User meta field
			    update_user_meta($user_id,$this->facebook_meta_key,$id);

				if(isset($this->settings['facebook_map_fields']) && is_array($this->settings['facebook_map_fields'])){
			   	    if(count($this->settings['facebook_map_fields']['field'])){
			   	  	   foreach($this->settings['facebook_map_fields']['field'] as $fb_key => $fb_field){
			   	  	 		xprofile_set_field_data($this->settings['facebook_map_fields']['bpfield'][$fb_key],$user_id,$$fb_field);
			   	  	   }
			   	    }
			    }

			    wp_update_user(
					    	array(
					    		'ID'=>$user_id,
					    		'user_url'=> $link,
					    		'user_nicename'=>$first_name,
					    		'display_name'=>$name,
					    		)
					    	);
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
		    }
		}
	}
}