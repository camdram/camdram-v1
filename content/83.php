DEPRECATED
<?php  
require_once('library/editors.php');
if(hasEquivalentToken('society',1))
{
if(isset($_POST['cid']))
{
	crashAndBurn();
	
	$param_list=array('cid','name','email','position','statement','pic','posid','group');
	foreach($param_list as $param)
	{
		 @$$param=$_POST[$param];
		 $val=$$param;
	}
	
	if($cid<=0)
	{
		
		$sql_query="INSERT INTO `acts_committee` (`name`, `email`, `position`, `statement`, `pic`, `id`, `posid`, `group`,  `pass`) VALUES ('Updating...', '', '', NULL, NULL, '', '', '0', '')";
		$q = sqlquery($sql_query, $adcdb) or die(mysql_error());
		$cid=mysql_insert_id($adcdb);
		actionlog("Created committee $cid $posid");
	}
	
	$sql_query="UPDATE `acts_committee` SET `name`='$name', `email`='$email', `position`='$position', `statement`='$statement', `pic`='$pic', `posid`='$posid', `group`=$group WHERE `id`=$cid";
	echo($sql_query);
	$q = sqlquery($sql_query, $adcdb)  or die(mysql_error());
	actionlog("Updated committee $cid $posid");
}
/*if(isset($_POST['com']))
{
	crashAndBurn();		// just in case page has lost its protection flag
	$param_list=array('com','comid','password','passwordchange','passwordchange2');
	
	foreach($param_list as $param)
	{
		 @$$param=$_POST[$param];
		 $val=$$param;
	}
	
	if(tryAuth($comid,md5($password),1)<0)
	{
		echo("<p><b>Couldn't reauthenticate your login; please <a href=\"index.php?id=84&com=$com\">try again</a>.</b></p>");
	} else {
		if($passwordchange!=$passwordchange2 || strlen($passwordchange)<4)
		{
			echo("<p><b>New passwords don't match or new password is invalid; please <a href=\"index.php?id=84&com=$com\">try again</a>.</b></p>");
		} else {
			$com=$_POST['com'];
			$pass=md5($passwordchange);
			actionlog("$comid update password for $com");
			$syntax = "UPDATE acts_committee SET pass='$pass' WHERE posid='$com' LIMIT 1";
			$s=sqlquery($syntax,$adcdb) or die(mysql_error());
			echo("<p><b>Password updated!</b></p>");
		}
	}
}
*/

if($_GET['action']=="startedit")
	$editid=$_GET['comid'];

if($_GET['action']=="delete")
{
	
	$did=$_GET['comid'];
	$sql_query="DELETE FROM acts_committee WHERE id=$did LIMIT 1";
	$s=sqlquery($sql_query,$adcdb) or die(mysql_error());
	actionlog("Deleted committee $did");
}

$query_committee = "SELECT * FROM acts_committee ORDER BY acts_committee.`group`";
$committee = sqlquery($query_committee, $adcdb) or die(mysql_error());
$row_committee = mysql_fetch_assoc($committee);
$totalRows_committee = mysql_num_rows($committee);


	

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
<table border="1" cellpadding="1" cellspacing="1">
  <tr>
    <td><strong>name</strong></td>
    <td><strong>email</strong></td>
    <td><strong>position</strong></td>
    <td><strong>pic</strong></td>
    <td><strong>id</strong></td>
    <td><strong>posid</strong></td>
    <td><strong>group</strong></td>
    <td><strong>Action</strong></td>
  </tr>
  <?php do { 
  if($editid==$row_committee['id']) $editrow=$row_committee;
  ?>
  <tr>
    <td><?php echo $row_committee['name']; ?></td>
    <td><?php echo $row_committee['email']; ?></td>
    <td><?php echo $row_committee['position']; ?></td>
    <td><?php echo $row_committee['pic']; ?></td>
    <td><?php echo $row_committee['id']; ?></td>
    <td><?php echo $row_committee['posid']; ?></td>
    <td><?php echo $row_committee['group']; ?></td>
    <td><a href="find.php?item=committee%20edit&comid=<?php echo $row_committee['id'] ?>&action=startedit">edit</a>
	 | <a href="find.php?item=committee%20edit&comid=<?php echo $row_committee['id'];?>" onclick="return confirmLink(this, 'Are you sure you want to delete?\nThis action cannot be undone.')">delete</a></td>
  </tr>
  <?php } while ($row_committee = mysql_fetch_assoc($committee)); ?>
</table>

<?php mysql_free_result($committee);?>
<h2>Add/Edit:</h2>
<form method="post" name="form1" action="find.php?item=committee%20edit">
  <table align="left">
    <tr valign="baseline">
      <td nowrap align="right">Name:</td>
      <td><input type="text" name="name" value="<?=$editrow['name']?>" size="32">
        <input name="cid" type="hidden" id="cid" value="<?=$editid?>">
      </td>
    </tr>
    <tr valign="baseline">
      <td nowrap align="right">Email:</td>
      <td><input type="text" name="email" value="<?=$editrow['email']?>" size="32">
      </td>
    </tr>
    <tr valign="baseline">
      <td nowrap align="right">Position:</td>
      <td><input type="text" name="position" value="<?=$editrow['position']?>" size="32">
      </td>
    </tr>
    <tr valign="baseline">
      <td align="right" valign="middle" nowrap>Statement:</td>
      <td><textarea name="statement" cols="60" rows="4" wrap="VIRTUAL"><?=$editrow['statement']?></textarea>
      </td>
    </tr>
    <tr valign="baseline">
      <td nowrap align="right">Pic:</td>
      <td><input type="text" name="pic" value="<?php
	  if(isset($_GET['uploaded'])) echo $_GET['uploaded']; else echo $editrow['pic'];?>" size="32">
        <?php if($editid!=0) { ?>[<?php makeLink(100,"upload",array("retid"=>"83","uploadtype"=>"1"));?>]<?php } ?></td>
    </tr>
    <tr valign="baseline">
      <td nowrap align="right">Posid:</td>
      <td><input type="text" name="posid" value="<?=$editrow['posid']?>" size="32">
      </td>
    </tr>
    <tr valign="baseline">
      <td nowrap align="right">Group:</td>
      <td><input type="text" name="group" value="<?=$editrow['group']?>" size="32"><br>
	  Affects ordering, so probably just corresponds to the group as per constitution.
	  Within a group, ordering is by creation date. Set group greater than 9
	    and the entry doesn't appear on the Committee page, but is available
	    for referencing
	  in pages using the <strong>committeeContact</strong> (PHP) or <strong>[CTTE:] </strong>(translator)
	  function.
      </td>
    </tr>
    <tr valign="baseline">
      <td nowrap align="right">&nbsp;</td>
      <td><input type="submit" value="<?php if(isset($editid)) echo("Update Record"); else echo("Insert Record"); ?>">
      </td>
    </tr>
  </table>
  <div align="left">
    <input type="hidden" name="id" value="">
    <input type="hidden" name="MM_insert" value="form1">
  </div>
</form>
<?php
} else inputFeedback(); ?>
