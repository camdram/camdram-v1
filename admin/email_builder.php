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

if (!hasEquivalentToken("society",0)) die('You are not permitted to access this page');
require_once('./library/editors.php');
require_once('./library/user.php');
global $adcdb;
$uid=$_SESSION['userid'];

/*
// shouldn't be required
// this was used when moving from single user to token system
$q = "SELECT * FROM acts_email";
$r = sqlQuery($q) or sqlEr($q);
echo "<ul>";
while($row=mysql_fetch_assoc($r)) {
  $emailid=$row[emailid];
  $userid=$row[userid];
  if(!hasToken('builderemail',$emailid,$userid)) {
    echo "<li>$row[title]  - ".getUserEmail($userid)."</li>";
    createToken($userid,'builderemail',$emailid,false);
  }
}
echo "</ul>";
mysql_free_result($r);
*/

if (isset($_POST['title']) && hasEquivalentToken("security",-2)) {
	if ($_POST['title']!="") {
		$title=mysql_real_escape_string(htmlspecialchars($_POST['title']));
		$summary=mysql_real_escape_string($_POST['summary']);
		$publicadd=(isset($_POST['publicadd']))?1:0;
		$query_create="INSERT INTO `acts_email` (`title`,`public_add`,`summary`) VALUES ('$title',$publicadd,'$summary');";
		if (checkSubmission()) {
			$q=sqlQuery($query_create,$adcdb) or die(mysql_error());
			$emailid=mysql_insert_id();
			actionlog("created email $emailid");
			creatorAccess('builderemail',$emailid,true);
		}
	}
	else {
		$error="You must supply a name for your email";
	}
}
if (isset($_POST['title']) && !hasEquivalentToken("security",-2)) {
	if ($_POST['title']!="") {
		$title=mysql_real_escape_string(htmlspecialchars($_POST['title']));
		$query_create="INSERT INTO `acts_email` (`userid`,`title`) VALUES ($uid,'$title');";
		if (checkSubmission()) {
			$q=sqlQuery($query_create,$adcdb) or die(mysql_error());
			$emailid=mysql_insert_id();
			actionlog("created email $emailid");
			creatorAccess('builderemail',$emailid,true);	 
		}
		
	}
	else {
		$error="You must supply a name for your email";
	}
}
if (isset($_POST['sig']) && checkSubmission()) {
	if ($_POST['sig']=="") {
		inputFeedback("You cannot create a blank signature");
	}
	else {
		$query_insert="INSERT INTO acts_email_sigs (`uid`,`sig`) VALUES (".$_SESSION['userid'].",'".mysql_real_escape_string($_POST['sig'])."')";
		sqlQuery($query_insert,$adcdb) or die(mysql_error());
	}
}
if (isset($_GET['deletealias'])){
	$deleteid=mysql_real_escape_string($_GET['deletealias']);
	$query_delete="DELETE FROM acts_email_aliases WHERE uid=$uid AND id=$deleteid";
	$q=sqlQuery($query_delete,$adcdb) or die(mysql_error());
	actionlog("deleted email alias $deleteid");
}
if (isset($_GET['deletesig'])){
	$deleteid=mysql_real_escape_string($_GET['deletesig']);
	$query_delete="DELETE FROM acts_email_sigs WHERE uid=$uid AND id=$deleteid";
	$q=sqlQuery($query_delete,$adcdb) or die(mysql_error());
	actionlog("deleted email signature $deleteid");
}
if (isset($_GET['delete']) && canEditBuilderEmail($_GET[delete])) {
	$deleteid=mysql_real_escape_string($_GET['delete']);
	$query_delete="DELETE FROM acts_email WHERE emailid=$deleteid";
	$q=sqlQuery($query_delete,$adcdb) or die(mysql_error());
	if (mysql_affected_rows()>0) {
		$query_delete="DELETE FROM acts_email_items WHERE emailid=$deleteid";
		$q=sqlQuery($query_delete,$adcdb) or die(mysql_error());
		actionlog("deleted email $deleteid");
	}
}

if(hasEquivalentToken('security',-1)) {
  if(!isset($_POST[emailfor])) $emailfor = $_SESSION[emailfor]; else {
    $emailfor = $_POST[emailfor];
  }
  $_SESSION[emailfor]="";
  if($emailfor!="") {
    $uid= userToID($emailfor);
    if($uid > 0) 
      {
	$emails_available = getUsersSelectString('emailid','builderemail',$uid);
	$_SESSION[emailfor] = $emailfor;
      }
    else {
      $emails_available = getUsersSelectString('emailid','builderemail');
      inputFeedback("User ".$emailfor." not known");
      unset($_SESSION[emailfor]);
    }
  } else {
    $emails_available = getUsersSelectString('emailid','builderemail');
  }
} else $emails_available = getUsersSelectString('emailid','builderemail');
if($emails_available!="") $emailquery="SELECT * FROM acts_email WHERE ".$emails_available." ORDER BY emailid DESC";  else $emailquery="SELECT * FROM acts_email WHERE 0=1";
if($emailfor=="all") $emailquery="SELECT * FROM acts_email ORDER BY emailid DESC";
$q=sqlQuery($emailquery,$adcdb) or die(sqlEr($emailquery));
     ?><h3>Edit or Send an Email:</h3><?php
if(hasEquivalentToken('security',-1)) {
 
  ?><h4>Admin</h4><p>Other user's emails are hidden for your convenience.
You can view them by typing in their id:</p>
 <form name="quicky" method="post" action="<?=thisPage(array("CLEARALL"=>"CLEARALL"))?>">
  <div align="left">
    <strong>View User</strong> 
    
    <input name="emailfor" type="text" value="<?=$emailfor?>" size="10">
    <input name="Go" type="submit" id="Go" value="Go">
    </div>
</form>
<?php } else {?>
  
<p>
If you want to write a one-off email manually, you can go straight to the
<?php makelink(161,"email sender",array("CLEARALL"=>"CLEARALL"));?>;
to use advanced features such as auto-creation of show details,
 you need to "create" an email below.
</p>
<?php } ?>
<?php
if (mysql_num_rows($q)>0) { ?><h4>Stored/In-Progress Emails:</h4><table class="dataview">
<tr><th>Title</th><th>Action</th></tr><?php
	
	while($row_email=mysql_fetch_assoc($q)) {
	  echo "<tr>";
		$title=$row_email['title'];
		$emailid=$row_email['emailid'];
		$num_query = "SELECT * FROM acts_email_items WHERE emailid=".$row_email[emailid];
		$num_result = sqlQuery($num_query);
		if($num_result>0) {
		  $number = mysql_num_rows($num_result);
		  mysql_free_result($num_result);
		  $extra = "(<i>$number ";
		  if($number==1) $extra.="item"; else $extra.="items";
		  $num_query = "SELECT * FROM acts_email_items WHERE emailid=".$row_email[emailid]." AND creatorid>0";
		  $num_result= sqlQuery($num_query);
		  if($num_result>0) {
		    $extnumber = mysql_num_rows($num_result);
		    if($extnumber>0) $extra.=", of which $extnumber public submissions";
		    mysql_free_result($num_result);
		  }
		  $extra.="</i>) ";
		}
	  echo "<td>$title $extra</td><td>";
	  makeLink (153,"Edit",array("CLEARALL"=>"CLEARALL","emailid"=>$emailid));
	  echo " | ";
	  makeLink('email_sender.php',"Send",array("CLEARALL"=>"CLEARALL","emailid"=>$emailid));
	  echo " | ";
	  
	  makeLink('resource_tokens.php','Access',array("emailid"=>$emailid,"CLEARALL"=>"CLEARALL","mode"=>4));
	  echo " | ";
		   echo "<a href=\"".thisPage(array("CLEARALL"=>"CLEARALL","delete"=>$emailid))."\" ".confirmer("delete this email").">Delete</a>";
		   
		echo "</td></tr>";
	}
	echo "</table>";
}
?>
<h4>Create a new email</h4>
<?php echo "<b>$error</b>"; ?>
<form method="post" action="<?=linkTo(80,array("CLEARALL"=>"CLEARALL"))?>">
<table class="editor"><input type="hidden" name="submitid" VALUE="<?php
echo (allowSubmission()); ?>"><tr><th>
Email Name</th><td> <input type="text" name="title" value="<?php echo $_POST['title']; ?>"></td></tr>

<?php if (hasequivalenttoken("security",-2)) {
?>
<tr><th>Public can add</th><td><input type="checkbox" name="publicadd"></td></tr>
<tr><th>Summary for public</th>
</th><td><textarea name="summary" wrap="virtual" cols=60 rows=5>
</textarea>
</td></tr>
<?php } ?>
<tr><td>&nbsp;</td><td><input type=submit value="add"></td></tr>
</table>
</form>

<h3>Email Aliases</h3>
Email aliases allow you to send emails directly from the email builder with an address other than your default address
<?php
$emailaliasquery="SELECT * FROM acts_email_aliases WHERE uid=$uid ORDER BY email";
$q=sqlQuery($emailaliasquery,$adcdb) or die(mysql_error());
if (mysql_num_rows($q)>0) {
	echo "<ul>";
	while($row_alias=mysql_fetch_assoc($q)) {
		$name=$row_alias['name'];
		$email=$row_alias['email'];
		$id=$row_alias['id'];
		echo "<li>$name ($email) - ";
		makeLink(80,"Delete",array("CLEARALL"=>"CLEARALL","deletealias"=>$id));
		echo "</li>";
	}
	echo "</ul>";
}

?>

<h4>Create a new alias</h4>
<form method="post" action="<?=linkTo(162,array("CLEARALL"=>"CLEARALL"))?>">
<input type="hidden" name="submitid" VALUE="<?php
echo (allowSubmission()); ?>">
<table class="editor">
<tr>
	<th>Name:</th><td><input type="text" name="name">
	<div class="smallgrey">E.g. "Joe Bloggs".</div>
	</td>
</tr>
<tr>
	<th>Email Address:</th><td><input type="text" name="email">
	<div class="smallgrey">E.g. "joe.bloggs@hotmail.com"</div>
	</td>
</tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Add"></td></tr>
</table>
<p>You will be required to validate this email address as an address you have access to. The above name would send emails "From: Joe Bloggs &lt;joe.bloggs@hotmail.com&gt;"</p>
</form>
<h3>Email Signatures</h3>
<div class="smallgrey">Email signatures can be added to the end of your message</div>
<?php
$emailsigquery="SELECT * FROM acts_email_sigs WHERE uid=$uid ORDER BY id";
$q=sqlQuery($emailsigquery,$adcdb) or die(mysql_error());
if (mysql_num_rows($q)>0) {
	echo "<ul>";
	while($row_sig=mysql_fetch_assoc($q)) {
		$sig=nl2br($row_sig['sig']);
		$id=$row_sig['id'];
		echo "<li>$sig - ";
		makeLink(80,"Delete",array("CLEARALL"=>"CLEARALL","deletesig"=>$id));
		echo "</li>";
	}
	echo "</ul>";
}

?>

<h4>Create a new Signature</h4>
<form method="post" action="<?=linkTo(80,array("CLEARALL"=>"CLEARALL"))?>">
<input type="hidden" name="submitid" VALUE="<?php
echo (allowSubmission()); ?>">
<table border=0>
<tr>
	<td>Signature</td><td><textarea name="sig" rows=4 cols=80 wrap=virtual></textarea>
	<div class="smallgrey">E.g. Joe Bloggs - email: joe.bloggs@hotmail.com<br />2 Dashes will automatically be added between the message and the signature, so you should not enter them here</div>
	</td>
</tr>
<tr><td colspan=2><input type=submit value="add"></td></tr>
</table>
</form>
