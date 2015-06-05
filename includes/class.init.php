<?php

class init_bp_social_connect extends bpc_config{

	var $settings;

	public function __construct(){
		$this->settings = $this->get();

		add_action('login_footer',array($this,'display_social_login'));
		new bp_social_connect_facebook;
		new bp_social_connect_twitter;
		new bp_social_connect_google;
		add_action('wp_head',array($this,'ajaxurl'));
		add_action('login_footer',array($this,'ajaxurl'));

	}	

	function display_social_login(){
		echo '<div class="bp_social_connect">';
		do_action('bp_social_connect');
		echo '</div>';
	}
	function ajaxurl() {
		wp_nonce_field($this->settings['security'],$this->security_key);
	?>
		<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		</script>
		<style>
			.bp_social_connect{
				text-align: center;
			}
			.bp_social_connect a {
			  background: #3b5998;
			  color: #FFF;
			  padding: 15px 20px;
			  display: inline-block;
			  text-decoration: none;
			  min-width: 220px;
			  margin: 10px;
			  border-radius: 2px;
			  letter-spacing: 1px;
			  box-shadow: 0 4px 0 rgba(0,0,0,0.1);
			}
			.bp_social_connect a:hover{
				box-shadow: none;	
			}
			.bp_social_connect a:focus{
				box-shadow: inset 0 4px 0 rgba(0,0,0,0.1)
			}
			#bp_social_connect_twitter{
				background:#4099FF;
			}
			#bp_social_connect_google{
				background:#DD4B39;
			}
			.loading{
				opacity: :0.2;
			}
			.bp_social_connect a{
			  opacity: 0;
			  transform-origin: 0% 0%;
			  animation-name: spinvert;
			  animation-duration: .5s;
			  animation-fill-mode: forwards;
			  animation-timing-function: ease;
			  animation-iteration-count: 1;
			}
			@keyframes spinvert{
				0%{opacity:.5;transform:translateY(50px) rotateY(0deg) rotateX(0deg) scale(0.8, 0.8)}
				100%{opacity:1;transform:translateY(0px) rotateY(180deg) rotateX(0deg) scale(1, 1)}
			}
		</style>
	<?php
	}
}

add_action('init','initialise_bp_socil_connect');
function initialise_bp_socil_connect(){
	new init_bp_social_connect;	
}

