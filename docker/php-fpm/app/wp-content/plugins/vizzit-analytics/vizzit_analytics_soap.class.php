<?php
/**
 * API Class
 * Version 0.5.1
 */
if( !class_exists( 'Vizzit_Analytics_Soap' ) ) {

  class Vizzit_Analytics_Soap_Server extends Vizzit_Analytics_Core {
    var $options 	= false;
    var $password 	= false;

    /**
     * Constructur, load all required stuff.
     */
    function __construct()
    {
      parent::__construct();

      $options 	= $this->get_options();

      // calculate password for current customer
      $sum	= $options[ 'va_customer_id' ] . $options[ 'va_crypt_iv' ];
      $pass = md5( $sum );
      $pass = pack('H*', $pass );
      $pass = base64_encode( $pass );

      // define
      $this->options 	= $options;
      $this->password	= $pass;
    }


    /**
     *
     */
    function soap_output( $output ) {
      return json_encode( $output );
    }


    /**
     *
     */
    function meta_get_last_schedule_history_row() {
      // fetch last history row
      $schedule_history_rows = $this->get_schedule_history_rows( array( 'limit' => 1 ) );
      if( count( $schedule_history_rows ) > 0 ) {
        foreach( $schedule_history_rows as $k => $h_data ) {
          $response = array(
            'Response' => 'OK',
            'Data' => array(
              'sequenceNumber'	=> $h_data[ 'va_sequence' ],
              'datetimeStart'	=> $h_data[ 'va_date_start' ],
              'datetimeEnd'		=> $h_data[ 'va_date_end' ],
              'startedBy' 		=> $h_data[ 'va_exec' ],
              'overallStatus' 	=> $h_data[ 'va_status' ],
              #'message' 		=> $h_data[ 'va_message' ],
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




    // http://www.php-resource.de/handbuch/ref.simplexml.htm
    function simplexml( $args ) {
      $xml = new SimpleXMLElement( file_get_contents( plugin_dir_path( __FILE__ ) . 'ws_status.xml' ) );

      $xml->addAttribute( 'Date', date( 'Y-m-d H:i:s' ) );
      $xml->CmsVersion 				= $args[ 'CmsVersion' ];
      $xml->VizzitPluginVersion 	= $args[ 'VizzitPluginVersion' ];
      $xml->SystemOS 				= $args[ 'SystemOS' ];
      $xml->SystemHostName 			= $args[ 'SystemHostName' ];

      $xml->SystemIps->addChild( 'IpAddress', $args[ 'SystemIps' ][0]['IpAddress'] );

      foreach( $args[ 'EnabledVizzitViews' ][ 0 ][ 'View' ] as $k => $views ) {
        $view = $xml->EnabledVizzitViews->addChild( 'View' );
        $view->addAttribute( 'name' , $views[ '@attributes' ][ 'name' ] );
        foreach( $views[ 'Group' ] as $role ) {
          $view->addChild( 'Group', $role );
        }
      }

      $xml->SequenceNumber 			= $args[ 'SequenceNumber' ];
      $xml->CustomerId 				= $args[ 'CustomerId' ];
      $xml->LogMethod 				= $args[ 'LogMethod' ];
      $xml->TestMode 				= ( $args[ 'TestMode' ] === false ? 'false' : 'true' );
      $xml->AnonymizeUserNames 		= ( $args[ 'AnonymizeUserNames' ] === false ? 'false' : 'true' );
      $xml->AnonymizeIP 			= ( $args[ 'AnonymizeIP' ] === false ? 'false' : 'true' );
      $xml->AppendUsername 			= ( $args[ 'AppendUsername' ] === false ? 'false' : 'true' );
      $xml->TimeOnPage 				= ( $args[ 'TimeOnPage' ] === false ? 'false' : 'true' );
      $xml->GroupsProcessEnabled 	= ( $args[ 'GroupsProcessEnabled' ] === false ? 'false' : 'true' );
      $xml->TreeProcessEnabled 		= ( $args[ 'TreeProcessEnabled' ] === false ? 'false' : 'true' );
      $xml->TransferMethod 			= $args[ 'TransferMethod' ];
      $xml->HTTPUploadTarget 		= $args[ 'HTTPUploadTarget' ];
      $xml->SchedulerStatus 		= ( $args[ 'SchedulerStatus' ] === false ? 'false' : 'true' );
      $xml->SchedulerNextExec 		= $args[ 'SchedulerNextExec' ];

      $xml->TempFolder->addChild( 'Purge', ( $args[ 'TempFolder' ][0]['Purge'] === false ? 'false' : 'true' ) );
      $xml->TempFolder->addChild( 'Location', $args[ 'TempFolder' ][0]['Location'] );
      $xml->TempFolder->addChild( 'PartitionFreeBytes', $args[ 'TempFolder' ][0]['PartitionFreeBytes'] );
      $xml->TempFolder->addChild( 'PartitionTotalBytes', $args[ 'TempFolder' ][0]['PartitionTotalBytes'] );

      if( count( $args[ 'TreeStructure' ][0] ) > 0 ) {
        $xml->TreeStructure->addChild( 'Roots' );
        foreach( $args[ 'TreeStructure' ][0][ 'Roots' ][ 'Root' ] as $k => $roots ) {
          $root = $xml->TreeStructure->Roots->addChild( 'Root' );
          $root->addChild( 'RootId', $roots[ 'RootId' ] );
        }
      }

      if( count( $args[ 'JobHistory' ][0] ) > 0 ) {
        foreach( $args[ 'JobHistory' ][0]['Job'] as $k => $jobs ) {
          $job = $xml->JobHistory->addChild( 'Job' );
          $job->addChild( 'ST', $jobs[ 'ST' ] );
          $job->addChild( 'ET', $jobs[ 'ET' ] );
          $job->addChild( 'PG', ( $jobs[ 'PG' ] === false ? 'false' : 'true' ) );
          $job->addChild( 'PT', ( $jobs[ 'PT' ] === false ? 'false' : 'true' ) );
          $job->addChild( 'PL', ( $jobs[ 'PL' ] === false ? 'false' : 'true' ) );
          $job->addChild( 'S', $jobs[ 'S' ] );
          $job->E = simplexml_load_string('<E>' . $jobs[ 'E' ][ '@cdata' ] . '</E>'); // simpleXML can't CDATA
          $job->addChild( 'SB', $jobs[ 'SB' ] );
          $job->addChild( 'SQ', $jobs[ 'SQ' ] );

          $jobt = $job->addChild( 'T' );
            $jobt->addChild( 'S', $jobs[ 'T' ][ 'S' ] );
            $jobt->addChild( 'P', $jobs[ 'T' ][ 'P' ] );
            $jobt->E = simplexml_load_string('<E>' . $jobs[ 'T' ][ 'E' ][ '@cdata' ] . '</E>'); // simpleXML can't CDATA
          $jobsf = $job->addChild( 'SF' );
            $jobsf->addChild( 'S', $jobs[ 'SF' ][ 'S' ] );
            $jobsf->E = simplexml_load_string('<E>' . $jobs[ 'SF' ][ 'E' ][ '@cdata' ] . '</E>'); // simpleXML can't CDATA
        }
      }

      return $xml->asXML();
    }



    function Status( $args ) {
      if( $args[ 'password' ] == $this->password ) {

        require_once plugin_dir_path( __FILE__ ) . 'Array2XML.php';
        if( class_exists( 'Array2XML' ) ) {
          // TODO: get from options which are active --> set the "right" attribute-names, get the groups for which these are active
          $roles = array( 'Administrator', 'Editor', 'Author', 'Contributor' );

          // get scheduler status
          $wp_scheduler = wp_get_schedule( $this->e_schedule );

          // prepare variables so that it will be "clean" later on
          $o_cms_version					= 'WordPress ' . get_bloginfo ( 'version' );
          $o_system_os						= ( ( $_SERVER[ 'SERVER_SOFTWARE' ] != '' ) ? $_SERVER[ 'SERVER_SOFTWARE' ] : '' );
          $o_system_host_name				= ( ( $_SERVER[ 'SERVER_NAME' ] != '' ) ? $_SERVER[ 'SERVER_NAME' ] : '' );
          $o_system_ip_address				= ( ( $_SERVER[ 'SERVER_ADDR' ] != '' ) ? array( 'IpAddress' => $_SERVER[ 'SERVER_ADDR' ] ) : array() );
          $o_enabled_vizzit_views	= array();
            if( $this->options[ 'va_display_vds' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vds' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_v2' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'v2' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_vwm' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vwm' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_vms' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vms' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_portal' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes' => array( 'name' => 'portal' ), 'Group' => array_values( $roles ) ); }
          $o_sequence_number				= (int) $this->get_last_sequence_number( false );
          $o_sequence_number				= ( ( $o_sequence_number >= 0 ) ? $o_sequence_number : '' );
          $o_customer_id 					= ( ( $this->options[ 'va_customer_id' ] != '' ) ? $this->options[ 'va_customer_id' ] : '' );
          $o_test_mode 						= ( ( $this->options[ 'va_test_mode' ] != '' ) ? true : false );
          $o_anonymize_usernames 			= ( ( $this->options[ 'va_anonymize_usernames' ] == 'on' ) ? true : false );
          $o_anonymize_ip 					= ( ( $this->options[ 'va_anonymize_ip' ] == 'on' ) ? true : false );
          $o_append_username 				= ( ( $this->options[ 'va_append_username' ] == 'on' ) ? true : false );
          $o_time_on_page 					= ( ( $this->options[ 'va_time_on_page' ] == 'on' ) ? true : false );
          $o_scheduler 						= ( ( $this->options[ 'va_scheduler' ] == 'on' ) ? true : false );
          $o_scheduler_next_exec			= ( ( $wp_scheduler !== false ) ? date( 'Y-m-d H:i:s', wp_next_scheduled( $this->e_schedule ) ) : '0000-00-00 00:00:00' );
          $o_disk_free_space 				= disk_free_space( dirname( __FILE__ ) );
          $o_disk_free_space				= ( $o_disk_free_space === false ) ? '' : $o_disk_free_space;
          $o_disk_total_space 				= disk_total_space( dirname( __FILE__ ) );
          $o_disk_total_space				= ( $o_disk_total_space === false ) ? '' : $o_disk_total_space;
          $_o_temp_folder					= array();
          $_o_temp_folder					= $_o_temp_folder = array( 'Purge' => false, 'Location' => dirname( __FILE__ ) . '/' . VAWP_DIR_TMP_FILES, 'PartitionFreeBytes' => $o_disk_free_space, 'PartitionTotalBytes' => $o_disk_total_space );
          $o_structure_roots				= array();
            if( $this->options[ 'va_structure_include_pages' ] == 'on' ) { $o_structure_roots['Roots']['Root'][] = array( 'RootId' => 'PAGES' ); }
            if( $this->options[ 'va_structure_include_posts' ] == 'on' ) { $o_structure_roots['Roots']['Root'][] = array( 'RootId' => 'POSTS' ); }
            if( $this->options[ 'va_structure_include_wordpress_system' ] == 'on' ) { $o_structure_roots['Roots']['Root'][] = array( 'RootId' => 'WORDPRESS' ); }
          $schedule_history_rows 			= array();
          $schedule_history_rows 			= $this->get_schedule_history_rows( array( 'limit' => 10 ) );
          if( count( $schedule_history_rows ) > 0 ) {
            foreach( $schedule_history_rows as $k => $h_data ) {
              $o_job_history_job['Job'][] = array(
                'ST' 	=> '' . $h_data[ 'va_date_start' ],
                'ET' 	=> '' . $h_data[ 'va_date_end' ],
                'PG' 	=> false,
                'PT' 	=> true, // $_BOOL_PROCESS_TREE_THIS_EXEC
                'PL' 	=> false,
                'S' 	=> '' . $h_data[ 'va_status' ],
                'E' 	=> array( '@cdata' => '' . filter_var( $h_data[ 'va_message' ], FILTER_SANITIZE_SPECIAL_CHARS ) ),
                'SB' 	=> '' . $h_data[ 'va_exec' ],
                'SQ' 	=> '' . $h_data[ 'va_sequence' ],
                'T'	=> array(
                  'S' => '' . $h_data[ 'va_tree_status' ],
                  'P' => '' . $h_data[ 'va_tree_num_pages_read' ],
                  'E' => array( '@cdata' => '' . filter_var( $h_data[ 'va_tree_excpt' ], FILTER_SANITIZE_SPECIAL_CHARS ) )
                ),
                'SF'	=> array(
                  'S' => '' . $h_data[ 'va_send_status' ],
                  'E' => array( '@cdata' => '' . filter_var( $h_data[ 'va_send_excpt' ], FILTER_SANITIZE_SPECIAL_CHARS ) )
                )
              );
            } // end foreach()
          } // end count

          // build XML
          $meta = array();
          $meta[ '@attributes' ] = array( 'Date' => date( 'Y-m-d H:i:s' ) );

          $meta[ 'CmsVersion' ] 			= $o_cms_version;
          $meta[ 'VizzitPluginVersion' ] 	= VAWP_VERSION;
          $meta[ 'SystemOS' ] 				= $o_system_os;
          $meta[ 'SystemHostName' ] 		= $o_system_host_name;
          $meta[ 'SystemIps' ] 				= array();
          $meta[ 'SystemIps' ][] 			= $o_system_ip_address;
          $meta[ 'EnabledVizzitViews' ] 	= array();
          $meta[ 'EnabledVizzitViews' ][] 	= $o_enabled_vizzit_views;
          $meta[ 'SequenceNumber' ] 		= $o_sequence_number;
          $meta[ 'CustomerId' ] 			= $o_customer_id;
          $meta[ 'LogMethod' ] 				= 'Tag';
          $meta[ 'TestMode' ] 				= $o_test_mode;
          $meta[ 'AnonymizeUserNames' ] 	= $o_anonymize_usernames;
          $meta[ 'AnonymizeIP' ] 			= $o_anonymize_ip; // TODO: this variable is missing in the specification
          $meta[ 'AppendUsername' ] 		= $o_append_username; // TODO: this variable is missing in the specification
          $meta[ 'TimeOnPage' ] 			= $o_time_on_page; // TODO: this variable is missing in the specification
          $meta[ 'GroupsProcessEnabled' ] 	= false;
          $meta[ 'TreeProcessEnabled' ] 	= $o_scheduler; // TODO: have this as an option in wordpress admin: as it is just the structure which is scheduled for tag-customers - use the same variable as for the scheduler
          $meta[ 'TransferMethod' ] 		= 'HTTP';
          $meta[ 'HTTPUploadTarget' ] 		= VAWP_PATH_FILE_UPLOAD;
          $meta[ 'SchedulerStatus' ] 		= $o_scheduler;
          $meta[ 'SchedulerNextExec' ] 		= $o_scheduler_next_exec;
          $meta[ 'TempFolder' ] 			= array();
          $meta[ 'TempFolder' ][] 			= $_o_temp_folder;
          $meta[ 'TreeStructure' ] 			= array();
          $meta[ 'TreeStructure' ][] 		= $o_structure_roots;
          $meta[ 'JobHistory' ] 			= array();
          $meta[ 'JobHistory' ][] 			= $o_job_history_job;

          // check if DOM could be used to create the XML
          if( class_exists( 'DOMDocument' ) ) {
            $xml = Array2XML::createXML( 'StatusReport', $meta );
            return $xml->saveXML();
          } else {
            // Fallback for not having libxml/libxsl on all servers
            return $this->simplexml( $meta );
          }

        } // end class_exists

        return $this->soap_output( array( 'Response' => 'FAILED', 'Data' => 'API KEY FAILED' ) );
      }
    }


    /**
     *
     */
    function __Status( $args ) {
      if( $args[ 'password' ] == $this->password ) {
        require_once plugin_dir_path( __FILE__ ) . 'Array2XML.php';
        // be shure that the required class exists
        if( class_exists( 'Array2XML' ) ) {
          // TODO: get from options which are active --> set the "right" attribute-names, get the groups for which these are active
          $roles = array( 'Administrator', 'Editor', 'Author', 'Contributor' );
          /*
          Super Admin - Someone with access to the blog network administration features controlling the entire network (See Create a Network).
          Administrator - Somebody who has access to all the administration features
          Editor - Somebody who can publish and manage posts and pages as well as manage other users' posts, etc.
          Author - Somebody who can publish and manage their own posts
          Contributor - Somebody who can write and manage their posts but not publish them
          Subscriber - Somebody who can only manage their profile
          */

          // get scheduler status
          $wp_scheduler = wp_get_schedule( $this->e_schedule );

          // prepare variables so that it will be "clean" later on
          $o_cms_version					= 'WordPress ' . get_bloginfo ( 'version' );
          $o_system_os						= ( ( $_SERVER[ 'SERVER_SOFTWARE' ] != '' ) ? $_SERVER[ 'SERVER_SOFTWARE' ] : '' );
          $o_system_host_name				= ( ( $_SERVER[ 'SERVER_NAME' ] != '' ) ? $_SERVER[ 'SERVER_NAME' ] : '' );
          $o_system_ip_address				= ( ( $_SERVER[ 'SERVER_ADDR' ] != '' ) ? array( 'IpAddress' => $_SERVER[ 'SERVER_ADDR' ] ) : array() );
          $o_enabled_vizzit_views	= array();
            if( $this->options[ 'va_display_vds' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vds' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_v2' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'v2' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_vwm' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vwm' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_vms' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes'	=> array( 'name' => 'vms' ), 'Group' => array_values( $roles ) ); }
            if( $this->options[ 'va_display_portal' ] == 'on' ) { $o_enabled_vizzit_views['View'][] = array( '@attributes' => array( 'name' => 'portal' ), 'Group' => array_values( $roles ) ); }
          $o_sequence_number				= (int) $this->get_last_sequence_number( false );
          $o_sequence_number				= ( ( $o_sequence_number >= 0 ) ? $o_sequence_number : '' );
          $o_customer_id 					= ( ( $this->options[ 'va_customer_id' ] != '' ) ? $this->options[ 'va_customer_id' ] : '' );
          $o_test_mode 						= ( ( $this->options[ 'va_test_mode' ] != '' ) ? true : false );
          $o_anonymize_usernames 			= ( ( $this->options[ 'va_anonymize_usernames' ] == 'on' ) ? true : false );
          $o_anonymize_ip 					= ( ( $this->options[ 'va_anonymize_ip' ] == 'on' ) ? true : false );
          $o_append_username 				= ( ( $this->options[ 'va_append_username' ] == 'on' ) ? true : false );
          $o_time_on_page 					= ( ( $this->options[ 'va_time_on_page' ] == 'on' ) ? true : false );
          $o_scheduler 						= ( ( $this->options[ 'va_scheduler' ] == 'on' ) ? true : false );
          $o_scheduler_next_exec			= ( ( $wp_scheduler !== false ) ? date( 'Y-m-d H:i:s', wp_next_scheduled( $this->e_schedule ) ) : '0000-00-00 00:00:00' );
          $o_disk_free_space 				= disk_free_space( dirname( __FILE__ ) );
          $o_disk_free_space				= ( $o_disk_free_space === false ) ? '' : $o_disk_free_space;
          $o_disk_total_space 				= disk_total_space( dirname( __FILE__ ) );
          $o_disk_total_space				= ( $o_disk_total_space === false ) ? '' : $o_disk_total_space;
          $_o_temp_folder					= array();
          $_o_temp_folder					= $_o_temp_folder = array( 'Purge' => false, 'Location' => dirname( __FILE__ ) . '/' . VAWP_DIR_TMP_FILES, 'PartitionFreeBytes' => $o_disk_free_space, 'PartitionTotalBytes' => $o_disk_total_space );
          $o_structure_roots				= array();
            if( $this->options[ 'va_structure_include_pages' ] == 'on' ) { $o_structure_roots['Roots']['Root'][] = array( 'RootId' => 'PAGES' ); }
            if( $this->options[ 'va_structure_include_posts' ] == 'on' ) { $o_structure_roots['Roots']['Root'][] = array( 'RootId' => 'POSTS' ); }
          $schedule_history_rows 			= array();
          $schedule_history_rows 			= $this->get_schedule_history_rows( array( 'limit' => 10 ) );
          if( count( $schedule_history_rows ) > 0 ) {
            foreach( $schedule_history_rows as $k => $h_data ) {
              $o_job_history_job['Job'][] = array(
                'ST' 	=> '' . $h_data[ 'va_date_start' ],
                'ET' 	=> '' . $h_data[ 'va_date_end' ],
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
          $meta[ 'SystemOS' ] 				= $o_system_os;
          $meta[ 'SystemHostName' ] 		= $o_system_host_name;
          $meta[ 'SystemIps' ] 				= array();
          $meta[ 'SystemIps' ][] 			= $o_system_ip_address;
          $meta[ 'EnabledVizzitViews' ] 	= array();
          $meta[ 'EnabledVizzitViews' ][] 	= $o_enabled_vizzit_views;
          $meta[ 'SequenceNumber' ] 		= $o_sequence_number;
          $meta[ 'CustomerId' ] 			= $o_customer_id;
          $meta[ 'LogMethod' ] 				= 'Tag';
          $meta[ 'TestMode' ] 				= $o_test_mode;
          $meta[ 'AnonymizeUserNames' ] 	= $o_anonymize_usernames;
          $meta[ 'AnonymizeIP' ] 			= $o_anonymize_ip; // TODO: this variable is missing in the specification
          $meta[ 'AppendUsername' ] 		= $o_append_username; // TODO: this variable is missing in the specification
          $meta[ 'TimeOnPage' ] 			= $o_time_on_page; // TODO: this variable is missing in the specification
          $meta[ 'GroupsProcessEnabled' ] 	= false;
          $meta[ 'TreeProcessEnabled' ] 	= $o_scheduler; // TODO: have this as an option in wordpress admin: as it is just the structure which is scheduled for tag-customers - use the same variable as for the scheduler
          $meta[ 'TransferMethod' ] 		= 'HTTP';
          $meta[ 'HTTPUploadTarget' ] 		= VAWP_PATH_FILE_UPLOAD;
          $meta[ 'SchedulerStatus' ] 		= $o_scheduler;
          $meta[ 'SchedulerNextExec' ] 		= $o_scheduler_next_exec;
          $meta[ 'TempFolder' ] 			= array();
          $meta[ 'TempFolder' ][] 			= $_o_temp_folder;
          $meta[ 'TreeStructure' ] 			= array();
          $meta[ 'TreeStructure' ][] 		= $o_structure_roots;
          $meta[ 'JobHistory' ] 			= array();
          $meta[ 'JobHistory' ][] 			= $o_job_history_job;


          // check if DOM could be used to create the XML
          if( class_exists( 'DOMDocument' ) ) {
            $xml = Array2XML::createXML( 'StatusReport', $meta );
            return $xml->saveXML();
          } else {
            // Fallback for not having libxml/libxsl on all servers
            return $this->simplexml( $meta );
          }

        } // end class_exists
      }
    }


    /**
     * Enable/Disable scheduler
     */
    function SetScheduleEnabled( $args ) {
      if( $args[ 'password' ] == $this->password ) {
        // check if all parameters are set and have required type
        if( isset( $args[ 'enableSchedule' ] ) && ( $args[ 'enableSchedule' ] === true || $args[ 'enableSchedule' ] === false ) ) {
          // prepare option
          $this->options[ 'va_scheduler' ] = ( ( $args[ 'enableSchedule' ] === true ) ? 'on' : '' );
          // store modified option to DB
          $this->update_options($this->options);
          // read from DB to get the "real" value
          $this->options = $this->get_options();
          // return result
          return $this->soap_output( array( 'Response' => 'OK', 'Data' => array( 'enableSchedule' => ( ( $this->options[ 'va_scheduler' ] == 'on' ) ? true : false ) ) ) );
        }
      } else {
        return $this->soap_output( array( 'Response' => 'FAILED', 'Data' => 'API KEY FAILED' ) );
      }
    } // fn


    /**
     * Get the last history row
     */
    function GetLastJobLogResult( $args ) {
      if( $args[ 'password' ] == $this->password ) {
        return $this->meta_get_last_schedule_history_row();
      } else {
        return $this->soap_output( array( 'Response' => 'FAILED', 'Data' => 'API KEY FAILED' ) );
      }
    }


    /**
     * Process structure
     */
    function ExecuteJob( $args ) {
      if( $args[ 'password' ] == $this->password ) {
        if( isset( $args[ 'processTree' ] ) && $args[ 'processTree' ] == true ) {
          // process manually
          $this->vizzit_analytics_process( 'MANUAL' );

          // show last history entry
          return $this->soap_output( $this->meta_get_last_schedule_history_row() );
        }
      } else {
        return $this->soap_output( array( 'Response' => 'FAILED', 'Data' => 'API KEY FAILED' ) );
      }
    }


  } // class








  class Vizzit_Analytics_Soap extends Vizzit_Analytics_Core
  {

    /**
     * Constructur, load all required stuff.
     */
    function __construct()
    {
      parent::__construct();

      $options = get_site_option(VAWP_OPTION_NAME);

      // check if webservice is allowed
      if( $options[ 'va_webservice' ] == 'on' ) {
        // Using a filter instead of an action to create the rewrite rules.
        // Write rules -> Add query vars -> Recalculate rewrite rules
        add_filter( 'rewrite_rules_array', 	array( &$this, 'create_rewrite_rules' ) );
        add_filter( 'query_vars', 			array( &$this, 'add_query_vars' ) );

        // Recalculates rewrite rules during admin init to save resourcees.
        // Could probably run it once as long as it isn't going to change or check the
        // $wp_rewrite rules to see if it's active.
        add_filter( 'admin_init', 			array( &$this, 'flush_rewrite_rules' ) );
        add_action( 'template_redirect', 	array( &$this, 'template_redirect_intercept' ) );
      }

    } // end __construct()


    /**
     *
     */
    function activate() {
        global $wp_rewrite;
        $this->flush_rewrite_rules();
    }


    /**
     *
     */
    // Took out the $wp_rewrite->rules replacement so the rewrite rules filter could handle this.
    function create_rewrite_rules($rules) {
        global $wp_rewrite;
        $newRule = array( 'episerver_vizzit/ws/(.+)' => 'index.php?vizzit_analytics_soap=' . $wp_rewrite->preg_index(1) );
        $newRules = $newRule + $rules;
        return $newRules;
    }


    /**
     *
     */
    function add_query_vars($qvars) {
        $qvars[] = 'vizzit_analytics_soap';
        return $qvars;
    }


    /**
     *
     */
    function flush_rewrite_rules() {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }


    /**
     *
     */
    function template_redirect_intercept() {
      global $wp_query;

      // do nothing and go on when not the right parameter
      if( !$wp_query->get( 'vizzit_analytics_soap' ) ) {
        return;
      }

      // set some soap params
      $soap_config = array(
        'encoding' 	=> 'UTF-8',
        'uri' 		=> 'urn:' . VAWP_HOOK
      );

      try {
        // Run SOAP server

        $soap_server = new SoapServer( NULL, $soap_config );
        $soap_server->setClass( 'Vizzit_Analytics_Soap_Server' );
        $soap_server->handle();

/*
        // use WSDL-file instead
        // Don't cache WSDL files, mandatory for development
        ini_set( 'soap.wsdl_cache_enabled', '0' );
        $soap_server = new SoapServer( 'http://192.168.1.253/~stefand/zzWP/wp-content/plugins/vizzit-analytics-for-wordpress/vizzit.wsdl', array( 'encoding' => 'UTF-8' ) );
        #$soap_server = new SoapServer( 'http://192.168.1.253/~stefand/zzWP/episerver_vizzit/ws/vizzit.asmx?wsdl', array( 'encoding' => 'UTF-8' ) );
        $soap_server->setClass( 'Vizzit_Analytics_Soap_Server' );
        $soap_server->handle();
*/
      } catch (SOAPFault $f) {
        print $f->faultstring;
      }

      exit;
    }

  } // end class Vizzit_Analytics_Api

}
?>