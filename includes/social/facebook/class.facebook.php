<?php

require_once "facebook.php";

class bp_social_connect_facebook extends bpc_config{

	var $fields = array(
		'id' => '',
		'email'=> '',
		'first_name'=> '',
		'gender'=> '',
		'last_name'=> '',
		'link'=> '',
		'locale'=> '',
		'name'=> '',
		'timezone'=> '',
		'updated_time'=> '',
		'verified'=> '',
		);


	function __construct(){
		$this->settings = $this->get();
		add_action('login_footer',array($this,'display_social_login'));
		add_action('init',array($this,'fb_login_validate'),100);
	}



	function display_social_login(){

		include_once dirname( __FILE__ ) . '/facebook/facebook.php';

		?>
		<script type="text/javascript">
		window.fbAsyncInit = function() {
			FB.init({
			appId      : "<?php echo $this->settings['facebook_app_id']; ?>", // replace your app id here
			status     : true, 
			cookie     : true, 
			xfbml      : true  
			});
		};
		(function(d){
			var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement('script'); js.id = id; js.async = true;
			js.src = "//connect.facebook.net/en_US/all.js";
			ref.parentNode.insertBefore(js, ref);
		}(document));

		function FBLogin(){
			FB.login(function(response){
				if(response.authResponse){
					window.location.href = "<?php echo site_url();?>?login=facebook";
				}
			}, {scope: 'email,user_likes'});
		}		
		</script>
		<?php
		echo '<a id="facebook" href="javascript:void(0)" onClick="FBLogin();">'.__('FACEBOOK','bp-social-connect').'</a>';	
	}

	function fb_login_validate(){
		
		if(isset($_REQUEST['login']) and $_REQUEST['login'] == "facebook"){ 
			global $wpdb;
			$facebook   = new Facebook(array(
				'appId' => $this->settings['facebook_app_id'],
				'secret' => $this->settings['facebook_app_secret'],
				'cookie' => TRUE,
				'oath'   => TRUE  /* Optional */
			));
			$fbuser = $facebook->getUser();
			if ($fbuser) {
				try {
					$user_profile = $facebook->api('/me');
				}catch (Exception $e) {
					echo $e->getMessage();
					exit();
				}
				$user_fbid	= $fbuser;
				$user_email = $user_profile["email"];
				$user_fnmae = $user_profile["first_name"];
			  if( email_exists( $user_email )) { // user is a member 
				  $user = get_user_by('login', $user_email );
				  $user_id = $user->ID;
				  wp_set_auth_cookie( $user_id, true );
			   } else { // this user is a guest	
				  $random_password = wp_generate_password( 10, false );
				  $user_id = wp_create_user( $user_email, $random_password, $user_email );
				  /*
				  $thumb = 'http://graph.facebook.com/'.$user_fbid.'/picture?width='.BP_AVATAR_THUMB_WIDTH.'&height='.BP_AVATAR_THUMB_HEIGHT;
				  $full = 'http://graph.facebook.com/'.$user_fbid.'/picture?width='.BP_AVATAR_FULL_WIDTH.'&height='.BP_AVATAR_FULL_HEIGHT;
				  
				  $thumbimage = file_get_contents($thumb);
				  $fullimage = file_get_contents($full);

				  $base_dir=wp_upload_dir();
				  $dir = $base_dir['baseurl'].'/avatars/'.$user_id;
				  if(!is_dir($dir)){
				  	if(mkdir($dir,'0777',true)){
				  		$thumb_avatar = $dir.'/'.$user_fnmae.'-bpthumb.jpg';
				  		$full_avatar = $dir.'/'.$user_fnmae.'-bpfull.jpg';
				  		file_put_contents($thumb_avatar,$thumbimage);
				  		file_put_contents($full_avatar,$fullimage);
				  	}
				  } */
				  wp_set_auth_cookie( $user_id, true );
			   }
			   
	   			wp_redirect( site_url() );
				exit;
			}		
		}
	}

}