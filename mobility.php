<?php
// config, adapt to your needs
$server = "192.168.0.1"; // IP address of the PBX
$user = "_TAPI_"; // The Name (not Long Name) of the User Object for SOAP sessions
$httpu = "_TAPI_"; // This User Object must have set "PBX Rights" to "cf-grp" to be able to setup a HTTP session
$httpp = "password"; // This User Object must have Passowrd configured
$debug = false; // Set "true" to enable logging to log.txt in local directory

if($debug){
	// init logger
	require_once("logger.class.php");
	$l = new Logger();
	$l->log("HTTP request ".print_r($_REQUEST,true),1);
}


// validate input

// Long Name of User Object to call from
if( isset($_REQUEST["cn"]) && (strlen($_REQUEST["cn"]) > 0)  && (strlen($_REQUEST["cn"]) < 100) ) {
	// dialled number OK
	$caller = $_REQUEST["cn"];
} else {
	if ($debug) $l->log("bad cn",1);	
	// Invalid username/password
	// send error code 0
	die("0");
}

// Device string provided under Mobility Fork destination, cloud be the same for all users
if( isset($_REQUEST["hw"]) && (strlen($_REQUEST["hw"]) > 0)  && (strlen($_REQUEST["hw"]) < 100)) {
	// dialled number OK
	$device = $_REQUEST["hw"];
} else {
	if ($debug) $l->log("bad hw",1);
	// Invalid username/password
	// send error code 0
	die("0");
}

// dialled numer should be at least one digit long, but not longer than 30 digits.
// can contain 0-9 , # and *
if( isset($_REQUEST["e164"]) && filter_var($_REQUEST["e164"], FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^[0-9*#]{1,30}$/"))) ) {
	// dialled number OK
	$destination = $_REQUEST["e164"];
} else {
	if ($debug) $l->log("bad e164",1);
	// Invalid number dialled
	// send error code 1
	die("1");
}

// get the innoPBX wrapper class
require_once('innopbx.class.php');
 
// dummy classes to map SOAP results to (really would love to use namespaces here...)
// you can add methods and variables to these classes as needed
class innoUserInfo { };
class innoCallInfo { };
class innoAnyInfo { };
class innoGroup { };
class innoNo { };
class innoInfo { };
 
// create connector to PBX
// class mapping is optional
$inno = new innoPBX($server, $httpu, $httpp, $user,
	    array('classmap' => array("UserInfo" => "innoUserInfo", 
				"CallInfo" => "innoCallInfo",
				"AnyInfo" => "innoAnyInfo",
				"Group" => "innoGroup",
				"No" => "innoNo",
				"Info" => "innoInfo",
			    )));
// Invalid username/password
if ($inno->key() == 0) die("0");
 
 
// create a call on behalf of user $caller
// first we need a user handle

if ($debug) $l->log("Obtaining a user handle for $caller...",1);
$uhandle = $inno->UserInitialize($inno->session(), $caller, false, false, $device);
if ($debug) $l->log("User Session ID $uhandle",1);
if ($uhandle == 0) {
	// Invalid username/password
    die("0");
}
 
// then we create a call
if ($debug) $l->log("Start callback from $caller to $destination...",1);
$call = $inno->UserCall($uhandle, null, $destination, null, 0, array());
if ($debug) $l->log("Call ID $call",1);
if ($call == 0) {
	if ($debug) $l->log("cant call on behalf of user $caller ($uhandle)",1);
	// send error code "Invalid number"
    die("1");
} else {
	if ($debug) $l->log("Callback started",1);
	// send status code "OK"
	print "15";
}

// terminate user session
if ($debug) $l->log("End session for user $caller",1);
$inno->UserEnd($uhandle);

// terminate the session
if ($debug) $l->log("Terminate SOAP session",1);
$e = $inno->End($inno->session());

/*
The server must return a status code  for OptiCaller 
depending on the success of the request.
0 - Invalid username/password
1 - Invalid number
15 - OK
*/

?>
