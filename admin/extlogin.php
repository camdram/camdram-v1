<?
if (isset($_GET['extlogout']))
{
	header("Location: ".$_GET['redirect']);
	die("Logged out");
}
$r = sqlQuery("SELECT * FROM acts_externalsites");
$found = false;
while ($row = mysql_fetch_assoc($r))
{
	$url = preg_quote($row['url']);
	$url = str_replace("/", "\\/", $url);
	$regexp = '/^'."$url".'/';
	if(preg_match($regexp, $_GET['redirect']) > 0)
	{
		$sitetext = $row['name'];
		$siteid = $row['id'];
		$found = true;
	}
}
if (!$found)
{
	die("Invalid website requesting camdram login");	
}
else
{
	echo("<p>The website \"".$sitetext."\" requires you to login to camdram. Please login below</p>");
	if (logindetails())
	{
		$tokenid = md5(rand());
		$query = "INSERT INTO acts_authtokens(token, siteid, userid, issued) VALUES ('$tokenid', '$siteid', '".$_SESSION['userid']."', NOW())";
		print $query;
		sqlQuery($query) or die(sqlEr());
		$char = "&";
		if (strpos($_GET['redirect'], "?")===false)
			{
			$char = "?";
			}
		header("Location: ".$_GET['redirect'].$char."camdramauthtoken=".$tokenid."&finaldestination=".urlencode($_GET['redirect']));
		die("Logged in");
	}
}
?>
