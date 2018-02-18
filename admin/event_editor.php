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

if (!isset($_GET['sortby'])) {
	$_GET['sortby']="date";
	$_GET['order']="down";
}
$perpage=10;
$page=isset($_GET['page'])?$_GET['page']:1;
require_once("library/editors.php");
require_once("library/table.php");
global $adcdb;
// REQUEST HANDLING


	if(isset($_POST['submit']))
	{
		if(checkSubmission() && $_POST['socid']>0)
		{
		
			if($_POST['editid']==0)
			{
				// create
				$create = "INSERT INTO acts_events VALUES()";
				$q = sqlQuery($create,$adcdb);
				if($q==0)
					inputFeedback("Creation failed",mysql_error());
				else
					$editid=mysql_insert_id($adcdb);
				actionlog("Create Event $editid");
			} else $editid=$_POST['editid'];
			$update = "UPDATE acts_events SET socid='".$_POST['socid']."', starttime='".$_POST['starthour'].":".$_POST['startminute']."', endtime='".$_POST['endhour'].":".$_POST['endminute']."', text='".$_POST['text']."', date='".$_POST['year']."-".$_POST['month']."-".$_POST['day']."', linkid='".$_POST['linkid']."', description='".$_POST['description']."' WHERE id=".$editid;
			if(hasEquivalentToken('society',$_POST['socid'])) $q = sqlQuery($update,$adcdb);
			if($q!=0) 
			{
				inputFeedback("Updated!"); 
				actionlog("Update Event $editid");
			} else inputFeedback("Could not update",mysql_error());
		}
	}
	
	if(isset($_GET['delid']))
	{
		if(checkSubmission() && isset($_GET['confirmed']))
		{
			$check = "SELECT socid FROM acts_events WHERE id=".$_GET['delid'];
			$q = sqlQuery($check,$adcdb);
			if($q>0)
			{
				$r = mysql_fetch_assoc($q);
				mysql_free_result($q);
				if(hasEquivalentToken('society',$r['socid']))
				{
					$del = "DELETE FROM acts_events WHERE id=".$_GET['delid']." LIMIT 1";
					$q = sqlQuery($del,$adcdb);
					if($q==0)
						inputFeedback("Delete failed",mysql_error());
					else
					{
						inputFeedback("Deleted event ".$_GET['delid']."!");
						actionLog("Deleted Event ".$_GET['delid']);
					}
				} else inputFeedback();
			} else inputFeedback();
		}
		unset($_GET['confirmed']);
		unset($_GET['delid']);
	}


allowSubmission();


// FETCH DATA
$query_events = "SELECT acts_events.*, acts_societies.shortname FROM acts_events LEFT JOIN acts_societies ON acts_events.socid=acts_societies.id WHERE ".societyAccessString().order();
$events = sqlQuery($query_events, $adcdb) or die(mysql_error());
$maxpage = splitResults($events,$perpage,$page);

?>
<?php if($maxpage>1) { ?><p><?=displayRangeField($page,$maxpage)?></p><?php } ?>

<?php
if (isset($_GET['editid'])) {
	$editid=$_GET['editid'];
	$query_event = "SELECT acts_events.*, acts_societies.shortname FROM acts_events LEFT JOIN acts_societies ON acts_events.socid=acts_societies.id WHERE ".societyAccessString()." AND acts_events.id='$editid'";
	$event = sqlQuery($query_event, $adcdb) or die(mysql_error());
  	$editrow=mysql_fetch_assoc($event);
	unset($_GET['editid']);
	mysql_free_result($event);
}
if(isset($editrow) || isset($_GET[addevent])) {
  unset($_GET[addevent]);
  if(isset($editrow)) echo "<h3>Edit Event<div class=\"headerbuttons\">".makeLinkText(0,"cancel",array())."</div></h3>"; else echo "<h3>Add New Event<div class=\"headerbuttons\">".makeLinkText(0,"cancel",array())."</div></h3><p><strong>An event listed here should not be listed under any other category on camdram.net.</strong> Examples of what does <i>not</i> constitute an event: auditions; application deadlines; shows (one-off or otherwise). Examples of what <i>does</i> constitute an event: workshops, parties, society trips."; ?>
<form name="form1" method="post" action="<?=thisPage()?>">
  <table width="100%" border="0" cellpadding="3" cellspacing="1">
    <tr>
      <td width="32%">Event Title</td>
      <td width="68%"><input name="text" type="text" id="text" value="<?=htmlspecialchars(stripcslashes($editrow['text']))?>">
      </td>
    </tr>
    <tr>
      <td>Date</td>
      <td><?php if(isset($editrow)) dateFieldSQL($editrow['date']); else dateField(0,0,0); ?>
      </td>
    </tr>
    <tr>
      <td height="27">Start Time</td>
      <td><?php if(isset($editrow)) timeFieldSQL($editrow['starttime'],"start"); else timeField(0,0,"start"); ?>
      </td>
    </tr>
    <tr>
      <td height="27">As Society</td>
      <td><select name="socid" id="societies")>
        <?php
	$query = "SELECT name,id FROM acts_societies WHERE ".societyAccessString("id")." ORDER BY name";
	$r=sqlQuery($query);
	if($r>0)
	{
		while($soc = mysql_fetch_assoc($r))
		{
			echo('<option value="'.$soc['id'].'"');
			if($soc['id']==$editrow['socid']) echo(" selected");
			echo ('>'.htmlspecialchars($soc['name'])."</option>");
		}
		mysql_free_result($r);
	}
	?>
      </select></td>
    </tr>
    <tr>
      <td>End Time (set to 00:00 for no specific end time)</td>
      <td><?php if(isset($editrow)) timeFieldSQL($editrow['endtime'],"end"); else timeField(0,0,"end"); ?>
      </td>
    </tr>
    <tr>
      <td>Link ID
        <p class="smallgrey">(if non-zero, the detailed description from the event ID given
        will be shown in place of the field below, or set <em>negative</em> to
        link to a website page)</p></td>
      <td><input name="linkid" type="text" value="<?=$editrow['linkid'] ?>" size="5" maxlength="5">
</td>
    </tr>
    <tr>
      <td><p>Detailed Description (appears when event is clicked on)<br>
          <em>This field is
          <?php makeLink(107,"translated"); ?>
          </em></p>
      </td>
      <td><textarea name="description" cols="60" rows="6"><?=htmlSpecialChars(stripcslashes($editrow['description']));?>
</textarea>
      </td>
    </tr>
  </table>
  <input name="editid" type="hidden" id="editid" value="<?=$editrow['id']?>">
  <input type="submit" name="submit" value="<?php echo(isset($editrow)?"Update":"Create"); ?>">
</form>
<?php echo "<p>".makeLink(0,"Click here to cancel",array())."</p>"; 
} else {
echo "<p>+ ".makeLinkText(0,"Add New Event",array("addevent"=>"addevent"))."</p>";

maketable($events,array(
		"id"=>"id",
		"soc"=>"shortname",
		"title"=>"text",
		"end time"=>"endtime",
		"start time"=>"starttime",
		"date"=>"date"
	),array(
		"soc"=>'echo maxChars(htmlspecialchars($row[\'shortname\']),30);',
		"title"=>'echo maxchars(htmlspecialchars($row[\'text\']),30);',
	),'
		echo "<a href=\"".thisPage(array("delid"=>$row[\'id\']))."\" ".confirmer("delete this event").">delete</a>";
		echo " | "; 
		makelink(112,"view",array("eventid"=>$row[\'id\']),true);
		echo " | ";
		echo "<a href=\"".thisPage(array("editid"=>$row[\'id\']))."\">edit</a>";
	',$perpage);

}

 mysql_free_result($events);
?>

