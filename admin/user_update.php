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

require_once("library/editors.php");
require_once("library/mailinglists.php");
$displayform=true;

if(isset($_POST['password']))
{
	if(tryAuth($_SESSION['user'],md5($_POST['password']),1)>0)
	{
	  $syntax = "UPDATE acts_users SET tel='$_POST[tel]' WHERE email='$_SESSION[user]' LIMIT 1";
	  $s = sqlQuery($syntax,$adcdb) or die(sqlEr());
		$pc=$_POST['passwordchange'];
		if(strlen($pc)>0)
		{
			if($pc==$_POST['passwordchange2'] && strlen($pc)>5)
			{
				actionlog("password change");
				$syntax = "UPDATE acts_users SET pass='".md5($pc)."' WHERE email='".$_SESSION['user']."' LIMIT 1";
				$s=sqlQuery($syntax,$adcdb)  or die(sqlEr());
				echo("<p><b>Password updated.</b></p>");
				if(isset($_GET['retid']))
				{
					echo("<p>Your password has been updated. Click ");
					makeLink($_GET['retid'],"here",array(),true);
					echo(" to continue.</p>");
				}
				$displayform=false;
			} else echo("<p><b>New passwords do not match or password is too short</b> - password is unchanged. Please try again.</p>");
		} else {
			$displayform=false;
			echo("<p><b>Your password has not been updated</b> as you did not specify a new password.</p>");
		}
		$tick_array = array("publishemail","forumnotify","dbemail","dbphone");
		foreach($tick_array AS $value)
			if(isset($_POST[$value])) @$$value=1; else @$$value=0;
		$upd = "UPDATE acts_users SET publishemail=$publishemail,forumnotify=$forumnotify,dbemail=$dbemail,dbphone=$dbphone WHERE email='".$_SESSION['user']."' LIMIT 1";
		$query = sqlQuery($upd,$adcdb) or die(sqlEr());
		echo("<p><b>Contact details & options updated.</b></p>");
		$lists=getpubliclistsdescrip();
		$userlists=getuserlists($_SESSION['userid']);
		$changed=1;
		foreach ($lists as $listid=>$descrip) {
			if(isset($_POST["list$listid"])) {
				if (!isset($userlists[$listid])) {
					$query="INSERT INTO acts_mailinglists_members(listid,uid) VALUES ('$listid','".$_SESSION['userid']."')";
					sqlQuery($query,$adcdb) or die(mysql_error());
					$changed=1;
				}
			}
			else {
				if (isset($userlists[$listid])) {
					$query="DELETE FROM acts_mailinglists_members WHERE listid=$listid and uid=".$_SESSION['userid'];
					sqlQuery($query,$adcdb) or die(mysql_error());
					$changed=1;
				}
			}
		}
		if ($changed==1) echo("<p><b>Mailing list options updated.</b></p>");
		if ($mode==2) echo('<p align="right"><a href="#" onclick=\'self.close();\'>close this window</a></p>');
	} else inputFeedback("Your current password was not entered correctly","No changes have been made. Please try again.");
}
$currentdetails = getUserRow($_SESSION['user']);
if($displayform)
{
?>
Updating Details for <?php echo $currentdetails['email'] ?>
<form name="form1" method="post" action="<?php thisPage(); ?>">
  <h3> 1. Please confirm your current password </h3>
  <p>For security purposes, you need to confirm your current password before continuing. </p>
  <table class="editor">
    <tr>
      <th>Current Password</th>
      <td><input name="password" type="password" id="password"></td>
    </tr>
  </table>
  <h3>2. Change your password if required</h3>
  <p>If you wish to update your password, type the new password in both fields below. If you don't wish to update your password, you can <strong>leave the fields blank and it will remain the same</strong>.</p> 
  <table class = "editor">
    <tr>
      <th>New Password</th>
      <td><input type="password" name="passwordchange">
      </td>
    </tr>
    <tr>
      <th>Confirm New Password</th>
      <td><input type="password" name="passwordchange2">
      </td>
    </tr>
  </table>
<p>Please note passwords are case sensitive (that is, capital letters are separate to lower case letters).</p>
<h3>3. Your personal details and how we may use them</h3>
<table class="editor">
    <tr>
      <th>Telephone Number</th>
      <td><p>You can update the telephone number we hold on record by typing it below. Please note that we never distribute your personal information to third parties, except where you specifically specify (below) that you wish to be on our searchable contact database. Otherwise, your telephone number is used only for us to contact you personally in the event of any urgent query.</p><input type="text" name="tel" value="<?=$currentdetails[tel]?>">
      </td>
    </tr>
<tr>
  <th>Contact Database</th>
<td><p>Your contact details can be made available to any registered
user searching
for you by name. Note that because people must seach by full name, we
do not anticipate any details published here will be misused in bulk;
nevertheless, if you choose to publish your details, camdram.net cannot
take responsibility for anyone misusing them.</p>
<p><input name="dbemail" type="checkbox" id="dbemail" value="yes" <?php
if($currentdetails[dbemail]==1) echo "checked" ?>>
Tick to <strong>publish your email address</strong> in our searchable
contact database.</p>
<p><input name="dbphone" type="checkbox" id="dbphone" value="yes" <?php
if($currentdetails[dbphone]==1) echo "checked" ?>>
Tick to <strong>publish your phone number</strong> in our searchable
contact database.</p>
</td>
</tr>
<tr>
	  <th>Publish e-mail address</th>
	  <td><p><input name="publishemail" type="checkbox" id="publishemail" value="yes" <?php if($currentdetails[publishemail]==1) echo "checked"; ?>>
	    <strong>Tick to allow <?=getConfig('site_name');?> to publish your email address on the site with content that you administrate</strong>, for example to allow people to contact you about one of your shows.
	    Please note that this only applies to content that you publish or administrate on <?=getConfig('site_name');?>.
	    </p>
	  </td>
</tr>
<?php
// FORUMS DISABLED
/*
<tr>
      <th>Forums</th>
      <td><p><input name="publishemail" type="checkbox" id="publishemail" value="yes" <?php if($currentdetails[publishemail]==1) echo "checked"; ?>>
        <strong>Tick to allow <?=getConfig('site_name');?> to publish your email address on the site when you make a post to the forums</strong>. Please note this will allow people to reply to you personally, but could also lead to your address being "harvested" by spam senders. We cannot accept responsibility for spam received as a result of publishing your address on the web.</p>
	<p><input name="forumnotify" type="checkbox" id="forumnotify" value="yes" <?php if($currentdetails[forumnotify]==1) echo "checked";?>>
	 Tick to receive an email notification when a reply is posted to one of your forum messages.
	 </p>
	</td>
    </tr> 
*/
?>
 </table>
  <h3>4. Your email subscriptions</h3>
  <table class="editor">
    <?php if($mark==2) { ?>
    <?php } ?>
    <tr>
      <th valign="top">Mailing Lists </th>
      <td><p><?php
      		$lists=getpubliclistsdescrip();
		$userlists=getuserlists($_SESSION['userid']);
		foreach ($lists as $listid=>$listdescrip) {
			echo "<p><input name=\"list$listid\" value=\"yes\" type=\"checkbox\"";
			if (isset($userlists[$listid])) echo "checked";
			echo "> Tick to subscribe to the $listdescrip</p>";
		}
	?>
        <p>Please note if you become an administrator for a society, you will automatically be added to our administrator mailing lists. </p></td>
    </tr>
    
  </table>
  <p>&nbsp;</p>
  <p>
    <input type="submit" name="Submit" value="Submit">
</p>
</form>
<?php
}?>
