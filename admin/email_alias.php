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
require_once("library/user.php");
global $adcdb,$site_support_email;
if (checkSubmission()) {
	if (isset($_POST['email'])) {
		$p=RandomName(8);
		$pc=md5($p);
		$email=$_POST['email'];
		$name=$_POST['name'];
		$fullemail=($name=="")?$email:"\"$name\" <$email>";
		$message="You have added the address $fullemail to your list of email aliases on ".getConfig('site_name')." In order to complete this you must authorize it.\n\n Your authorization code is $p\n\nIf you did not add this address, you can ignore this email.\n\nIf you have any problems please email the ".getConfig('site_name')." support team on ".$site_support_email;
		if (mailTo($fullemail,"Email alias on ".getConfig('site_name'),$message,"","","",getConfig('site_name')." <".$site_support_email.">")) {
			$_SESSION['emailauthcode']=$pc;
			$_SESSION['emailauth']=$email;
			$_SESSION['emailauthname']=$name;
?><p>An Email has been sent to <?php echo $email;?> with the authorization code, which you must enter below</p>
<form method="post" action="<?=linkTo(162,array("CLEARALL"=>"CLEARALL"))?>">
<input type="hidden" name="submitid" VALUE="<?php
echo (allowSubmission()); ?>">
<p>Authorization Code: <input type="text" name="authcode"></p>
<p><input type=submit value="Authorize"></p>
</form>
<?php
		}
	}
	if (isset($_POST['authcode'])) {
		$authcode=$_POST['authcode'];
		if (md5($authcode)==$_SESSION['emailauthcode']) {
			$name=$_SESSION['emailauthname'];
			$email=$_SESSION['emailauth'];
			$uid=$_SESSION['userid'];
			unset($_SESSION['emailauthcode']);
			unset($_SESSION['emailauthname']);
			unset($_SESSION['emailauth']);
			$query_insert="INSERT INTO acts_email_aliases (`uid`,`name`,`email`) VALUES ($uid,'$name','$email')";
			sqlQuery($query_insert,$adcdb) or die(mysql_error());
			echo "<p>Authorization Successful</p>";
		}
		else {
		?>
<p>Authorization Code Incorrect. Please try again</p>
<form method="post" action="<?=linkTo(162,array("CLEARALL"=>"CLEARALL"))?>">
<input type="hidden" name="submitid" VALUE="<?php
echo (allowSubmission()); ?>">
<p>Authorization Code: <input type="text" name="authcode"></p>
<p><input type=submit value="Authorize"></p>
</form>
		<?php
		}
	}
}
echo "<p>";
makeLink(80,"< Back to Email Selector",array("CLEARALL"=>"CLEARALL"));
echo "</p>";
?>
