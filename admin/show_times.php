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

require_once("library/editors.php");
require_once("library/showfuns.php");

$param_list=array('startday','startmonth','startyear','endday','endmonth','endyear','excludeday','excludemonth','excludeyear','hour','minute','displaydate','venue', 'display', 'venid');
global $adcdb;

if($_GET['showid']>0)
{
	$showid = $_GET['showid'];
	if (!is_numeric($showid))
		die();
	if(hasEquivalentToken('show',$showid) || $_GET['newshow']=="true")
	{
		$query = "SELECT * FROM acts_shows WHERE id=".$showid;
		$show = sqlQuery($query) or die(sqlEr($query));
		$mainshow=mysql_fetch_assoc($show);
		mysql_free_result($show);

		if ($_GET['newshow']!="true") authorizeWarning($mainshow);
		
		if (isset($_GET['delid'])) {
			if (!is_numeric($_GET['delid']))
				die();
			$query="DELETE FROM acts_performances WHERE id=".$_GET['delid'];
			sqlQuery($query,$adcdb) or die(mysql_error());
			unset($_GET['delid']);
			showuniqueref($mainshow);
		}	
		if(isset($_POST['Submit']))
		{
			if($_POST['editid']>0)
			{
				$check =getPerformanceRow($_POST['editid']); 
				if($check['sid']==$showid)
					$updateid = $_POST['editid'];
				else inputFeedback("Show doesn't match - can't update");
			} else {
				$query="INSERT INTO `acts_performances` VALUES()";
				$res = sqlQuery($query,$adcdb) or die(sqlEr($query));
				$updateid = mysql_insert_id($adcdb);
			}
			
			if(isset($updateid))
			{
				foreach($param_list as $param)
				{
					@$$param=removeEvilTags($_POST[$param]);
					if(@$$param!=$_POST[$param]) $warn.="<li>Removing some HTML tags from field $param</li>";
					$val=mysql_real_escape_string($$param);
				}
				$startdate = "$startyear-$startmonth-$startday";
				$enddate = "$endyear-$endmonth-$endday";
                                $excludedate = "$excludeyear-$excludemonth-$excludeday";
                                if ($hour == "24")
				  $hour = "00";
					
				$time = "$hour:$minute:00";

				if (strtotime ($enddate) < strtotime ($startdate))
				{
					inputFeedback("The start date must be before the end date!");
					$failed = true;
				} else {
				 	if (!$venid) $venid = 'NULL';
					else $venid = (int) $venid;
					$query="UPDATE acts_performances SET startdate='$startdate', excludedate='$excludedate', enddate='$enddate', time='$time', venid=$venid, venue='$venue', sid='$showid' WHERE id=$updateid LIMIT 1";
					$r = sqlQuery($query,$adcdb) or die(sqlEr($query));
					showuniqueref($mainshow);
					markShowUpdate($showid);
				}
			}
			if (! $failed) {
				inputFeedback("Updated!");
				unset($_GET['editid']);
			}
		}
		$row = getShowRow($showid);
		
	

		if(isset($_GET[editid]))
		    {
		      if($_GET[editid]>0) $edit=getPerformanceRow($_GET[editid]);
		      
		      
		      if($edit['sid']!=$showid && $edit['sid']!=0) inputFeedback("Show/Range Mismatch","This range does not appear to belong to this show. Please try again."); else {
			if($edit['sid']==0) echo "<h4>Create performance range</h4>";
			else
			  echo "<h4>Edit performance range</h4>";
			  ?>

	<form name="form1" method="post" action="">				       <?php termCalculator(); ?>
		
			<table class="editor">
		<tr>
		 <input name="submitid" type="hidden" id="submitid" value="<?=allowSubmission()?>">
		  <th>Start Date</th>
		  <td>
		   <?php dateFieldSQL($edit['startdate'],'start'); ?>
		  </td>
		</tr>
		<tr>
		  <th>End Date</th>
		  <td><?php dateFieldSQL($edit['enddate'],'end'); ?>
	</td>
		</tr>
	    <tr><th>Excluding</th><td><?php dateFieldSQL($edit[excludedate],'exclude'); ?></td></tr>
		<tr>
		  <th>Time</th>
		  <td><input name="hour" type="text" id="hour" value="<?php if($showid==0) echo("19"); else echo(substr($edit['time'],0,2)); ?>" size="4" maxlength="2">
	:
	  <input name="minute" type="text" id="minute" value="<?php if($showid==0) echo("45"); else echo(substr($edit['time'],3,2)); ?>" size="4" maxlength="2">
	  <br>
	  <strong>(24-hour clock) </strong> </td>
		</tr>
		<tr bordercolor="#CCCCCC">
          <td valign="top"><strong> Venue</strong></td>
          <td><?php displaySocField("venid",isset($edit)?$edit['venid']:$row['venid'],"venue",isset($edit)?$edit['venue']:$row['venue'],1); ?>
          </td>
		</tr>
		
		
         
		<tr bordercolor="#CCCCCC">
		  <td valign="top">&nbsp;</td>
		  <td>
			<div align="right">
			  <?php if(isset($edit)) { ?><input name="editid" type="hidden" value="<?=$edit['id']?>"><?php } ?>
			  <input type="submit" name="Submit" value="<?php if($edit>0) echo "Edit"; else echo "Create"; ?>">
			
		  </div></td>
		</tr>
	</table>
	</form> 
<?php
			     }
			     
		    }

		$query = "SELECT * FROM acts_performances WHERE sid=".$showid." ORDER BY startdate,enddate,time";
		$dummyshows = sqlQuery($query) or die(sqlEr());
		
		echo "<p><strong>+ </strong>";
		makeLink(0,"Add a performance range",array("editid"=>-1));
		echo "</p>";
		if(mysql_num_rows($dummyshows)>0) 
		  makeTable($dummyshows,array("Date"=>"NONE","Time"=>"NONE","Venue"=>"NONE"),
			  array("Date"=>'echo dateFormat(strtotime($row[startdate]),strtotime($row[enddate]));',
				"Time"=>'echo timeFormat(strtotime($row[time]));',
				"Venue"=>'echo venueName($row);'),
			  'makeLink(0,"edit",array("editid"=>$row[id])); echo " | "; makeLink(0,"delete",array("delid"=>$row[id]));');
		  
		else
		  inputFeedback("There are currently no performances associated with this show.");
		mysql_free_result($dummyshows);

		
		}
	
} ?>
<script type="text/javascript">
//<!--
opener.document.getElementById("showperftab").innerHTML="<?=addslashes(str_replace("\n","",showPerfTable($showid)))?>";

//-->
</script>
