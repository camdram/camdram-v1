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

function getUserRow($uid)
{
	if(((int)$uid)>0) $query = "SELECT * FROM acts_users WHERE id=".$uid;
	else $query = "SELECT * FROM acts_users WHERE email='".$uid."'";
	
	global $adcdb;
	$r = sqlQuery($query,$adcdb);
	
	if($r>0)
	{
		$row = mysql_fetch_assoc($r);
		mysql_free_result($r);
		return $row;
	} else return array("email"=>"Unknown User","name"=>"Unknown User");
}

function EmailtoUser($email, $removecam = true)
{
	$email=ereg_replace("[^[:alnum:]@.+-]", "", $email);
	if ($removecam)
	{
		$email=ereg_replace("@cam.ac.uk","",$email);
		$email=ereg_replace("@hermes.cam.ac.uk","",$email);
	}
	return strtolower($email);
}

function userToEmail($ec)
{
	// usernames are stored w/o cam.ac.uk for convenient login
	
	if(!strchr($ec,'@'))
			$ec.="@cam.ac.uk";
	return $ec;
}

function userToID($user)
{
  $euser = EmailToUser($user);
  $q = "SELECT id FROM acts_users WHERE `email`='$euser' OR `email`='$user'";
  $r = sqlQuery($q);
  if($r>0) {
    $f = mysql_fetch_assoc($r);
    mysql_free_result($r);
    return $f[id];
  } else return 0;
}

function getUserEmail($uid)
{
	$row = getUserRow($uid);
	return userToEmail($row['email']);
}

function getUserName($uid)
{
	$row = getUserRow($uid);
	return $row['name'];
}

function getUserString($uid,$long=false)
{
	$row = getUserRow($uid);
	$st="";
	if($row[publishemail]==1) $st.= "<a href=\"mailto:" .userToEmail($row[email])."\">";
	$st.=($row[name]);
	if($row[publishemail]==1) $st.= "</a>";
	
	if(hasEquivalentToken('security',-3) && $long)
	  {
	    
	    $st.=" <small>[".makeLinkText(110,"Who?",array('uid'=>$row[id]),true)."]</small>";
	  }

	if(hasEquivalentToken('security',-3,$row[id]) && $long && false)
	  $st.="<br/><small>camdram.net&nbsp;admin</small>";
	return $st;
}

$cached_tokens=array();

function hasToken($type,$rid,$uid = 0)
{
	// hasToken checks whether a user has a specific security token
	// $uid=0 -> check for current (logged in) user
	
	global $cached_tokens,$verbose;
	$cache_ref = $type.".".$rid.".".$uid;
	if(isset($cached_tokens[$cache_ref]))
		return $cached_tokens[$cache_ref];
		
	
	if($uid==0)	$uid = $_SESSION['userid'];
	$query = "SELECT * FROM acts_access WHERE `type`='$type' AND `rid`='$rid' AND `uid`='$uid' AND `revokeid` IS NULL";
	$result = sqlQuery($query);
	$n = mysql_num_rows($result);
	mysql_free_result($result);
	
	$cached_tokens[$cache_ref]=($n>0);
	if(isset($verbose)) {
	  echo '<div float="right"><small>'.$type.'/'.$rid.'=';
	  
	  if($n>0)
	    echo '<font color="#00ff00">OK</font>';
	  else
	    echo '<font color="#ff0000">BLOCK</font>';
	  if($cached==true) echo '<font color="#0000ff"> (cache)</font>';
	  echo "</small></div>";
	}
	return ($n>0);
}

$cached_equivalent_tokens=array();

function hasEquivalentToken($type,$rid,$uid = 0)
{
	// hasToken checks whether a user has a specific security token
	// or an equivalent allowing for access to the specified resource
	// $uid=0 -> check for current (logged in) user
	
	global $cached_equivalent_tokens,$verbose;
	$cached = false;
	$cache_ref = $type.".".$rid.".".$uid;
	if(isset($cached_equivalent_tokens[$cache_ref]))
	{
	  $cached = true;
	  $n = $cached_equivalent_tokens[$cache_ref];
	} else {
		
	  global $adcdb;
	  if($type=='show' && $rid!=0)
	    $query = "SELECT acts_access.*, acts_shows.socid, acts_shows.id FROM acts_access, acts_shows WHERE ";
	  else
	    $query="SELECT * FROM acts_access WHERE ";
	  $query.=securityQuery($type,$rid,$uid);
	  $result = sqlQuery($query,$adcdb);
	  if($result==0) return false;
	  $n = mysql_num_rows($result);
	  mysql_free_result($result);
	  
	  $cached_equivalent_tokens[$cache_ref]=($n>0);
        }
	if(isset($verbose)) {
	  $message=$type.'/'.$rid;
	  if($uid>0) $message.= "(for alternate user)";
	  $message.= '&lt;';
	  
	  if($n>0)
	    $message.= '<font color="#00ff00">OK</font>';
	  else
	    $message.= '<font color="#ff0000">BLOCK</font>';
	  if($cached==true) $message.= '<font color="#0000ff"> (cache)</font>';
	  echo hint("Sec",$message,($n==0));
	}
	return ($n>0);
}

function whoHas($type,$rid)
{
  // returns array of userids having a specified token
  $s = "SELECT uid FROM acts_access WHERE type='$type' AND rid='$rid'";
  $r = sqlQuery($s);
  if(mysql_num_rows($r)>0) {
    $ret = array();
    while($row = mysql_fetch_assoc($r))
      $ret[$row[uid]]=$row[uid];
    mysql_free_result($r);
    return $ret;
  }
  return array();
    
}
function securityQuery($type,$rid,$uid=0)
{
	// builds an SQL string to pick out database entries where the user has
	// access. Note this may be quite inefficient -> revise.
	// $uid=0 -> check for current (logged in) user
	
	if($uid==0 && array_key_exists('userid',$_SESSION))
		   $uid = $_SESSION['userid'];
	switch($type)
	{
	case 'security':
		$query = "acts_access.`type`='security' AND acts_access.`rid`>=$rid";
		break;
	case 'show':
		if($rid!=0) {
			$allsocs=societies($rid);
			$query = "((acts_access.`type`='show' AND (acts_access.`rid`='$rid' OR acts_access.`rid`=-1)) OR (acts_access.`type`='society' AND (acts_access.`rid`=acts_shows.`socid` OR acts_access.`rid`=acts_shows.`venid` OR acts_access.`rid`=-1";
			if (isset($allsocs)) {
				foreach ($allsocs as $socid) {
					$query=$query." OR acts_access.rid=$socid";
				}
			}
			$query=$query.") AND acts_shows.`id`='$rid') OR (acts_access.`type`='security' AND acts_access.`rid`>=-3))";
		}
		else $query = " uid=".$_SESSION['userid'];
		break;
	case 'show-requested':
		if($rid!=0)
			$query = "acts_access.`type`='show-requested' AND acts_access.`rid`='$rid' AND acts_access.`uid`='$uid'";
		else
			$query = "acts_access.`type`='show-requested' AND acts_access.`uid`='$uid'";
		break;
	case 'society':
		if($rid!=0 || $rid!="") {
			$rid=addslashes($rid);
			$allsocs=allsocs($rid);
			$query = "((acts_access.`type`='society' AND (acts_access.`rid`=$rid";
			if (isset($allsocs)) {
				foreach ($allsocs as $socid) {
					$query=$query." OR acts_access.rid=$socid";
				}
			}
			$query .= " OR acts_access.`rid`=-1)) OR (acts_access.`type`='security' AND acts_access.`rid`>=-3))";
		}
		else $query = "acts_access.`type`='society' OR acts_access.`type`='security'";
		break;
	case 'knowledgebase':
		$query="((acts_access.`type`='knowledgebase' AND acts_access.`rid`=$rid) OR acts_access.`type`='security')";
		break;
	case 'knowledgebasesubpage':
		$query="((acts_access.`type`='knowledgebasesubpage' AND acts_access.`rid`=$rid) OR acts_access.`type`='security')";
		break;
	case 'store':
		if ($rid!=0 || $rid!="") {
			$query="((acts_access.`type`='store' AND acts_access.`rid`=$rid) OR acts_access.`type`='security')";
			}
		else {
			$query="((acts_access.`type`='store') OR acts_access.`type`='security')";
		}
		break;
	case 'include':
		if ($rid!=0 || $rid!="") $query="((acts_access.`type`='include' AND acts_access.`rid`=$rid) OR (acts_access.`type`='security' AND acts_access.`rid`>=-2))";
		else $query="((acts_access.`type`='include') OR (acts_access.`type`='security' AND acts_access.`rid`>=-2))";
		break;
	case 'email':
		$query="((acts_access.`type`='email' AND acts_access.`rid`=$rid) OR (acts_access.`type`='security' and acts_access.`rid`>=-2))";
		break;
	case 'builderemail':
	  $query="((acts_access.`type`='builderemail' AND acts_access.`rid`=$rid) OR (acts_access.`type`='security' and acts_access.`rid`>=-2))";
	  break;
	}
	
	$query="($query) AND acts_access.`uid`='$uid' AND acts_access.`revokeid` IS NULL"; 
	
	return $query;
}

// USER LOGIN/OUT


function tryAuth($comid,$pass)	// authorize as $comid with password $pass (md5ed) - returns false for fail
{
	$origcomid = $comid;
	$comid= EmailtoUser($comid);	
	global $adcdb;
$query_check = "SELECT * FROM acts_users WHERE email=\"$comid\" AND pass=\"$pass\"";
	$check = sqlQuery($query_check, $adcdb) or die(mysql_error());
	
	$rows_check = mysql_num_rows($check);
	$ch=mysql_fetch_assoc($check);
	mysql_free_result($check);
	if ($rows_check<=0)
	{
		// Try without removing cam.ac.uk
		$comid = EmailtoUser($origcomid, false);
		$query_check = "SELECT * FROM acts_users WHERE email=\"$comid\" AND pass=\"$pass\"";
		$check = sqlQuery($query_check, $adcdb) or die(mysql_error());

		$rows_check = mysql_num_rows($check);
		$ch=mysql_fetch_assoc($check);
		mysql_free_result($check);
	}

	if($rows_check<=0) return -1; else return $ch['id'];
}	

$done_displayloginbox = false;
$done_displaylinks = false;

function logindetails($displayloginbox=true,$displaystatus=true,$displaylinks=true,$displaytimeout=false)	
// if user is logged in, returns true and displays links. Otherwise returns false and displays error
{
	global $currentid,$mode,$lasttry;
	global $done_displayloginbox, $done_displaylinks;
	if($done_displayloginbox==true) {
		$displayloginbox=false;
		$displaytimeout=false;
	}
	
	if($done_displaylinks==true)
		$displaylinks=false;
		
	if($displayloginbox==true)
		$done_displayloginbox=true;
	
	if($displaylinks==true)
		$done_displaylinks=true;
	
	if(isset($_GET['logout']))
	{
		logout();
		$_SESSION['logout']=$_GET['logout'];
		unset($_GET['logout']);
	}
		
	if(!isset($_SESSION['user']))
	{
		if($displayloginbox)
		{
			if ($_GET['error']==1) {
				?><h3>log in</h3><p>Your <strong>username or password was incorrect</strong>; please try again</p><?php
														} else echo "<h3>log in</h3>";
			unset($_GET['error']);
			login(false,false,false);
		} else if($displaylinks)
		{
		  if(!isset($_GET['loginbox'])) {
		    makeLink(0,"Click here to log in",array("loginbox"=>"display"), false, "", false, "", array(), true);
		    
		    
		  } else { 
		    makeLink(0,"Cancel log in",array("loginbox"=>"NOSET"), false, "", false, "", array(), true);
		  }
		  echo " or, if you don't have one: ";
		  makeLink(109,"get a free account...",array(),true);
		}
		else if($displaystatus)
		{
			if ($_GET['error']==1) {
				?>Your username or password was incorrect; please try again<br /><?php
			}
			else echo "Not logged in.";
			
		}
		return false;
	}
	
	$comid=isset($_SESSION['user']) ? $_SESSION['user'] : "";
	$auth=isset($_SESSION['authlevel']) ? $_SESSION['authlevel'] : "";
	$expire=isset($_SESSION['expire']) ? $_SESSION['expire'] : 0;
	if($expire<time())
	{
		$alw=0;
		if(isset($_POST['pass']))
		{
			$alw=tryAuth($_SESSION['user'],md5($_POST['pass']),1);
			if($alw>0)
			{
				$_SESSION['authlevel']=$alw;
				$auth=$alw;
				actionlog("Reauthenticated - DEPRECATED");
			}
		}
		if($alw<=0)
		{
			
			if($displayloginbox || $displaytimeout)
			{
				
				$done_displayloginbox=true;
				login();
				unset($_SESSION['authlevel']);
			} else if($displaylinks) {
			  displayUserLinks();
			} else if ($displaystatus) {
				echo "Session has timed out - please log in again.";
			}
			return false;
		}
	}
	
	$_SESSION['expire']=time()+600;
	if($auth>0 && $mode<2)
	{ 
		if($displaylinks)
		  {
		     displayUserLinks();
		}
	}
	return ($auth>0);
}

function displayUserLinks() {
  $comid=$_SESSION['user'];
  if(isset($_SESSION['ghostid']) && $_SESSION['ghostid']>0) echo "<b>Emulating</b> ".$comid; else echo "<b>Logged in as</b> ".$comid;
  
  /*if($propereditor && !$small) { 
				makeLink(78,"menu");
				echo $separator;
				makeLink(102,"help",array("CLEARALL"=>"CLEARALL","helpid"=>$currentid));
				echo $separator;
				makeLink(73,"logout"); 
			} else */
  echo " | ";
  makeLink(1,"logout",array("logout"=>"logout")); 

  echo " | ";
  makeLink(84,"account details"); 
  /* Superceded by tabs:

  echo " | ";
  
  makeLink(79,"show manager");
  echo " | ";
  makeLink(78,"full admin menu");
  */
}

function su($toUser)
{
  if(hasEquivalentToken('security',-1) || hasEquivalentToken('security',-1,$_SESSION['ghostid'])) {
    actionLog("SU: $toUser");
    $uid= userToID($toUser);
    if($uid>0) {
      if(!isset($_SESSION['ghostid'])) 
	{
	  $_SESSION['ghostid']=$_SESSION['userid'];
	 
	}
     
      if($uid==$_SESSION['ghostid']) 
	{
	  $_SESSION['ghostid']=0;
	  
	}
      $_SESSION['userid']=$uid;
      $_SESSION['user']=$toUser;

      // purge token cache

      global $cached_equivalent_tokens, $cached_tokens;
      $cached_equivalent_tokens=array();
      $cached_tokens=array();

    } else inputFeedback("User not found");
    
  } else inputFeedback("Cannot switch user","Insufficient priviledges as current user; please log back in as an administrator.");
}

function logout()
{
	session_start();
	session_unset();
	session_destroy();
	global $cookiedomain;
  	setcookie('camdramuser','',0,'/');
	session_start();
}

function login($insecure=false,$small=false,$title=true)
{
  if(!isset($_SESSION)){
    session_start();
  }
global $loginsuccess,$site_support_email,$extra_page_info_string;
if ($loginsuccess =="true") {
  echo "<strong>Your browser does not appear to have cookies enabled.</strong> You must enable cookies to log in to ".getConfig('site_name')." For assistance, email <a href=\"mailto:$site_support_email\">$site_support_email</a>.";
}
else {
	// login form
	global $currentid;
	global $mode;
	if (true || isset($_SERVER["HTTPS"]) || $insecure) {	// remove true || to prevent login from non secure connections
if ($small==false) {
 global $loginscript;
?>
<form name="loginform" id="loginform" method="post" action="<?php if ($currentid==73) echo linkTo(78,array(mode=>$mode,"sid"=>session_id()),$loginscript,false,"",false); else echo(linkto($currentid,array("mode"=>$mode,"sid"=>session_id()),$loginscript,false,"",false)); ?>"> 
<?php if($title) {
  echo "<h4>";
  if (isset($_SESSION['user'])) {
  	if (isset($_SESSION['expire'])) {
  		echo "session time out: confirm your password";
  	}
  	else {
  		echo "please confirm your password";
  	}
  }
  else echo "log in"; 
  echo "</h4>";
}
?>  

<table class="editor">
<input type="hidden" id="extra_page_info" name="extra_page_info" value="<?php echo substr($extra_page_info_string, 1)?>">
<tr><th>CRSID/Email</th><td>
   <input name="loginid" type="text" id="user" size="20" maxlength="70" value="<?=$_SESSION['user']?>" <?php 
									       
if (isset($_SESSION['user'])) {?> disabled >
   	<input name="loginid2" type="hidden" value="<?=$_SESSION['user']?>">
	
	<span class="smallgrey"><br>
	Click <?=makeLink(1,"here",array("logout"=>"logout","loginbox"=>"display"))?> to log in as a new user.</span><?php } else echo(">");?></td>
  </tr>
  <tr>
    <th>Password</th>
<td>	<input name="pass" type="password" size="20" id="pass" maxlength="20"><br/><div class="smallgrey" align="right"><?php makeLink(198,'Forgotten? Click here.'); ?></div>

</td></tr>

<? if (!isset($_SESSION['user'])) {
	echo "<tr><td></td><td><input type=\"checkbox\" name=\"remember\"> Remember my username on this computer</td></tr>";
}
?>
<tr><td colspan="2"><div align="right">
  <input name="Submit" type="submit" <?php if($insecure) echo "class=\"smallentry\""; ?> value="Log in">
							       <div class="smallgrey"><?php
											       echo " - ";
	makeLink(109,"get a free account...");
											       
											       if(isset($_GET['loginbox'])) {
	    echo "<br/> - ";
	    makeLink(0,"cancel log in and hide this box",array("loginbox"=>"NOSET"), false, "", false, "", array(), true);
	  }
?>
    </div></div></td>
  </tr>
 </table>
<?php foreach($_POST as $key=>$value) if($key!="pass" && $key!="user") echo("<input name=\"".$key."\" type=\"hidden\" value=\"".htmlspecialchars(stripslashes($value))."\">");?>

</form>
<?php }
else {
?>
 global $loginscript;
<form name="loginform" method="post" action="<?php if ($currentid==73) echo linkTo(78,array(mode=>$mode,"sid"=>session_id()),$loginscript,false,"",false); else echo(linkto($currentid,array("mode"=>$mode,"sid"=>session_id()),$loginscript,false,"",false)); ?>">
<table cellspacing="0" cellpadding="2">
  <tr>
    <td valign="middle">
	  <span class="smallgrey">CRSID/Email </span></td>
    <td valign="middle">
	  <input name="loginid" type="text" id="user" size="20" class="smallentry" maxlength="70" value="<?=$_SESSION['user']?>" <?php if (isset($_SESSION['user'])) {?> disabled > <?php } else echo(">");?>
	 </td>
    </tr>
  <tr>
      <td valign="middle"><span class="smallgrey"> Password</span>
        </td>
      <td valign="middle"><input name="pass" type="password" size="20" id="pass" class="smallentry" maxlength="70"></td>
    </tr>
 </table>
<table cellspacing="0" cellpadding="2">
  <tr>
    <td valign="middle">        <span class="smallgrey">
<?=makeLink(109,"get a free account...")?>
</span>
<?php foreach($_POST as $key=>$value) if($key!="pass" && $key!="user") echo("<input name=\"".$key."\" type=\"hidden\" value=\"".htmlspecialchars(stripslashes($value))."\">");?> </span></td>
    <td valign="middle"><input name="Submit" type="submit" class="smallentry" value="Log in"></td>
  </tr>
</table>
</form>
<?php } ?>
<script type="text/javascript">
//<!--
if(document.loginform.loginid.value=='') document.loginform.loginid.focus();
else document.loginform.pass.focus();
//-->
</script>
<?php
} else { ?><b>Can't log in over non-secure connection.</b> Please go to <a href="https://www.srcf.ucam.org/acts/?id=<?=$currentid?>">https</a> to continue.<?php }
}
}

function getUserForAdmin($userid)
{
	// returns user email loginid, or if permitted, a link to the user access manager (p.108)

	if(hasEquivalentToken('security',-3))
	{
		if($username=="") $row= getUserRow($userid);
		return makeLinkText(110,$row['email'],array('uid'=>$userid),true)." (".$row['name'].")";
	}
	else
		return getUserString($userid);
}

function authorizeShow($showid)
{
	global $adcdb;
	$societies=societies($showid);
	$hastoken=0;
	if (isset($societies)) {
		foreach ($societies as $soc) {
			if(hasEquivalentToken('society',$soc)) $hastoken=1;
		}
	}
	$row=getShowRow($showid);
    if ($row['socid'] !== null) {
        if(hasEquivalentToken('society',$row['socid']) || hasEquivalentToken('society',$row['venid']) || $hastoken==1)
        {
            $update = "UPDATE acts_shows SET authorizeid=".$_SESSION['userid']." WHERE id=".$row['id'];
            if(sqlQuery($update,$adcdb)>0)
            {
                actionlog("authorize show $showid");
                return true;
            }
        }
    }
    return false;

}

function creatorAccess($type,$rid,$force=false)
{
	global $adcdb;
	if (!hasEquivalentToken($type,$rid) || $force)
	{
		$ins = "INSERT INTO `acts_access` (`rid`, `uid`, `type`, `issuerid`, `creationdate`) VALUES ( '".$rid."', '".$_SESSION['userid']."', '".$type."', NULL, NOW())";
		$r= sqlQuery($ins,$adcdb) or die(mysql_error());
	}
}

function modeToSecurity($mode)
{
	$securitynecessary=-5;
	switch($mode)
	{
	case 'filtered':
		$securitynecessary=-3;
		break;
	case 'noprocess': case 'normal':
		$securitynecessary=-2;
		break;
	case 'include':
		$securitynecessary=-1;
		break;
	}
	return $securitynecessary;
}

function idFromUser($email)
{
	global $adcdb;
	$query_check = "SELECT * FROM acts_users WHERE `email`='$email'";
	$check = sqlQuery($query_check, $adcdb);
	if (!$user = mysql_fetch_assoc($check)) return -1;
	mysql_free_result($check);
	return($user['id']);
}

function userFromId($uid)
{
	global $adcdb;
	$query_check = "SELECT * FROM acts_users WHERE id=$uid";
	$check = sqlQuery($query_check, $adcdb);
	$user = mysql_fetch_assoc($check);
	mysql_free_result($check);
	return($user['email']);
}

function revokeToken($tid)
{
	global $adcdb;
	$query_check = "SELECT * FROM acts_access WHERE id=".$tid;
	$c = sqlQuery($query_check,$adcdb) or die(mysql_error());
	$num = mysql_num_rows($c);
	$res=true;
	if($num!=1)
		$res=false;
	else {
		$deleting = mysql_fetch_assoc($c);
		if(hasEquivalentToken($deleting['type'],$deleting['rid']))
		{
			$query_delete = "UPDATE acts_access SET revokeid=".$_SESSION['userid'].", revokedate=NOW() WHERE id='".$tid."' LIMIT 1";
			$del = sqlQuery($query_delete, $adcdb) or die(mysql_error());
			actionlog("revoke token $tid");
		} else $res=false;
	}
	mysql_free_result($c);
	return $res;
}

function deleteToken($tid)
{
	global $adcdb;
	$res=true;
	if(hasToken('security',-1))
	{
		$query_delete = "DELETE FROM acts_access WHERE id='".$tid."' LIMIT 1";
		$del = sqlQuery($query_delete, $adcdb) or die(mysql_error());
		actionlog("delete token $tid");
	} else $res=false;

	return $res;
}

function createToken($forUser,$type,$rid,$email=true)
{
	if(!hasEquivalentToken($type,$rid)) {
		return false;
	}
	else 
	{
		if(!hasToken($type,$rid,$forUser))
		{
			global $adcdb,$site_support_email;
			$siteurl=getconfig('site_url');
			$cr="INSERT INTO acts_access (uid,rid,type,issuerid,creationdate) VALUES ($forUser,$rid,'$type',".$_SESSION['userid'].",NOW())";
			$r = sqlQuery($cr,$adcdb) or die(mysql_error());
			if ($type == 'show')
				sqlQuery("DELETE FROM acts_access WHERE uid=$forUser AND type='request-show' LIMIT 1");
			actionlog("create token $ins (uid:$forUser, rid:$rid, type:$type)");
			switch($type) {
				case 'show':
					$show=getshowrow($rid);
					$subject="Access to show ".$show['title']." on ".getconfig('site_name')." granted";
					$text="You have been granted access to edit the show ".$show['title']." on ".getconfig('site_name').". This allows you to change the show description people see when visiting the site, and to add other information such as production team/cast lists and production team adverts.\n\nTo do this visit http://$siteurl/administration/edit_show?showid=$rid.\n\nIf you have any queries, please email $site_support_email.\n\nThank you,\n\nThe ".getconfig('site_name')." team.";
					break;
				case 'society':
					$society=getsocrow($rid);
					$subject="Access to society ".$society['name']." on ".getconfig('site_name')." granted";
					$text="You have been granted access to edit the society ".$society['name']." on ".getconfig('site_name').". This allows you to change the society description people see when visiting the site, and to add shows funded by your society.\n\nTo edit the society, please visit http://$siteurl/administration/edit_society\n\nIf you have any queries, please email $site_support_email.\n\nThank you,\n\nThe ".getconfig('site_name')." team.";
					break;
				case 'security':
					$subject="Administrator access to ".getconfig('site_name')." granted";
					switch ($rid) {
						case -1:
							$admin="a full administrator";
							break;
						case -2:
							$admin="an administrator";
							break;
						case -3:
							$admin="a content administrator";
							break;
					}
					$text="You have been granted access as $admin on ".getconfig('site_name').". To access the admin menu log in at http://$siteurl and click on \"admin\".\n\nIf you have any queries, please contact $site_support_email\n\nThank you,\n\nThe ".getconfig('site_name')." team";
					break;
			}
				
			if ($email=true && $subject!="") mailTo(userToEmail(userfromid($forUser)),$subject,$text,"","","",getconfig('site_name')." <$site_support_email>");
			return true;
		} else return false;
	}
}
function createPendingToken($forUser,$type,$rid,$email=true)
{
	if(!hasEquivalentToken($type,$rid)) {
		return false;
	}
	global $adcdb,$site_support_email;
	$siteurl=getconfig('site_url');
	$query="SELECT * FROM acts_pendingaccess WHERE email='$forUser' AND rid='$rid' AND type='$type'";
	$res=sqlQuery($query,$adcdb) or die(mysql_error());
	if (mysql_num_rows($res)>0) return false;
	$cr="INSERT INTO acts_pendingaccess (email,rid,type,issuerid,creationdate) VALUES ('$forUser',$rid,'$type',".$_SESSION['userid'].",NOW())";
	$r = sqlQuery($cr,$adcdb) or die(mysql_error());
	switch($type) {
		case 'show':
			$show=getshowrow($rid);
			$subject="Access to show ".$show['title']." on ".getconfig('site_name')." granted";
			$text="You have been granted pending access to edit the show ".$show['title']." on ".getconfig('site_name').". This will allow you to change the show description people see when visiting the site, and to add other information such as production team/cast lists and production team adverts.\n\nTo make use of this, you must create an account at at http://$siteurl. If you have any queries, please email $site_support_email.\n\nThank you,\n\nThe ".getconfig('site_name')." team.";
			break;
		case 'society':
			$society=getsocrow($rid);
			$subject="Access to society ".$society['name']." on ".getconfig('site_name')." granted";
			$text="You have been granted pending access to edit the society ".$society['name']." on ".getconfig('site_name').". This will allow you to change the society description people see when visiting the site, and to add shows funded by your society.\n\nTo make use of this, you must create an account at at http://$siteurl. If you have any queries, please email $site_support_email.\n\nThank you,\n\nThe ".getconfig('site_name')." team.";
			break;
		case 'security':
			$subject="Administrator access to ".getconfig('site_name')." granted";
			switch ($rid) {
				case -1:
					$admin="a full administrator";
					break;
				case -2:
					$admin="an administrator";
					break;
				case -3:
					$admin="a content administrator";
					break;
			}
		$text="You have been granted pending access as $admin on ".getconfig('site_name').". To activate this you must sign up at http://$siteurl.\n\nIf you have any queries, please contact $site_support_email\n\nThank you,\n\nThe ".getconfig('site_name')." team";
			break;
	}
	if ($email=true && $subject!="") mailTo(userToEmail($forUser),$subject,$text,"","","",getconfig('site_name')." <$site_support_email>");
	actionlog("create pending token for ($forUser, rid:$rid, type:$type)");
	return true;
}

function describeToken($token)
{
	$st="";
	switch($token['type'])
	{
	case 'show':
		$r=getShowRow($token['rid']);
		$st=maxChars($r['title'],30);
		break;
	case 'society':
		$r=getSocRow($token['rid']);
		$st=maxChars($r['name'],30);
		break;
	case 'email':
		$r=getListRow($token['rid']);
		$st=maxChars($r['name'],30);
		break;
	case 'knowledgebasesubpage':
		$r=getpagerow($token['rid']);
		$st=maxChars($r['title'],30);
		break;
	case 'security':
		switch($token['rid'])
		{		
		case -1:
			$st = "Full Admin";
			break;
		case -2:
			$st = "Admin";
			break;
		case -3:
			$st = "Content Admin";
			break;
		default:
			$st = "Security entry unknown";
		}
		break;
	default:
		$st = "Token unknown";
	}
	if($token['revokeid']!=0) $st.=" (revoked)";
	return $st;
}

function canEditToken($tokenid) {
	$query="SELECT * FROM acts_access WHERE id='$tokenid'";
	$result=sqlQuery($query) or die (mysql_error());
	if (!$row=mysql_fetch_assoc($result)) return false;
	$type=$row['type'];
	if ($type=="request-show") $type="show";
	if (hasEquivalentToken($type,$row['rid'])) return true;
	return false;
}




function RandomName( $nameLength )
{
	// nicked from php.net
  $NameChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $Vouel = 'aeiouAEIOU';
  $Name = "";

  for ($index = 1; $index <= $nameLength; $index++)
  {       
     if ($index % 3 == 0)
     {
       $randomNumber = rand(1,strlen($Vouel));
       $Name .= substr($Vouel,$randomNumber-1,1);   
     }else
       {
         $randomNumber = rand(1,strlen($NameChars));
         $Name .= substr($NameChars,$randomNumber-1,1);
       }
  }

  return $Name;
 }
 
 function allocPassword($rsid)
 {
 	global $adcdb,$site_support_email;
    $query_details="SELECT * FROM acts_users WHERE id=$rsid LIMIT 1";
	$people = sqlQuery($query_details, $adcdb) or die(mysql_error());
	$row_people = mysql_fetch_assoc($people);
	$num = mysql_num_rows($people);
	mysql_free_result($people);
	if($num==1)
	{
		$e=$row_people['email'];
		$ec = $e;
		if(!strchr($ec,'@'))
			$ec.="@cam.ac.uk";
		$p=RandomName(8);
		$pc=md5($p);
		$syntax = "UPDATE acts_users SET pass='$pc' WHERE id=$rsid LIMIT 1";
		$s=sqlQuery($syntax,$adcdb) or die(mysql_error());
		mailTo($ec,"Access to ".getConfig('site_name'),"This is an automated email from ".getConfig('site_name').".\n\n Your account is now active or your password has been renewed.
You may log in with the following details:\n\n  Username: $e\n  Password: $p  (password is case sensitive)
You are advised to change your password immediately. You can do this by clicking on the \"account details\" button which appears once you're logged in.

If you experience any problems, please contact ".$site_support_email." and we will attempt to solve your problem as fast as possible.","","","","\"".getConfig('site_name')."\" <".$site_support_email.">"); 
		actionlog("Password allocate for user $rsid");
		return $ec;
	}
	return false;
 }

 ?>
