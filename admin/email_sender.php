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
global $adcdb;
require_once("library/user.php");
require_once("library/editors.php");
require_once("library/mailinglists.php");
require_once("library/emailGen.php");


$emailid=$_GET['emailid'];
if (isset($_GET['emailid'])) makeLink(153,"&lt; Back to Email Editor",array("CLEARALL"=>"CLEARALL","emailid"=>$emailid));
else makelink(80,"&lt; Back to Email Selector",array("CLEARALL"=>"CLEARALL"));
echo "<br /><br />";
?>
<?php $emailsent=false;
$sendemail=true;
$uid=$_SESSION['userid'];

if (isset($_POST['message'])) {
	$fromid=$_POST['from'];

	if ($fromid==0) {
		$from="\"" . getUserName($_SESSION['userid'])."\" <".getUserEmail($_SESSION['userid']).">";
	}
	else {
		$query_from="SELECT * FROM acts_email_aliases WHERE id='$fromid'";
		$result=sqlQuery($query_from,$adcdb) or die(mysql_error());
		if($row=mysql_fetch_assoc($result)) {
			$name=$row['name'];
			$email=$row['email'];
			$id=$row['id'];
			$from=($name=="")?$name:"\"$name\" <$email>";
		}
		else {
			$from="";
			$sendemail=false;
		}
	}
	$listaddresses=getaddresses($_POST['list']);
	foreach ($_POST as $postkey=>$postvalue) $_POST[$postkey]=stripslashes($postvalue);
	$to=$_POST['to'];
	$cc=$_POST['cc'];
	$bcc=$_POST['bcc'];
	$subject=$_POST['subject'];
	$message=str_replace(chr(13),"",$_POST['message']);
	if ($_POST['sig'] !="") $message=$message."\n\n--\n".$_POST['sig'];
	if ($bcc!="") {
		$bcc.=", ";
	}
	foreach ($listaddresses as $address) {
		$bcc.=$address.", ";
	}
	//if ($bcc!="") $bcc=$bcc." ,";
	// echo "<p>Attempting to send to ";
	// if ($to!="") echo $to,";";
	// if ($cc!="") echo $cc,";";
	// if ($bcc!="") echo $bcc,";";
	// echo "</p>";
	if (checkSubmission() and $sendemail) {
		$emailsent=mailTo($to,$subject,$message,"",$cc,$bcc,$from);
	} else {
	  $emailsent = false;
	}
}
if ($emailsent) { 
  
  
	actionlog("Sent email");
	$message="";
	if(isset($_POST['emailid'])) {
	  $emailid = $_POST['emailid'];
	  $query_email="SELECT * FROM acts_email WHERE acts_email.emailid=$emailid";
	  $r = sqlQuery($query_email);
	  $message="No items were deleted after sending.";
	  if($r>0 && canEditBuilderEmail($emailid)) {
	    $email = mysql_fetch_assoc($r);
	    mysql_free_result($r);
	    if($email[deleteonsend]) {
	      $delete = "DELETE FROM acts_email_items WHERE acts_email_items.emailid='$emailid' AND acts_email_items.protect=0";
	      $q = sqlQuery($delete);
	      $affected = mysql_affected_rows();
	      if($affected>1) $message = "Deleted ".$affected." email items after sending.";
	      elseif($affected==1) $message="Deleted one email item after sending.";
	      
	    }
	  }
	}
	inputFeedback("Email sent",$message);
} else {
  $error_message = "Unknown reason. Please contact websupport@camdram.net.";
  if($to=="")
    $error_message = "This is probably because the \"to\" field is blank";
  inputFeedback("Email sending failed",$error_message);
}

	foreach ($_POST as $postkey=>$postvalue) $_POST[$postkey]=str_replace("\'","'",$postvalue);
		if (isset($_POST['subject'])) {
			$subject=$_POST['subject'];
			$message=str_replace(chr(13),"",$_POST['message']);
			$bcc=$_POST['bcc'];
		}
		else {
			if (isset($_GET['emailid']) && canEditBuilderEmail($_GET[emailid])) {
			        
				$query_email="SELECT * FROM acts_email WHERE acts_email.emailid=$emailid";
				$q=sqlQuery($query_email,$adcdb) or die(mysql_error());
				if ($res=mysql_fetch_assoc($q)) {
				  $subject=$res['title'];
				  $defaultfrom = $res[from];
				  $defaultlist = $res[listid];
				  $deleteonsend = $res[deleteonsend];
				}
				$query_items="SELECT id,acts_email_items.creatorid,text FROM acts_email_items,acts_email WHERE acts_email.emailid=acts_email_items.emailid AND acts_email_items.emailid=$emailid ORDER BY orderid";
				$q=sqlQuery($query_items,$adcdb) or die(mysql_error());
				$firstitem=true;
				while ($row_item=mysql_fetch_assoc($q)) {
					if($firstitem==false) $message.="***************************************************\n";
					
					if($row_item[creatorid]!=0) {
					  $creator_info = getUserRow($row_item[creatorid]);
					  $message.=strtoupper("Submitted by ".$creator_info[name]." (".$creator_info[email]."):\n\n");
					}
					
					if($row_item['text']=="~~~TABLE_OF_CONTENTS~~~") {
						$message.=htmlspecialchars(toc($emailid))."\n\n";
					}
					else {
						$message.=htmlspecialchars($row_item['text'])."\n\n";
					}
					$firstitem=false;
				}
				$message=wordwrap($message,76);
				$pattern= "/([a-z0-9\-\_\.\+]+@[a-z0-9\.\-]+[a-z0-9])|([a-z][a-z]+[0-9]+)/i";
				preg_match_all($pattern,$message,$matches);
				$first=1;
				
				foreach($matches as $key1=>$matcharr) {
				  foreach($matcharr as $key2=>$match)
				    $matches[$key1][$key2]=strtolower($match);
				}
				$matches[0]=array_unique($matches[0]);
				foreach ($matches[0] as $match) {
					if ($first==1) $first=0;
					else $bcc.=", ";
					global $site_support_email;
					if (strrpos($match,"@")==false) {
						$bcc.="$match@cam.ac.uk";
					}
					else if ($match!=$site_support_email) {
						$bcc.="$match";
					}
				}
			}
		}
?>
<form action="<?=linkTo(161,array("CLEARALL"=>"CLEARALL"))?>" method="post">
<input type="hidden" value="<?php echo allowsubmission();?>" name=submitid>
<?php if (isset($emailid)) { 
  ?><input type="hidden" value="<?=$emailid?>" name="emailid">
<?php } ?>
<table border=0>
<tr valign=top>
	<td>From:</td>
	<td><?php fromField(isset($_POST[from])?$_POST[from]:$defaultfrom,$uid); ?></td>
</tr>
<tr valign=top>
	<td>To:</td>
	<td><input type="text" name="to" value="<?php echo $_POST['to'];?>" SIZE=50>
</tr>
<tr valign=top>
	<td>CC:</td>
	<td><input type="text" name="cc" value="<?php echo $_POST['cc'];?>" SIZE=50>
</tr>
<tr valign=top>
	<td>BCC:</td>
	<td><input type="text" name="bcc" value="<?php echo $bcc;?>" SIZE=50>
</tr>
<tr valign=top>
<?php
$lists=getlists();
$targetid = ((isset($_POST['list']))?$_POST['list']:$defaultlist);
if (count($lists)>0) { 
  ?>
	<td>Mailing List:</td>
	<td><select name="list">
	<?php
	echo "<option value=\"-1\"";
	if ($targetid == -1) echo " selected";
	echo ">(None)</option>";
	foreach ($lists as $id=>$list) {
		echo "<option value=\"$id\"";
		if ($targetid == $id) echo " selected";
		echo ">$list</option>";
	}
	?></select></td>
	
</tr>
<?php }?>
<tr valign=top>
	<td>Subject:</td>
	<td><input type="text" name="subject" value="<?php echo $subject;?>" SIZE=50>
</tr>
<tr valign=top>
	<td>Message:</td>
	<td><textarea name=message rows=15 cols=80 wrap=virtual><?php echo $message;?></textarea></td>
</tr>
<tr valign=top>
	<td>Signature</td>
	<td><table border=0><tr><td><input type="radio" name="sig" value="" checked="checked"></td><td>(None)</td></tr>
<?php
	$query="SELECT * FROM acts_email_sigs WHERE uid=".$_SESSION['userid'];
	$result=sqlQuery($query,$adcdb) or die(mysql_error());
	while ($sigrow=mysql_fetch_assoc($result)) {
		$sig=$sigrow['sig'];
		echo "<tr><td><input type=\"radio\" name=\"sig\" value=\"$sig\"></td><td>",nl2br($sig),"</td></tr>\n";
	}
?>
	</table></td>
</tr>
<tr valign=top>
	<td colspan=2><input type="submit" value="Send">
<?php 

	if($deleteonsend>0) {
	  $delete = "SELECT * FROM acts_email_items WHERE acts_email_items.emailid='$emailid' AND acts_email_items.protect=0";
	  $q = sqlQuery($delete);
	  if($q>0) {
	    $affected = mysql_num_rows($q);
	    mysql_free_result($q);
	    $message = "";
	    if($affected>1) $message = $affected." email items will be deleted automatically after sending.";
	    elseif($affected==1) $message="One email item will be deleted automatically after sending.";
	    
	    echo " - <strong>".$message."</strong>";
	  }
	}
?></td>
</tr>
</table>
</form>
</p>
