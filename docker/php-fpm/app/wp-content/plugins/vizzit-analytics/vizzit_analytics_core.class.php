<?php
/**
 * Backend Class
 * Version 0.5.1
 */
if( !class_exists( 'Vizzit_Analytics_Core' ) ) {
  class Vizzit_Analytics_Core {

    var $hook 			= '';
    var $filename		= '';
    var $longname		= '';
    var $shortname		= '';
    var $vaicon			= '';
    var $vaicon32		= '';
    var $homepage		= '';
    var $currentUser 	= false;
    var $is_network = false;
    var $networkhook = 'vizzit-analytics-for-wordpress/vizzit-analytics-for-wordpress.php';

    var $e_schedule		= 'vizzit_analytics_process_daily'; // event-hook for scheduler
    var $fn_schedule	= 'vizzit_analytics_process'; // function to use for event-hook

    var $process_msg	= array(); // all messages during processing
    var $process_meta	= array( // meta information about processing
      'tree' => array( 'status' => 'FAILED', 'num_pages_read' => 0, 'excpt' => '' ),
      'send' => array( 'status' => 'FAILED', 'excpt' => '' )
    );
    var $process_msg_count	= array(); // amount of errors, warnings, status messages


    /**
     * Constructur, load all required stuff.
     */
    function __construct()
    {
      // Check if network enabled or not
      if(is_multisite() && is_plugin_active_for_network($this->networkhook))
        $this->is_network = true;
    }


    /**
     * Really basic stuff when initializing
     */
    function vizzit_analytics_init() {
      // Load localization
      load_plugin_textdomain( VAWP_LOCALE_HOOK, false, dirname( plugin_basename( __FILE__ ) ) . '/' . VAWP_DIR_LOCALE );

      // Get the current WordPress user for hrefs to Vizzit
      $this->get_current_wp_user();
    } // end vizzit_analytics_init()


    /**
     * Creates key and iv for encryption
     */
    function generate_crypt_keys() {
      $key	= hash( 'sha256', $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] . date( 'Y-m-d H:i:s' ), TRUE );
      $key 	= substr( $key, 0, mcrypt_enc_get_key_size( mcrypt_module_open( MCRYPT_3DES, '', MCRYPT_MODE_CBC, '' ) ) ); // cut off all unnecessary after max-supported keylength for chosen crypt
      $iv	= mcrypt_create_iv( 8 );

      return array( 'key' => $key, 'iv' => $iv);
    }

    /**
     * Get options depending on if it's network or not
     * @return array
     */
    function get_options()
    {
      return $this->get_option(VAWP_OPTION_NAME);
    }

    /**
     * Get option depending on if it's network or not
     * @param string $option
     * @return array
     */
    function get_option($option)
    {
      if($this->is_network)
        return get_site_option($option);
      else
        return get_option($option);
    }

    /**
     * Get options depending on if it's network or not
     * @param array $options
     * @return array
     */
    function update_options($options)
    {
      if($this->is_network)
        return update_site_option(VAWP_OPTION_NAME, $options);
      else
        return update_option(VAWP_OPTION_NAME, $options);
    }

    /**
     * NOT USED RIGHT NOW: Process groups
     */
    /*
    function process_groups() {
      global $wp_roles;

      // http://www.garyc40.com/2010/04/ultimate-guide-to-roles-and-capabilities/
      // get_userdata() is located in wp-includes/pluggable.php

      $va_wp_groups 		= array(); // groups-array as needed for json
      $va_wp_groups_users 	= array(); // temporary array which stores all users for the different roles
      $va_wp_users 			= array(); // users-array as needed for json

      $user_levels = array(
        '0' 	=> 'Subscriber',
        '1' 	=> 'Contributor',
        '2' 	=> 'Author',
        '3' 	=> 'Editor',
        '4' 	=> 'Editor',
        '5' 	=> 'Editor',
        '6' 	=> 'Editor',
        '7' 	=> 'Editor',
        '8' 	=> 'Administrator',
        '9' 	=> 'Administrator',
        '10'	=> 'Administrator'
      );

      // get all role-names
      $roles = $wp_roles->get_names();

      // http://codex.wordpress.org/Function_Reference/get_users
      // &role=editor
      $wp_user_search = get_users( 'fields=all_with_meta&orderby=registered&order=ASC' );
      foreach( $wp_user_search as $u ) {
        $user_id 		= (int) $u->ID;
        $user_level		= (int) $u->user_level;
        $user_login		= stripslashes( $u->user_login );
        $user_full_name	= ucwords( strtolower( $u->first_name . ' ' . $u->last_name ) );
        $user_email		= stripslashes( $u->user_email );

        // add to editors
        $va_wp_users[] = array(
          'Username'	=> $user_login,
          'FullName' 	=> $user_full_name,
          'Email' 		=> $user_email
        );

        // create array with all users for the roles
        $va_wp_groups_users[ $user_levels[ $user_level ] ][] = $user_login;
      }

      // combine users with the groups
      foreach( $roles as $role => $role_name ) {
        $users_in_group = ( count( $va_wp_groups_users[ $role_name ] ) > 0 ) ? $va_wp_groups_users[ $role_name ] : array();
        $va_wp_groups[] = array( 'GroupName' => $role_name, 'UsersInGroup' => $users_in_group );
      }

      $json_arr = array(
        'Groups' 	=> $va_wp_groups,
        'Users' 	=> $va_wp_users
      );

      return $json_arr;
    } // end process_groups()
    */


    /**
     * Parse structure and write into file
     */
    function process_structure_write($args)
    {
      global $wpdb;
      $options = $this->get_options();
      $defaults = array("filename_tree" => false);
      $args = wp_parse_args($args, $defaults);

      // TODO: check out this to get temp-dir as well as just have "virtual"/"real" temp files
      #http://php.net/manual/de/function.sys-get-temp-dir.php
      #http://php.net/manual/de/function.tempnam.php
      #http://www.php.net/manual/de/function.tmpfile.php

      // TODO: decide if to use all post_status (just don't set the variable in the array uses all as default) or one of "publish", "draft", "future", ( "private" ?)

      #------------------------------------------------------------------

      # Go through each blog (multisite in mind)
      $treeFileContent = '';
      $current = $wpdb->blogid;
      $blogs = array($current);
      
      // If plugin is network enabled, get all blogs
      if($this->is_network)
        $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

      foreach ($blogs as $blogId)
      {
        if(is_multisite())
          switch_to_blog($blogId);

        # Get tree-structure for ALL post types
        if(isset($options['va_structure_include_all']) && $options['va_structure_include_all'] == 'on')
        {
          # Get post types
          $post_types = get_post_types('', 'names');
          
          # Get tree-structure for each post type
          foreach($post_types as $post_type) {
            $this->ptree(array(
              'post_type' => $post_type,
              'post_status' => 'publish'));
          }
        }
        # Get tree-structure for all PUBLIC post types
        else if(isset($options['va_structure_include_public']) && $options['va_structure_include_public'] == 'on')
        {
          # Set argument to fetch only public post types
          $post_type_args = array(
            'public'   => true
          );

          # Get post types
          $post_types = get_post_types($post_type_args, 'names');
          
          # Get tree-structure for each post type
          foreach($post_types as $post_type) {
            $this->ptree(array(
              'post_type' => $post_type,
              'post_status' => 'publish'));
          }
        }
        else {
          # Get tree-structure for pages
          if(isset($options['va_structure_include_pages']) && $options['va_structure_include_pages'] == 'on')
            $this->ptree(array(
              'post_type' => 'page',
              'post_status' => 'publish'));

          # Get tree-structure for posts
          if(isset($options['va_structure_include_posts']) && $options['va_structure_include_posts'] == 'on')
            $this->ptree(array(
              'post_type' => 'post',
              'post_status' => 'publish'));
        }

        // get the tree-structure for wordpress special URLs
        $wpSpecialTree = '';
        if(isset($options['va_structure_include_wordpress_system']) && $options['va_structure_include_wordpress_system'] == 'on')
        {
          $adminUrl = parse_url(admin_url('/', 'https')); // split permalink
          $rss2Url 	= parse_url(get_bloginfo('rss2_url')); // split permalink
          $rssUrl 	= parse_url(get_bloginfo('rss_url')); // split permalink
          $atomUrl 	= parse_url(get_bloginfo('atom_url')); // split permalink
          $rdfUrl 	= parse_url(get_bloginfo('rdf_url')); // split permalink

          $wpSpecialTree .= $this->return_structure_row(array("level" => "1", "name" => "[WordPress]", "url" => $adminUrl[ 'path' ], "id" => "wp", "pagetype" => VAWP_WP_PAGETYPE_FOLDER, "created_by" => VAWP_WP_SYSTEM_USER, "changed_by" => VAWP_WP_SYSTEM_USER, "status" => 'publish')) . "\n";
          $wpSpecialTree .= $this->return_structure_row(array("level" => "2", "name" => "FEED", "url" => $rss2Url[ 'path' ], "id" => "wp.feed", "pagetype" => VAWP_WP_PAGETYPE_FEED, "created_by" => VAWP_WP_SYSTEM_USER, "changed_by" => VAWP_WP_SYSTEM_USER, "status" => 'publish')) . "\n";
          $wpSpecialTree .= $this->return_structure_row(array("level" => "2", "name" => "RSS", "url" => $rssUrl[ 'path' ], "id" => "wp.feed.rss", "pagetype" => VAWP_WP_PAGETYPE_FEED, "created_by" => VAWP_WP_SYSTEM_USER, "changed_by" => VAWP_WP_SYSTEM_USER, "status" => 'publish')) . "\n";
          $wpSpecialTree .= $this->return_structure_row(array("level" => "2", "name" => "Atom", "url" => $atomUrl[ 'path' ], "id" => "wp.feed.atom", "pagetype" => VAWP_WP_PAGETYPE_FEED, "created_by" => VAWP_WP_SYSTEM_USER, "changed_by" => VAWP_WP_SYSTEM_USER, "status" => 'publish')) . "\n";
          $wpSpecialTree .= $this->return_structure_row(array("level" => "2", "name" => "RDF", "url" => $rdfUrl[ 'path' ], "id" => "wp.feed.rdf", "pagetype" => VAWP_WP_PAGETYPE_FEED, "created_by" => VAWP_WP_SYSTEM_USER, "changed_by" => VAWP_WP_SYSTEM_USER, "status" => 'publish')) . "\n";
        }

        $startNode = $this->return_structure_row(array("level" => "0", "name" => get_bloginfo('name'), "url" => get_bloginfo('url').'/', "id" => 'blog_' . $blogId, "pagetype" => 'page', "created_by" => VAWP_WP_SYSTEM_USER, "changed_by" => VAWP_WP_SYSTEM_USER, "status" => 'publish')) . "\n";
        $treeFileContent .= $startNode . $this->render_ptree();
        $treeFileContent .= $wpSpecialTree;
        #$treeFileContent .= $this->render_ptree() . $wpSpecialTree; // fetch formatted treefile rows
      }

      // Switch back to main blog
      if(is_multisite())
        switch_to_blog($current);

      // open file and insert content
      if( !$fp = fopen( $args[ 'filename_tree' ], "w" ) ) {
        $this->process_msg[] = '<strong>' . __( 'ERROR: could not open treeFile for write.', VAWP_LOCALE_HOOK ) . '</strong>';
        $this->process_msg_count[ 'error' ]++;
      }
      if( !fwrite( $fp, $treeFileContent ) ) {
        $this->process_msg[] = '<strong>' . __( 'ERROR: could not write to treeFile.', VAWP_LOCALE_HOOK ) . '</strong>';
        $this->process_msg_count[ 'error' ]++;
        // TODO: how these messages are stored needs to be refactored - as well as catch the previous error as well
        $this->process_meta[ 'tree' ][ 'status' ] = 'FAILED';
        $this->process_meta[ 'tree' ][ 'num_pages_read' ] = 0;
        $this->process_meta[ 'tree' ][ 'excpt' ] = __( 'ERROR: could not write to treeFile.', VAWP_LOCALE_HOOK );
      }

      if( is_file( $args[ 'filename_tree' ] ) === true ) {
        // count lines in file
        $lines = count( file( $args[ 'filename_tree' ] ) );
        $this->process_msg[] = 'OK: ' . $lines . ' ' . __( 'entries written to treeFile.', VAWP_LOCALE_HOOK );
        $this->process_msg_count[ 'status' ]++;
        // TODO: how these messages are stored needs to be refactored
        $this->process_meta[ 'tree' ][ 'status' ] = 'OK';
        $this->process_meta[ 'tree' ][ 'num_pages_read' ] = (int) $lines;
        $this->process_meta[ 'tree' ][ 'excpt' ] = $lines . ' ' . __( 'entries written to treeFile.', VAWP_LOCALE_HOOK );
        fclose( $fp );
      }

      return;
    } // end process_structure_write()


    /**
     * Create encrypted zip file of structure
     */
    function process_zip_write( $args ) {
      $options		= $this->get_options();
      $defaults 	= array(
        "filename_tree" 	=> false,
        "filename_zip"		=> false,
        "filename_encrypt"	=> false,
        "encrypt" 			=> true,
        "sequence_number"	=> '0'
      );
      $args 		= wp_parse_args( $args, $defaults );

      // zip the file
      if( !$this->create_zip( array( "files" => array( $args[ 'filename_tree' ] ), "destination" => $args[ 'filename_zip' ], "overwrite" => true, 'sequence_number'=> $args[ 'sequence_number' ]) ) ) {
        $this->process_msg[] = '<strong>' . __( 'ERROR: could not write zipFile.', VAWP_LOCALE_HOOK ) . '</strong>';
        $this->process_msg_count[ 'error' ]++;
      }

      // Open module, and create IV
      $td 	= mcrypt_module_open( MCRYPT_3DES, '', MCRYPT_MODE_CBC, '' );
      $key 	= base64_decode( $options[ 'va_crypt_key' ] );
      $iv  	= base64_decode( $options[ 'va_crypt_iv' ] );

      //open the file for reading
      if( !$f = fopen( $args[ 'filename_zip' ], 'r' ) ) {
        $this->process_msg[] = '<strong>' . __( 'ERROR: could not open zipFile for read.', VAWP_LOCALE_HOOK ) . '</strong>';
        $this->process_msg_count[ 'error' ]++;
      }
      if( !$fContents = fread( $f, filesize( $args[ 'filename_zip' ] ) ) ) {
        $this->process_msg[] = '<strong>' . __( 'ERROR: could not read from zipFile.', VAWP_LOCALE_HOOK ) . '</strong>';
        $this->process_msg_count[ 'error' ]++;
      }

      // write to encrypted file
      if( !$fw = fopen( $args[ 'filename_encrypt' ], "w" ) ) {
        $this->process_msg[] = '<strong>' . __( 'ERROR: could not open encryptFile for write.', VAWP_LOCALE_HOOK ) . '</strong>';
        $this->process_msg_count[ 'error' ]++;
      }

      // Initialize encryption handle
      if( mcrypt_generic_init( $td, $key, $iv ) != -1 ) {
        // Encrypt data
        $encryptedContents = mcrypt_generic( $td, $fContents );
        // write encrypted data
        if( !fwrite( $fw, $encryptedContents ) ) {
          $this->process_msg[] = '<strong>' . __( 'ERROR: could not write to encryptFile.', VAWP_LOCALE_HOOK ) . '</strong>';
          $this->process_msg_count[ 'error' ]++;
        }
      }

      // Clean up
      mcrypt_generic_deinit( $td );
      mcrypt_module_close( $td );
      fclose( $f );
      fclose( $fw );

      return;
    } // process_zip_write()


    /**
     * Upload of encrypted zip to Vizzit
     */
    function process_upload( $args ) {
      $options			= $this->get_options();
      $defaults 		= array(
        "filename_encrypt"	=> false,
      );
      $args 			= wp_parse_args( $args, $defaults );

      // check if customer_id exists and is not empty
      if( !isset( $options[ 'va_customer_id' ] ) || empty( $options[ 'va_customer_id' ] ) ) {
        return false;
      } else {
        ini_set( 'track_errors', 1 ); // enable tracking errors as it is not active as default
        $php_errormsg = ''; // just be shure to define variable

        $va_customer_id = ( $options[ 'va_test_mode' ] == 'on' ) ? $options[ 'va_customer_id' ] . '_test' : $options[ 'va_customer_id' ];
        $fileName 	= basename( $args[ 'filename_encrypt' ] );

        // Drop .encrypted in XXXXXXXXXXX.encrypted.zip
        $fileName 	= str_replace( '.encrypted', '', $fileName );
        $data 		= '';
        $boundary 	= "---------------------".substr( md5( rand( 0,32000 ) ), 0, 10 );

        // Collect Postdata
        $data .= "--$boundary\n";
        $data .= "Content-Disposition: form-data; name=\"CustomerId\"\n\n" . $va_customer_id . "\n";
        $data .= "--$boundary\n";

        // Add the file
        $data .= "--$boundary\n";
        $data .= "Content-Disposition: form-data; name=\"UploadFile\"; filename=\"" . $fileName . "\"\n";
        $data .= "Content-Type: application/octet-stream\n";
        $data .= "Content-Transfer-Encoding: binary\n\n";

        $fileContents = file_get_contents( $args[ 'filename_encrypt' ] ) . "\n";
        if( $fileContents == false ) {
          $this->process_msg[] = '<strong>' . __( 'ERROR: upload to Vizzit failed.', VAWP_LOCALE_HOOK ) . '<code> Unable to read file ' . $args[ 'filename_encrypt' ] . '</code></strong>'; // DEBUG: add the return value(s) to the message
          $this->process_msg_count[ 'error' ]++;
          $this->process_meta[ 'send' ][ 'status' ] = 'FAILED';
          $this->process_meta[ 'send' ][ 'excpt' ] 	= '<code>' . $php_errormsg . '</code>';
          return;
        }
        $data .= $fileContents."\n";
        $data .= "--$boundary--\n";

        $params = array( 'http' => array(
                         'method' => 'POST',
                         'header' => 'Content-Type: multipart/form-data; boundary=' . $boundary,
                         'content' => $data
        ));
        $ctx 	= stream_context_create( $params );
        $fp 	= fopen( VAWP_PATH_FILE_UPLOAD, 'rb', false, $ctx );

        if( !$fp ) {
          $this->process_msg[] = '<strong>' . __( 'ERROR: upload to Vizzit failed.', VAWP_LOCALE_HOOK ) . ' ' . '<code> Status code: ' . $php_errormsg . VAWP_PATH_FILE_UPLOAD . '</code></strong>'; // DEBUG: add the return value(s) to the message
          $this->process_msg_count[ 'error' ]++;
          $this->process_meta[ 'send' ][ 'status' ] 	= 'FAILED';
          $this->process_meta[ 'send' ][ 'excpt' ] 		= '<code>' . $php_errormsg . '</code>';
          return;
        }

        $response = @stream_get_contents( $fp );
        if( $response === false ) {
            $this->process_msg[] = '<strong>' . __( 'ERROR: upload to Vizzit failed.', VAWP_LOCALE_HOOK ) . ' ' . '<code> Status code: ' . $php_errormsg . VAWP_PATH_FILE_UPLOAD . '</code></strong>'; // DEBUG: add the return value(s) to the message
            $this->process_msg_count[ 'error' ]++;
            $this->process_meta[ 'send' ][ 'status' ] 	= 'FAILED';
            $this->process_meta[ 'send' ][ 'excpt' ] 	= '<code>' . $php_errormsg . '</code>';
            return;
         }

         #$this->process_msg[] = 'OK: SendFile (' . $data . ').';
         $this->process_msg[] = 'OK: SendFile.';
         $this->process_msg_count[ 'status' ]++;
         $this->process_meta[ 'send' ][ 'status' ] 	= 'OK';
         $this->process_meta[ 'send' ][ 'excpt' ] 	= '';

         return;
      }

    } // end process_upload()


    /**
     * Removes the temporarily created files during the process
     */
    function process_cleanup( $args ) {
      $defaults = array(
        "filename_tree" 	=> false,
        "filename_zip"		=> false,
        "filename_encrypt"	=> false
      );
      $args 	= wp_parse_args( $args, $defaults );

      // loop through all files
      foreach( $args as $k => $f ) {
        $parts = pathinfo( $f ); // filename = $parts[ 'basename' ]

        // when file selected to delete
        if( $f === false ) {
          $this->process_msg[] = '<strong>' . __( 'WARNING: temporary file not choosen to delete: ', VAWP_LOCALE_HOOK ) . '<code>' . $k . '</code></strong>';
          $this->process_msg_count[ 'warning' ]++;
        } else {
          if( is_file( $f ) === true ) {
            if( unlink( $f ) !== true ) {
              $this->process_msg[] = '<strong>' . __( 'ERROR: failed to delete temporary file: ', VAWP_LOCALE_HOOK ) . '<code>' . $f . '</code></strong>';
              $this->process_msg_count[ 'error' ]++;
            } else {
              // TODO: decide if to show these status messages or not
              #$this->process_msg[] = __( 'OK: Temporary file deleted: ', VAWP_LOCALE_HOOK ) . '<code>' . $parts[ 'basename' ] . '</code>';
              #$this->process_msg_count[ 'status' ]++;
            } // end unlink
          } else { // end is_file
            $this->process_msg[] = '<strong>' . __( 'WARNING: temporary file does not exist: ', VAWP_LOCALE_HOOK ) . '<code>' . $k . '</code></strong>';
            $this->process_msg_count[ 'warning' ]++;
          }
        } // end file exists
      } // end foreach

      return;
    } // end process_cleanup()


    /**
     * Insert meta information about last processing into database
     */
    function process_history_insert( $args ) {
      global $wpdb;

      $defaults 		= array(
        "sequence_number"	=> false,
        "va_exec"			=> 'SCHEDULER'
      );
      $args 			= wp_parse_args( $args, $defaults );
      $status			= '';
      $message 			= '';
      $history_update	= false;

      $status = ( $this->process_msg_count[ 'warning' ] > 0 ) 	? __( 'WARNING' ) : __( 'OK' );
      $status = ( $this->process_msg_count[ 'error' ] > 0 ) 	? __( 'FAILED' ) : $status;

      // TODO: localize a bit better
      $message = ( $this->currentUser != '' ) ? '[' . $this->currentUser . '] ' : '';
      $message .= 'Process ' . $args[ 'sequence_number' ] . __( ' finished with ', VAWP_LOCALE_HOOK ) . $this->process_msg_count[ 'error' ] . ' error(s) and ' . $this->process_msg_count[ 'warning' ] . ' warning(s)' . '<br />';
      $message .= implode( '<br />', $this->process_msg );

      // update history with all the values
      $history_update = $wpdb->update(
        $wpdb->prefix . VAWP_DBT_HISTORY,
        array(
          'va_date_end'	=> date( 'Y-m-d H:i:s' ),
          'va_exec' 	=> $args[ 'va_exec' ],
          'va_status' 	=> $status,
          'va_message' 	=> $message,
        ),
        array( 'va_sequence' => (int) $args[ 'sequence_number' ] ),
        array(
          '%s',
          '%s',
          '%s',
          '%s'
        ),
        array( '%d' )
      );

      if( $history_update !== false ) {
        // insert into meta-data table
        $wpdb->query( $wpdb->prepare(
          "INSERT INTO `" . $wpdb->prefix . VAWP_DBT_HISTORY_META . "`
          ( va_sequence, va_tree_status, va_tree_num_pages_read, va_tree_excpt, va_send_status, va_send_excpt ) VALUES ( %d, %s, %s, %s, %s, %s )",
         array(
          (int) $args[ 'sequence_number' ],
          $this->process_meta[ 'tree' ][ 'status' ],
          $this->process_meta[ 'tree' ][ 'num_pages_read' ],
          $this->process_meta[ 'tree' ][ 'excpt' ],
          $this->process_meta[ 'send' ][ 'status' ],
          $this->process_meta[ 'send' ][ 'excpt' ]
         )
        ) );
      }

      return;
    } // end process_history_insert()


    /**
     * Process (all steps: read, summarize, write, encrypt, upload) - Action function for scheduler
     *
     * @author Vizzit International AB
     * @version 1.0
     */
    function vizzit_analytics_process( $va_exec = 'SCHEDULER' ) {
      global $wpdb;
      $options    				= $this->get_options();

      $this->process_msg		= array(); // reset message variable
      $this->process_msg_count 	= array( 'error' => 0, 'warning' => 0, 'status' => 0 ); // reset amount of errors, warning, status messages
      $sequenceNumber 			= $this->get_next_sequence_number(); // get the last (highest) sequence number
      $vizzit_dir_tmp 			= dirname( __FILE__ ) . '/' . VAWP_DIR_TMP_FILES;
      $treeFile   				= dirname( __FILE__ ) . '/' . VAWP_DIR_TMP_FILES . $sequenceNumber . '.nav.txt';
      $zipFile					= dirname( __FILE__ ) . '/' . VAWP_DIR_TMP_FILES . $sequenceNumber . '.structure.zip';
      $encryptFile 				= dirname( __FILE__ ) . '/' . VAWP_DIR_TMP_FILES . $sequenceNumber . '.encrypted.zip';
      
      // insert sequence number and date_start: this entry will be updated later when finished
      $wpdb->query( $wpdb->prepare( "INSERT INTO `" . $wpdb->prefix . VAWP_DBT_HISTORY . "` ( va_sequence, va_date_start ) VALUES ( %d, %s )", array( (int) $sequenceNumber, date( 'Y-m-d H:i:s' ) ) ) );

      // check if the directory is writable - don't do stuff at all if not
      if( is_writable( $vizzit_dir_tmp ) ) {

        // parse and write structure file
        $this->process_structure_write( array( 'filename_tree' => $treeFile ) );

        // create zip file
        $this->process_zip_write( array( 'filename_tree' => $treeFile, 'filename_zip' => $zipFile, 'filename_encrypt' => $encryptFile, 'sequence_number' => $sequenceNumber ) );

        // upload zip file
        if( isset( $options[ 'va_hidden_pref_no_upload' ] ) && $options[ 'va_hidden_pref_no_upload' ] == 'on' ) {
          // do nothing
        } else {
          $this->process_upload( array( 'filename_encrypt' => $encryptFile ) );
        }

        // delete all (temporary) files
        // TODO: activate the "real" version which deletes all
        if( isset( $options[ 'va_hidden_pref_no_file_delete' ] ) && $options[ 'va_hidden_pref_no_file_delete' ] == 'on' ) {
          // do nothing
          #$this->process_cleanup( array( 'filename_zip' => $zipFile, 'filename_encrypt' => $encryptFile ) ); // leave the tree file for checking
          #$this->process_cleanup( array( 'filename_encrypt' => $encryptFile ) ); // leave the tree file AND zip file for checking
          $this->process_cleanup( array( 'filename_tree' => $treeFile, 'filename_encrypt' => $encryptFile ) ); // leave the zip file for checking
        } else {
          $this->process_cleanup( array( 'filename_tree' => $treeFile, 'filename_zip' => $zipFile, 'filename_encrypt' => $encryptFile ) ); // clean all temp files
        }
      } else {
        // prepare error message to add
        $this->process_msg[] = '<strong>' . __( 'ERROR: processing cancelled. Cannot write to directory: ', VAWP_LOCALE_HOOK ) . '<code>' . $vizzit_dir_tmp . '</code></strong>';
        $this->process_msg_count[ 'error' ]++;
      } // if !is_writable

      // add status/error to history
      $this->process_history_insert( array( 'sequence_number' => $sequenceNumber, 'va_exec' => $va_exec ) );

    } // end vizzit_analytics_process()


    /**
     * Format the sequence number with "0"
     */
    function format_sequence_number( $sequence_number = false ) {
      if( $sequence_number === false ) {
        return false;
      } else {
        return str_pad( $sequence_number, 10, '0', STR_PAD_LEFT );
      }
    } // end format_sequence_number()


    /**
     * Retrieve the last used sequence number
     */
    function get_last_sequence_number( $formatted = true ) {
      global $wpdb;
      $sequenceNumber = false;
      $sequenceNumber = (int) $wpdb->get_var("SELECT MAX(`va_sequence`) FROM `" . $wpdb->prefix . VAWP_DBT_HISTORY . "`");

      if($formatted === true)
        $sequenceNumber = $this->format_sequence_number($sequenceNumber);

      return $sequenceNumber;
    } // end get_last_sequence_number()


    /**
     * Retrieve the next sequence number for processing (for filenames, insert into history, ...)
     */
    function get_next_sequence_number($formatted=true)
    {
      global $wpdb;

      $sequenceNumber = $this->get_last_sequence_number(false);
      $sequenceNumber++;

      if($formatted === true)
        $sequenceNumber = $this->format_sequence_number($sequenceNumber);

      return $sequenceNumber;
    } // end get_next_sequence_number()


    /**
     * Retrieve history entries for processings; possible to set limit (default is last 10 entries)
     */
    function get_schedule_history_rows( $args ) {
      global $wpdb;

      $defaults = array(
        "limit" => 10
      );
      $args 			= wp_parse_args( $args, $defaults );
      $lastHistoryRows 	= false;
      $hrows 			= array();

      #$lastHistoryRows = $wpdb->get_results( " SELECT * FROM `" . $wpdb->prefix . VAWP_DBT_HISTORY . "` ORDER BY `va_date` DESC LIMIT " . $args[ 'limit' ] . " " );
      $lastHistoryRows = $wpdb->get_results( " SELECT * FROM `" . $wpdb->prefix . VAWP_DBT_HISTORY . "` h LEFT JOIN `" . $wpdb->prefix . VAWP_DBT_HISTORY_META . "` m ON h.`va_sequence` = m.`va_sequence` ORDER BY `va_date_start` DESC LIMIT " . $args[ 'limit' ] . " " );

      if( $lastHistoryRows ) {
        foreach( $lastHistoryRows as $lhrow ) {
            $hrows[] = array(
              'va_sequence'				=> (int) $lhrow->va_sequence,
              'va_date_start' 			=> $lhrow->va_date_start,
              'va_date_end'				=> $lhrow->va_date_end,
              'va_exec'					=> $lhrow->va_exec,
              'va_status' 				=> $lhrow->va_status,
              'va_message' 				=> $lhrow->va_message,

              'va_tree_status' 			=> $lhrow->va_tree_status,
              'va_tree_num_pages_read' 	=> $lhrow->va_tree_num_pages_read,
              'va_tree_excpt' 			=> $lhrow->va_tree_excpt,
              'va_send_status' 			=> $lhrow->va_send_status,
              'va_send_excpt' 			=> $lhrow->va_send_excpt
            );
        }
      }

      return $hrows;
    } // end get_schedule_history_rows()


    /**
     * Helper function: format row for writing to structure file
     */
    function return_structure_row( $args ) {
      // level, name, url, id, start, end, pagetype, created_by, changed_by, created, changed, status, visible_in_menu, category
      $defaults = array(
        "level" 			=> "0",
        "name" 				=> "",
        "url" 				=> "",
        "id" 				=> "0",
        "pagetype" 			=> "",
        "start" 			=> "0000-00-00", // Start Publish (YYYY-mm-dd)
        "changed" 			=> "0000-00-00", // Last Saved (YYYY-mm-dd) --- will be/is same as last_changed
        "created_by" 		=> "",
        "changed_by" 		=> "",
        "created" 			=> "0000-00-00", // Created Date (YYYY-mm-dd)
        "end" 				=> "9999-12-31", // Stop Publish (YYYY-mm-dd)
        "status" 			=> "",
        "visible_in_menu" 	=> "",
        "category" 			=> ""
      );
      $args = wp_parse_args( $args, $defaults );
      $s = '';
      $s .= '"' . $args[ 'level' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'name' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'url' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'id' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'pagetype' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'start' ] . '"'; // Start Publish (YYYY-mm-dd)
      $s .= ';';
      $s .= '"' . $args[ 'changed' ] . '"'; // Last Saved (YYYY-mm-dd) --- will be/is same as last_changed
      $s .= ';';
      $s .= '"' . $args[ 'created_by' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'changed_by' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'created' ] . '"'; // Created Date (YYYY-mm-dd)
      $s .= ';';
      $s .= '"' . $args[ 'end' ] . '"'; // Stop Publish (YYYY-mm-dd)
      $s .= ';';
      $s .= '"' . $args[ 'changed' ] . '"'; // Last Changed  (YYYY-mm-dd)
      $s .= ';';
      $s .= '"' . $args[ 'status' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'visible_in_menu' ] . '"';
      $s .= ';';
      $s .= '"' . $args[ 'category' ] . '"';

      return $s;
    } // end return_structure_row


    /**
     * Formats the structure-tree
     */
    function render_ptree() {
      $options    = $this->get_options();
      $ptree 			= '';
      $globalization 	= array( '' ); // default: no globalization

      // check for existence of plugin qTranslate - fill globalization
      if( $this->is_plugin_active_qtranslate() ) {
        global $q_config;
        $globalization = qtrans_getSortedLanguages(); // get the (enabled) languages from qTranslate in the order they are defined
      }

      // loop around the tree (ids) and fill with values
      foreach( $this->P as $k => $p ) {
        $result = array(); // for qTranslate

        // check if it is frontpage or frontpost - decrease level to get "0"
        if($this->get_option('page_on_front') == $p->ID) {
          $p->level--;
        }

        // set the post-title
        $p->post_the_title = get_the_title( $p->ID );

        // get permalink, but just the path, no domain
        $permalink = parse_url( get_permalink( $p->ID ) ); // split permalink
        $p->url = $permalink[ 'path' ];

        // get the username which creates the post
        $p->created_by = get_the_author_meta( 'user_login', $p->post_author );
        $p->changed_by = $p->created_by;

        // getting last-change username
        if(($last_id = get_post_meta($p->ID, '_edit_last', true))) {
          $user_info = get_userdata($last_id);
          $p->changed_by = ( $user_info->user_login != '' ) ? $user_info->user_login : $p->created_by;
        }

        // check for existence of plugin qTranslate
        if( $globalization !== false ) {
          // loop around and build tree for the enabled languages
          foreach( $globalization as $lang ) {

            // when qTranslate exists and language is active, modify values
            if( $this->is_plugin_active_qtranslate() && qtrans_isEnabled( $lang ) ) {
              $localeUrl 	= '';
              $localeTitle 	= '';

              $localeUrl = qtrans_convertURL( get_permalink( $p->ID ), $lang, true );
              $localeUrl = parse_url( $localeUrl ); // split permalink
              $p->url = $localeUrl[ 'path' ] . $localeUrl["query"]; // take care of the ?lang= parameter if different type of path mode is used

              $localeTitle = qtrans_split( $p->post_title );
              $p->post_the_title = $localeTitle[ $lang ];
            }

            // anonymize (md5) usernames when option is set
            if( $options[ 'va_anonymize_usernames' ] == 'on' ) {
              $p->changed_by = md5( $p->changed_by );
              $p->created_by = md5( $p->created_by );
            }

            // Get category name
            $post_category = '';
            if(is_array($p->post_category) && isset($p->post_category[0]))
              $post_category = get_cat_name($p->post_category[0]);

            // fetch formatted row
            $ptree .= $this->return_structure_row(
              array(
                'level'       	=> $p->level,
                'name'        	=> $p->post_the_title,
                'url'         	=> $p->url,
                'id'          	=> $p->ID,
                'pagetype'    	=> $p->post_type,
                'start'       	=> date( "Y-m-d", strtotime( $p->post_date ) ),
                'changed'  		=> date( "Y-m-d", strtotime( $p->post_modified ) ),
                'created_by'  	=> $p->created_by,
                'changed_by'  	=> $p->changed_by,
                'created'  		=> date( "Y-m-d", strtotime( $p->post_date ) ),
                'status'      	=> $p->post_status,
                'category'		=> $post_category
              )
            );
            $ptree .= "\n";

          } // end foreach( $globalization )
        } // end !== globalization

      } // end foreach( $this->P

      return $ptree;
    } // end render_ptree()


    /**
     * Get the structure-tree (pages) - TODO: pages or posts
     */
    function ptree( $args, $level = 0 ) {
      $defaults = array(
        "post_type" 		=> "page",
        "parent" 			=> "0",
        "post_parent" 		=> "0",
        "numberposts" 		=> "-1",
        "orderby" 			=> "menu_order",
        "order" 			=> "ASC",
        "post_status" 		=> "any",
        "suppress_filters" 	=> 0 // suppose to fix problems with WPML
      );
      $args 	= wp_parse_args( $args, $defaults );
      $pages 	= get_posts( $args ); // fetch pages with the given arguments

      if( count( $pages ) > 0 ) {
        foreach ($pages as $one_page) {
          $level++;

          // add css if we have childs
          $args_childs = $args;
          $args_childs["parent"]		= $one_page->ID;
          $args_childs["post_parent"]	= $one_page->ID;
          $args_childs["child_of"]	= $one_page->ID;

          // add level
          $one_page->level = $level;
          $this->P[ $one_page->ID ]	= $one_page;

          $this->ptree($args_childs, $level);

          $level--;
        } // end foreach()
      } // end count($pages)

      return;
    } // end ptree()


    /**
     * NOT USED RIGHT NOW: Read the blog posts structure
     */
    /*
    function process_structure_posts() {
      global $month, $wpdb, $wp_version;

      // a mysql query to get the list of distinct years and months that posts have been created
      $sql = 'SELECT
          DISTINCT YEAR(post_date) AS year,
          MONTH(post_date) AS month,
          count(ID) as posts
        FROM ' . $wpdb->posts . '
        WHERE post_status="publish"
          AND post_type="post"
          AND post_password=""
        GROUP BY YEAR(post_date),
          MONTH(post_date)
        ORDER BY post_date DESC';

      // use get_results to do a query directly on the database
      $archiveSummary = $wpdb->get_results( $sql );

      // if there are any posts
      if( $archiveSummary ) {

        $postFrontPageId 	= $this->get_option('page_for_posts');
        $postFrontPageData 	= get_page( $postFrontPageId );

        // create startnode which contains all posts
        echo '<h1>0 <a href="' . get_permalink( $postFrontPageData->ID ) . '">' . $postFrontPageData->post_title . '</a> [POSTS]</h1>';

        $year = '';
        $level_start = 1;
        $level = $level_start;

        // loop through the posts
        foreach( $archiveSummary as $date ) {
          // reset the query variable
          unset( $bmWp );

          if( $year != $date->year ) {
            $year = $date->year;
            $level = $level_start;
            echo $level . ' <a href="' . get_year_link( $year ) . '">' . $year . '</a><br />';
            $level++;
          }

          // create a new query variable for the current month and year combination
          $bmWp = new WP_Query( 'year=' . $date->year . '&monthnum=' . zeroise( $date->month, 2 ) . '&posts_per_page=-1' );

          // if there are any posts for that month display them
          if ($bmWp->have_posts()) {
            // display the archives heading
            #$url = get_month_link($date->year, $date->month);
            #$text = $month[zeroise($date->month, 2)] . ' ' . $date->year;
            #$text = $level . ' __ ' . zeroise($date->month, 2);
            #echo get_archives_link($url, $text, '', '<h3>', '</h3>');
            echo $level . ' <a href="' . get_month_link($date->year, $date->month) . '">' . zeroise($date->month, 2) . '</a><br />';

#            $this->P[ 'asdasd' ] = $date->year . '.' . $date->month;

            $level++;

            // display an unordered list of posts for the current month
            while ($bmWp->have_posts()) {
              $bmWp->the_post();

              $bmWp->post->level = $level;
              $this->P[ $bmWp->post->ID ] = $bmWp->post;

              #echo '<li>' . $level . ' <a href="' . get_permalink( $bmWp->post ) . '" title="' . wp_specialchars( $text, 1 ) . '">' . wptexturize( $bmWp->post->post_title ) . '</a></li>';
            }
            $level--;

          }
        }
      }
    } // end process_structure_posts()
    */


    /**
     * Register the scheduler
     */
    // TODO: unregister schedule when disabling the plugin
    function vizzit_analytics_scheduler() {

      if($this->is_network)
      {
        // Not main site, drop any schedules just to be safe
        if(!is_main_site(get_current_blog_id()))
        {
          $timestamp = wp_next_scheduled($this->e_schedule);
          wp_unschedule_event($timestamp, $this->e_schedule);
          return;
        }
      }

      $options        = $this->get_options();
      $referenceDate  = strtotime( date("Y-m-d 02:03:00") );
      $tomorrow       = strtotime(date('Y-m-d H:i:s', strtotime('+1 day', $referenceDate)));

      if( isset( $options[ 'va_scheduler' ] ) && ( $options[ 'va_scheduler' ] == 'on' ) ) {
        if( !wp_next_scheduled( $this->e_schedule ) ) {
          wp_schedule_event( $tomorrow, 'daily', $this->e_schedule ); // activate schedule: set time, frequency and hook
        }
      } else {
        $timestamp = wp_next_scheduled( $this->e_schedule ); // read the timestamp for next schedule
        wp_unschedule_event( $timestamp, $this->e_schedule ); // with this timestamp, disable
      }
    } // end vizzit_analytics_scheduler


    /**
     * Check for existence of plugin "qtranslate" - used for globalization of WordPress
     */
    function is_plugin_active_qtranslate() {
      /*
       * NOTE: defined in wp-admin/includes/plugin.php, so this is only available from within the admin pages.
       * If you want to use this function from within a template, you will need to manually require plugin.php.
       */
      if( !is_admin() ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }

      if( is_plugin_active( 'qtranslate/qtranslate.php' ) ) {
        return true;
      }

      return false;
    } // end is_plugin_active_qtranslate()


    /**
     * Get and set the current WordPress user - to be used in links
     */
    function get_current_wp_user() {
      $current_user = wp_get_current_user();

      if( !( $current_user instanceof WP_User ) ) {
        return false;
      } else {
        $this->currentUser = $current_user->user_login;
        return true;
      }
    } // end get_current_wp_user


    /**
     * Create a history table from an array of rows
     */
    function history_table( $rows ) {
      $content = '<table class="form-table">';

      $content .= '<tr class="va_row">';
        $content .= '<th>' . __( 'Date', VAWP_LOCALE_HOOK ) . '</th>';
        $content .= '<th>' . __( 'Status', VAWP_LOCALE_HOOK ) . '</th>';
        $content .= '<th>' . __( 'Message', VAWP_LOCALE_HOOK ) . '</th>';
      $content .= '</tr>';

      $i = 1;
      foreach( $rows as $row ) {
        $class 			= '';
        $classStatus 	= '';

        $class .= 'va_row';
        $class .= ( $i % 2 == 0 ) ? ' even' : '';

        if( $row[ 'va_status' ] == 'OK' ) 		{ $classStatus = 'va_history_status_ok'; }
        if( $row[ 'va_status' ] == 'WARNING' ) 	{ $classStatus = 'va_history_status_warning'; }
        if( $row[ 'va_status' ] == 'FAILED' ) 	{ $classStatus = 'va_history_status_failed'; }

        $content .= '<tr id="' . $row[ 'va_sequence' ] . '_row" class="' . $class . '">';
          $content .= '<td width="1%" nowrap="nowrap" valign="top">';
            $content .= $row[ 'va_date_end' ];
          $content .= '</td>';
          $content .= '<td width="1%" class="' . $classStatus . '" valign="top">';
            $content .= $row[ 'va_status' ];
          $content .= '</td>';
          $content .= '<td width="98%" valign="top">';
            $content .= $row[ 'va_message' ];
            if( $row[ 'va_status' ] == 'FAILED' ) {
              // TODO: localize this string!
              $content .= '<br />If you could not solve the problem by yourself, have a look at <a href="' . VAWP_PATH_FAQ . '" title="" target="_blank"><strong>Vizzits FAQ</strong></a> or <a href="mailto:' . VAWP_EMAIL_SUPPORT . '" title="">contact us</a>.';
            }
          $content .= '</td>';
        $content .= '</tr>';
        $i++;
      }
      $content .= '</table>';

      return $content;
    } // end history_table()


    /**
     * Create zip-file from structure
     */
     function create_zip( $args ) {
      // set default parameter
      $defaults = array(
        "files"       		=> array( dirname( __FILE__ ) . VAWP_DIR_TMP_FILES . '/structure.txt' ),
        "destination" 		=> dirname( __FILE__ ) . VAWP_DIR_TMP_FILES . '/structure.zip',
        "overwrite"   		=> false,
        "sequence_number"	=> '0'
      );
      $args = wp_parse_args( $args, $defaults );

      // if the zip file already exists and overwrite is false, return false
      if( file_exists( $args[ 'destination' ] ) && !$args[ 'overwrite' ] ) { return false; }

      $valid_files = array();
      // if files were passed in...
      if( is_array( $args[ 'files' ] ) ) {
        // cycle through each file
        foreach( $args[ 'files' ] as $file ) {
          // make sure the file exists
          if( file_exists( $file ) ) {
            $valid_files[] = $file;
          }
        }
      }

      // if we have files...
      if( count( $valid_files ) ) {

        $zip = new ZipArchive(); // create the archive

        if( $zip->open( $args[ 'destination' ], $args[ 'overwrite' ] ? ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true ) {
          return false;
        }

        // add the files
        foreach( $valid_files as $file ) {
          $zip->addFile( $file, $args['sequence_number'] . '.tree' ); // rename the file when adding (removes the complete path as well)
        }

        $zip->close(); // close the zip

        return file_exists( $args[ 'destination' ] ); //check to make sure the file exists
      } else {
        return false;
      }
    } // end create_zip

  } // class Vizzit_Analytics
} // !class_exists
?>