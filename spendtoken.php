<?
require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");
session_start();

sqlQuery("DELETE FROM `acts_authtokens` WHERE issued < NOW() - 60") or die(sqlEr());


$r = sqlQuery("SELECT * FROM acts_authtokens WHERE token = '".mysql_real_escape_string($_GET['tokenid'])."'") or die(sqlEr());

if ($row = mysql_fetch_assoc($r))
{
	echo "OK\n";
	$userid = $row['userid'];
	$userrow=getuserrow($userid);
	echo $userid."\n";
	echo $userrow['email']."\n";
	echo $userrow['name']."\n";
	sqlQuery("DELETE FROM `acts_authtokens` WHERE token = '".mysql_real_escape_string($_GET['tokenid'])."'") or die(sqlEr());
}
else
{
	echo "ERROR\n";
}
?>
