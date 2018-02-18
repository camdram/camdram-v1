<?
ob_start();
$original_server = $_SERVER['SERVER_NAME'];
$original_url = $_SERVER['REQUEST_URI'];
$querystring = $_SERVER['QUERY_STRING'];
chdir("../");
require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");
global $currentbase;
if ($original_server == "this.is.something.special")
{
	// Use this to set a different redirect for different server names
}
else
{
	// By default, redirect to normal camdram with same parameters as request
	$newurl = $currentbase;
	$newurl .= $original_url;
}
Header("Location: $newurl");
ob_end_flush();
?>
