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

require_once("library/user.php");
require_once("library/table.php");
require_once("library/editors.php");
$uid=$_GET['uid'];
if(isset($_POST['tel']))
{
	$q = "UPDATE acts_users SET tel='$_POST[tel]', email='$_POST[email]' WHERE id=$uid LIMIT 1";
	$r = sqlQuery($q) or die(sqlEr());
	$updated="user email/telephone number";
}
$userrow = getUserRow($uid);
?>
<h3>Access Control for User <i><?=$userrow[email]?></i> (<?php echo $userrow[name]; if($userrow[tel]!="") echo " - $userrow[tel]"; ?>)</h3><?php
echo "Registered ".dateFormat(strtotime($userrow[registered]));
echo "<br/>Last login ".dateFormat(strtotime($userrow[login]));
echo "<br/>";
if(isset($updated)) inputFeedback("Updated $updated");
makeLink(99,"View Activity",array("logmode"=>"1","search"=>($userrow[email].":")));
if(isset($_GET['refuid']))
{
	$refuid=$_GET['refuid'];
	?><p align="right">back to <a href="<?=thisPage(array("uid"=>$refuid,"refuid"=>"NOSET"))?>"><?=userFromID($refuid)?></a></p><?php
}

if(isset($_POST['Submit']))
{
	if(createToken($uid,$_POST['type'],$_POST['rid']))
		echo("<p><strong>Created token successfully.</strong></p>");
	else
		echo("<p><strong>Unable to create this token. You may not have sufficient access rights.</strong></p>");
	
}
	
if(isset($_GET['revid']))
{
	if(revokeToken($_GET['revid']))
		echo("<p><strong>Revoked Token ".$_GET['revid']."</strong></p>");
	else
		echo("<p><strong>Unable to revoke token ".$_GET['revid'].". You may not have sufficient access rights.</strong></p>");
	unset($_GET['revid']);
}

if(isset($_GET['delid']))
{
	if(deleteToken($_GET['delid']))
		echo("<p><strong>Deleted Token ".$_GET['delid']."</strong></p>");
	else
		echo("<p><strong>Unable to delete token ".$_GET['delid'].". You may still be able to revoke (invalidate without deleting) the token.</strong></p>");
	unset($_GET['delid']);
}
?><p>This user owns the following security tokens:</p>
<?php
$query_tokens = "SELECT * FROM acts_access WHERE uid=$uid ORDER BY revokeid, type, rid";
$tok = sqlQuery($query_tokens,$adcdb) or die(mysql_error());?>

<?php
function tokenLinks($row)
{
	if($row['type']=='show')
	{
		echo "<br />[view: ";
		makeLink(104,'show',array('showid'=>$row['rid']),true);
		echo " | ";
		makeLink(111,'owners',array('showid'=>$row['rid'],'mode'=>0),true);
		echo "]";
		
	}
	if($row['type']=='society')
	{	
		echo "<br />[view: ";
		makeLink(116,'society',array('socid'=>$row['rid']),true);
		echo " | ";
		makeLink(111,'owners',array('socid'=>$row['rid'],'mode'=>1),true);
		echo "]";
		
	}
}
maketable($tok,array(
	"Token ID"=>"id",
	"User ID"=>"uid",
	"Type"=>"type",
	"Resource ID"=>"rid",
	"Interpretation"=>"NONE",
	"Granted"=>"NONE",
	"Revoked"=>"NONE"
),array(
	"Interpretation"=>'echo describeToken($row); tokenLinks($row);',
	"Granted"=>'
		if($row[\'issuerid\']>0)
		{
			echo ("<a href=\"".thisPage(array("uid"=>$row[\'issuerid\'],"refuid"=>$row[\'uid\']))."\">".userFromId($row[\'issuerid\'])."</a>" );
		} else {
			echo("System");
		}
		echo (" on ".date("dS M Y",strtotime($row[\'creationdate\'])));',
	"Revoked"=>'
		if($row[\'revokeid\']>0)
		{
			echo ("<a href=\"".thisPage(array("uid"=>$row[\'revokeid\'],"refuid"=>$row[\'uid\']))."\">".userFromId($row[\'revokeid\'])."</a>" );
			echo (" on ".date("dS M Y",strtotime($row[\'revokedate\']))); 
		} else {
			echo("Active");
		}'
),'
    echo "<a href=\"".thisPage(array("revid"=>$row[\'id\']))."\">revoke</a> | <a href=\"".thisPage(array("delid"=>$row[\'id\']))."\">delete</a>";
');
?>
<p><?php makeLink($_GET['retid'],"Refresh User Manager",array()); ?></p>

<h3>Manual Token Creation</h3>

<table class="Editor">
<form name="form1" method="post" action="<?=thisPage()?>">
<tr><th>Type</th><td>
  <select name="type" id="type">
    <option value="show">show</option>
    <option value="society">society</option>
    <option value="email">email list</option>
    <option value="knowledgebasesubpage">knowlegde base subpage</option>
  </select></td></tr>
<tr><th>
  Resource ID</th><td>
  <input name="rid" type="text" id="rid">
  <input name="Submit" type="submit" id="Submit" value="Create">
</td></tr>
</form>
</table>
<br/>
<table class="Editor">
<form name="form2" method="post" action="<?=thisPage()?>">
<tr><th>Or: </th>
<td><input name="type" type="hidden" id="type" value="security">
<select name="rid" id="rid">
<option value="-3">Content Administrator</option>
<option value="-2">Admin Except Security</option>
<option value="-1">Full Admin (access to all)</option>
</select><input name="Submit" type="submit" id="Submit" value="Create"></td>
</form>
</table>



<h3>Personal Details</h3>
<form action="<?=thisPage()?>" method="post" name="telupdate" id="telupdate">
<table class="editor">
<tr><th>Tel</th>
<td>  <input name="type" type="hidden" id="type" value="security">
  <input name="tel" type="text" id="tel" value="<?=$userrow[tel]?>" size="30" maxlength="60"></td></tr>
<tr><th>
  Email</th>
<td>  <input name="email" type="text" id="email" value="<?=$userrow[email]?>" size="30" maxlength="60"></td></tr>

<tr><th /><td>
  <input name="Edit" type="submit" id="Edit" value="Edit">
</td></tr></table>
</form>

