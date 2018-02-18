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
require_once("library/editors.php");
require_once('library/table.php');
global $adcdb,$site_support_email;
$allaccess=hasEquivalentToken('society',-1);
if(hasEquivalentToken('society',0))
{
if(isset($_POST['submit']))
{
	if(checkSubmission())
	{
		$editid=$_POST['editid'];
		if($editid==0)
		{
			if(hasEquivalentToken('security',-2))
			{
				$create = "INSERT INTO acts_societies VALUES()";
				$q = sqlQuery($create);
				$continue=false;
				if($q==0)
					inputFeedback();
				else 
				{				
					$editid=mysql_insert_id();
					actionLog("Created soc $editid");
					$continue=true;
					$update2 = "UPDATE acts_shows SET socid=".$editid.", society=NULL WHERE ";
					if($_POST['type']==0) $update2.="(society='".$_POST['name']."' OR society='".$_POST['shortname']."')";
					 else $update2.="(venue='".$_POST['name']."' OR venue='".$_POST['shortname']."')";
					$update2.=" AND socid=0";
					if(sqlQuery($update2)==0)
						inputFeedback("Warning: Could not gather existing shows under this society/venue name");
					else
						actionLog("Gather text references to $editid - affected rows ".mysql_affected_rows($adcdb));
				}
			} else {
				$continue=false;
				inputFeedback();
			}
		} else $continue=true;
					
		if($continue)
		{
			if(hasEquivalentToken('society',$editid))
			{
				$affiliate=0;
				if (isset($_POST['affiliate'])) $affiliate=1;
				if (hasEquivalentToken('security', -2))
					$update = "UPDATE acts_societies SET name='".$_POST['name']."', college=".(($_POST['college']=="")?"NULL":("'".$_POST['college']."'")).", shortname='".$_POST['shortname']."', description='".$_POST['description']."', type='".$_POST['type']."', affiliate=$affiliate";
				else
					$update = "UPDATE acts_societies SET name='".$_POST['name']."', college=".(($_POST['college']=="")?"NULL":("'".$_POST['college']."'")).", shortname='".$_POST['shortname']."', description='".$_POST['description']."', type='".$_POST['type']."'";
				if (isset ($_POST['deletelogo']))
					$update .= ", logourl = ''";
				$update .= " WHERE id=".$editid;
				$q = sqlQuery($update);
				$continue = false;
				if($q==0)
					inputFeedback();
				else
					inputFeedback("Updated!");
				
			} else inputFeedback();
		}
	}
}

if(isset($_GET['deleteid']) && isset($_GET['confirmed']))
{
	if($allaccess)
	{
	  
		$deleteid=$_GET['deleteid'];
		$query = "SELECT * FROM acts_societies WHERE id=".$deleteid;
		$r=sqlQuery($query,$adcdb) or die(sqlEr());
		$nres = mysql_num_rows($r);
		if($nres!=1)
			inputFeedback("Incorrect number of rows returned during final check before delete","Please report this error to ".$site_support_email.", with as many details as possible of events leading up to the error.");
		else {
			$row = mysql_fetch_assoc($r);
			mysql_free_result($r);
			$update = "UPDATE acts_shows SET venue='".addslashes($row['name'])."', venid=NULL WHERE venid=".$row['id'];
			$r = sqlQuery($update);
			if($r<=0) sqlEr();
			$z=mysql_affected_rows($adcdb);
			$update = "UPDATE acts_shows SET society='".addslashes($row['name'])."', socid=NULL WHERE socid=".$row['id'];
			$r = sqlQuery($update);
			if($r<=0) sqlEr();
			$z+=mysql_affected_rows($adcdb);
			$delete = "DELETE FROM acts_societies WHERE id=".$row['id']." LIMIT 1";
			$r=sqlQuery($delete);
			if($r<=0) sqlEr();
			unset($_GET['deleteid']);
			inputFeedback("Deleted!","Society/Venue ".$row['name']." has been deleted");
			actionLog("Deleted society ".$row['id']." (".$row['name'].") and converted ".$z." references to text");
		}
	} else
		inputFeedback("Delete not available");
}
allowSubmission();
if (isset($_GET['sortby'])) {
	$order=order();
}
else {
	$order=" ORDER BY college,name";
}
$query = "SELECT DISTINCT acts_societies.* FROM acts_societies RIGHT JOIN acts_access ON acts_access.`uid`=".$_SESSION['userid']." WHERE ".securityQuery('society',"acts_societies.`id`")." $order";
$table = sqlQuery($query) or die(mysql_error());
echo "<p>You have access to the following societies/venues:</p>";

maketable($table,
		array(
			"Society/Venue"=>"name",
			"Short name"=>"shortname",
			"College"=>"college",
			"Type"=>"type"
		),array(
			"College"=>'if ($row[\'college\']=="") echo "University"; else echo $row[\'college\'];',
			"Type"=>'if ($row["type"]==0) echo "Society"; else echo "Venue";'
			),'
  			echo "<a href=\"".thisPage(array("editid"=>$row[\'id\']))."\">edit</a>";
			echo " | ";
			makeLink(111,"access",array("socid"=>$row[\'id\'], "mode"=>1)); 
			echo(" | ");
			makeLink("applications_editor.php","applications",array("socid"=>$row[\'id\']));
			if(hasEquivalentToken(\'society\',-1)) {
			  	echo(" | ");
				makeLink(0,"delete",array("deleteid"=>$row[\'id\']),false,confirmer("delete " . $row[\'name\']));
  			}
			echo " | ";
			makeLink(116,"view",array("socid"=>$row[\'id\']));
		'
	);

	if(isset($_GET['editid'])) {
		$editid=$_GET['editid'];
		$query = "SELECT acts_societies.* FROM acts_societies RIGHT JOIN acts_access ON acts_access.`uid`=".$_SESSION['userid']." WHERE".securityQuery('society',"acts_societies.`id`")." AND acts_societies.id=$editid";
		$soc = sqlQuery($query) or die(mysql_error());
		$edit = mysql_fetch_assoc($soc);
		unset($_GET['editid']);
	}

if(hasEquivalentToken('security',-2) || isset($edit)) { 
if(isset($edit)) echo("<h2>Edit society/venue details</h2>"); else echo("<h2>Create society/venue</h2>");?>
<p>
<form name="form1" method="post" action="<?= thisPage() ?>">
<table border="0" cellspacing="1" cellpadding="0">
  <tr>
    <td>Society/Venue name</td>
    <td><input name="name" type="text" value="<?=$edit['name']?>">
    </td>
  </tr>
  <tr>
    <td>Short name</td>
    <td><p>
        <input name="shortname" type="text" value="<?=$edit['shortname']?>">
      </p>
    </td>
  </tr>
  <tr>
    <td>College</td>
    <td><p>
        <input name="college" type="text" id="college" value="<?=$edit['college']?>">
(leave blank for University-wide)</p>
    </td>
  </tr>
  <tr>
    <td>Type</td>
    <td><p>
      <select name="type">
      <option value="0" <?php if($edit['type']==0) echo "selected"; ?>>society</option>
      <option value="1" <?php if($edit['type']==1) echo "selected"; ?>>venue</option>
      </select>
    </p>
      </td>
  </tr>
<?php
if (hasEquivalentToken("security", -2))
{
?>
  <tr>
    <td>Affiliated to ACTS?</td>
    <td><p>
    	<input type="checkbox" name="affiliate"<? if ($edit['affiliate']==1) echo " CHECKED"; ?>>
    </p>
      </td>
  </tr>
<?php
}
?>
  <tr>
    <td><p>Description<br>
      <em>This field is
      <?php makeLink(107,"translated"); ?>
      </em>    </p>
      </td>
    <td>
      <textarea name="description" cols="50" rows="10" wrap=virtual><?=htmlspecialchars($edit['description']);?></textarea>
    </td>
	<input type="hidden" name="editid" value="<?=$edit['id']?>">
  </tr>
  
  <tr>
    <td><p>Logo</td>
    <td> <?php
	  	if($edit['logourl']!="") {
			echo("There is currently a logo associated with this society. You can overwrite it with another by ");
			makeLink(100,"uploading",array("retid"=>115,"uploadtype"=>"society","editid"=>$editid));
			echo(" or you can delete it by ticking the box below.<br /><br />");
			?> <input name="deletelogo" type="checkbox" id="deletephoto" value="checkbox"> delete logo <?php
		} else makeLink(100,"upload logo",array("retid"=>115,"uploadtype"=>"society","editid"=>$editid));
	?>
    </td>
</table>
  <input type="submit" name="submit" value="<?php if(isset($edit)) echo "Edit"; else echo "Create"; ?>">
</p>
</form>
<?php 
}
} else inputFeedback(); ?>
