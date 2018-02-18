<?php
// Display Event

global $adcdb;
$eid=$_GET['eventid'];
if (!is_numeric($eid))
  die();
$query = "SELECT acts_events.*, acts_societies.name FROM acts_events,acts_societies WHERE acts_events.id=$eid AND acts_societies.id=acts_events.socid";
$q=sqlquery($query,$adcdb) or die(mysql_error());
$details = mysql_fetch_assoc($q);
$socname=$details['name'];
$socid=$details['socid'];
mysql_free_result($q);

echo("<h2>".$details['text']."</h2>");
echo("<p><em>".date("dS M Y",strtotime($details['date']))."</em>");
if($details['starttime']!="00:00:00") echo ("<br />".timeFormat(strtotime($details['starttime']),strtotime($details['endtime'])));
echo "</p>";
if($details['linkid']>0)
{
	$query = "SELECT * FROM acts_events WHERE id=".$details['linkid'];
	$q=sqlquery($query,$adcdb) or die(mysql_error());
	if(mysql_num_rows($q)>0) $details = mysql_fetch_assoc($q);
	mysql_free_result($q);
}
echo("<p>".preprocess($details['description'])."</p>");
global $mode;
if($mode!=3)
{
?><p class="smallgrey">Event listed by <?=makeLink(116,$socname,array("socid"=>$socid,"retid"=>112))?></p>
<p class="smallgrey" align="right"><?php if (hasEquivalentToken('society',$socid)) makeLink(92,"edit this event",array("editid"=>$eid),true); ?></p><?php
													  } ?>
