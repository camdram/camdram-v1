<?
require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");
session_start();

global $base;

$q = "SELECT url FROM acts_search_cache WHERE id = '$_GET[id]'";

$r = SqlQuery($q) or die (SqlEr());

if ($row = mysql_fetch_assoc($r))
	{
	Header("Location:$base$row[url]");
	}
else
	{
	Header("Location:".$_SERVER['HTTP_REFERER']);
	}
?>

