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
?>
<h3>Access Control</h3>
<?php
require_once("library/showfuns.php");
require_once("library/user.php");
require_once("library/editors.php");
require_once("library/table.php");

$showid = $_GET['showid'];
$socid = $_GET['socid'];
$listid=$_GET['listid'];
$pageid=$_GET['pageid'];
$emailid = $_GET['emailid'];
$includeid = $_GET['includeid'];
$storeid = $_GET['storeid'];
$mode = $_GET['mode'];
global $contact;
$contact=0;
switch ($mode) {
 case 0:
   $rid=$showid;
   $sectype='show';
   $contact = 1;
   break;
 case 1:
   $rid=$socid;
   $sectype='society';
   $contact=1;
   break;
 case 2:
   $rid=$listid;
   $sectype='email';
   break;
 case 3:
   $rid=$pageid;
   $sectype='knowledgebasesubpage';
   break;
 case 4:
   $rid=$emailid;
   $sectype="builderemail";
   break;
 case 5:
   $rid=$includeid;
   $sectype="include";
   break;
 case 6:
   $rid=$storeid;
   $sectype='store';
   $contact=1;
   break;
}


?>
<?php
$canedit=false;
if(($mode==0) && canEditShow($showid) && is_numeric($rid)) {
	$showdetails=getshowrow($showid);
	//showDispBasics($showdetails);
	global $adcdb;
	if (isset($_GET['grant']) && is_numeric($_GET['grant'])) {
		if (canedittoken($_GET[grant])) {
			$query="UPDATE acts_access SET type='show', issuerid=$_SESSION[userid] WHERE id=$_GET[grant] LIMIT 1";
			sqlQuery($query) or die(mysql_error());
			actionlog("token $_GET[grant] granted");
		}
		unset($_GET['grant']);
	}
	if (isset($_GET['deny']) && is_numeric($_GET['deny'])) {
		if (canedittoken($_GET[deny])) {
			$query="DELETE FROM acts_access WHERE id=$_GET[deny] LIMIT 1";
			sqlQuery($query) or die(mysql_error());
			actionlog("token $_GET[grant] denied");
		}
		unset($_GET['deny']);
	}
	$query = "SELECT acts_access.*, acts_users.email FROM acts_access, acts_users WHERE type='request-show' AND acts_access.rid=$rid AND acts_access.revokeid IS NULL AND acts_users.id=acts_access.uid";
	$res = sqlQuery($query,$adcdb) or die(mysql_error());
	if (mysql_num_rows($res)>0) {
		echo "<p><b>The following people have requested access to this show:</b></p>";
		maketable($res,array("User"=>"email"),array("User"=>'echo getUserForAdmin($row[uid]);'),
			'echo "<a href=\"".thisPage(array("grant"=>$row[\'id\']))."\">Grant</a>";
			echo " | <a href=\"".thisPage(array("deny"=>$row[\'id\']))."\">Deny</a>";
			');
	}
	echo "<h4>Show: ".$showdetails[title]."</h4>";
	// showDispBasics($showdetails);
	$canedit=true;
}
if(($mode==1) && canEditSoc($socid)) {
	$socid = mysql_real_escape_string($_GET['socid']);
	$query = "SELECT * FROM acts_societies WHERE id=$socid";
	$result = sqlQuery($query);
	$soc=mysql_fetch_assoc($result);
	echo("<h4>Society: ".$soc['name']."</h4>");
	$canedit=true;
}
if(($mode==2) && hasEquivalentToken('security',-2)) {
	$row=getlistrow($listid);
	echo "<h4>Mailing List: $row[name]</h4>";
	$canedit=true;
}
if(($mode==3) && hasEquivalentToken('security',-2) && is_numeric($pageid)) {
	$query="SELECT * FROM acts_pages WHERE id=$pageid";
	$result=sqlQuery($query,$adcdb) or die(mysql_error());
	$row=mysql_fetch_assoc($result);
	mysql_free_result($result);
	echo "<h3>Knowledge Base Subpage Creation: $row[title]</h3>";
	$canedit=true;
}
if(($mode==4) && canEditBuilderEmail($emailid)) {
  $query = "SELECT title FROM acts_email WHERE emailid='$emailid'";
  $result = sqlQuery($query) or die(mysql_error());
  $row = mysql_fetch_assoc($result);
  mysql_free_result($result);
  echo "<h4>Email $emailid - $row[title]</h4>";
  $canedit = true;
}
if(($mode==5) && hasEquivalentToken('include',$includeid)) {
  $query = "SELECT name FROM acts_includes WHERE id='$includeid'";
  $result = sqlQuery($query) or die(mysql_error());
  $row = mysql_fetch_assoc($result);
  mysql_free_result($result);
  echo "<h4>Include $includeid - $row[name]</h4>";
  $canedit = true;
}
if (($mode==6) && hasEquivalentToken('store',$storeid)) {
	$query = "SELECT * FROM acts_stores WHERE id=$storeid";
	$result = sqlQuery($query);
	$store=mysql_fetch_assoc($result);
	echo("<h4>Store: ".$store['name']."</h4>");
	$canedit=true;
}

if ($canedit==true) {

$displayform=true;

if(isset($_GET['pendingemail']))
{
	$email=EmailToUser($_GET['pendingemail']);
	if(createPendingToken($_GET['pendingemail'],$sectype,$rid)) {
		inputFeedback("Pending access granted for user ".$email);
	}
	unset($_GET['pendingemail']);
}

if(isset($_GET['grantid']))
{
	if(createToken($_GET['grantid'],$sectype,$rid))
	{
		inputFeedback("Access granted for user ".userFromId($_GET['grantid']));
	} else 
		inputFeedback("Grant error","Granting access to user ".userFromId($_GET['grantid'])." failed. There are a 
		number of possible reasons for this.<ul><li>The user already has access to this resource (directly
		or indirectly by access to a society or site portion)</li><li>The user account has been invalidated</li>
		<li>The user has been specifically prevented from taking access of the resource you have tried to assign</li></ul>",true);
	
	unset($_GET['grantid']);
}
if(isset($_GET['revokeid']))
{
	if(revokeToken($_GET['revokeid']))
		inputFeedback("Access revoked successfully");
	else
		inputFeedback();
	unset($_GET['revokeid']);
	
}
if(isset($_GET['setcontact']) && is_numeric($_GET['setcontact'])) {
	global $adcdb;
	$query="UPDATE acts_access SET contact=1 WHERE id=$_GET[setcontact]";
	unset($_GET['setcontact']);
	$result=sqlQuery($query,$adcdb);
}
if(isset($_GET['removecontact']) && is_numeric($_GET['removecontact'])) {
	global $adcdb;
	$query="UPDATE acts_access SET contact=0 WHERE id=$_GET[removecontact]";
	unset($_GET['removecontact']);
	$result=sqlQuery($query,$adcdb);
}
if(isset($_GET['deletependingid']) && is_numeric($_GET['deletependingid']))
{
	global $adcdb;
	$query="DELETE FROM acts_pendingaccess WHERE id='$_GET[deletependingid]'";
	unset($_GET['deletependingid']);
	$result=sqlQuery($query,$adcdb);
	if(mysql_affected_rows()>0)
		inputFeedback("Pending Token Deleted");
	else
		inputFeedback();
	unset($_GET['revokeid']);
	
}
if(isset($_POST['email']))
{
		$displayform=false;
	$email=emailToUser($_POST['email']);
	$grantuid=idFromUser($email);
	if($grantuid>0)
	{
 $getName = "SELECT name FROM acts_users WHERE id=$grantuid";
 $r = sqlQuery($getName) or die(sqlEr($getName));
 $row = mysql_fetch_assoc($r);
?>
<p>Are you sure you want to grant access for this resource to <strong><?=$row[name]." (".$email.")"?></strong>?</p>
<p><?php makeLink(0,"Abort",array("email"=>"NOSET")); ?> | <?php makeLink(0,"Continue",array("grantid"=>$grantuid)); ?></p>  <?php
	} else {
		$pattern = "/([a-z0-9\-\_\.\+]+@[a-z0-9\.\-]+[a-z0-9])|([a-z][a-z]+[0-9]+)/i";
		if ($email != "" && preg_match ($pattern, $email)) {
		?>
The email address <?=$email?> is not registered with us. Do you want to grant access that will become active when they sign up?"
<p><?php makeLink(0,"No",array("email"=>"NOSET")); ?> | <?php makeLink(0,"Yes",array("pendingemail"=>$email)); ?></p>  <?php
		} else {
?>
You must enter a valid email address.<br>
<?php makeLink(0, "Back", array("email"=>"NOSET"));
		}
	}
}
if($displayform)
{
?>
</p>
<p>If you want someone else to be able to make changes to this resource, enter their email below:</p>
<form action="<?=thisPage() ?>" method="post" name="form1">
User login address:
  <input name="email" type="text" id="email" value="<?=$_POST['email']?>"><input type="submit" name="Submit" value="Allow Access">
</form>
<p>Currently the following people have access to this resource <strong>specifically</strong> (note
<?php if($mode==0) echo("society administrators or "); ?>site administrators are not listed here):</p>
<?php
	global $adcdb;
	$query = "SELECT acts_access.*, acts_users.email FROM acts_access, acts_users WHERE type='".$sectype."' AND acts_access.rid=$rid AND acts_access.revokeid IS NULL AND acts_users.id=acts_access.uid";
	$res = sqlQuery($query,$adcdb) or die(mysql_error());
	maketable($res,array("User"=>"email"),array("User"=>'echo getUserForAdmin($row[uid]);'),
		'
			echo "<a href=\"".thisPage(array("revokeid"=>$row[\'id\']))."\">Revoke</a>";
			global $contact;
			if ($contact==1) {
				if ($row[\'contact\']==0) echo " | <a href=\"".thisPage(array("setcontact"=>$row[\'id\']))."\">Add to contacts</a>";
				if ($row[\'contact\']==1) echo " | <a href=\"".thisPage(array("removecontact"=>$row[\'id\']))."\">Remove from contacts</a>";
			}
		');

	$query = "SELECT * FROM acts_pendingaccess WHERE type='".$sectype."' AND rid=$rid";
	$res = sqlQuery($query,$adcdb) or die(mysql_error());
        if(mysql_num_rows($res)>0) {
	echo "<h4>Users not yet signed up</h4>";
	maketable($res,array("User"=>"email"),array(),'echo "<a href=\"".thisPage(array("deletependingid"=>$row[\'id\']))."\">Delete</a>";');
}

?>


<?php } 
}?>

