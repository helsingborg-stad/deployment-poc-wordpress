<?php
/*
Plugin Name: Vizzit Analytics for WordPress
Plugin URI: https://www.vizzit.se/modules/wordpress/
Description: This plugin makes it simple to add Vizzit Analytics to your WordPress blog.
Version: 1.0.0
Author: Vizzit International AB
Author URI: https://www.vizzit.se/
License: GPL2
*/

/* Copyright 2012 Vizzit International AB (email : support@vizzit.se)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


#-------------------------------------------------------------------------------------------------------
# Definitions
#-------------------------------------------------------------------------------------------------------
define( 'VAWP_VERSION',   			'1.0.0' ); // Vizzit Analytics version
define( 'VAWP_MIN_PHP',   			'5.2' ); // minimal required PHP version
define( 'VAWP_MIN_WP',    			'3.2.1' ); // minimal required WordPress version (at least 3.0)
define( 'VAWP_HOOK',    			'vizzit-analytics-for-wordpress' ); // unique identifier for plugin

define( 'VAWP_OPTION_NAME',    		'Vizzit_Analytics' ); // variable name to store all plugin settings
define( 'VAWP_LOCALE_HOOK',    		VAWP_HOOK ); // unique identifier for localization
define( 'VAWP_DBT_HISTORY',    		'vizzit_analytics_history' ); // scheduler history database table
define( 'VAWP_DBT_HISTORY_META', 	'vizzit_analytics_history_meta' ); // scheduler history meta database table

define( 'VAWP_DIR_ASSETS',  		'assets/' ); // directory for styles, javascript
define( 'VAWP_DIR_LOCALE',  		'locale/' ); // directory for plugin localization files
define( 'VAWP_DIR_TMP_FILES',  	'files/' ); // directory to store temporary files

define( 'VAWP_FILE',            WP_PLUGIN_DIR.'/'.VAWP_HOOK.'/'.basename(__FILE__));

define( 'VAWP_PATH_APP_VDS',   		'https://www.vizzit.se/episerver/' ); // path to application Vizzit This Page
define( 'VAWP_PATH_APP_V2',    		'https://www.vizzit.se/episerver/' ); // path to application Vizzit V2
define( 'VAWP_PATH_APP_VMS',   		'https://www.vizzit.se/my_pages/' ); // path to application Vizzit My Pages
define( 'VAWP_PATH_APP_VWM',   		'https://www.vizzit.se/master_pages/' ); // path to application Vizzit Webmaster
define( 'VAWP_PATH_APP_PORTAL',   'https://www.vizzit.se/portal/' ); // path to application Vizzit Portal

define( 'VAWP_PATH_FAQ',    		'https://www.vizzit.se/faq/' ); // path to FAQ
define( 'VAWP_PATH_FILE_UPLOAD',    'http://www.vizzit.se/files/upload.php' ); // path to upload-script
define( 'VAWP_PATH_TAG',    		'//www.vizzit.se/vizzittag/' ); // path to tag folder

define( 'VAWP_EMAIL_SUPPORT',  		'support@vizzit.se' ); // mail address for support

// define standard values for wordpress system stuff used in treefile
define( 'VAWP_WP_SYSTEM_USER', 		'wp.user.system' ); // user "system"
define( 'VAWP_WP_PAGETYPE_FOLDER', 	'wp.pagetype.folder' ); // pagetype for folders
define( 'VAWP_WP_PAGETYPE_FEED', 	'wp.pagetype.feed' ); // pagetype for newsfeeds

/**
 * .. when activating the plugin
 */
function vizzit_analytics_plugin_activate( $networkwide ) {
  $error       = '';
  $phpversion  = phpversion();
  $wpversion   = get_bloginfo( 'version' );

  if( version_compare( VAWP_MIN_PHP, $phpversion, '>' ) ) {
    $error .= sprintf( "Minimum PHP version required is %s, not %s.\n", VAWP_MIN_PHP, $phpversion );
    $error .= '<br />';
  }

  if( version_compare( VAWP_MIN_WP, $wpversion, '>' ) ) {
    $error .= sprintf( "Minimum WordPress version required is %s, not %s.\n", VAWP_MIN_WP, $wpversion );
  }

  if( !$error ) {
    // do NOT forget this global
    global $wpdb;

	  if (function_exists('is_multisite') && is_multisite()) {
      // check if it is a network activation - if so, run the activation function for each blog id
      if ( $networkwide ) {
        $old_blog = $wpdb->blogid;
        // Get all blog ids
        $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blogids as $blog_id) {
          switch_to_blog($blog_id);
          _vizzit_analytics_plugin_activate();
        }
        switch_to_blog($old_blog);
        return;
      }
    }
    _vizzit_analytics_plugin_activate();
  }

  deactivate_plugins( VAWP_FILE );
} // end vizzit_analytics_plugin_activate()
register_activation_hook( VAWP_FILE, 'vizzit_analytics_plugin_activate' );

/**
 * .. If not multiblog site
 */
function _vizzit_analytics_plugin_activate() {

	// do NOT forget this global
	global $wpdb;

	// this if statement makes sure that the table does not exist already
	$sql = "CREATE TABLE " . $wpdb->prefix . VAWP_DBT_HISTORY . " (
	    id mediumint(9) NOT NULL AUTO_INCREMENT,
	    va_sequence mediumint(9) NOT NULL,
	    va_date_start datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	    va_date_end datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	    va_exec enum('MANUAL','SCHEDULER') NOT NULL,
	    va_status enum('OK','FAILED','WARNING') NOT NULL,
	    va_message text NOT NULL,
	    UNIQUE KEY id (id)
	  );";
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta( $sql );

	// this if statement makes sure that the table doe not exist already
	$sql = "CREATE TABLE " . $wpdb->prefix . VAWP_DBT_HISTORY_META . " (
	    va_sequence mediumint(9) NOT NULL,
	    va_tree_status varchar(32) NOT NULL,
	    va_tree_num_pages_read mediumint(9) NOT NULL,
	    va_tree_excpt text NOT NULL,
	    va_send_status varchar(32) NOT NULL,
	    va_send_excpt text NOT NULL,
	    UNIQUE KEY va_sequence (va_sequence)
  );";
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	return;

} // end vizzit_analytics_plugin_activate()

/**
 * .. when deactivating the plugin
 */
register_deactivation_hook( VAWP_FILE, 'vizzit_analytics_plugin_deactivate' );
function vizzit_analytics_plugin_deactivate( $networkwide ) {
  global $wpdb;

  if (function_exists('is_multisite') && is_multisite()) {
  // check if it is a network activation - if so, run the activation function for each blog id
    if ($networkwide) {
      $old_blog = $wpdb->blogid;
      // Get all blog ids
      $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
      foreach ($blogids as $blog_id) {
        switch_to_blog($blog_id);
        _vizzit_analytics_plugin_deactivate();
      }
      switch_to_blog($old_blog);
      return;
    }
  }
  _vizzit_analytics_plugin_deactivate();
} // end vizzit_analytics_plugin_deactivate()

function _vizzit_analytics_plugin_deactivate() {
  // the strings for the schedule event needs to match the one defined in vizzit_analytics_core.class variable definition
  $timestamp = wp_next_scheduled( 'vizzit_analytics_process_daily' ); // read the timestamp for next schedule
  wp_unschedule_event( $timestamp, 'vizzit_analytics_process_daily' ); // with this timestamp, disable
} // end _vizzit_analytics_plugin_deactivate()


/**
 * adding link to settings on plugin-page
 */
function add_settings_link( $links, $file ) {
  if( $file == plugin_basename( __FILE__ ) ) {
    $l = '<a href="' . admin_url( 'admin.php?page=' . VAWP_HOOK ) . '" title="">'. __( 'Settings', VAWP_LOCALE_HOOK ) .'</a>';
    array_unshift( $links, $l );
  }
  return $links;
} // end add_settings_link
add_filter( 'plugin_action_links', 'add_settings_link', 10, 2 );


#-------------------------------------------------------------------------------------------------------
# Wordpress plugin functions
#-------------------------------------------------------------------------------------------------------
if(!function_exists('is_plugin_active_for_network'))
  require_once(ABSPATH . '/wp-admin/includes/plugin.php');


#-------------------------------------------------------------------------------------------------------
# Core Backend Class
#-------------------------------------------------------------------------------------------------------
require_once plugin_dir_path( __FILE__ ) . 'vizzit_analytics_core.class.php';


#-------------------------------------------------------------------------------------------------------
# Admin User Interface
#-------------------------------------------------------------------------------------------------------
if( is_admin() && !class_exists( 'Vizzit_Analytics_Admin' ) ) {
  require_once plugin_dir_path( __FILE__ ) . 'vizzit_analytics_admin.class.php';

  $vizzit_analytics_admin = new Vizzit_Analytics_Admin();
}


#-------------------------------------------------------------------------------------------------------
# Main Class
#-------------------------------------------------------------------------------------------------------
if( !class_exists( 'Vizzit_Analytics' ) ) {
  require_once plugin_dir_path( __FILE__ ) . 'vizzit_analytics.class.php';

  $vizzit_analytics = new Vizzit_Analytics();
}


#-------------------------------------------------------------------------------------------------------
# SOAP class
#-------------------------------------------------------------------------------------------------------
if( !class_exists( 'Vizzit_Analytics_Soap' ) ) {
  require_once plugin_dir_path( __FILE__ ) . 'vizzit_analytics_soap.class.php';

  $vizzit_analytics_soap = new Vizzit_Analytics_Soap();
}

?>