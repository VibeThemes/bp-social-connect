<?php

class init_bp_social_connect extends bpc_config{

	var $settings;

	public function __construct(){
		$this->settings = $this->get();
		new bp_social_connect_facebook;
	}

	
}

add_action('init','initialise_bp_socil_connect');
function initialise_bp_socil_connect(){
	new init_bp_social_connect;	
}

