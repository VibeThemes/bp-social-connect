<?php


 if ( ! defined( 'ABSPATH' ) ) exit;

class init_bp_social_connect extends bpc_config{

	var $settings;

	public function __construct(){
		$this->settings = $this->get();
		new bp_social_connect_facebook;
		//new bp_social_connect_twitter;
		new bp_social_connect_google;
		add_action('wp_head',array($this,'ajaxurl'));
		add_action('login_footer',array($this,'ajaxurl'));
		add_action('bp_before_member_body',array($this,'verify_email'));
		add_action('login_footer',array($this,'display_social_login'));
		add_action('bp_before_account_details_fields',array($this,'display_register_social_login'));
		add_action('bp_after_sidebar_login_form',array($this,'display_social_login'));
		add_Action('bp_social_connect',array($this,'styling'));
	}	

	function display_social_login(){
		echo '<div class="bp_social_connect">';
		do_action('bp_social_connect');
		echo '</div>';
	}

	function display_register_social_login(){
		echo '<div class="bp_social_connect">';
		do_action('bp_social_connect');
		echo '</div>';
	}

	function styling(){
		echo '<style>.bp_social_connect { display: inline-block; width: 100%; }
			.bp_social_connect_facebook{background:#3b5998;}.bp_social_connect_google{background:#DD4B39 !important;}.bp_social_connect > a{text-align:center;float:left;padding:15px;border-radius:2px;color:#fff !important;width:200px;margin:0 5px;}.bp_social_connect > a:first-child{margin-left:0;}
			.bp_social_connect > a:before{float:left;font-size:16px;font-family:fontawesome;opacity:0.6;}.bp_social_connect_facebook:before{content:"\f09a";}.bp_social_connect_google:before{content:"\f0d5";}</style>';
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

