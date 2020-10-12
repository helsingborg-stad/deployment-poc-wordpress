<?php
/**
 * API Class
 * Version 0.5.1
 */
if( !class_exists( 'Vizzit_Analytics_Api' ) ) {

  class Vizzit_Analytics_Api extends Vizzit_Analytics_Core
  {
    /**
     * Constructur, load all required stuff.
     */
    function __construct()
    {
      parent::__construct();
    }

    function activate() {
        global $wp_rewrite;
        $this->flush_rewrite_rules();
    }

    function create_rewrite_rules($rules) {
        global $wp_rewrite;
        $newRule = array( 'vizzit_analytics_api/(.+)' => 'index.php?vizzit_analytics_api=' . $wp_rewrite->preg_index(1) );
        $newRules = $newRule + $rules;
        return $newRules;
    }

    function add_query_vars($qvars) {
        $qvars[] = 'vizzit_analytics_api';
        return $qvars;
    }

    function flush_rewrite_rules() {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    function meta_get_last_schedule_history_row() {
      // fetch last history row
      $schedule_history_rows = $this->get_schedule_history_rows( array( 'limit' => 1 ) );
      if( count( $schedule_history_rows ) > 0 ) {
        foreach( $schedule_history_rows as $k => $h_data ) {
          $response = array(
            'Response' => 'OK',
            'Data' => array(
              'sequenceNumber'	=> $h_data[ 'va_sequence' ],
              'datetimeStart'	=> $h_data[ 'va_date' ],
              'datetimeEnd'		=> $h_data[ 'va_date' ],
              'startedBy' 		=> $h_data[ 'va_exec' ],
              'overallStatus' 	=> $h_data[ 'va_status' ],
              #'message' 			=> $h_data[ 'va_message' ],
              'tree' => array(
                'status' 		=> $h_data[ 'va_tree_status' ],
                'pages' 		=> $h_data[ 'va_tree_num_pages_read' ],
                #'message' 		=> $h_data[ 'va_tree_excpt' ]
              ),
              'sendfile' => array(
                'status' 		=> $h_data[ 'va_send_status' ],
                #'message' 		=> $h_data[ 'va_send_excpt' ]
              )
            )
          );
        }
      } else {
        $response = array( 'Response' => 'FAILED', 'Data' => 'No result for query of schedule history.' );
      }

      return $response;
    }

	/**
	 *
	 */
    function template_redirect_intercept() {
      global $wp_query;

      // do nothing and go on when not the right parameter -> needed that the whole gui-template is still working
      if( !$wp_query->get( 'vizzit_analytics_api' ) ) {
        return;
      }

      $options = $this->get_options();
      $q_api 	= $wp_query->get( 'vizzit_analytics_api' ); // get api query

      $ACC 		= array( 'Status', 'GetLastJobLogResult', 'ExecuteJob', 'SetScheduleEnabled' ); // allowed values
      $response = array( 'Response' => 'FAILED', 'Data' => array() ); // prepare default json-response

      // calculate password for current customer
      $sum		= $options[ 'va_customer_id' ] . $options[ 'va_crypt_iv' ];
      $pass 	= md5( $sum );
      $pass 	= pack('H*', $pass );
      $pass 	= base64_encode( $pass );

      if( $_POST[ 'Password' ] != $pass || !in_array( $q_api, $ACC ) ) {
        die(); // just die, white page
      } else {

        if( $q_api == 'Status' ) {
          require_once plugin_dir_path( __FILE__ ) . 'Array2XML.php';
          // be sure that the required class exists
          if( class_exists( 'Array2XML' ) ) {
            // TODO: get from options which are active --> set the "right" attribute-names, get the groups for which these are active
            $roles = array( 'Administrator', 'Editor', 'Author', 'Contributor' );

            // get scheduler status
            $wp_scheduler = wp_get_schedule( $this->e_schedule );

            // prepare variables so that it will be "clean" later on
            $o_cms_version					= 'WordPress ' . get_bloginfo ( 'version' );
            $o_system_os					= ( ( $_SERVER[ 'SERVER_SOFTWARE' ] != '' ) ? $_SERVER[ 'SERVER_SOFTWARE' ] : '' );
            $o_system_host_name				= ( ( $_SERVER[ 'SERVER_NAME' ] != '' ) ? $_SERVER[ 'SERVER_NAME' ] : '' );
            $o_system_ip_address			= ( ( $_SERVER[ 'SERVER_ADDR' ] != '' ) ? array( 'IpAddress' => $_SERVER[ 'SERVER_ADDR' ] ) : array() );
            $o_enabled_vizzit_views	= array();
              if( $options[ 'va_display_vds' ] 	== 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vds' ), 'Group' => array_values( $roles ) ); }
              if( $options[ 'va_display_v2' ] 	== 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'v2' ), 'Group' => array_values( $roles ) ); }
              if( $options[ 'va_display_vwm' ] 	== 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vwm' ), 'Group' => array_values( $roles ) ); }
              if( $options[ 'va_display_vms' ] 	== 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vms' ), 'Group' => array_values( $roles ) ); }
              if( $options[ 'va_display_portal' ]  == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'  => array( 'name' => 'portal' ), 'Group' => array_values( $roles ) ); }
            $o_sequence_number				= (int) $this->get_last_sequence_number( false );
            $o_sequence_number				= ( ( $o_sequence_number >= 0 ) ? $o_sequence_number : '' );
            $o_customer_id 					= ( ( $options[ 'va_customer_id' ] != '' ) ? $options[ 'va_customer_id' ] : '' );
            $o_test_mode 					= ( ( $options[ 'va_test_mode' ] != '' ) ? true : false );
            $o_anonymize_usernames 			= ( ( $options[ 'va_anonymize_usernames' ] == 'on' ) ? true : false );
            $o_anonymize_ip 				= ( ( $options[ 'va_anonymize_ip' ] == 'on' ) ? true : false );
            $o_append_username 				= ( ( $options[ 'va_append_username' ] == 'on' ) ? true : false );
            $o_time_on_page 				= ( ( $options[ 'va_time_on_page' ] == 'on' ) ? true : false );
            $o_scheduler 					= ( ( $options[ 'va_scheduler' ] == 'on' ) ? true : false );
            $o_scheduler_next_exec			= ( ( $wp_scheduler !== false ) ? date( 'Y-m-d H:i:s', wp_next_scheduled( $this->e_schedule ) ) : '0000-00-00 00:00:00' );
            $o_disk_free_space 				= disk_free_space( dirname( __FILE__ ) );
            $o_disk_free_space				= ( $o_disk_free_space === false ) ? '' : $o_disk_free_space;
            $o_disk_total_space 			= disk_total_space( dirname( __FILE__ ) );
            $o_disk_total_space				= ( $o_disk_total_space === false ) ? '' : $o_disk_total_space;
            $_o_temp_folder					= array();
            $_o_temp_folder					= $_o_temp_folder = array( 'Purge' => false, 'Location' => dirname( __FILE__ ) . '/' . VAWP_DIR_TMP_FILES, 'PartitionFreeBytes' => $o_disk_free_space, 'PartitionTotalBytes' => $o_disk_total_space );
            $o_structure_roots				= array();
              if( $options[ 'va_structure_include_pages' ] == 'on' ) { $o_structure_roots['Roots']['Root'][] = array( 'RootId' => 'PAGES' ); }
              if( $options[ 'va_structure_include_posts' ] == 'on' ) { $o_structure_roots['Roots']['Root'][] = array( 'RootId' => 'POSTS' ); }
            $schedule_history_rows 			= array();
            $schedule_history_rows 			= $this->get_schedule_history_rows( array( 'limit' => 10 ) );
            if( count( $schedule_history_rows ) > 0 ) {
              foreach( $schedule_history_rows as $k => $h_data ) {
                $o_job_history_job['Job'][] = array(
                  'ST' 	=> '' . $h_data[ 'va_date' ],
                  'ET' 	=> '' . $h_data[ 'va_date' ],
                  'PG' 	=> false,
                  'PT' 	=> true, // $_BOOL_PROCESS_TREE_THIS_EXEC
                  'PL' 	=> false,
                  'S' 	=> '' . $h_data[ 'va_status' ],
                  'E' 	=> array( '@cdata' => '' . $h_data[ 'va_message' ] ),
                  'SB' 	=> '' . $h_data[ 'va_exec' ],
                  'SQ' 	=> '' . $h_data[ 'va_sequence' ],
                  'T'	=> array(
                    'S' => '' . $h_data[ 'va_tree_status' ],
                    'P' => '' . $h_data[ 'va_tree_num_pages_read' ],
                    'E' => array( '@cdata' => '' . $h_data[ 'va_tree_excpt' ] )
                  ),
                  'SF'	=> array(
                    'S' => '' . $h_data[ 'va_send_status' ],
                    'E' => array( '@cdata' => '' . $h_data[ 'va_send_excpt' ] )
                  )
                );
              } // end foreach()
            } // end count


            // build XML
            $meta = array();
            $meta[ '@attributes' ] = array( 'Date' => date( 'Y-m-d H:i:s' ) );

            $meta[ 'CmsVersion' ] 			= $o_cms_version;
            $meta[ 'VizzitPluginVersion' ] 	= VAWP_VERSION;
            $meta[ 'SystemOS' ] 			= $o_system_os;
            $meta[ 'SystemHostName' ] 		= $o_system_host_name;
            $meta[ 'SystemIps' ] 			= array();
            $meta[ 'SystemIps' ][] 			= $o_system_ip_address;
            $meta[ 'EnabledVizzitViews' ] 	= array();
            $meta[ 'EnabledVizzitViews' ][] = $o_enabled_vizzit_views;
            $meta[ 'SequenceNumber' ] 		= $o_sequence_number;
            $meta[ 'CustomerId' ] 			= $o_customer_id;
            $meta[ 'LogMethod' ] 			= 'Tag';
            $meta[ 'TestMode' ] 			= $o_test_mode;
            $meta[ 'AnonymizeUserNames' ] 	= $o_anonymize_usernames;
            $meta[ 'AnonymizeIP' ] 			= $o_anonymize_ip; // TODO: this variable is missing in the specification
            $meta[ 'AppendUsername' ] 		= $o_append_username; // TODO: this variable is missing in the specification
            $meta[ 'TimeOnPage' ] 			= $o_time_on_page; // TODO: this variable is missing in the specification
            $meta[ 'GroupsProcessEnabled' ] = false;
            $meta[ 'TreeProcessEnabled' ] 	= $o_scheduler; // TODO: have this as an option in wordpress admin: as it is just the structure which is scheduled for tag-customers - use the same variable as for the scheduler
            $meta[ 'TransferMethod' ] 		= 'HTTP';
            $meta[ 'HTTPUploadTarget' ] 	= VAWP_PATH_FILE_UPLOAD;
            $meta[ 'SchedulerStatus' ] 		= $o_scheduler;
            $meta[ 'SchedulerNextExec' ] 	= $o_scheduler_next_exec;
            $meta[ 'TempFolder' ] 			= array();
            $meta[ 'TempFolder' ][] 		= $_o_temp_folder;
            $meta[ 'TreeStructure' ] 		= array();
            $meta[ 'TreeStructure' ][] 		= $o_structure_roots;
            $meta[ 'JobHistory' ] 			= array();
            $meta[ 'JobHistory' ][] 		= $o_job_history_job;

            header( 'Content-type: text/xml' );
            $xml = Array2XML::createXML( 'StatusReport', $meta );
            echo $xml->saveXML();

          } // end class_exists
        } // end $q_api == 'status'


        if( $q_api == 'ExecuteJob' ) {
          if( isset( $_POST[ 'ProcessTree' ] ) && $_POST[ 'ProcessTree' ] == 'true' ) {
            // process manually
            $this->vizzit_analytics_process( 'MANUAL' );

            // show last history entry
            $this->output( $this->meta_get_last_schedule_history_row() );
          }

          $this->output( $response );
        } // end $q_api == 'ExecuteJob'


        if( $q_api == 'GetLastJobLogResult' ) {
          $this->output( $this->meta_get_last_schedule_history_row() );
        }


        if( $q_api == 'SetScheduleEnabled' ) {
          if( isset( $_POST[ 'EnableSchedule' ] ) && ( $_POST[ 'EnableSchedule' ] == 'true' || $_POST[ 'EnableSchedule' ] == 'false' ) )
          {
            // modify option
            $options[ 'va_scheduler' ] = ( ( $_POST[ 'EnableSchedule' ] == 'true' ) ? 'on' : '' );
            
            // store modified options to DB
            $this->update_options($options);

            // prepare response
            $response = array(
              'Response' => 'OK',
              'Data' => array( 'EnableSchedule' => $_POST[ 'EnableSchedule' ] )
            );
          }

          $this->output( $response );
        } // end $q_api == 'SetScheduleEnabled'

      } // if accepted $ACC

      exit;
    }

    function output( $output ) {
        header( 'Cache-Control: no-cache, must-revalidate' );
        header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );

        // Commented to display in browser.
        #header( 'Content-type: application/json' );
        echo json_encode( $output );
    }
  } // end class Vizzit_Analytics_Api


/*
<!-- file: post.php -->
<html>

<fieldset><legend>Status</legend>
<form action="http://192.168.1.253/~stefand/zzWP/vizzit_analytics_api/Status/" method="post">
  <input type="hidden" name="Password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="submit" name="submit" value="Fetch status" />
</form>
</fieldset>


<fieldset><legend>GetLastJobLogResult</legend>
<form action="http://192.168.1.253/~stefand/zzWP/vizzit_analytics_api/GetLastJobLogResult/" method="post">
  <input type="hidden" name="Password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="submit" name="submit" value="Fetch status" />
</form>
</fieldset>


<fieldset><legend>ExecuteJob</legend>
<form action="http://192.168.1.253/~stefand/zzWP/vizzit_analytics_api/ExecuteJob/" method="post">
  <input type="hidden" name="Password" value="w/+OgbIlD8dHidh1xa0abQ==" />

  <input type="hidden" name="ProcessTree" value="true" />

  <input type="hidden" name="ProcessGroups" value="false" />
  <input type="hidden" name="ProcessLog" value="false" />
  <input type="hidden" name="Attachfiles" value="false" />
  <input type="hidden" name="ProcessLogsFrom" value="0000-00-00 00:00:00" />
  <input type="hidden" name="ProcessLogsTo" value="0000-00-00 00:00:00" />

  <input type="submit" name="submit" value="Process structure manually" />
</form>
</fieldset>

<fieldset><legend>SetScheduleEnabled</legend>
<form action="http://192.168.1.253/~stefand/zzWP/vizzit_analytics_api/SetScheduleEnabled/" method="post">
  <input type="hidden" name="Password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="hidden" name="EnableSchedule" value="true" />
  <input type="submit" name="submit" value="Enable" />
</form>

<form action="http://192.168.1.253/~stefand/zzWP/vizzit_analytics_api/SetScheduleEnabled/" method="post">
  <input type="hidden" name="Password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="hidden" name="EnableSchedule" value="false" />
  <input type="submit" name="submit" value="Disable" />
</form>
</fieldset>

</html>
*/

}
?>