<?php

abstract class bpc_config{

	var $version = '1.0';
	var $option = 'bp_social_connect';
	var $social_options = array(
		'facebook',
		'google',
		'twitter',
		'linkedin'
		);
	function get_version(){
		return $this->version;
	}
	function get_social_options(){
		return $social_options;
	}
	function get(){
		return get_option($this->option);
	}

	function put($value){
		update_option($this->option,$value);
	}

}