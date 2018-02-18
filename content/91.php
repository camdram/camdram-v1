<?php 
require_once('library/editors.php');
global $adcdb;

if(hasEquivalentToken('society',1))
{
switch($_GET['action'])
{
case 'edit':
	$editid=$_GET['editid'];
	break;
CASE 'delete':
	$deleteid=$_GET['deleteid'];
	$query = "DELETE FROM acts_colcont WHERE id=$deleteid LIMIT 1";
	$q = sqlquery($query,$adcdb) or die(mysql_error());
	echo("<p>Deleted</p>");
	actionlog("Deleted college contact $deleteid");
	break;
case 'doedit':
	$editid=$_GET['editid'];
	$name=$_POST['name'];
	$contact=$_POST['contact'];
	$email=$_POST['email'];
	$college=$_POST['college'];
	echo("<p>");
	if($editid<=0)
	{
		$query="INSERT INTO acts_colcont (`college`,`name`,`email`) VALUES ('creating','','')";
		$q = sqlquery($query,$adcdb) or die(mysql_error());
		echo("created... ");
		$editid=mysql_insert_id();
	}
	$query="UPDATE acts_colcont SET `college`='$college', `name`='$name', `email`='$email' WHERE `id`=$editid LIMIT 1";
	$q = sqlquery($query,$adcdb) or die(mysql_error());
	echo("updated!</p>");
	actionlog("Edited college contact $editid");
	unset($_GET['editid']);
	unset($editid);
}
	

$query_collegecontacts = "SELECT * FROM acts_colcont ORDER BY acts_colcont.college";
$collegecontacts = sqlquery($query_collegecontacts, $adcdb) or die(mysql_error());
$row_collegecontacts = mysql_fetch_assoc($collegecontacts);
$totalRows_collegecontacts = mysql_num_rows($collegecontacts); 

unset($_GET['action']);
unset($_GET['deleteid']);
?>
<script language="JavaScript" type="text/JavaScript">
<!--
function confirmLink(theLink, theQuery)
{
    // Confirmation is not required in the configuration file

    var is_confirmed = confirm( theQuery);
    if (is_confirmed) {
        theLink.href += '&action=delete';
    }

    return is_confirmed;
} // end of the 'confirmLink()' function
//-->
</script>
<table border="1" cellpadding="2" cellspacing="1">
  <tr>
    <td><b>College</b></td>
    <td><b>Contact</b></td>
	<td><b>Email</b></td>
    <td><b>Action</b></td>
  </tr>
  <?php
  
  do { 
  $cid=$row_collegecontacts['id'];
  if($cid==$editid) $row_edit=$row_collegecontacts;
  ?>
  <tr>
    <td><?=$row_collegecontacts['college']?></td>
    <td><?=$row_collegecontacts['name']?></td>
	<td><?=$row_collegecontacts['email']?></td>
    <td><a href="<?=thisPage(array("editid" => $cid, "action" => "edit")); ?>">edit</a> | <a href="<?=thisPage(array("deleteid" =>$cid))?>" onClick="confirmLink(this,'Are you sure you want to delete?')">delete</a></td>
  </tr>
  <?php } while ($row_collegecontacts = mysql_fetch_assoc($collegecontacts)); ?>
</table>
<?php

mysql_free_result($collegecontacts);
?>
<p>
<form name="form1" method="post" action="<?=thisPage(array("action" => "doedit"))?>">
  College
    <input type="text" value="<?=$row_edit['college']?>" name="college">
<br>
  Name 
  <input type="text" value="<?=$row_edit['name']?>" name="name">
  <br>
  Email
  <input type="text" value="<?=$row_edit['email']?>" name="email">
  (Don't forget to update the zoneedit entry for this email address if necessary!)<br>
  <input type="submit" name="Submit" value="<?php if(isset($editid)) echo("Update"); else echo("Create"); ?>">
</form>
</p>
<?php } else inputFeedback();?>
