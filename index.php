<?php
/*
    This file is part of Camdram.

    Camdram is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Camdram is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Camdram; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
    Copyright (c) 2006-2012. See the AUTHORS file for a list of authors.
*/
if(isset($_GET['auth_token'])) {
require_once("facebook/fcam.php");
header("location: http://www.camdram.net/");
?>
Redirecting to <a href="http://www.camdram.net/">www.camdram.net</a>...
<?php
exit(0);
}

require_once("config.php");
ob_start();
$mode = 1;
if(!isset($_SESSION)){
	session_start();
}
require_once("library/adcdb.php");
require_once("library/lib.php");

if (!isset($mail_alsomail))
	$mail_alsomail = getconfig('site_admin_email');


if (isset($_COOKIE['camdramuser']) && !isset($_SESSION['logout']) && !isset($_SESSION['userid'])) {
	$_SESSION['user']=$_COOKIE['camdramuser'];
	$_SESSION['userid']=userToID($_SESSION['user']);
}
if(isset($_SESSION['camdramuser'])) {
  	setcookie('camdramuser',$_SESSION['camdramuser'],2147483647,'/'); /* expire at the end of the universe, ie. 19th January 2038 */
	unset($_SESSION['camdramuser']);
}
if (isset($_SESSION["POST"])) {
	foreach ($_SESSION["POST"] as $key=>$value) {
		$_POST[$key]=$value;
		unset($_SESSION["POST"][$key]);
	}
}
// what page are we serving?
if (!isset($currentid) && isset($_GET['id']))
  $currentid = (int) $_GET['id'];
if(!isset($currentid) || $currentid==0) $currentid=1;


require_once("furniture/".$theme."/code.php");

microlog("index.php, ".$currentid);

?>
<!DOCTYPE html>
<html>
<head>
<title>camdram.net - Association of Cambridge Theatre Societies</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="google-site-verification" content="N0VVLj0Wp9bfiD42HnW6aYqfLyolRmPwMrmGTQTZLDs" />
<meta name="keywords" content="cambridge,theatre,drama,comedy,shows,society,societies,ACTS,association" />
<meta name="description" content="ACTS: Association of Cambridge Theatre Societies. Our website contains details of forthcoming shows and miscellaneous information on Cambridge Drama." />

<?php if(isset($_GET['noindextag'])) echo '<meta name="robots" content="noindex">'; ?>
<link href="<?=$currentbase?>/furniture/common/camdram.css" rel="stylesheet" type="text/css" />
<link href="<?=$currentbase?>/furniture/<?=$theme?>/camdram.css" rel="stylesheet" type="text/css" />
<?php 
if($row_page['rssfeeds']!="") {
  $rssfeeds = explode(";",$row_page['rssfeeds']);
  foreach($rssfeeds as $feed) {
    echo '<link href="/rss.php?type='.$feed.'" rel="alternate" type="application/rss+xml" title="'.$feed.'" />';
  }
  
} 

if(is_file("content/".$row_page['id']."_header.php")) {
  echo "<!-- custom headers follow -->";
  require_once("content/".$row_page['id']."_header.php");
  echo "<!-- end custom headers -->";
}
?>
<script src="/scriptaculous/prototype.js" type="text/javascript"></script>
<script src="/scriptaculous/scriptaculous.js" type="text/javascript"></script>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-37770738-1']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body><div id="body">
<?php include("beta_advert.php"); ?>
<?php
if(isset($_GET['systemverbose']) &&$_GET['systemverbose']=='off') $_SESSION['systemverbose']='off';
if(( isset($GET_['systemverbose']) && $_GET['systemverbose']=='on' || isset($_SESSION['systemverbose']) && $_SESSION['systemverbose']=='on') && hasEquivalentToken('security',-3,$_SESSION['ghostid']))
{
  $verbose = 1;
  $_SESSION['systemverbose']='on';
}
unset($_GET['systemverbose']);
?>

<?php
    $continueloading = true; 
    if(isset($loginas)) {
      
      $continueloading = false;
      
      foreach($loginas AS $checklog)
	if($_SESSION[userid]==UserToID($checklog) || $_SESSION[ghostid]==UserToID($checklog))
	  $continueloading = true;
      if($continueloading==false)
	{
	  echo "<h3>Unrecognized User</h3><p>This configuration of camdram.net requires you to log in as one of the following users:</p>";
	  echo "<ul>";
	  foreach($loginas AS $checklog)
	    echo "<li>$checklog</li>";
	  echo "</ul>";
	  echo "<p>Please log in to continue.</p>";
	  unset($_GET['loginsuccess']);
	  logout();
	  login();
	}
    }
if($continueloading) {

	?>
  <?php if(isset($_GET['loginbox'])) {
  			logindetails(true,false,false);
			unset($_GET['loginbox']);
		} else {
		  global $page_in_div;
		  if ($page_in_div) {
		    echo "<div id=\"page\">";
		    	 
		    loadPage($row_page['id']);
		    echo "</div>";
		  }
		  else {
		    loadPage($row_page['id']);
		  }	
		}
  do_theme (); ?>

</div><div id="copyright"><?php
if (! $showing_knowledgebase) {
?>
<a href="http://www.camdram.net/" >camdram.net</a><br/>
&copy; Association of Cambridge Theatre Societies and contributing groups 2004 &#8212; 2012<br />
<?php
} else {
?>
<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/2.5/"><img alt="Creative Commons License" border="0" src="http://creativecommons.org/images/public/somerights20.png"/></a><br/>This page is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/2.5/">Creative Commons Attribution-NonCommercial-ShareAlike 2.5 License</a>.<br />
<?php
}
?>
Comments &amp; queries to <a href="mailto:websupport@camdram.net">websupport@camdram.net</a><br/>
<?php makeLink(206, "Privacy policy"); ?><br/>
</div>
<?php
					    //					    ultraadvert("<strong>Could you run, or help to run, camdram.net/ACTS?</strong><br/> Technical and non-technical ".makeLinkText("run acts","expertise urgently needed").". ");
?>
<?=js_header() ?>
</body>
</html>
<?php
	}
ob_end_flush();
?>
