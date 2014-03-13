<?php
/*
Plugin Name: Smart eCart Addon - User's Dashboard and Profile Updates
Version: 1
Plugin URI: http://groupbuyingsite.com/marketplace
Description: Adds a custom SEC report for merchants and allow them to update credits and view purchase information.
Author: Sprout Venture
Author URI: http://sproutventure.com/wordpress
Plugin Author: Dan Cameron
Contributors: Dan Cameron 
Text Domain: group-buying
Domain Path: /lang

*/
define ('SEC_PROFILES_REPORT_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );

add_action('plugins_loaded', 'gb_load_custom_reporting_profiles');
function gb_load_custom_reporting_profiles() {
	if ( class_exists('Group_Buying_Controller') ) {
		require_once('classes/SEC_Report_Addon.php');
		add_filter( 'gb_addons', array( 'SEC_Report_Addon', 'gb_addon' ), 10, 1 );
	}
}