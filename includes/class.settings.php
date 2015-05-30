<?php

class bp_social_connect_settings {

	var $version = '1.0';
	var $settings;

	public function __construct(){
		add_options_page(__('BP Social Connect settings','bp-social-connect'),__('BP Social Connect','bp-social-connect'),'manage_options','bp-social-connect',array($this,'settings'));
		add_action('admin_enqueue_scripts',array($this,'enqueue_admin_scripts'));
		$this->settings=get_option('bp_social_connect_settings');
	}

	function enqueue_admin_scripts($hook){
		if ( 'settings_page_bp-social-connect' != $hook ) {
        	return;
    	}
    	wp_enqueue_style( 'bp_social_connect_admin_style', plugin_dir_url( __FILE__ ) . '../assets/css/admin.css' );
	}

	function settings(){
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		$this->settings_tabs($tab);
		$this->$tab();
	}

	function settings_tabs( $current = 'general' ) {
	    $tabs = array( 
	    		'general' => __('General','bp-social-connect'), 
	    		'facebook' => __('Facebook','bp-social-connect'), 
	    		'twitter' => __('Twitter','bp-social-connect'), 
	    		'google' => __('Google','bp-social-connect'), 
	    		'linkedin' => __('Linkedin','bp-social-connect'), 
	    		);
	    echo '<div id="icon-themes" class="icon32"><br></div>';
	    echo '<h2 class="nav-tab-wrapper">';
	    foreach( $tabs as $tab => $name ){
	        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
	        echo "<a class='nav-tab$class' href='?page=bp-social-connect&tab=$tab'>$name</a>";

	    }
	    echo '</h2>';
	    if(isset($_POST['save'])){
	    	$this->save();
	    }
	}

	function general(){
		echo '<h3>'.__('BP Social Connect Settings','bp-social-connect').'</h3>';
	
		$settings=array(
			);

		$this->generate_form('general',$settings);
	}

	function facebook(){
		echo '<h3>'.__('Facebook Social Connect Settings','bp-social-connect').'</h3>';
	}
	function twitter(){
		echo '<h3>'.__('Twitter Social Connect Settings','bp-social-connect').'</h3>';
	}
	function google(){
		echo '<h3>'.__('Google Social Connect Settings','bp-social-connect').'</h3>';
	}
	function linkedin(){
		echo '<h3>'.__('LinkedIn Social Connect Settings','bp-social-connect').'</h3>';
	}

	function generate_form($tab,$settings=array()){
		echo '<form method="post">';
		wp_nonce_field('chat_settings','_wpnonce');   
		echo '<ul class="chat-settings">';

		foreach($settings as $setting ){
			echo '<li>';
			switch($setting['type']){
				case 'textarea':
					echo '<label>'.$setting['label'].'</label>';
					echo '<textarea name="'.$setting['name'].'">'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:'').'</textarea>';
					echo '<span>'.$setting['desc'].'</span>';
				break;
				case 'select':
					echo '<label>'.$setting['label'].'</label>';
					echo '<select name="'.$setting['name'].'" class="chzn-select">';
					foreach($setting['options'] as $key=>$option){
						echo '<option value="'.$key.'" '.(isset($this->settings[$setting['name']])?selected($key,$this->settings[$setting['name']]):'').'>'.$option.'</option>';
					}
					echo '</select>';
					echo '<span>'.$setting['desc'].'</span>';
				break;
				case 'checkbox':
					echo '<label>'.$setting['label'].'</label>';
					echo '<input type="checkbox" name="'.$setting['name'].'" '.(isset($this->settings[$setting['name']])?'CHECKED':'').' />';
					echo '<span>'.$setting['desc'].'</span>';
				break;
				case 'number':
					echo '<label>'.$setting['label'].'</label>';
					echo '<input type="number" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:'').'" />';
					echo '<span>'.$setting['desc'].'</span>';
				break;
				case 'hidden':
					echo '<input type="hidden" name="'.$setting['name'].'" value="1"/>';
				break;
				default:
					echo '<label>'.$setting['label'].'</label>';
					echo '<input type="text" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:'').'" />';
					echo '<span>'.$setting['desc'].'</span>';
				break;
			}
			
			echo '</li>';
		}
		echo '</ul>';
		echo '<input type="submit" name="save" value="'.__('Save Settings','bp-social-connect').'" class="button button-primary" /></form>';
	}


	function save(){
		$none = $_POST['chat_settings'];
		if ( !isset($_POST['save']) || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'chat_settings') ){
		    _e('Security check Failed. Contact Administrator.','bp-social-connect');
		    die();
		}
		foreach($_POST as $key => $value){
			$this->settings[$key]=$value;
		}
		update_option('chat_settings',$this->settings);
	}
}

add_action('admin_menu','init_bp_social_connect_settings_settings',100);
function init_bp_social_connect_settings_settings(){
	new bp_social_connect_settings;	
}
