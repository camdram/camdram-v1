<?php

require_once 'config.php';
require_once 'library/adcdb.php';
require_once 'facebook.php';
require_once 'library/gen.php';
require_once 'library/datetime.php';
require_once 'library/page.php';
require_once 'library/showfuns.php';

$appapikey = 'key';
$appsecret = 'secret';

global $facebook;
$facebook = new Facebook(array('appId' => $appapikey, 'secret' => $appsecret));

function doLink($url) {
	return '<a target="_parent" href="'.$url.'" onclick="opener.location=\''.$url.'\';self.close(); return false">';
}


function linkIfIKnow($name) {
return '';
global $facebook;

try {
  $user = $facebook->getUser();
  $q = "SELECT uid2 FROM friend WHERE uid1='$user' AND uid2 IN (SELECT uid FROM user WHERE name='$name')";
var_dump($q);
  $arr = $facebook->api('/fql','GET', array('q' => $q));
  

  if(is_array($arr))
	return '['.doLink("http://www.facebook.com/profile.php?id=".$arr[0]['uid2']."&ref=nf").'your friend on facebook</a>]';
 else {
	return '['.doLink("http://cambridge.facebook.com/s.php?q=".urlencode($name)).'search on facebook</a>]';	
	}
} catch (Exception $ex) {	
	//$facebook->set_user(null, null);
}


}


function facebook() {

global $facebook;

if(isset($_GET["fb_logout"])) {
	header("Location:".$facebook->getLogoutUrl());
	unset($_GET["fb_logout"]);
}
elseif (!isset($_GET['fb_login'])) {
  return "Find out what your friends are up to: ".makeLinkText(0,"click here to log in to your facebook profile",Array("fb_login"=>1)).".";
}
else if(isset($_GET["fb_login"])) {
	if (!$user = $facebook->getUser()) {
		header("Location:".$facebook->getLoginUrl());
		die();
	}
	unset($_GET["fb_login"]);

// require_login();


//[todo: change the following url to your callback url]
$appcallbackurl = 'http://www.camdram.net';  

//catch the exception that gets thrown if the cookie has an invalid session_key in it
try {
  $q = "SELECT name, uid FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1=$user) OR uid=$user";
  $arr = $facebook->api('/fql', 'GET', array('q' => $q));

} catch (Exception $ex) {
  //this will clear cookies for your application and redirect them to a login prompt
  return "Find out what your friends are up to: ".makeLinkText(0,"click here to log in to your facebook profile",Array("fb_login"=>1)).".";
}

// require_once 'appinclude.php';

if(is_array($arr)) {
 
$st = "SELECT DISTINCT `acts_shows_people_link`.sid AS sid, `acts_shows_people_link`.pid AS pid, `acts_people_data`.name AS name FROM `acts_shows_people_link` INNER JOIN `acts_people_data` ON acts_shows_people_link.pid=acts_people_data.id WHERE ";

$l = false;
foreach($arr['data'] as $k) {
  if($k['uid']==$user) $username=$k['name'];
  if($l) $st.=" OR ";
  $st.="`acts_people_data`.`name`='".addslashes($k['name'])."'";
  $l=true;
}

$st.=" ORDER BY acts_shows_people_link.sid, acts_shows_people_link.type DESC,acts_shows_people_link.`order`";

$r = mysql_query($st) or die(mysql_error());

if($r>0) {
  if(mysql_num_rows($r)>0) {
    $select = "SELECT acts_shows.id AS sid,acts_shows_refs.ref,acts_shows.title,acts_shows.socid,acts_shows.society,acts_shows.venid,acts_shows.venue,MAX(acts_performances.enddate) AS enddate, MIN(acts_performances.startdate) AS startdate FROM acts_shows_refs,acts_shows LEFT JOIN acts_performances ON (acts_shows.id=acts_performances.sid) WHERE acts_shows.authorizeid>0 AND enddate>'".date("Y-m-d",strtotime("-14 days"))."' AND (";

    $l = false;
    while($row = mysql_fetch_assoc($r)) {
      if($l) $select.=" OR ";
      $select.="sid=".$row["sid"]; 
      if($row["name"]==$username) $row["name"]="<span class=\"attention\">Yourself</span>";
      if(isset($showToPeep[$row["sid"]]))
	$showToPeep[$row["sid"]].=", ".makeLinkText(105,$row["name"],array("person"=>$row["pid"]));
      else
	$showToPeep[$row["sid"]]=makeLinkText(105,$row["name"],array("person"=>$row["pid"]));
      $l=true;
    }

    $select.=") GROUP BY acts_shows.id ORDER BY enddate ";
    

    $shows = mysql_query($select) or die(mysql_error());
    actionlog("Facebook: found ".mysql_num_rows($shows)." shows for ".$username);	     

    if(mysql_num_rows($shows)>0) {

    $titled = 0;	
    
    $ret="";

    while($row= mysql_fetch_assoc($shows)) {
      if(strtotime($row['enddate'])>strtotime("-14 days") && $titled<1) {
	$ret.="<p><b>Shows your friends have recently been in:</b>";
	$titled=1;
      }
      if(strtotime($row['startdate']." 23:59")<=strtotime("0 days") && strtotime($row['enddate']." 23:59")>=strtotime("0 days") && $titled<2) {
	$ret.="</p><p><b>Shows your friends are currently in:</b>";
	$titled=2;
      }
      if(strtotime($row['startdate']." 23:59")>strtotime("0 days") && $titled<3) {
	$ret.="</p><p><b>Shows your friends are preparing for:</b>";
	$titled=3;
      }	
      $ret.="<br/><strong>".makeLinkText(104,$row['title'], array("showid"=>$row["sid"]))."</strong> (".$showToPeep[$row["sid"]];	
      
      switch($titled) {
      case 1:
	$ret.="; finished ".dateFormat(strtotime($row['enddate']));
	break;
      case 2:
	$ret.="; finishes ".dateFormat(strtotime($row['enddate']));
	break;
      case 3:
	$ret.="; starts ".dateFormat(strtotime($row['startdate']));
	break;
      }
      $ret.=")";
    }
  }
  }
  if($ret=="") $ret="<p>Sorry, we can't find you or your friends in any shows. Perhaps you're all doing work.";
  global $site_support_email;

  $ret.="</p><p>(We know this because you're currently logged into facebook via camdram.net. ".makeLinkText(0,"Click here to forget your facebook details.",Array("fb_logout"=>1)).")";
  $ret.="</p><p><small>If something appears to be missing, it's probably because the producer of the relevant show hasn't added you or your friends to the camdram.net record of the cast/crew list. They can add you via our admin interface, so ask them to do so or email <a href=\"mailto:".$site_support_email."\">".$site_support_email."</a>.</small>";
}

    
unset($_GET["auth_token"]);

return $ret;

} else echo "<p>Sorry, it looks like the facebook site is experiencing technical problems.</p>";
}
} 

// $facebook->api_client->profile_setFBML($ret, $user);
// $facebook->redirect($facebook->get_facebook_url() . '/profile.php');

?>
