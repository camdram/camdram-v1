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
global $showid;
$defaultdays=getconfig("techies_advert_default_days");
$maxdays=getconfig("techies_advert_max_days");
$maxlines=getconfig("techies_info_max_lines");
$showid = $_GET['showid'];
if (isset($_GET['renew'])) {
	$renew=$_GET['renew'];
	unset($_GET['renew']);
}
if(canEditShow($showid))
{
if($password=="") $password="NONE";
$row_thisshow=getShowRow($showid);
if (isset($_POST['techextra'])) $techextra=stripslashes($_POST['techextra']);
	$desc=str_replace("\r","",$techextra);
	 if(isset($_POST['unwrap'])) {
		$desc=str_replace("\n\n","\r\r",$desc);
		$desc=str_replace("\n"," ",$desc);
		$desc=str_replace("\r","\n",$desc);
	}
	 $techextra=$desc;
if (!isset($_GET['referred'])) editorLinks($row_thisshow,'Production Team Positions Advertised');
else makeLink("show_wizard.php","Return to show entry",array("showid"=>$showid,"enteredpage"=>4));
?>
<?php
function printform ($positions, $contact, $deadline,$expiry,$submittext,$editid,$showid,$adcdb,$techextra,$deadlinetime) {
	$i=0;
	$query="SELECT * FROM acts_techies_positions ORDER BY orderid";
	$result=sqlQuery($query) or die(mysql_error());
?>
<?php
if (mysql_num_rows($result)>0) {
?><script language="javascript">
<!--
function insert() {
<?php
	echo "var position=new Array(".mysql_num_rows($result).");";
	while($row=mysql_fetch_assoc($result)) {
		$build=$build."<option value=\"$row[position]\">$row[position]</option>";
		echo "position[$i]='$row[position]';\n";
		$i++;
	}
?>
	addtext=position[document.forms["editad"].elements["pos"].selectedIndex];
	document.forms["editad"].elements["positions"].value=document.forms["editad"].elements["positions"].value+addtext+"\n";
}
-->
</script>
<?php } ?>
<form action="<?=thispage()?>" method="post" name="editad">
<?php $submitid=allowSubmission();?>
<input type="hidden" name="submitid" value="<?php echo $submitid;?>">
<input type="hidden" name="edit" value="<?php echo $editid;?>">

      <table class="editor"><tr><th>
        Positions available</th><td>
	<?php
if (mysql_num_rows($result)>0) {
  echo "<p>Common Positions:<br/>";
	echo "<select name=\"pos\">";
	echo $build;
	echo "</select>";
	echo "<input type=button onClick='insert()' value=\"Add to list\"></p>";
}
	?>
        <br/>
        <p>You can also type directly in the box below, <strong>one position per line</strong></p>
	<textarea name="positions" wrap="virtual" rows=6 cols="40" id="textarea"><?php if (trim($positions)!="") echo trim($positions),"\n";?></textarea>
        <br />
	
	<br /></td></tr><tr><th>
        Contact Details</th><td>
        <input name="contact" type="text" size="80" maxlength="255" value="<?php echo $contact;?>">
        <br />
	Please supply a full sentence e.g. <em>Email Joe Bloggs on jb789 for more information or to apply</em>
	</td></tr><tr><th>
        Deadline</th><td>
        <?php 
		if ($deadline==1) {
			dateFieldSQL($expiry,'');
		}
		else {
			?><input name="day" id="day" type="text"  value="" size="4" maxlength="2">
			<select name="month" id="month">
			<option value="1">Jan
			<option value="2">Feb
			<option value="3">Mar
			<option value="4">Apr
			<option value="5">May
			<option value="6">Jun
			<option value="7">Jul
			<option value="8">Aug
			<option value="9">Sep
			<option value="10">Oct
			<option value="11">Nov
			<option value="12">Dec
			</select>
			<input name="year" id="year" type="text" value="" size="7" maxlength="4">
		<?php
		}
$defaultdays=getconfig("techies_advert_default_days");
		?>
										    <br />If you fill this in, the deadline will be displayed and the advert hidden after this date. Otherwise the advert will be shown for <?php echo $defaultdays;?> days, after which you can renew it if needed. </td></tr>
     <tr><th>Deadline time</th><td> <input type="text" name="deadlinetime" value="<?php echo $deadlinetime;?>"></td>
  
   </tr><tr><th>Further information (this is displayed in addition to your show blurb)<br />
    <em>This field is <?php makeLink(107,"translated"); ?>
      </em><br />This field is limited to <?php echo getconfig("techies_info_max_lines");?> lines</th><td>
      <textarea name="techextra" cols="80" rows="10" wrap="VIRTUAL" id="textarea4"><?php echo($techextra);?></textarea><br />
      leave blank if unwanted.
  	<p><input type=checkbox name="unwrap" value=1 checked> Strip Carriage Returns (useful when copying from emails)</p>
      </td></tr><tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submittext;?>"></td></tr></table>
</form>
<?php
}  
?>

<?php
 
$validate=1;
 authorizeWarning($row_thisshow);
 

if(isset($_GET['delete']))
{
	$id=$_GET['delete'];
	unset($_GET['delete']);
	$query = "DELETE FROM `acts_techies` WHERE id=$id AND showid=$showid LIMIT 1";
	$r = sqlQuery($query,$adcdb) or die(mysql_error());
	if (mysql_affected_rows()>0) {
		echo("Deleted!<br><br>");
		actionLog("Delete production team advert $id");
	}
	markShowUpdate($showid);
}
if (isset($renew)) {
	$seconds=$defaultdays*86400;
	$date=date("Y-m-d",time()+$seconds);
	$query="UPDATE acts_techies SET expiry='$date', deadline=0, remindersent=0,lastupdated=NOW() WHERE id='$renew'";

	sqlQuery($query,$adcdb) or die(mysql_error());
	actionLog("Renewed production team advert $renew");
	markShowUpdate($showid);
}
if($_POST['submit']=="add")
{

	$param_list=array("submit","year","positions","month","day","contact");
	foreach($param_list as $param)
	{
  		 @$$param=htmlspecialchars($_POST[$param]);
		 $val=mysql_real_escape_string($$param);
	}
	$date= "$year-$month-$day";
	$deadline=1;
        $error="";
        if ((strtotime($date)+86400)<=time()) {
	      $seconds=$defaultdays*86400;
	      $date=date("Y-m-d",time()+$seconds);
	      $deadline=0;
        }
        if ((strtotime($date)-$maxdays*86400)>=time()) {
		$validate=0;
		$error="$error You cannot set a deadline more than $maxdays days in the future\n";
	}
	if ($positions=="") {
		$validate=0;
                $error="$error You must enter some positions\n";
	}
	if ($contact=="") {
		$validate=0;
                $error="$error You must supply contact details\n";
	}
	if (linecount($_POST['techextra'])>$maxlines) {
		$validate=0;
		$error="$error The length of the extra information field is limited to $maxlines lines\n";
	}
        if ($validate==1) {
         if (checkSubmission()) {
	 $query_add = "INSERT INTO `acts_techies` (`showid`, `positions`, `contact`, `deadline`, `expiry`,`remindersent`,`deadlinetime`,`lastupdated`) VALUES ($showid,'$positions','$contact','$deadline','$date',0,'".mysql_real_escape_string($_POST[deadlinetime])."',NOW())";
	 
	 $q = sqlQuery($query_add, $adcdb) or die(mysql_error());
	 $techid=mysql_insert_id($adcdb);
	 if($techid==0) die(mysql_error());
	 echo("<br><b>Advert Created!</b> id=$techid<br>");
	 actionlog("created production team advert $techid");
	 markShowUpdate($showid);
	$desc=str_replace("\r","",$_POST['techextra']);
	 if(isset($_POST['unwrap'])) {
		$desc=str_replace("\n\n","\r\r",$desc);
		$desc=str_replace("\n"," ",$desc);
		$desc=str_replace("\r","\n",$desc);
	}
	 $query="UPDATE acts_techies SET techextra='".$desc."',lastupdated=NOW() WHERE id=$techid";
	 $q = sqlQuery($query, $adcdb) or die(mysql_error());
	 if (mysql_affected_rows() >0) {
	 	actionlog("Updated tech information on show $showid");
		$row_thisshow=getShowRow($showid);
        }
	}
       }

}
elseif($_POST['submit']=="edit")
{
        $param_list=array("submit","year","positions","month","day","contact","edit");
	foreach($param_list as $param)
	{
  		@$$param=htmlspecialchars($_POST[$param]);
		$val=mysql_real_escape_string($$param);
	}
	$date= "$year-$month-$day";
	$deadline=1;
        $error="";
        if ((strtotime($date)+86400)<=time()) {
	      $seconds=$defaultdays*86400;
	      $date=date("Y-m-d",time()+$seconds);
	      $deadline=0;
        }
        if ((strtotime($date)-$maxdays*86400)>=time()) {
		$validate=0;
		$error="$error You cannot set a deadline more than $maxdays days in the future\n";
	}

	if ($positions=="") {
		$validate=0;
                $error="$error You must enter some positions\n";
	}
	if ($contact=="") {
		$validate=0;
                $error="$error You must supply contact details\n";
	}
	if (linecount($_POST['techextra'])>$maxlines) {
		$validate=0;
		$error="$error The length of the extra information field is limited to $maxlines lines\n";
	}
        if ($validate==1) {
	  if (checkSubmission()) {
          $query_add = "UPDATE acts_techies SET positions='$positions', contact='$contact', deadline='$deadline', expiry='$date', remindersent=0,deadlinetime='".mysql_real_escape_string($_POST[deadlinetime])."',lastupdated=NOW() WHERE showid=$showid AND id=$edit";
	 $q = sqlQuery($query_add, $adcdb) or die(mysql_error());
	 echo("<br><b>Advert Updated!</b> id=$edit<br>");
	 actionlog("updated production team advert $edit");
	$desc=str_replace("\r","",$_POST['techextra']);
	 if(isset($_POST['unwrap'])) {
		$desc=str_replace("\n\n","\r\r",$desc);
		$desc=str_replace("\n"," ",$desc);
		$desc=str_replace("\r","\n",$desc);
	}
	 $query="UPDATE acts_techies SET techextra='".$desc."',lastupdated=NOW() WHERE id=$edit";
	 $q = sqlQuery($query, $adcdb) or die(mysql_error());
	 markShowUpdate($showid);
	 if (mysql_affected_rows() >0) actionlog("Updated tech information on show $showid");
         }
        }
        else { ?>
<h4>Edit Advert</h4>
<?php if ($error!="") inputfeedback($error) ?>
<?php $expiry="$_POST[year]-$_POST[month]-$_POST[day]";
printform($_POST['positions'],$_POST['contact'],$_POST['deadline'],$expiry,"edit",$_POST['edit'],$showid,$adcdb,$techextra,$_POST['deadlinetime']);
$printed=true;
?>
<?php
        }
}

?>

<?php 
$query_techies = "SELECT * FROM acts_techies WHERE acts_techies.showid=$showid;";

$techies = sqlQuery($query_techies, $adcdb) or die(mysql_error());
$row_techies = mysql_fetch_assoc($techies);
$totalRows_techies = mysql_num_rows($techies);


if(isset($_GET['edit'])) {
   $id=$_GET['edit'];
	unset($_GET['edit']);
   $query_techies = "SELECT * FROM acts_techies WHERE acts_techies.showid=$showid AND acts_techies.id=$id";

   $techies = sqlQuery($query_techies, $adcdb) or die(mysql_error());
   $row_techies = mysql_fetch_assoc($techies);
   $totalRows_techies = mysql_num_rows($techies);

?>
<h4>Edit Advert</h4>
<?php printform($row_techies['positions'],$row_techies['contact'],$row_techies['deadline'],$row_techies['expiry'],"edit",$id,$showid,$adcdb,$row_techies['techextra'],$row_techies['deadlinetime']);
$printed=true;
?>



  <?php } elseif($totalRows_techies>0 and $validate==1) { 

$row_thisshow=getShowRow($showid);
?>
<p>Current advert: </p>
<table class="editor">
  <tr>
    <td>Positions available</td>
    <td><?php $row_techies['positions'] = str_replace("\n",", ",$row_techies['positions']);
    $row_techies['positions']=str_replace(chr(13),"",$row_techies['positions']);
    echo $row_techies['positions']; ?></td>
  </tr>
  <tr>
    <td>Contact Details</td>
    <td><?php echo $row_techies['contact']; ?></td>    
  </tr>
  <tr>
    <td>Expiry Date</td>
    <td><?php echo $row_techies['expiry']; ?></td>
  </tr>
<?php
if ((strtotime($row_techies['expiry'])+86400)<=time()) {
?>
  <tr>
    <td colspan=2><strong>This advert has expired. Click <?php makelink(151,"here",array("renew"=>$row_techies['id']));?> to renew (display for another <?php echo $defaultdays;?> days)</strong></td>
  </tr>
<?php }?>
</table>
<p><?php makelink(151,"edit",array("CLEARALL"=>"CLEARALL","showid"=>$showid,"edit"=>$row_techies['id']));?>
  |
<a href="/administration/edit_show/techies?showid=<?=$showid ?>&delete=<?= $row_techies['id']?>">delete</a><?php if ($row_techies['deadline']==0) {
	echo " | ";
	makelink(151,"renew",array("renew"=>$row_techies['id']));
}
?>
</P>
<?php } elseif ($totalRows_techies==0) { ?>
<h4>Create Production Team Advert</h4>
<?php if ($error!="") inputfeedback($error) ?>
<?php 
if(!isset($_POST['day'])) $day=(date("d")); else $day=($_POST['day']);
if(!isset($_POST['year'])) $year=(date("Y")); else $year=($_POST['year']);
if (!isset($_POST['month'])) $month=date("m"); else $month=$_POST['month'];
if (isset($_POST['techextra'])) $techextra=stripslashes($_POST['techextra']);
printform($_POST['positions'],$_POST['contact'],$_POST['deadline'],"$year-$month-$day","add",0,$showid,$adcdb,$techextra,$_POST['deadlinetime']);
$printed=true;
?>
  <?php } 
	mysql_free_result($techies);
 
}
?>
