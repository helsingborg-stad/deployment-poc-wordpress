<?php
/**
 * Main Class
 * Version 0.5.1
 */
if( !class_exists( 'Vizzit_Analytics' ) ) {

  class Vizzit_Analytics extends Vizzit_Analytics_Core {

    var $debug = false; // display debug information as HTML-comment in footer?


    /**
     * Constructur, load all required stuff.
     */
    function __construct()
    {
      parent::__construct();

      $this->plugin_url = plugins_url( '', __FILE__ ) . '/';

      // Register e.g. localization
      add_action('init', array(&$this, 'vizzit_analytics_init') );

      // generic activation of schedule via "wp"
      add_action('wp', array(&$this, 'vizzit_analytics_scheduler') );

      // Register event-function for scheduler-hook
      add_action( $this->e_schedule, array(&$this, $this->fn_schedule ) );

      // Register debug info output
      add_action('wp_footer', array(&$this, 'apply_vizzit_analytics_debug') );

      // Add the Vizzit Analytics Tag Code
      add_action('wp_footer', array(&$this, 'apply_vizzit_analytics_tag_code' ) );

    } // end __construct()


    /**
     * Set debug-variable
     */
    function set_debug( $bool ) {
      $this->debug = ( $bool === true ) ? true : false;
    } // end set_debug


    /**
     * Display debug information as HTML-comment in footer
     */
    function apply_vizzit_analytics_debug() {
      $options = $this->get_options();

      if( $this->debug ) {
        echo '<!-- PLUGIN Vizzit Analytics for WordPress DEBUG START -->';
        echo '<!--';
        echo "\n";
          var_dump( $options );
        echo "\n";
        echo '-->';
        echo '<!-- PLUGIN Vizzit Analytics for WordPress DEBUG END -->';
      }
    } // end apply_vizzit_analytics_debug()


    /**
     * Add parameter before inserting tag code
     */
    function add_tag_parameter() {
      $options  = $this->get_options();

      if( ( $options[ 'va_anonymize_ip' ] == 'on' ) || ( $options[ 'va_append_username' ] == 'on' ) || ( $options[ 'va_time_on_page' ] == 'on' ) ) {
        echo '<script type="text/javascript">';

        if( $options[ 'va_anonymize_ip' ] == 'on' ) {
          echo '$vizzit_anonymizeIP = "true";';
        }
        if( $options[ 'va_append_username' ] == 'on' ) {
          echo '$vizzit_user = "'.$this->currentUser.'";';
        }
        if( $options[ 'va_time_on_page' ] == 'on' ) {
          echo '$vizzit_keepAlive = "true";';
        }

        echo '</script>';
      } // end if( one-of-the-tag-parameters is set )

    } // end add_tag_parameter()


    /**
     * Insert the tag code
     */
    function apply_vizzit_analytics_tag_code() {
      $options = $this->get_options();

      // if on a page and customer ID is set
      // TODO: what about the list/overview of posts? this is not a page and not a post/single
      if( !is_admin() && ( isset( $options[ 'va_customer_id' ] ) && !empty( $options[ 'va_customer_id' ] ) ) ) {
        // add "_test" to the customer-id if this is a test installation
        $va_customer_id = ( $options[ 'va_test_mode' ] == 'on' ) ? $options[ 'va_customer_id' ] . '_test' : $options[ 'va_customer_id' ];

        // add some tag parameters?
        $this->add_tag_parameter();

        //echo '__TAG_ADDED__';

        echo '<!-- Vizzit Analytics Tag Start -->';
          echo '<script type="text/javascript" src="' . VAWP_PATH_TAG . 'tag.php?d=' . $va_customer_id . '"></script>';
          echo '<noscript><img src="' . VAWP_PATH_TAG . 'assets/nojs.gif?d=' . $va_customer_id . '" border="0" width="1" height="1"></noscript>';
        echo '<!-- Vizzit Analytics Tag End -->';

      }
    } // end apply_vizzit_analytics_tag_code()


  } // end class Vizzit_Analytics

}
?>