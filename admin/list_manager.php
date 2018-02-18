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
require_once('library/mailinglists.php');
global $adcdb,$site_support_email;
if(hasEquivalentToken('security',-2))
{
if(isset($_POST['submit']))
{
	if(checkSubmission())
	{
		$editid=$_POST['editid'];
		$continue=true;
		if($editid==0)
		{
			$create = "INSERT INTO acts_mailinglists VALUES()";
			$q = sqlQuery($create);
			$continue=false;
			if($q==0)
				inputFeedback();
			else 
			{				
				$editid=mysql_insert_id();
				actionLog("Created email list $editid");
				$continue=true;
			}
		}
					
		if($continue)
		{
			$update = "UPDATE acts_mailinglists SET name='".$_POST['name']."', shortname='".$_POST['shortname']."', description='".$_POST['description']."', public='".((isset($_POST['public']))?1:0)."', defaultsubscribe='".((isset($_POST['default']))?1:0)."' WHERE id=".$editid;
			
			$q = sqlQuery($update) or die(mysql_error());
			$continue = false;
				inputFeedback("Updated!");
			
		}
	}
}

if(isset($_GET['deleteid']) && isset($_GET['confirmed']))
{
	$deleteid=$_GET['deleteid'];
	$query = "SELECT * FROM acts_mailinglists WHERE id=".$deleteid;
	$r=sqlQuery($query,$adcdb) or die(sqlEr());
	$nres = mysql_num_rows($r);
	if($nres!=1)
		inputFeedback("Incorrect number of rows returned during final check before delete","Please report this error to ".$site_support_email.", with as many details as possible of events leading up to the error.");
	else {
		$delete = "DELETE FROM acts_mailinglists WHERE id=".$deleteid." LIMIT 1";
		$r=sqlQuery($delete);
		if($r<=0) sqlEr();
		unset($_GET['deleteid']);
		inputFeedback("Deleted!","Email List ".$deleteid." has been deleted");
		actionLog("Deleted list ".$deleteid);
	}
}
allowSubmission();
if (isset($_GET['sortby'])) {
	$order=order();
}
else {
	$order=" ORDER BY id";
}
$query = "SELECT * FROM acts_mailinglists $order";
$table = sqlQuery($query) or die(mysql_error());

maketable($table,
		array(
			"ID"=>"id",
			"Short name"=>"shortname",
			"Name"=>"name",
			"Public"=>"public"
		),array("Public"=>'if ($row[\'public\']==0) echo "-"; else echo "y";')
			,'
  			echo "<a href=\"".thisPage(array("editid"=>$row[\'id\']))."\">edit</a> | ";
			makeLink(111,"access",array("listid"=>$row[\'id\'], "mode"=>2)); 
		  	echo(" | ");
			makeLink(0,"delete",array("deleteid"=>$row[\'id\']),false,confirmer("delete the list" . $row[\'name\']));
		'
	);

	if(isset($_GET['editid'])) {
		$editid=$_GET['editid'];
		$query = "SELECT * FROM acts_mailinglists WHERE acts_mailinglists.id=$editid";
		$list = sqlQuery($query) or die(mysql_error());
		$edit = mysql_fetch_assoc($list);
		unset($_GET['editid']);
	}

if(hasEquivalentToken('security',-2) || isset($edit)) { 
if(isset($edit)) echo("<h2>Edit mailing list details</h2>"); else echo("<h2>Create mailing list</h2>");?>
<p>
<form name="form1" method="post" action="<?= thisPage() ?>">
<table border="0" cellspacing="1" cellpadding="0">
  <tr>
    <td>List name</td>
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
    <td>Public</td>
    <td><p>
        <input name="public" type="checkbox" id="public" value="yes" <?php if ($edit['public']==1) echo "checked";?>>
</p>
    </td>
  </tr>
  <tr>
    <td>Default Subscribe new users</td>
    <td><p>
        <input name="default" type="checkbox" id="default" value="yes" <?php if ($edit[defaultsubscribe]==1) echo "checked";?>>
</p>
    </td>
  </tr>
  <tr>
    <td><p>Description
      </p>
      </td>
    <td>
        <input name="description" type="text" value="<?=$edit['description']?>" size=60>
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
