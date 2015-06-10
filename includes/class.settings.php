<?php

class bp_social_connect_settings extends bpc_config{

	var $settings;

	public function __construct(){
		add_options_page(__('BP Social Connect settings','bp-social-connect'),__('BP Social Connect','bp-social-connect'),'manage_options','bp-social-connect',array($this,'settings'));
		add_action('admin_enqueue_scripts',array($this,'enqueue_admin_scripts'));
		$this->settings=$this->get(); 
	}

	function enqueue_admin_scripts($hook){
		if ( 'settings_page_bp-social-connect' != $hook ) {
        	return;
    	}
    	wp_enqueue_style( 'bp_social_connect_admin_style', plugin_dir_url( __FILE__ ) . '../assets/css/admin.css' );
    	wp_enqueue_script( 'bp_social_connect_admin_style', plugin_dir_url( __FILE__ ) . '../assets/js/admin.js',array('jquery'),'1.0',true);
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
				array(
					'label' => __('Redirect Settings','vibe-customtypes'),
					'name' =>'redirect_link',
					'type' => 'select',
					'options'=> apply_filters('bp_social_connect_redirect_settings',array(
						'' => __('Same Page','vibe-customtypes'),
						'home' => __('Home','vibe-customtypes'),
						'profile' => __('BuddyPress Profile','vibe-customtypes'),
						)),
					'desc' => __('Set Login redirect settings','vibe-customtypes')
				),
				array(
					'label' => __('Security Key','vibe-customtypes'),
					'name' =>'security',
					'type' => 'text',
					'std'=>wp_generate_password( 16, false ),
					'desc' => __('Set a random security key value','vibe-customtypes')
				),
				array(
					'label' => __('Social Button Styling','vibe-customtypes'),
					'name' =>'button_css',
					'type' => 'textarea',
					'std'=> '
					.bp_social_connect{
						text-align: center;
					}
					.bp_social_connect a {
					  background: #3b5998;
					  color: #FFF;
					  font-weight: 600;
					  padding: 15px 20px;
					  display: inline-block;
					  text-decoration: none;
					  min-width: 220px;
					  margin: 5px 0;
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
					}',
					'desc' => __('Change default style of buttons','vibe-customtypes')
				),
			);

		$this->generate_form('general',$settings);
	}

	function facebook(){
		echo '<h3>'.__('Facebook Social Connect Settings','bp-social-connect').'</h3>';
		$settings = array(
				array(
					'label' => __('Enable Facebook Login','vibe-customtypes'),
					'name' =>'facebook',
					'type' => 'select',
					'options'=>array(
						'0' => __('No','bp-social-connect'),
						'1' => __('Yes','bp-social-connect'),
					),
					'desc' => __(' Enable facebook login','vibe-customtypes')
				),
				array(
					'label' => __('APP ID','vibe-customtypes'),
					'name' => 'facebook_app_id',
					'type' => 'text',
					'desc' => sprintf(__('Set your Facebook APP ID, create a new app from %s','vibe-customtypes'),'<a href="https://developers.facebook.com/">https://developers.facebook.com/</a>'),
				),
				array(
					'label' => __('APP Secret','vibe-customtypes'),
					'name' => 'facebook_app_secret',
					'type' => 'text',
					'desc' => __('Enter facebook App secret','vibe-customtypes')
				),
			);
		$settings = apply_filters('bp_social_connect_facebook_fields',$settings);
		$this->generate_form('facebook',$settings);
	}
	function twitter(){
		echo '<h3>'.__('Twitter Social Connect Settings','bp-social-connect').'</h3>';
		$settings = array(
				array(
					'label' => __('Enable Twitter Login','vibe-customtypes'),
					'name' =>'twitter',
					'type' => 'select',
					'options'=>array(
						'0' => __('No','bp-social-connect'),
						'1' => __('Yes','bp-social-connect'),
					),
					'desc' => sprintf(__('Open this link %s to create an app and enter your consumer keys to enable twitter connect.','vibe-customtypes'),'<a href="https://dev.twitter.com/apps/">https://dev.twitter.com/apps/</a>')
				),
				array(
					'label' => __('Consumer Key','vibe-customtypes'),
					'name' => 'twitter_consumer_key',
					'type' => 'text',
					'desc' => __('Set your Twitter Consumer key','vibe-customtypes')
				),
				array(
					'label' => __('Consumer Secret','vibe-customtypes'),
					'name' => 'twitter_consumer_secret',
					'type' => 'text',
					'desc' => __('Enter twitter consumer key secret','vibe-customtypes')
				),
				array(
					'label' => __('Callback','vibe-customtypes'),
					'name' => 'twitter_callback',
					'type' => 'text',
					'desc' => __('Enter twitter callback','vibe-customtypes')
				),
			);
		$settings = apply_filters('bp_social_connect_twitter_fields',$settings);
		$this->generate_form('twitter',$settings);
	}
	function google(){
		echo '<h3>'.__('Google Social Connect Settings','bp-social-connect').'</h3>';
		$settings = array(
				array(
					'label' => __('Enable Google Login','vibe-customtypes'),
					'name' =>'google',
					'type' => 'select',
					'options'=>array(
						'0' => __('No','bp-social-connect'),
						'1' => __('Yes','bp-social-connect'),
					),
					'desc' => ''
				),
				array(
					'label' => __('Client ID','vibe-customtypes'),
					'name' => 'google_client_id',
					'type' => 'text',
					'desc' => sprintf(__('Set your Google client id, create a new project for web and grab the client id from %s','vibe-customtypes'),'<a href="https://console.developers.google.com">https://console.developers.google.com</a>'),
				),
				array(
					'label' => __('Client Secret','vibe-customtypes'),
					'name' => 'google_client_secret',
					'type' => 'text',
					'desc' => __('Enter Google client secret','vibe-customtypes')
				),
				array(
					'label' => __('Client Uri','vibe-customtypes'),
					'name' => 'google_redirect_uri',
					'type' => 'text',
					'desc' => __('Enter redirect uri','vibe-customtypes')
				),
			);
		$settings = apply_filters('bp_social_connect_google_fields',$settings);
		$this->generate_form('google',$settings);
		
	}

	function generate_form($tab,$settings=array()){
		echo '<form method="post">
				<table class="form-table">';
		wp_nonce_field('save_settings','_wpnonce');   
		echo '<ul class="save-settings">';

		foreach($settings as $setting ){
			echo '<tr valign="top">';
			global $wpdb,$bp;
			switch($setting['type']){
				case 'textarea': 
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><textarea name="'.$setting['name'].'">'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:(isset($setting['std'])?$setting['std']:'')).'</textarea>';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'select':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><select name="'.$setting['name'].'" class="chzn-select">';
					foreach($setting['options'] as $key=>$option){
						echo '<option value="'.$key.'" '.(isset($this->settings[$setting['name']])?selected($key,$this->settings[$setting['name']]):'').'>'.$option.'</option>';
					}
					echo '</select>';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'checkbox':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="checkbox" name="'.$setting['name'].'" '.(isset($this->settings[$setting['name']])?'CHECKED':'').' />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'number':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="number" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:'').'" />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'hidden':
					echo '<input type="hidden" name="'.$setting['name'].'" value="1"/>';
				break;
				case 'bp_fields':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><a class="add_new_map button">'.__('Add BuddyPress profile field map','bp-social-connect').'</a>';

					$table = $wpdb->prefix.'bp_xprofile_fields';
					$bp_fields = $wpdb->get_results("SELECT DISTINCT name FROM {$table}");

					echo '<ul class="bp_fields">';
					if(is_array($this->settings[$setting['name']]['field']) && count($this->settings[$setting['name']]['field'])){
						foreach($this->settings[$setting['name']]['field'] as $key => $field){
							echo '<li><label><select name="'.$setting['name'].'[field][]">';
							foreach($setting['fields'] as $k=>$v){
								echo '<option value="'.$k.'" '.(($field == $k)?'selected=selected':'').'>'.$k.'</option>';
							}
							echo '</select></label><select name="'.$setting['name'].'[bpfield][]">';
							foreach($bp_fields as $f){
								echo '<option value="'.$f->name.'" '.(($this->settings[$setting['name']]['bpfield'][$key] == $f->name)?'selected=selected':'').'>'.$f->name.'</option>';
							}
							echo '</select><span class="dashicons dashicons-no remove_field_map"></span></li>';
						}
					}
					echo '<li class="hide">';
					echo '<label><select rel-name="'.$setting['name'].'[field][]">';
					foreach($setting['fields'] as $k=>$v){
						echo '<option value="'.$k.'">'.$k.'</option>';
					}
					echo '</select></label>';
					echo '<select rel-name="'.$setting['name'].'[bpfield][]">';
					
					foreach($bp_fields as $f){
						echo '<option value="'.$f->name.'">'.$f->name.'</option>';
					}
					echo '</select>';
					echo '<span class="dashicons dashicons-no remove_field_map"></span></li>';
					echo '</ul></td>';
				break;
				default:
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="text" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:(isset($setting['std'])?$setting['std']:'')).'" />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
			}
			
			echo '</tr>';
		}
		echo '</tbody>
		</table>';
		echo '<input type="submit" name="save" value="'.__('Save Settings','bp-social-connect').'" class="button button-primary" /></form>';
	}


	function save(){
		$none = $_POST['save_settings'];
		if ( !isset($_POST['save']) || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'save_settings') ){
		    _e('Security check Failed. Contact Administrator.','bp-social-connect');
		    die();
		}
		unset($_POST['_wpnonce']);
		unset($_POST['_wp_http_referer']);
		unset($_POST['save']);

		foreach($_POST as $key => $value){
			$this->settings[$key]=$value;
		}

		$this->put($this->settings);
	}
}

add_action('admin_menu','init_bp_social_connect_settings_settings',100);
function init_bp_social_connect_settings_settings(){
	new bp_social_connect_settings;	
}
