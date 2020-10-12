/*
 * va_scripts
 */
jQuery(document).ready(function($) {
  // $() will work as an alias for jQuery() inside of this function

  /*
   * Confirm while activate test mode
   */
  $( '#va_test_mode' ).live( 'click', function( e ) {
    var checked = $(this).is(':checked');
    var message = $(this).attr( 'data-message' );

    // when unchecked -> checked
    if( checked == true ) {
      if( confirm( message ) == true ) {
        return true;
      } else {
        return false;
      }
    }
  }); // confirm test mode


});