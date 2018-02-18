<?php 
require_once("library/user.php");
require_once("library/editors.php");
require_once("library/mailinglists.php");
require_once("recaptcha/recaptchalib.php");
$showform=true;
$mark=0;
global $site_support_email;

if(isset($_POST['Submit']))
{
	global $recaptcha_private_key;

	$resp = recaptcha_check_answer ($recaptcha_private_key,
					$_SERVER["REMOTE_ADDR"],
					$_POST["recaptcha_challenge_field"],
					$_POST["recaptcha_response_field"]);


	$name=ereg_replace("[^[:alnum:] '-]", "", $_POST['name']);
	$email=EmailToUser($_POST['email']);
	$l=strstr($name," ");
	if($l==false || strlen($l)<1)
	{
		inputFeedback("Please provide your name.");
		$mark=2;
	}
	elseif(!$resp->is_valid)
	{
		inputFeedback("The security validation was not completed correctly");
		$mark=3;
	}
	else {
		$query_check = "SELECT * FROM acts_users WHERE email=\"$email\"";
		$check = sqlquery($query_check, $adcdb) or die(mysql_error());
		$rows_check = mysql_num_rows($check);
		mysql_free_result($check);
		if($rows_check>0 || strlen($email)<4) {
			inputFeedback("Email address already registered or invalid.");
			$mark=1;
		} else {
			$showform=false;
			if(isset($_POST['publishemail'])) $publishemail=1; else $publishemail=0;
			if(isset($_POST['forumnotify'])) $forumnotify=1; else $forumnotify=0;
			if(isset($_POST['dbphone'])) $dbphone=1; else $dbphone=0;
			if(isset($_POST['dbemail'])) $dbemail=1; else $dbemail=0;
			
			$hearabout = $_POST['hearabout'];
			$occupation = $_POST['occupation'];
			$graduation = $_POST['graduation'];
			$phone = $_POST['phone'];
			$query_create = "INSERT INTO acts_users (name,email,tel,registered,login,publishemail,forumnotify,hearabout,graduation,occupation,dbphone,dbemail) VALUES (\"$name\",\"$email\",\"$phone\",NOW(),\"0000-00-00\",$publishemail,$forumnotify,'$hearabout', '$graduation','$occupation',$dbphone,$dbemail)";
			$r = sqlquery($query_create, $adcdb) or die(mysql_error());
			$newid=mysql_insert_id($adcdb);
			$query="SELECT * FROM acts_pendingaccess WHERE email='$email'";
			$res=sqlQuery($query) or die(mysql_error());
			while($row=mysql_fetch_assoc($res)) {
				$query="INSERT INTO acts_access(uid,rid,type,issuerid,creationdate) VALUES('$newid','$row[rid]','$row[type]','$row[issuerid]','$row[creationdate]')";
				sqlquery($query,$adcdb) or die(mysql_error());
				$query="DELETE FROM acts_pendingaccess WHERE id=$row[id]";
				sqlquery($query,$adcdb) or die(mysql_error());
				
			}
			$lists=getpubliclistsdescrip();
			foreach ($lists as $listid=>$descrip) {
				if(isset($_POST["list$listid"])) {
					$query="INSERT INTO acts_mailinglists_members(listid,uid) VALUES ('$listid','".$newid."')";
					sqlquery($query,$adcdb) or die(mysql_error());
				}
			}
			$lists=getpubliclists
			?>
 <p><strong>Thank you for registering, <?=$name ?>!</strong></p>
<p>We are despatching access details to <?=allocPassword($newid); ?>.</p>
<p><?php if(isset($_GET['retid'])) makeLink($_GET['retid'],"close this window",array("retid"=>"NOSET"));
			else echo('<a href="#" onclick=\'self.close();\'>close this window</a>'); ?></p><?php
			
			
		}
	}
}
else { ?>
<p><strong>Register Now</strong></p>
   <p>Please note you can only register <i>as an individual</i>. If you
   are interested in affiliating your society with ACTS, please contact
   <a href="mailto:websupport@camdram.net">websupport@camdram.net</a>
   in the first instance.</p>
<?php }
if($showform) {?>
<form name="form1" method="post" action="<?=thisPage()?>">
<table class="editor">
  <tr>
    <th>Your Full Name</th>
    <td><input name="name" type="text" id="name" value="<?=$_POST['name'] ?>" size="30" maxlength="60"></td>
  </tr>
 <?php if($mark==2) { ?>
 <tr bgcolor="#CCCCCC">
  <td bgcolor="#DDDDDD">&nbsp;</td>
    <td bgcolor="#DDDDDD" class="smallgrey"><p>Please provide us with your name</p></td></tr>
<?php } ?>
  <tr bgcolor="#CCCCCC">
    <th>Email Address</th>
    <td bgcolor="#DDDDDD"><input name="email" type="text" id="email" value="<?=$_POST['email'] ?>" size="30" maxlength="60">
        <?php if($mark==2) { ?>
        <?php } ?>
      </td>
  </tr>
 <tr bgcolor="#CCCCCC">
    <td bgcolor="#DDDDDD">&nbsp;</td>
	<?php if($mark==1) { ?>
    <td bgcolor="#DDDDDD" class="smallgrey"><p>This email address is already registered with us.
        If you are experiencing difficulties or have forgotten your password,
        please contact <a href="mailto:<?=$site_support_email?>"><?=$site_support_email?></a>.
      </p>
        <?php } else {?>
    <td bgcolor="#DDDDDD" class="smallgrey"><p>Please note you must register with a valid email
        address, or we will not be able to send your password. You may omit @cam.ac.uk
        if you wish to register such an address.</p><?php } ?>
    </td>
  </tr>

    <tr>
      <th>Telephone Number</th>
      <td><p>Providing us with a telephone number will help us contact
	you if we have urgent queries about information you have entered.
	You can also choose to publish this number in the contact database
	(see below). If you do not want us to have your phone number, you may
	leave this field blank.</p><input type="text" name="tel" value="<?=$_POST[tel]?>">
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
if(isset($_POST[dbemail])) echo "checked" ?>>
Tick to <strong>publish your email address</strong> in our searchable
contact database.</p>
<p><input name="dbphone" type="checkbox" id="dbphone" value="yes" <?php
if(isset($_POST[dbpohne])) echo "checked" ?>>
Tick to <strong>publish your phone number</strong> in our searchable
contact database.</p>
<p><input name="publishemail" type="checkbox" id="publishemail" value="yes" <?php if($currentdetails[publishemail]==1) echo "checked"; ?>>
	    <strong>Tick to allow <?=getConfig('site_name');?> to publish your email address on the site with content that you administrate</strong>, for example to allow people to contact you about one of your shows.
	    Please note that this only applies to content that you publish or administrate on <?=getConfig('site_name');?>.
	    </p>
	  </td>
</tr>
<?php

			     // FORUMS DECOMISSIONED...
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
<?php
			     */
?>
<tr>
          <th>Mailing Lists </th>
          <td>
      		<?php $lists=getpubliclistsdescrip();
		$defaultlists=getdefaultlists();
		foreach ($lists as $listid=>$listdescrip) {
			echo "<p><input name=\"list$listid\" value=\"yes\" type=\"checkbox\"";
			if (isset($defaultlists[$listid])) echo "checked";
			echo "> Tick to subscribe to the $listdescrip</p>";
		} ?>
            <p>Please note if you become an administrator for a society, you will automatically be added to our administrator mailing lists. </p></td>
    </tr>
	<tr>
	  <th>Optional Information</th>
	  <td valign="top" bgcolor="#DDDDDD"><p>Providing us with the following information will help us understand who is using our service and improve it in the future. </p>
      <p><strong>Where did you hear about camdram.net?</strong><br>
        <select name="hearabout" id="hearabout">
		  <option value=""> - </option>
		  <option value="ACTS Publicity">ACTS Event/Brochure</option>
          <option value="From a friend / word of mouth">From a friend / word of mouth</option>
          <option value="Student press">Student press</option>
          <option value="Another theatre website">Another website</option>
	  <option value="Google etc">Google/other search engine</option>
          <option value="Other">Other</option>
        </select> 
      </p>
      <p><strong>Are you a current University student or member of staff? </strong><br>
        <select name="occupation" id="occupation">
		  <option value="" selected> - </option>
          <option value="Yes, Cambridge University">Yes, Cambridge University</option>
          <option value="Yes, APU">Yes, APU</option>
          <option value="Yes, another University">Yes, another University</option>
          <option value="No">No</option>
          </select>
      </p>
      <p><strong>If applicable, what year did you graduate or do you expect to graduate? </strong><br>
        <input name="graduation" type="text" id="graduation" size="4" maxlength="4">
</p>
</td>
</tr>
<tr>
	<th>Security Validation</th>
	<td>
	<?php
		global $recaptcha_public_key;
		echo recaptcha_get_html($recaptcha_public_key);
	?>
	</td>
</tr>
</table>
<p align="right"><input type="submit" name="Submit" value="Submit">
  </p>
<script type="text/javascript">
//<!--
document.forms[0].name.focus();
//-->
    </script>
</form>
<p>&nbsp;</p>
<?php } ?>
