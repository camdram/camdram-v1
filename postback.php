<?php

if(isset($_SERVER['HTTP_ORIGIN'])){
  if(preg_match("/camdram.net\$/i", $_SERVER['HTTP_ORIGIN'])){ 
    header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
      header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
      header('Access-Control-Allow-Headers: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
      header('Content-Type: text/plain');
      header('Content-Length: 0');
      exit(0);
    }
  }else{ // Server URL does not end in camdram.net
    header("HTTP/1.1 403 Access Forbidden - your url doesn't end in camdram.net - it's " . $_SERVER['HTTP_ORIGIN']);  
    header("Content-Type: text/plain");  
    echo "This server is not allowed to call the OPTIONS header. You cannot repeat this request";
    exit(0);    
  }
}
require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");

if(!isset($_SESSION)){
	session_start();
}
if(isset($_SESSION['userid']) || $_GET['type']=="generic_search")
{
	if(file_exists("postback/".$_GET['type'].".php"))
	{
		require_once("postback/".$_GET['type'].".php");
	}
	else
	{
		echo "Error in updating!";
		actionlog("Error in postback - no such postback req ".$_GET['type']);
	}
}
else
{
	actionlog("Postback from unauthenticated user");
}
?>
