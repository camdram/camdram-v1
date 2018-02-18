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
$allaccess=hasEquivalentToken('security',-3);
if(isset($_POST['submit']))
{
	if(checkSubmission())
	{
		$editid=$_POST['editid'];
		if($editid==0)
		{
			if(hasEquivalentToken('security',-3))
			{
				$shortname=strtolower(preg_replace("/\W/","",$_POST['name']));
				$count=1;
				$i=0;
				while($count!=0) {
					if ($i==0) $curshortname=$shortname; else $curshortname=$shortname."_".$i;
					$query="SELECT * FROM acts_stores WHERE shortname='$curshortname'";
					$q=sqlQuery($query) or die(sqlEr());
					$count=mysql_num_rows($q);
					$i++;
				}
				$create = "INSERT INTO acts_stores (shortname) VALUES('$curshortname')";
				$q = sqlQuery($create);
				$continue=false;
				if($q==0)
					inputFeedback();
				else 
				{				
					$editid=mysql_insert_id();
					actionLog("Created store $editid");
					$continue=true;
				}
			} else {
				$continue=false;
				inputFeedback();
			}
		} else $continue=true;
					
		if($continue)
		{
			if(hasEquivalentToken('store',$editid))
			{
				$update = "UPDATE acts_stores SET name='".$_POST['name']."' WHERE id=".$editid;
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
		$query = "SELECT * FROM acts_stores WHERE id=".$deleteid;
		$r=sqlQuery($query,$adcdb) or die(sqlEr());
		$nres = mysql_num_rows($r);
		$row=mysql_fetch_assoc($r);
		if($nres!=1)
			inputFeedback("Incorrect number of rows returned during final check before delete","Please report this error to ".$site_support_email.", with as many details as possible of events leading up to the error.");
		else {
			$delete = "DELETE FROM acts_stores WHERE id=".$row['id']." LIMIT 1";
			$r=sqlQuery($delete);
			if($r<=0) sqlEr();
			unset($_GET['deleteid']);
			inputFeedback("Deleted!","Store ".$row['name']." has been deleted");
			actionLog("Deleted store ".$row['id']." (".$row['name'].") and converted ".$z." references to text");
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
$query = "SELECT DISTINCT acts_stores.* FROM acts_stores RIGHT JOIN acts_access ON acts_access.`uid`=".$_SESSION['userid']." WHERE".securityQuery('store',"acts_stores.`id`")." $order";
$table = sqlQuery($query) or die(mysql_error());
echo "<p>You have access to the following stores:</p>";

maketable($table,
		array(
			"Store"=>"name",
		),array(),'
			makelink(228,"edit contents",array("storeid"=>$row[\'id\'],"CLEARALL"=>"CLEARALL"));
  			echo " | <a href=\"".thisPage(array("editid"=>$row[\'id\']))."\">edit name</a>";
			if(hasEquivalentToken(\'security\',-2)) {
			  	echo(" | ");
				makeLink(0,"delete",array("deleteid"=>$row[\'id\']),false,confirmer("delete " . $row[\'name\']));

  			}
			echo " | ";
			makeLink(111,"access",array("storeid"=>$row[\'id\'], "mode"=>6)); 
			echo " | ";
			makeLink(227,"view",array("storeid"=>$row[\'id\'],"CLEARALL"=>"CLEARALL"));
		'
	);

	if(isset($_GET['editid'])) {
		$editid=$_GET['editid'];
		$query = "SELECT acts_stores.* FROM acts_stores RIGHT JOIN acts_access ON acts_access.`uid`=".$_SESSION['userid']." WHERE".securityQuery('store',"acts_stores.`id`")." AND acts_stores.id=$editid";
		$store = sqlQuery($query) or die(mysql_error());
		$edit = mysql_fetch_assoc($store);
		unset($_GET['editid']);
	}

if(hasEquivalentToken('security',-2) || isset($edit)) { 
if(isset($edit)) echo("<h2>Edit store details</h2>"); else echo("<h2>Create store</h2>");?>
<p>
<form name="form1" method="post" action="<?= thisPage() ?>">
<table border="0" cellspacing="1" cellpadding="0">
  <tr>
    <td>Store name</td>
    <td><input name="name" type="text" value="<?=$edit['name']?>">
    </td>
  </tr>
	<input type="hidden" name="editid" value="<?=$edit['id']?>">
</table>
  <input type="submit" name="submit" value="<?php if(isset($edit)) echo "Edit"; else echo "Create"; ?>">
</p>
</form>
<?php 
}
?>
