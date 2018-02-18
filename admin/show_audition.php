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
global $adcdb;
$showid = $_GET['showid'];
if(canEditShow($showid))
{
if($password=="") $password="NONE";
$row_thisshow=getShowRow($showid);


if (!isset($_GET['referred'])) editorLinks($row_thisshow,'Auditions');
else makeLink("show_wizard.php","Return to show entry",array("showid"=>$showid,"enteredpage"=>3));

 authorizeWarning($row_thisshow);
if(isset($_GET['delete']))
{
	$id=$_GET['delete'];
	$query = "DELETE FROM `acts_auditions` WHERE id=$id AND showid=$showid LIMIT 1";
	$r = sqlQuery($query,$adcdb) or die(mysql_error());
	echo("Deleted!<br><br>");
	unset($_GET['delete']);
}
if($_POST['submit']=="add")
{
	$param_list=array("submit","year","nonscheduled","month","day","hour","minute","hour2","minute2","location");
	foreach($param_list as $param)
	{
  		 @$$param=$_POST[$param];
		 $val=$$param;
	}
	if($nonscheduled!=1) $nonscheduled=0;
	$date= "$year-$month-$day";
	$query_add = "INSERT INTO `acts_auditions` (`date`, `starttime`, `endtime`, `location`, `showid`, `nonscheduled`) VALUES ('$date','$hour:$minute:00','$hour2:$minute2:00','$location',$showid,$nonscheduled)";
	$q = sqlQuery($query_add, $adcdb);
	$audid=mysql_insert_id($adcdb);
	if($audid==0) die(mysql_error());
	echo("<br><b>Audition Created!</b> id=$audid<br>");
	actionlog("created audition $audid");
	markShowUpdate($showid);
}
elseif($_POST['submit']=="update") {
	if (checkSubmission()) {
		$desc=str_replace("\r","",$_POST['audextra']);
		if(isset($_POST['unwrap'])) {
			$desc=str_replace("\n\n","\r\r",$desc);
			$desc=str_replace("\n"," ",$desc);
			$desc=str_replace("\r","\n",$desc);
		}
		$query="UPDATE acts_shows SET audextra='".$desc."' WHERE id=$showid";
		$result=sqlQuery($query,$adcdb) or die(mysql_error());
		if (mysql_affected_rows()>0) {
			inputFeedback("Updated extra information");
			$row_thisshow=getShowRow($showid,true);
			actionLog("Updated Audition Information on Show $showid");
		}
		markShowUpdate($showid);
	}
}

?>
<?php


// DO UPDATES

function update($field, $value)
{
  global $adcdb,$showid,$row_thisshow;
  $row_thisshow[$field]=$value;
  $updq="UPDATE acts_shows SET $field='$value' WHERE id=$showid";
  $q = sqlQuery($updq,$adcdb) or die(mysql_error());
}
if($_POST['markactors']!="")
{
	if ($row_thisshow['actorsend']!=1)
		update("actorsend",1);
	else
		update("actorsend",0);
	
}
$query_auditions = "SELECT * FROM acts_auditions WHERE acts_auditions.showid=$showid ORDER BY acts_auditions.`date`,acts_auditions.starttime";
$auditions = sqlQuery($query_auditions, $adcdb) or die(mysql_error());
$totalRows_auditions = mysql_num_rows($auditions);

?>
<h4>Create Auditions Advert</h4>
<form action="<?=thisPage(array("showid"=>$showid,"CLEARALL"=>"CLEARALL"))?>" method="post">


  
      <table class="editor">
      <tr><td>  Date</td><td> 
<?php dateField($year,$month,$day,""); ?>
        </td></tr><tr><td>
        Start Time </td><td>
        <input name="hour" type="text"  value="<?php echo($hour); ?>" size="4" maxlength="2">
:
<input name="minute" type="text"  value="<?php echo($minute); ?>" size="4" maxlength="2">
(24 hour clock)</td></tr><tr><td>
End Time</td><td>
        <input name="hour2" type="text" value="<?php echo($hour2); ?>" size="4" maxlength="2">
        : 
        <input name="minute2" type="text" value="<?php echo($minute2); ?>" size="4" maxlength="2">
(24 hour clock)</td></tr><tr><td>
Location </td><td>
<input name="location" type="text" id="location" value="<?php echo(stripslashes($location)); ?>">
</td></tr><tr><td>Non-Scheduled</td><td>
<input name="nonscheduled" type="checkbox" id="nonscheduled" value="1">
 (the date becomes an expiry date for the advert. You can then leave the location field blank, or add a brief description such as 'email abc12 for more information')</td></tr><tr><td>&nbsp;</td><td><input type="submit" name="submit" value="add">
</td></tr></table>
</form>
<?php if($totalRows_auditions>0) 
{ ?>

<h4>Currently Known Auditions </h4>
  <?php 
  makeTable($auditions,array("Date"=>"NONE","Start Time"=>"NONE","End Time"=>"NONE","Location"=>"NONE"),
	    array("Date"=>'echo dateFormat(strtotime($row[date]),0,true);',
	          "Start Time"=>'if($row[nonscheduled]==0) 
				   echo timeFormat(strtotime($row[starttime]),0); 
			          else echo("NS");',
		  "End Time"=>'if($row[nonscheduled]==0) 
				  echo timeFormat(strtotime($row[endtime]),0); 
			       else echo("NS");',
		  "Location"=>'echo $row[location];'),'makeLink(74,"delete",array(\'delete\'=>$row[id]));' );
  } else { ?>
<p>No auditions scheduled yet</p>
  <?php } 
	mysql_free_result($auditions);

?>
<form action="<?php echo thisPage(array("showid"=>$showid,"CLEARALL"=>"CLEARALL"))?>" method="post">
<?php $submitid=allowSubmission();?>
<input type="hidden" name="submitid" value="<?php echo $submitid;?>">
<h4>Extra information for auditions page</h4>
        <p><em>This field is
        <?php makeLink(107,"translated"); ?>
      </em><br/> 
 <textarea name="audextra" cols="80" rows="10" wrap="VIRTUAL" id="textarea4"><?php echo($row_thisshow['audextra']);?></textarea>
  	<p><input type=checkbox name="unwrap" value=1 checked> Strip Carriage Returns (useful when copying from emails)</p>
<br/>Leave blank if unwanted. This will affect all auditions.
<br/><input type="submit" name="submit" value="update">
</p>
<?php
}
?>
