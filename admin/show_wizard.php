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

require_once('library/showfuns.php');
require_once('library/editors.php');
require_once('library/user.php');
require_once('library/emailGen.php');
require_once('library/show_authorize_email.php');

global $mail_alsomail, $site_support_email;
global $adcdb;
$page=$_GET['enteredpage'];
if (isset($_POST['enteredpage'])) $page=$_POST['enteredpage'];
if (!isset($_POST['enteredpage']) && !isset($_GET['enteredpage'])) $page=0;
$showid=$_GET['showid'];
if (isset($_POST['showid'])) $showid=$_POST['showid'];
$displaypage=0;
if ($page==0) {
	$key = md5(microtime());
	$query="INSERT INTO acts_shows (`title`,`entered`,`entryexpiry`) VALUES (\"$key\",0,CURDATE()+2)";
	$result=sqlquery($query,$adcdb) or die(mysql_error());
        $query = "SELECT id FROM acts_shows WHERE title = \"$key\"";
	$result = sqlquery($query,$adcdb) or die(mysqlerror());
	$res = mysql_fetch_assoc($result);
	$query = "UPDATE acts_shows SET title = '' WHERE title = \"$key\"";
	$showid=$res['id'];
	$displaypage=1;
}
if ($page==1) {
	if (checksubmission()) {
		$validate=1;
		$title=$_POST['title'];
		$author=$_POST['author'];
		$socid=(int) $_POST['socid'];
		$society=$_POST['society'];
		$venid=(int) $_POST['venid'];
		$venue=$_POST['venue'];
		if (!$venid) $venid = 'NULL';
		if (!$socid) $socid = 'NULL';
		if ($title=="") {
			$validate=0;
			$error=$error."You must enter a title for your show";
		}
		$displaypage=1.1;
		if ($validate==0) $displaypage=1;
		if ($validate==1) {
			$keywords=array("adc"=>"ADC Theatre","playroom"=>"Corpus Christi Playroom",
				"play room"=>"Corpus Christi Playroom","octagon"=>"St Chad's Octagon","octogon"=>"St Chad's Octagon",
				"new cellars"=>"Pembroke New Cellars","arts theatre"=>"Cambridge Arts Theatre", "fitzpat"=>"Fitzpatrick Hall, Queens' College");
			foreach($keywords as $key=>$value)
			{
				$key=addslashes($key);
				$value=addslashes($value);
				if(stristr($venue,$key) && $venue!=$value)
				{
					
					$warn.="<li>Venue name standardised to <i>".stripslashes($value)."</i></li>";
					$venue=$value;
				}
			}
			if ($warn!="") inputFeedback($warn);
			$query="UPDATE acts_shows SET title='$title',author='$author',socid=$socid,society='$society',venid=$venid,venue='$venue' WHERE id='$showid'";
			sqlQuery($query,$adcdb) or die (mysql_error());
            upgradeShowToV2($showid);
			showuniqueref(getshowrow($showid),true);
		}
	}
}
if ($page=="1.1") {
	$displaypage=1.1;
}
if ($page=="1.2") {
	$displaypage=2;
	$validate=1;
			if (!isset($_POST['nocleverness'])) {
				$query="SELECT MAX(enddate) AS ed,MIN(startdate) AS sd FROM acts_performances WHERE sid='$showid' GROUP BY sid";
				$r=sqlQuery($query,$adcdb) or die(sqlEr());
				if ($row=mysql_fetch_assoc($r)) {
					$outersd=date("Y/m/d",strtotime("-7 days",strtotime($row['sd'])));
					$outered=date("Y/m/d",strtotime("+7 days",strtotime($row['ed'])));
					$query = "SELECT acts_shows.*,MAX(acts_performances.enddate),MIN(acts_performances.startdate) FROM acts_shows,acts_performances WHERE startdate>='$outersd' AND enddate<='$outered' AND acts_performances.sid=acts_shows.id GROUP BY acts_shows.id";
					$r=sqlQuery($query,$adcdb) or die(sqlEr());
					while ($simshow=mysql_fetch_assoc($r)) {
						$perc=0;
						similar_text(strtolower($simshow['title']),strtolower($title),$perc);
						if ($perc>75) {
							$abort=true;
							$text.="<li>".makeLinkText(104,$simshow['title'],array("showid"=>$simshow['id']));
							if (!hasequivalenttoken('show',$simshow['id']) && !hasEquivalentToken('show',-1)) $text .= " [" . makeLinkText(201, "Request access", array("showid"=>$simshow['id'])) . "]";
							$text .= "</li>";
						}
					}
					mysql_free_result($r);
					if ($abort) {
						actionLog("Similar Show Detected");
						?><h3>Similar Show Detected</h3>
						<p>There seems to be one or more shows already in our database which
						match your description. Please check the following shows:</p>
						<ul><?=$text?></ul>
						<p>If none of these shows is the one you are trying to enter, click continue (below). Otherwise, please <em>do not create a duplicate show</em>. If you find you do not have editing access to a show you need to edit, please select <b>Request access</b> on the show you wish to edit.</p> <?php
						allowSubmission();
						continueButton();
						$validate=0;
						$displaypage=0;
					}
				}
				$query="SELECT * FROM acts_performances WHERE sid='$showid'";
				$r=sqlQuery($query,$adcdb) or die(sqler());
				if (!mysql_fetch_assoc($r)) {
					echo "<h3>No dates entered</h3>\n";
					echo "<p>You have not entered any dates for this show. If this is correct (ie you have not had dates confirmed yet), click continue (below) and <em>enter dates when this information becomes available</em>. You may be periodically reminded to do this by email\n";
					echo "Otherwise, please go back to the ";
					makeLink("show_wizard.php","Performance Editor",array("showid"=>$showid,"newshow"=>"true","enteredpage"=>"1.1"));
					echo " to add some dates\n";
					$validate=0;
					$displaypage=0;
					allowSubmission();
					continueButton();
				}
			}
			else {
				$query="SELECT * FROM acts_performances WHERE sid='$showid'";
				$r=sqlQuery($query,$adcdb) or die(sqler());
				if (!mysql_fetch_assoc($r)) actionLog("No performance overridden by user");
				else actionLog("Duplication detection overriden by user");
				$validate=1;
			}
		if ($validate==1) {
			$query="UPDATE acts_shows SET entered=1 WHERE id=$showid";
			actionLog("Created show $showid");
			sqlQuery($query,$adcdb) or die (sqler());
			creatorAccess('show',$showid);
			if(authorizeShow($showid)) 
				inputFeedback("Auto-authorized this show.","The show is now live");
			else {
				inputFeedback("This show is not authorized.","The show will not be visible on public portions of the website until it is authorized by an administrator. An email has been sent to notify the administrators that your show needs authorizing");
				SendEmailToRequestShowAuthorization($showid);
			}
            upgradeShowToV2($showid);
		}
}
if ($page==2) {
	if (checksubmission()) {
		$desc=$_POST['desc'];
		$prices=$_POST['prices'];
		$category=$_POST['category'];
		$desc=str_replace("\r","",$desc);
		if (isset($_POST['unwrap'])) {
			$desc=str_replace("\n\n","\r\r",$desc);
			$desc=str_replace("\n"," ",$desc);
			$desc=str_replace("\r","\n",$desc);
		}
	}
	$sql_query="UPDATE acts_shows SET prices='$prices', description='$desc',category='$category' WHERE id=$showid LIMIT 1";
	sqlquery($sql_query,$adcdb) or die(sqler());
	$displaypage=2.1;
}
if ($page=="2.1") {
	if (checksubmission()) {
		$userid=idFromUser(emailToUser($_POST['email']));
		if ($userid==-1) {
	                $pattern = "/([a-z0-9\-\_\.\+]+@[a-z0-9\.\-]+[a-z0-9])|([a-z][a-z]+[0-9]+)/i";
	                if ($_POST['email'] != "" && preg_match ($pattern, $_POST['email'])) {
				
				$username=emailToUser($_POST['email']);
				createPendingToken($username,'show',$showid);
			}
		}
		else {
			createToken($userid,'show',$showid);
		}
	}
	$displaypage=3;
}
if ($page==3) $displaypage=4;
if ($page==4) $displaypage=5;
if ($displaypage==1) {
?>
  <form action="<?php echo thisPage();?>" method="POST">
  <input type="hidden" name="submitid" value="<?php echo allowsubmission(); ?>">
  <input type="hidden" name="enteredpage" value="1">
  <input type="hidden" name="showid" value="<?php echo $showid;?>">
	<h2>Basic Show information</h2>
<?php	if (isset($error)) {
		inputFeedback($error);
	} ?>
  <p>You must provide us with the following details about your show:   </p>
  <table class="editor" width="100%">
      
    <tr bordercolor="#CCCCCC">
      <td width="200" valign="top"><strong>Title</strong></td>
      <td><input name="title" type="text" id="title3" value="<?php echo(stripslashes($_POST['title']));?>" size="30">
      </td>
			     </tr> 
    <tr bordercolor="#CCCCCC">
      <td><strong>Author</strong></td>
      <td><input name="author" type="text" id="author2" value="<?php echo(stripslashes($_POST['author']));?>" size="30">
        <br>
      (you may leave this blank only if there is no author or the author is unknown)</td>
    </tr>
    <tr bordercolor="#CCCCCC">
      <td height="24" valign="top"><strong>Society</strong></td>
      <td>
        <p> It is essential you pick the correct society, as the Committee
          of that society will be asked to authorize your show before it will
          become available to the public. If the society is not displayed in
          the drop down list, they do not have an account with us and your show
          will be passed to ACTS. <?php if(hasEquivalentToken('society',0)) echo("Please note that if you select a society for which you are an administrator, the show will automatically be authorized.");?>
<script language="JavaScript" type="text/JavaScript">
//<!--
function updateFormState(societies,societytext,autoselect) 
{
	if(societies.options[societies.selectedIndex].value==0)
	{
		societytext.disabled=false;
		if(autoselect) societytext.focus();
	} else {
		societytext.value="";
		societytext.disabled=true;
	}
}

//-->
</script>
              <br>
              <select name="socid" id="societies" onChange="updateFormState(societies,societytext,true); ")>
                <option value="-1"></option>
                <?php
	$query = "SELECT name,id FROM acts_societies WHERE type=0 ORDER BY name";
	$r=sqlQuery($query);
	if($r>0)
	{
		while($soc = mysql_fetch_assoc($r))
		{
			echo('<option value="'.$soc['id'].'"');
			if($soc['id']==$_POST['socid']) echo(" selected");
			echo ('>'.htmlspecialchars($soc['name'])."</option>");
		}
		mysql_free_result($r);
	}
	?>
                <option value="0" <?php if($_POST['socid']==0 && isset($_POST['socid'])) echo("selected");?>>Other (please specify)
              </select>		  
              <br>
        Select from list above, or choose &quot;other&quot; and specify below:<br>
        <input name="society" type="text" id="societytext" value="<?php echo($_POST['society']);?>" size="30">
      </td>
    </tr>
    <tr bordercolor="#CCCCCC">
      <td valign="top"><strong>Main Venue</strong><br/>The venue where all or the majority of performances will be held.</td>
      <td><select name="venid" id="venid" onChange="updateFormState(venid,venue,true);")>
        <option value="-1"></option>
        <?php
	$query = "SELECT name,id FROM acts_societies WHERE type=1 ORDER BY name";
	$r=sqlQuery($query);
	if($r>0)
	{
		while($soc = mysql_fetch_assoc($r))
		{
			echo('<option value="'.$soc['id'].'"');
			if($soc['id']==$_POST['venid']) echo(" selected");
			echo ('>'.htmlspecialchars($soc['name'])."</option>");
		}
		mysql_free_result($r);
	}
	?>
        <option value="0" <?php if($_POST['venid']==0 && isset($_POST['venid'])) echo("selected");?>>Other
        (please specify)
      </select>
        <br>
Select from list above, or choose &quot;other&quot; and specify below:<br>
<input name="venue" type="text" id="venue" value="<?php echo($_POST['venue']);?>" size="30">
        <br>
        (only leave this field blank if a venue has not yet been confirmed and
      you are opening applications/auditions)</td>
    </tr>
  </table>
<p> <input type="submit" value="Next &gt;"></p>
<script language="JavaScript" type="text/JavaScript">
//<!--
updateFormState(document.forms[0].societies,document.forms[0].societytext,false);
updateFormState(document.forms[0].venid,document.forms[0].venue,false);
//-->
</script>	
 </form>
 <?php
}
if ($displaypage==1.1) {
	$_GET['enteredpage']=1.1;
	$_GET['showid']=$showid;
	$_GET['newshow']=true;
	echo "<h2>Performance Information</h2>";
	loadPage(117);
	unset($_GET['newshow']);
	echo "<form method=\"POST\" action=\"".thispage(array("enteredpage"=>"1.2"))."\"><input type=\"submit\" value=\"Next &gt;\"></form>";
}	
if ($displaypage==2) {
?>
	<h2>Additional Show Information</h2>
  <p>You may also want to provide the following details:</p>
  <form action="<?php echo thisPage();?>" method="POST">
  <input type="hidden" name="submitid" value="<?php echo allowsubmission(); ?>">
  <input type="hidden" name="enteredpage" value="2">
  <input type="hidden" name="showid" value="<?php echo $showid;?>">
  <table width="100%" class="editor">

    <tr>
      <td width="200"><strong>Prices</strong></td>
      <td><input name="prices" type="text" id="prices" size="30">
      </td>
    </tr>
    <tr>
      <td width="200"><strong>Show Category</strong><br/>This is used to classify shows by some sites which use our content, e.g. cambridgeeye.com</td><td><?php genericSelect("acts_shows","category",'other', array('drama','comedy', 'musical', 'opera', 'dance', 'other')); ?></td></tr>
    <tr>
      <td><strong>Description</strong><br>
          <em>This field is
          <?php makeLink(107,"translated"); ?>
      .</em><br />Only the first paragraph will appear in emails created for auditions and technical position email lists</td>
      <td><textarea name="desc" cols="80" rows="10" wrap="VIRTUAL" id="textarea"><?php echo($row_show['description']);?></textarea>
  <p><input type=checkbox name="unwrap" value=1 checked> Strip Carriage Returns (useful when copying from emails)</p>
      </td>
    </tr>
    <tr>
      <td width="200"><strong>Photo</strong></td>
      <td>
		<?php makeLink(100,"upload photo",array("uploadtype"=>3,"showid"=>$showid));?>
	</td>
    </tr>
  </table>
  <p>
  <input type="submit" value="Next &gt;">
  <br>
  </p>
  </form>
<?php
}
if ($displaypage==2.1) {
	$showrow=getshowrow($showid);
	$socid=$showrow['socid'];
	if ($socid==0) $socid=-1;
	if (!hasequivalenttoken('society',$socid)) {
		$displaypage=3;
	}
	else {
	?>
	<h2>Add users to show</h2>
	<p>This show was auto-authorised. If there is someone (e.g. the producer) who should be given access to the show, please enter their email address or CRSID here: (leave blank if you do not want to add anyone)</p>
  	<form action="<?php echo thisPage();?>" method="POST">
  	<input type="hidden" name="submitid" value="<?php echo allowsubmission(); ?>">
	<input type="hidden" name="enteredpage" value="2.1">
  	<input type="hidden" name="showid" value="<?php echo $showid;?>">
	Email address: <input type="text" name="email" width=30>
	<input type="submit" value="Next &gt;">
	</form>
	<?
	}
}
if ($displaypage==3) {
?>
	<h2>Add auditions</h2>
	<p>Would you like to add auditions to your show?</p>
<?php
	makeLink(74,"Yes",array("showid"=>$showid,"referred"=>"true"));
	echo " | ";
	makeLink("show_wizard.php","No",array("showid"=>$showid,"enteredpage"=>3));
}
if ($displaypage==4) {
?>
	<h2>Add production team advert</h2>
	<p>Would you like to add an advert for production team positions to your show?</p>
<?php
	makeLink(151,"Yes",array("showid"=>$showid,"referred"=>"true"));
	echo " | ";
	makeLink("show_wizard.php","No",array("showid"=>$showid,"enteredpage"=>4));
}
if ($displaypage==5) {
?>
	<h2>Show Entry Complete</h2>
	<?php makeLink(79,"Return to show manager",array("CLEARALL"=>"CLEARALL"));
}
?>
