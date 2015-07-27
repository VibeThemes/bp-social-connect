<?php
/*
Plugin Name: BuddyPress Social Connect
Plugin URI: http://www.vibethemes.com
Description: Connect BuddyPress Login and Registration with Social Networks
Version: 1.1
Author: VibeThemes
Author URI: http://www.VibeThemes.com/
License : GPLv2 or Later
*/



/*====== BEGIN BP SOCIAL CONNECT =====*/
include_once('includes/class.config.php');

include_once('includes/social/facebook/class.facebook.php');
include_once('includes/social/twitter/class.twitter.php');
include_once('includes/social/google/class.google.php');

include_once('includes/class.init.php');
include_once('includes/class.settings.php');

add_action('plugins_loaded','bp_social_connect_translations');
function bp_social_connect_translations(){
    $locale = apply_filters("plugin_locale", get_locale(), 'bp-social-connect');
    $lang_dir = dirname( __FILE__ ) . '/languages/';
    $mofile        = sprintf( '%1$s-%2$s.mo', 'bp-social-connect', $locale );
    $mofile_local  = $lang_dir . $mofile;
    $mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

    if ( file_exists( $mofile_global ) ) {
        load_textdomain( 'bp-social-connect', $mofile_global );
    } else {
        load_textdomain( 'bp-social-connect', $mofile_local );
    }   
}