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

global $site_support_email; ?>
<h3>Forgotten your password?</h3>
<? if (isset($_GET['uid'])) {
	$uid=$_GET['uid'];
	if (!is_numeric($uid))
		die();
	$code=md5($_GET['reset']);
	$q="SELECT * FROM acts_users WHERE resetcode != '' AND resetcode = '$code' AND id = '$uid';";
	$result=sqlQuery($q) or die(sqlEr());
	if (mysql_num_rows($result)==0) {
		unset($_GET['uid']);
		unset($_GET['reset']);
	}
	else {
		allocPassword($uid);
		$q="UPDATE acts_users SET resetcode='' WHERE id='$uid';";
		sqlQuery($q) or die(sqlEr());
		echo "Your password has been reset. Please check your email for your new password";
	}
}
?>
<? if (isset($_POST['email']) && checkSubmission()) {
	$user=userToID($_POST['email']);
	if ($user==0)
	{
		$error="<p>User does not exist</p>";
		unset($_POST['email']);
	}
	else 
	{
		$email=getUserEmail($user);
		$code=rand();
		$hash=md5($code);
		$q="UPDATE acts_users SET resetcode='$hash' WHERE id='$user'";
		sqlQuery($q) or die(sqlEr());
		global $currentbase;
		$url="$currentbase/new_password?uid=$user&reset=$code";
		$subject="Password reset on ".getConfig('site_name');
		$text="You have requested a password reset on ".getConfig('site_name')." In order to complete this visit\n\n$url\n\nIf you did not request this password reset, you can ignore this email\n\nIf you experience any problems, please contact ".$site_support_email." and we will attempt to solve your problem as fast as possible.";
		mailTo($email,$subject,$text,"","","","\"".getConfig('site_name')."\" <".$site_support_email.">");
		actionlog("Password reset request for user $user");
		echo "<p>An email has been sent to you. Please follow the instructions in there to complete the reset operation</p>";
	}
}
?>
<? if (!isset($_POST['email']) && !isset($_GET['uid'])) { ?>
<?=$error?>
<p>Your password was sent to you in an email when you signed up, but you might have changed it since then.</p>
<p>If you can't remember your password, enter your email address below, and we'll send you instructions of how to reset your password</p>

<form action=<?=thisPage()?> method="post">
<p>Email address/CRSID: <input type="text" name="email"></p>
<p><input type="submit" value="Reset Password"><input type="hidden" name="submitid" value="<?=allowSubmission()?>"></p>
</form>
<?}?>
