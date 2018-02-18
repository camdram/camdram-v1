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
$allaccess=hasEquivalentToken('include',-1);
if(hasEquivalentToken('include',0))
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
				$create = "INSERT INTO acts_includes VALUES()";
				$q = sqlQuery($create);
				$continue=false;
				if($q==0)
					inputFeedback();
				else 
				{				
					$editid=mysql_insert_id();
					actionLog("Created include $editid");
					$continue=true;
				}
			} else {
				$continue=false;
				inputFeedback("Failed to insert");
			}
		} else $continue=true;
					
		if($continue)
		{
			if(hasEquivalentToken('include',$editid))
			{
				$update = "UPDATE acts_includes SET name='".$_POST['name']."', text='".$_POST['text']."' WHERE id=".$editid;
				$q = sqlQuery($update);
				$continue = false;
				if($q==0)
					inputFeedback("Failed to update");
				else
					inputFeedback("Updated!");
				
			} else inputFeedback("You don't have rights to edit this include");
		}
	}
}

if(isset($_GET['deleteid']) && isset($_GET['confirmed']))
{
	if($allaccess)
	{
	  
		$deleteid=$_GET['deleteid'];
		$query = "SELECT * FROM acts_includes WHERE id=".$deleteid;
		$r=sqlQuery($query,$adcdb) or die(sqlEr());
		$nres = mysql_num_rows($r);
		if($nres!=1)
			inputFeedback("Incorrect number of rows returned during final check before delete","Please report this error to ".$site_support_email.", with as many details as possible of events leading up to the error.");
		else {
			$row = mysql_fetch_assoc($r);
			mysql_free_result($r);
			$delete = "DELETE FROM acts_includes WHERE id=".$row['id']." LIMIT 1";
			$r=sqlQuery($delete);
			if($r<=0) sqlEr();
			unset($_GET['deleteid']);
			inputFeedback("Deleted!","Include ".$row['name']." has been deleted");
			actionLog("Deleted include ".$row['id']." (".$row['name'].")");
		}
	} else
		inputFeedback("Delete not available");
}
allowSubmission();
if (isset($_GET['sortby'])) {
	$order=order();
}
else {
	$order=" ORDER BY name";
}
$query = "SELECT DISTINCT acts_includes.* FROM acts_includes RIGHT JOIN acts_access ON acts_access.`uid`=".$_SESSION['userid']." WHERE".securityQuery('include',"acts_includes.`id`")." $order";
$table = sqlQuery($query) or die(mysql_error());
echo "<p>You have access to the following includes:</p>";

maketable($table,
		array(
			"Name"=>"name",
		),array("Name"=>'echo "<b>$row[name]</b>";'),
			'echo "<a href=\"".thisPage(array("editid"=>$row[\'id\']))."\">edit</a> | ";
			makeLink(111,"access",array("includeid"=>$row[\'id\'], "mode"=>5)); 
			if(hasEquivalentToken(\'include\',-1)) {
			  	echo(" | ");
				makeLink(0,"delete",array("deleteid"=>$row[\'id\']),false,confirmer("delete this include"));
  			}
		'
	);

	if(isset($_GET['editid'])) {
		$editid=$_GET['editid'];
		$query = "SELECT acts_includes.* FROM acts_includes RIGHT JOIN acts_access ON acts_access.`uid`=".$_SESSION['userid']." WHERE".securityQuery('include',"acts_includes.`id`")." AND acts_includes.id=$editid";
		$soc = sqlQuery($query) or die(mysql_error());
		$edit = mysql_fetch_assoc($soc);
		unset($_GET['editid']);
	}

if(hasEquivalentToken('security',-2) || isset($edit)) { 
if(isset($edit)) echo("<h2>Edit include</h2>"); else echo("<h2>Create include</h2>");?>
<p>
<form name="form1" method="post" action="<?= thisPage() ?>">
<table border="0" cellspacing="1" cellpadding="0">
  <tr>
    <td>Include name</td>
    <td><?php if (isset($edit)) { echo $edit['name'];
    ?><input name="name" type="hidden" value="<?=$edit['name']?>">
    <td><?php } else { ?><input name="name" type="text" value="<?=$edit['name']?>"><?php }?>
    </td>
  </tr>
  <tr>
    <td><p>Text<br>
      <em>This field is
      <?php makeLink(107,"translated"); ?>
      </em>    </p>
      </td>
    <td>
      <textarea name="text" cols="50" rows="10" wrap=virtual><?=htmlspecialchars($edit['text']);?></textarea>
    </td>
	<input type="hidden" name="editid" value="<?=$edit['id']?>">
  </tr>
</table>
  <input type="submit" name="submit" value="<?php if(isset($edit)) echo "Edit"; else echo "Create"; ?>">
</p>
</form>
<?php 
}
} else inputFeedback(); ?>
