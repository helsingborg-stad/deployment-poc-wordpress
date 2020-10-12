<?php

#-------------------------------------------------------------------------------------------------------
# Definitions
#-------------------------------------------------------------------------------------------------------
// create password

$customer_id 	= 'vawp_test';
$iv 			= 'ZzxArf2lGjw=';
/*
$customer_id 	= 'vizzit_se';
$iv 			= 'rLYdeiNNhSc=';
*/
$sum 			= $customer_id . $iv;
$password		= base64_encode( pack( 'H*', md5( $sum ) ) );

// create new SOAP client
$soap_config 	= array(
  'encoding' 	=> 'UTF-8',
  'location' 	=> 'http://192.168.1.253/~stefand/zzWP/episerver_vizzit/ws/vizzit.asmx?wsdl',
#  'location' 	=> 'http://www.vizzit.se/h/episerver_vizzit/ws/vizzit.asmx?wsdl',
  'uri' 		=> 'urn:vizzit-analytics-for-wordpress'
);
$sc = new SoapClient( NULL, $soap_config );


// use WSDL-file instead
#$sc = new SoapClient( 'http://192.168.1.253/~stefand/zzWP/wp-content/plugins/vizzit-analytics-for-wordpress/vizzit.wsdl', array( 'encoding' => 'UTF-8' ) );
#$sc = new SoapClient( 'http://192.168.1.253/~stefand/zzWP/episerver_vizzit/ws/vizzit.asmx?wsdl', array( 'encoding' => 'UTF-8' ) );



# PHP Fatal error:  Class 'DomDocument' not found in /var/www/html/h/wp-content/plugins/vizzit-analytics-for-wordpress/Array2XML.php on line 38
  # http://blog.randell.ph/2010/10/01/php-fatal-error-class-domdocument-not-found/



#-------------------------------------------------------------------------------------------------------
# Functions to call
#-------------------------------------------------------------------------------------------------------
function Status() {
  global $sc, $password, $soap_config;

  if( is_object( $sc ) ) {
    $params = array( 'password' => $password );
    $result = $sc->Status( $params );

    // output
    header( 'Content-type: text/xml' );
    echo $result;
  } else die( 'SOAP Server not available' );
}


function GetLastJobLogResult( $pretty = false ) {
  global $sc, $password, $soap_config;

  if( is_object( $sc ) ) {
    $params = array( 'password' => $password );
    $result = $sc->GetLastJobLogResult( $params );

    // Debug output
    if( $pretty ) {
      echo '[' . $result[ 'Data' ][ 'overallStatus' ] . '] ' . $result[ 'Data' ][ 'startedBy' ] . ': ' . $result[ 'Data' ][ 'datetimeEnd' ] . '<br />';
    } else {
      print_r( $result );
    }
  } else die( 'SOAP Server not available' );
}


function ExecuteJob( $param ) {
  global $sc, $password, $soap_config;

  if( is_object( $sc ) ) {
    $params = array( 'password' => $password,
    'processTree' 		=> $param[ 'processTree' ],
    'processGroups' 	=> $param[ 'processGroups' ],
    'processLog' 		=> $param[ 'processLog' ],
    'attachfiles' 		=> $param[ 'attachfiles' ],
    'processLogsFrom' 	=> $param[ 'processLogsFrom' ],
    'processLogsTo' 	=> $param[ 'processLogsTo' ]
    );
    $result = $sc->ExecuteJob( $params );

    // Debug output
    print_r( $result );
  } else die( 'SOAP Server not available' );
}


function SetScheduleEnabled( $bool ) {
  global $sc, $password, $soap_config;

  if( is_object( $sc ) ) {
    $params = array( 'password' => $password, 'enableSchedule' => $bool );
    $result = $sc->SetScheduleEnabled( $params );

    // Debug output
    print_r( $result );
  } else die( 'SOAP Server not available' );
}





#-------------------------------------------------------------------------------------------------------
# Check for POST variables
#-------------------------------------------------------------------------------------------------------
if( isset( $_POST[ 'Status' ] ) ) {
  Status();
}
if( isset( $_POST[ 'GetLastJobLogResult' ] ) ) {
  GetLastJobLogResult( false );
}
if( isset( $_POST[ 'ExecuteJob' ] ) ) {
  $param = array();
  $param[ 'processTree' ] 		= ( $_POST['processTree'] == 'true' ) ? true : false;
  $param[ 'processGroups' ] 	= ( $_POST['processGroups'] == 'true' ) ? true : false;
  $param[ 'processLog' ] 		= ( $_POST['processLog'] == 'true' ) ? true : false;
  $param[ 'attachfiles' ] 		= ( $_POST['attachfiles'] == 'true' ) ? true : false;
  $param[ 'processLogsFrom' ] 	= ( $_POST['processLogsFrom'] == 'true' ) ? true : false;
  $param[ 'processLogsTo' ] 	= ( $_POST['processLogsTo'] == 'true' ) ? true : false;

  ExecuteJob( $param );
}
if( isset( $_POST[ 'SetScheduleEnabled' ] ) ) {
  $bool = ( $_POST['enableSchedule'] == 'true' ) ? true : false;
  SetScheduleEnabled( $bool );
}

if( !isset( $_POST[ 'submit' ] ) ) {
?>
<html>
<head>
<style>
  html { font: 14px calibri, arial, sans-serif; }
  form { margin: 0; padding: 0; float: left; }
  fieldset { margin: 0 0 32px 0; border: none; border-top: 2px solid #e2e2e2; border-left: 2px solid #e2e2e2; background: #f5f8fa; }
  fieldset legend { padding: 0 8px; font-weight: bold; color: #555; }
</style>
</head>

<fieldset><legend>Information om installationen:</legend>
<form method="post">
  <input type="hidden" name="password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="hidden" name="Status" value="true" /><!-- the function name to call -->
  <input type="submit" name="submit" value="Fetch status" />
</form>
</fieldset>


<fieldset><legend>GetLastJobLogResult &ndash; <?php GetLastJobLogResult( true ); ?></legend>
<form method="post">
  <input type="hidden" name="password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="hidden" name="GetLastJobLogResult" value="true" /><!-- the function name to call -->
  <input type="submit" name="submit" value="Fetch last history" />
</form>

</fieldset>


<fieldset><legend>ExecuteJob</legend>
<form method="post">
  <input type="hidden" name="password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="hidden" name="ExecuteJob" value="true" /><!-- the function name to call -->
  <input type="hidden" name="processTree" value="true" />
  <input type="hidden" name="processGroups" value="false" />
  <input type="hidden" name="processLog" value="false" />
  <input type="hidden" name="attachfiles" value="false" />
  <input type="hidden" name="processLogsFrom" value="0000-00-00 00:00:00" />
  <input type="hidden" name="processLogsTo" value="0000-00-00 00:00:00" />

  <input type="submit" name="submit" value="Process structure manually" />
</form>
</fieldset>

<fieldset><legend>SetScheduleEnabled</legend>
<form method="post">
  <input type="hidden" name="password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="hidden" name="SetScheduleEnabled" value="true" /><!-- the function name to call -->
  <input type="hidden" name="enableSchedule" value="true" />
  <input type="submit" name="submit" value="Enable" />
</form>

<form method="post">
  <input type="hidden" name="password" value="w/+OgbIlD8dHidh1xa0abQ==" />
  <input type="hidden" name="SetScheduleEnabled" value="false" /><!-- the function name to call -->
  <input type="hidden" name="enableSchedule" value="false" />
  <input type="submit" name="submit" value="Disable" />
</form>
</fieldset>

</html>
<?php
}
?>