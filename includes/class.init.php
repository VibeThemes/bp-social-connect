<?php

class init_bp_social_connect extends bpc_config{

	var $settings;

	public function __construct(){
		$this->settings = $this->get();
		new bp_social_connect_facebook;
		new bp_social_connect_twitter;
		new bp_social_connect_google;
		add_action('wp_head',array($this,'ajaxurl'));
		add_action('login_footer',array($this,'ajaxurl'));
		add_action('bp_before_member_body',array($this,'verify_email'));
		add_action('login_footer',array($this,'display_social_login'));
		add_action('bp_before_account_details_fields',array($this,'display_register_social_login'));
		add_action('bp_after_sidebar_login_form',array($this,'display_social_login'));
	}	

	function display_social_login(){
		echo '<div class="bp_social_connect">';
		do_action('bp_social_connect');
		echo '</div><style>#bp_social_connect_facebook:before{content:"\f305";float:left;font-size:16px;font-family:dashicons}#bp_social_connect_twitter:before{content:"\f301";float:left;font-size:16px;font-family:dashicons}#bp_social_connect_google:before{content:"\f462";float:left;font-size:16px;font-family:dashicons}</style>';
	}

	function display_register_social_login(){
		echo '<div class="bp_social_connect">';
		do_action('bp_social_connect');
		echo '</div><style>#bp_social_connect_facebook:before{content:"\f305";float:left;font-size:16px;font-family:dashicons}#bp_social_connect_twitter:before{content:"\f301";float:left;font-size:16px;font-family:dashicons}#bp_social_connect_google:before{content:"\f462";float:left;font-size:16px;font-family:dashicons}#signup_form .bp_social_connect{display: inline-block;width: 100%;line-height: 0;}#signup_form .bp_social_connect a{float: left;line-height: 1.2;margin-right: 5px;}</style>';
	}

	function verify_email(){
		if (!is_user_logged_in()) return;
		global $current_user;
		get_currentuserinfo();
		if (empty($current_user->user_email)) {
		    echo '<div class="message error"><p style="text-transform: none;">'.sprintf(__('Please update your email id in Profile - Settings , your password is %s','bp-social-connect'),strtolower($current_user->user_login).'@123').'</p></div>';
		}
	}
	function ajaxurl() {
		wp_nonce_field($this->settings['security'],$this->security_key);
	?>
		<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		</script>
		<style>
			<?php echo $this->settings['button_css']; ?>
		</style>
	<?php
	}
}

add_action('init','initialise_bp_socil_connect');
function initialise_bp_socil_connect(){
	new init_bp_social_connect;	
}

