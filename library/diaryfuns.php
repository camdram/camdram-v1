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

/**
  * Returns all shows, events, auditions and applications between 2 certain dates, taking $_GET['socid'] and $_GET['noauds'] into account.
  * @param $startDate Lower bound of dates to search in
  * @param $endDate Upper bound of dates to search in
  * @param $show_blacklist Shows to exclude
  * @param $shows_only Only return shows
  * @return associative array, containing 4 more arrays named 'shows', 'events', 'applications', 'auditions' and 'richtimeinfo'
  * @return Each of these arrays contains an associative array containing information about each event; fields returned are as follows:
  * @return
  * @return events - id, text, date, starttime, endtime, linkid, socid
  * @return shows - id, title, author, photourl, description, socid, primaryref, times (times is for internal use and refers to richtimeinfo)
  * @return auditions - date, id
  * @return applications - socid, deadlinedate
  * @return richtimeinfo is for internal use
  * @return
  * @return What this all means is $returned_value['shows'][$x]['title'] should work.
  * @return
  * @return The output of this function can be dumped straight back into other functions in this file to parse richtimeinfo
*/
function getAllEvents ($startDate, $endDate, $show_blacklist = array(), $shows_only = false)
{
	global $adcdb;
  
	$return = array('shows' => array(), 'events' => array(), 'applications' => array(), 'auditions' => array());
  
	$sqlDate1 = date ("Y/m/d", $startDate);
	$sqlDate2 = date ("Y/m/d", $endDate);
  
	$query_shows = "SELECT acts_performances.*, acts_societies.name FROM acts_performances LEFT JOIN acts_societies ON acts_performances.venid = acts_societies.id WHERE (acts_performances.startdate<='$sqlDate2') AND (acts_performances.enddate>='$sqlDate1') AND acts_performances.sid IS NOT NULL";
	for ($x = 0; $x < sizeof ($show_blacklist); $x++)
	{
		$query_shows .= " AND acts_performances.sid != " . $show_blacklist[$x];
	}
	$showResult = sqlQuery ($query_shows, $adcdb) or die (mysql_error());
	while ($row_shows = mysql_fetch_assoc ($showResult))
	{
		if (! array_key_exists ("i" . $row_shows['sid'], $return['shows']))
		{
			$query_shows = "SELECT id, title, author, photourl, description, socid, ref FROM acts_shows, acts_shows_refs WHERE acts_shows.primaryref=acts_shows_refs.refid AND authorizeid>0 AND id = " . $row_shows ['sid'];
			if (isset ($_GET['socid']) && is_numeric($_GET['socid']))
			{
				$rowsoc=getsocrow($_GET['socid']);
				$socname=$rowsoc['name'];
				$shortname=$rowsoc['shortname'];
				$query_shows.= " AND (acts_shows.socid=".$_GET['socid']." OR acts_shows.society LIKE \"%".addslashes($socname)."%\" OR acts_shows.society LIKE \"%".addslashes($shortname)."%\")";
			}
			$showResult2 = sqlQuery ($query_shows, $adcdb) or die (mysql_error());
			if (!$row_show = mysql_fetch_assoc ($showResult2)) continue;
			$return["shows"]["i" . $row_shows['sid']] = $row_show;
			$return["shows"]["i" . $row_shows['sid']]['times'] = -1;
		}
		if(is_null($row_shows['name'])){
			$venue = $row_shows['venue'];
		}else{
			$venue = $row_shows['name'];
		}
		$return['richtimeinfo'][] = array ('startdate' => $row_shows ['startdate'], 'enddate' => $row_shows ['enddate'], 'time' => $row_shows['time'], 'excludedate' => $row_shows ['excludedate'], 'venue' => $venue, 'next' => $return["shows"]["i" . $row_shows['sid']]['times']); /* next is for singly linked list stuff */
		$return["shows"]["i" . $row_shows['sid']]['times'] = sizeof ($return['richtimeinfo']) - 1;
	}
	
	if ($shows_only)
		return $return;

	$query_events = "SELECT id, text, date, starttime, endtime, linkid, socid FROM acts_events WHERE (acts_events.date<='$sqlDate2' AND acts_events.date >= '$sqlDate1')";
	if(isset($_GET['socid']) && is_numeric($_GET['socid'])) $query_events.=" AND acts_events.socid=".$_GET['socid'];
	$result = sqlQuery ($query_events, $adcdb) or die (mysql_error());
	while ($row_event = mysql_fetch_assoc ($result)) $return['events']["i" . $row_event['id']] = $row_event;

	$query_applications = "SELECT socid, deadlinedate FROM acts_applications WHERE (acts_applications.socid > 0) AND (acts_applications.deadlinedate<='$sqlDate2' AND acts_applications.deadlinedate>='$sqlDate1' AND acts_applications.deadlinedate >=NOW())";
	if(isset($_GET['socid']) && is_numeric($_GET['socid'])) $query_applications.=" AND acts_applications.socid='".$_GET['socid']."'";
	$result = sqlQuery ($query_applications, $adcdb) or die (mysql_error());
       
	while ($row_application = mysql_fetch_assoc ($result)) $return['applications']["i" . $row_application['socid']] = $row_application;

	$query_auds = "SELECT date, acts_auditions.id id FROM acts_auditions, acts_shows WHERE (acts_auditions.date<='$sqlDate2' AND acts_auditions.date>='$sqlDate1' AND acts_auditions.date>=NOW() AND acts_auditions.nonscheduled=0 AND acts_auditions.showid=acts_shows.id)";
	if (isset ($_GET['socid']) && is_numeric($_GET['socid']))
	{
		$rowsoc=getsocrow($_GET['socid']);
		$socname=$rowsoc['name'];
		$shortname=$rowsoc['shortname'];
		$query_auds.= " AND (acts_shows.socid=".$_GET['socid']." OR acts_shows.society LIKE \"%".addslashes($socname)."%\" OR acts_shows.society LIKE \"%".addslashes($shortname)."%\")";
	}
				      
	/* FIXME: socid? */
	$result = sqlQuery ($query_auds, $adcdb) or die (mysql_error());
	while ($row_aud = mysql_fetch_assoc ($result)) $return['auditions']["i" . $row_aud['id']] = $row_aud;

	return $return;
	/* And now there is no excuse for you to do any more sql. Bwahahaha. */
}

/**
  * Count shows between two dates
  * @param $startdate Lower bound of date
  * @param $enddate Upper bound of date - defaults to camdram "end of time"
  * @return Count of shows
*/
function eventCount($startdate, $enddate="2033-12-31") {
	$count=0;
	global $adcdb;
	$startdate = date ("Y-m-d", strtotime($startdate));
	$enddate = date ("Y-m-d", strtotime($enddate));
	$query="SELECT DISTINCT sid FROM acts_performances WHERE startdate<'$enddate' AND enddate>'$startdate' AND (excludedate!='$enddate' OR excludedate!='$startdate')";
	$result=sqlQuery($query,$adcdb) or die(sqlEr());
	$count+=mysql_num_rows($result);
	return $count;
	
}

/**
  Gets times for a specified show on a specified dat
  @param $bigbadblob the return value from getAllEvents()
  @param $show - the show in question (ie. $bigbadblob['shows'][$x])
  @param $date - the date in question (in normal php format)
  @return Array of show times
*/
function getShowTimesOnDay ($bigbadblob, $show, $date)
{
	$return = array ();
	for ($timeinfo = $show['times']; $timeinfo != -1; $timeinfo = $bigbadblob['richtimeinfo'][$timeinfo]['next'])
	{
		if (strtotime($bigbadblob['richtimeinfo'][$timeinfo]['startdate']) <= strtotime($date) && strtotime($bigbadblob['richtimeinfo'][$timeinfo]['enddate']) >= strtotime($date) && strtotime($bigbadblob['richtimeinfo'][$timeinfo]['excludedate']) != strtotime($date))
		{
		$return[] = $bigbadblob['richtimeinfo'][$timeinfo]['time'];
		}
	}

	return $return;
}

/**
  * Internal function decides which of the 2 parameters has a greater time
  * @param $row1 show row from db
  * @param $row2 show row from db
  * @return 1 or -1 depending on which time is greater
  * @protected
*/
function _sorttimes ($row1, $row2)
{
	$first = strcmp ($row1['time'], $row2['time']);
	if ($first) return $first;
	elseif (strtotime($row1['startdate']) < strtotime($row2['startdate'])) return -1;
	else return 1;
}

/**
  * Gets timing information for a show
  * @param $events Output from getAllEvents
  * @param $date1 Lower bound of dates to search
  * @param $date2 Upper bound of dates to search
  * @return An array of associative arrays that contain information about the timing of a show, selecting 
  * @return only events between $date1 and $date2. Essentially it extracts useful information from the rich time info in $events.
  * @return
  * @return Each associative array element represents a separate continuous run of shows. The array is ordered; the highest level
  * @return of ordering is to sort by show time, and within each time the elements are ordered by start date.
  * @return
  * @return The keys in the associative array are 'startdate', 'enddate', 'time', 'show'
  * @return 'show' is exactly the same as the corresponding output of getAllEvents
*/
function getShowTimesArray ($events, $date1, $date2)
{

	$times = array();
	$times_startdates = array();
	$times_times = array();

	foreach ($events['shows'] as $show)
	{
		for ($timeinfo = $show['times']; $timeinfo != -1; $timeinfo = $events['richtimeinfo'][$timeinfo]['next'])
		{
			/* if we are inside the frame of interest */
			if ((strtotime($events['richtimeinfo'][$timeinfo]['startdate']) >= $date1 && strtotime($events['richtimeinfo'][$timeinfo]['startdate']) <= $date2) || (strtotime($events['richtimeinfo'][$timeinfo]['enddate']) >= $date1 && strtotime($events['richtimeinfo'][$timeinfo]['enddate']) <= $date2) || (strtotime($events['richtimeinfo'][$timeinfo]['startdate']) <= $date1 && strtotime($events['richtimeinfo'][$timeinfo]['enddate']) >= $date2))
			{
			/* if there are no excludes */
				if (strtotime($events['richtimeinfo'][$timeinfo]['excludedate']) <= strtotime($events['richtimeinfo'][$timeinfo]['startdate']) || strtotime($events['richtimeinfo'][$timeinfo]['excludedate']) >= strtotime($events['richtimeinfo'][$timeinfo]['enddate']))
				{
					$index = sizeof ($times);
					$times[$index]['startdate'] = $events['richtimeinfo'][$timeinfo]['startdate'];
					$times[$index]['enddate'] = $events['richtimeinfo'][$timeinfo]['enddate'];
					$times[$index]['time'] = $events['richtimeinfo'][$timeinfo]['time'];
					$times[$index]['venue'] = $events['richtimeinfo'][$timeinfo]['venue'];
					$times[$index]['show'] = $show;
					$times_startdates[$index] = strtotime($times[$index]['startdate']);
					$times_times[$index] = strtotime($times[$index]['time']);
				}
				else
				{
					$index = sizeof ($times);
					$excludedate = strtotime($events['richtimeinfo'][$timeinfo]['excludedate']);
					/* frame of interest */
					if ((strtotime($events['richtimeinfo'][$timeinfo]['startdate']) >= $date1 && strtotime($events['richtimeinfo'][$timeinfo]['startdate']) <= $date2) || (strtotime(date("Y-m-d", mktime (0,0,0,date("m",$excludedate) ,date("d",$excludedate)-1,date("Y",$excludedate)))) >= $date1 && strtotime(date("Y-m-d", mktime (0,0,0,date("m",$excludedate) ,date("d",$excludedate)-1,date("Y",$excludedate)))) <= $date2) || (strtotime($events['richtimeinfo'][$timeinfo]['startdate']) <= $date1 && strtotime(date("Y-m-d", mktime(0,0,0,date("m",$excludedate), date("d", $excludedate)-1,date("Y",$excludedate)))) >= $date2))
					{
						$times[$index]['startdate'] = $events['richtimeinfo'][$timeinfo]['startdate'];
						$times[$index]['enddate'] = date("Y-m-d", mktime (0,0,0,date("m",$excludedate) ,date("d",$excludedate)-1,date("Y",$excludedate)));;
						$times[$index]['time'] = $events['richtimeinfo'][$timeinfo]['time'];
						$times[$index]['venue'] = $events['richtimeinfo'][$timeinfo]['venue'];
						$times[$index]['show'] = $show;
						$times_startdates[$index] = strtotime($times[$index]['startdate']);
						$times_times[$index] = strtotime($times[$index]['time']);
					}

					/* frame of interest */
					if ((strtotime($events['richtimeinfo'][$timeinfo]['enddate']) >= $date1 && strtotime($events['richtimeinfo'][$timeinfo]['enddate']) <= $date2) || (strtotime(date("Y-m-d", mktime (0,0,0,date("m",$excludedate) ,date("d",$excludedate)+1,date("Y",$excludedate)))) >= $date1 && strtotime(date("Y-m-d", mktime (0,0,0,date("m",$excludedate) ,date("d",$excludedate)+1,date("Y",$excludedate)))) <= $date2) || (strtotime(date("Y-m-d", mktime (0,0,0,date("m",$excludedate) ,date("d",$excludedate)+1,date("Y",$excludedate)))) <= $date1 && strtotime($events['richtimeinfo'][$timeinfo]['enddate']) >= $date2))
					{
						$index = sizeof ($times);
						$times[$index]['startdate'] = date("Y-m-d",mktime (0,0,0,date("m",$excludedate) ,date("d",$excludedate)+1,date("Y",$excludedate)));
						$times[$index]['enddate'] = $events['richtimeinfo'][$timeinfo]['enddate'];
						$times[$index]['time'] = $events['richtimeinfo'][$timeinfo]['time'];
						$times[$index]['venue'] = $events['richtimeinfo'][$timeinfo]['venue'];
						$times[$index]['show'] = $show;
						$times_startdates[$index] = strtotime($times[$index]['startdate']);
						$times_times[$index] = strtotime($times[$index]['time']);
					}
				}
			}
		}
	}

	uasort ($times, "_sorttimes");
	return $times;
}

/**
  * Display auditions and deadlines for a specified date
  * @param $events the return value from getAllEvents()
  * @param $date the date in question
  * @return none
*/
function makeAuditionsAndDeadlines ($events, $date)
{
	$mainbuild = "";
	foreach ($events['auditions'] as $auditions) 
	{
		if (strtotime($auditions['date']) == $date)
		{
			$mainbuild .="<div class=\"applications\">".startLink(69)."<b>Auditions</b> (click)</a></div>";
			break;
		}
	}


	if(sizeof ($events['applications'])>0) {
		$buildtext="";
		$first = true;
		foreach ($events['applications'] as $application)
		{
			if (strtotime($application['deadlinedate']) == $date)
			{
				$socid = $application[socid];
				if(!$first)
					$buildtext.= ", ";
				else {
					$buildtext.=MakeLinkText(205,"Director/Producer Applications Deadline",array("BASENAME"=>$application[deadlinedate]));
					$buildtext.="</strong> (";
				}
				$first = false;
				$socRow = getSocRow($application[socid]);
				$buildtext.= $socRow[shortname];
			}
		}
		if ($buildtext != "")
		{
			$mainbuild .="<div class=\"applications\"><strong>$buildtext)</div>";
		}
	}
	return $mainbuild;
}

/**
  * Display header for Diary week row
  * @param $weekno Week Number in term
  * @param $row_term Row from acts_terms about current term
  * @param $currentday Start of week
  * @param $renderfor Date to highlight (normally today)
  * @return none
*/
function putDiaryTableHeader ($weekno, $row_term, $currentday, $renderfor)
{
?>
	<table class = "maindiary">
	<thead>
	<tr>
        <td class="diary" colspan="7"><?php if($weekno>=$row_term['firstweek'] && $weekno<=$row_term['lastweek']) { echo("<b>".$row_term['name']); if ($row_term['displayweek'] == 1) echo " Week $weekno</b>"; } else echo "&nbsp;</b>"; ?></td>
	</tr>
	<tr>
	<?php 
	$currentday = mktime (0,0,0,date("m",$currentday), date("d", $currentday),
	date("Y", $currentday));
	for($n=1;$n<=7;$n++)
	{
		?><td class="<?php if($currentday==$renderfor) echo 'todayheader'; else echo 'dayheader'; ?>"><?php 
		echo date('D d M',$currentday); 
		$currentday = mktime (0,0,0,date("m",$currentday) ,date("d",$currentday)+1,date("Y",$currentday));
		?></td><?php
   }
   ?> </tr>
   </thead>
   <tbody>
<?php
}


/**
  * Close table that makes a week in the diary
  * @return none
*/
function putDiaryTableFooter ()
{
   ?>
    </tr>
	</tbody>
	</table>
	<?php
}



/* ############ STYLE 0 FUNCTIONS ################### */
/**
  * Display events for a specific date for style 0
  * @param $date date to display for
  * @param $show_blacklist shows to ignore
  * @return none
  * @deprecated
*/
function putEvents_style0($date, $show_blacklist=array())
{
	global $adcdb,$theme,$currentbase;
	$events = getAllEvents ($date, $date, $show_blacklist);
	foreach ($events['events'] as $event)
  {
		$create="<div class=\"event\">";
		if(hasEquivalentToken('society',$event[socid])) $create.= "<span class=\"editme\">".startLink(92,array("editid"=>$event[id]))."<img src=\"$currentbase/furniture/".$theme."/editme.gif\" width=\"10\" height=\"10\" alt=\"Edit ".htmlspecialchars($event[text])."\"></a></span>";
		$t=$event['text'];
		$t="$t";
		if($event['starttime']<>"00:00:00")
		{
			$st=timeFormat(strtotime($event['starttime']),strtotime($event['endtime']));
			$create.="<b>$st</b><br>";
		} 

		if($event['linkid']<0) $t=startLink(-$event['linkid']).$t."</a>";
		else $t=startLink(112,array("eventid"=>$event['id'])).$t."</a>";

		$create="$create$t<br></div>";

		$EventInfo[$max_ev_id]=$create;
		$EventTime[$max_ev_id]=$event['starttime'];
		$max_ev_id++;
	}
	foreach ($events['shows'] as $show)
	{
		$times = getShowTimesOnDay ($events, $show, date ("Y/m/d", $date) );
		foreach ($times as $time)
		{
			$st=timeFormat(strtotime($time),0);
			$create = "<div class=\"show\">";
			if (hasEquivalentToken('show', $show['id'])) $create .= "<span class=\"editme\">".startLink(79,array("showid"=>$show[id]))."<img src=\"$currentbase/furniture/".$theme."/editme.gif\" width=\"10\" height=\"10\" alt=\"Edit ".htmlspecialchars($show['title'])."\"></a></span>";
			if ($time != "00:00:01") $create .= "<b>$st</b><br />"; else $create.="<b>time unknown</b><br />";
      	$create.=startLink(104,array(), true, 0, "", false, $show[ref], array("showid"=>$show[id])) . $show['title'] . "</a></div>";
			$EventInfo[$max_ev_id]=$create;
			$EventTime[$max_ev_id]=$time;
			$max_ev_id++;
		}
	}
  
	echo makeAuditionsAndDeadlines($events, $date);
  
	if($max_ev_id==0)
	{
		echo("&nbsp;");
	}
	else
	{
		asort($EventTime);
		while (list($key, $val) = each($EventTime)) 
		{		
			echo($EventInfo[$key]);
		}
	}
}

/**
  * Display a week in style 0
  * @param $row_term Term to display week for
  * @param $wkoffset Current week to display
  * @param $renderfor Date to highlight (normally Today)
  * @param $ignorepast Whether or not to display shows in the past
  * @param $show_blacklist Shows to ignore
  * @return none
  * @deprecated
*/
function putWeek_style0($row_term,$wkoffset,$renderfor,$ignorepast=false,$show_blacklist=array ())
{
	$week0=strtotime($row_term['startdate']);
	$renderfor = mktime(0,0,0,date("m",$renderfor),date("d",$renderfor),date("Y",$renderfor));
	$dayofweek=date('w',$renderfor);
	if($dayofweek==0) $dayofweek=7;// display sundays correctly!
	$startofweek = strtotime("-". $dayofweek + 1 ." days",$renderfor);	// start of this week
	if($wkoffset>-2)
	{
		// rendering specific week
		$xsow=$startofweek;
		$startofweek =  mktime (0,0,0,date("m",$week0) ,date("d",$week0)+7*$wkoffset,date("Y",$week0));
		if($startofweek<$xsow && $ignorepast) return;
	}

	$currentday = $startofweek;
	$row_term = whatTerm ($currentday, true); // Warning: row_term is potentially rewritten here
	$week0 = strtotime($row_term['startdate']);
	$weekno=date("W",$startofweek)-date("W",$week0);

	putDiaryTableHeader ($weekno, $row_term, $currentday, $renderfor);
	?> <tr> <?php

	for($n=1;$n<=7;$n++)
	{
		?>
		<td class="diaryevents">
		<?php
		putEvents_style0($currentday, $show_blacklist);
		$currentday = mktime (0,0,0,date("m",$currentday) ,date("d",$currentday)+1,date("Y",$currentday));
		?> </td> 
		<?php
	}
   
	?> </tr> <?php
	putDiaryTableFooter ();
}

/* ############ STYLE 1 FUNCTIONS ################### */
/**
  * Display a week in style 1
  * @param $row_term Term to display week for
  * @param $wkoffset Current week to display
  * @param $renderfor Date to highlight (normally Today)
  * @param $ignorepast Whether or not to display shows in the past
  * @param $show_blacklist Shows to ignore
  * @return none
*/
function putWeek_style1($row_term,$wkoffset,$renderfor,$ignorepast=false,$show_blacklist = array())
{
 	global $adcdb, $currentbase, $theme; 
	$week0=strtotime($row_term['startdate']);
  
	$renderfor = mktime(0,0,0,date("m",$renderfor),date("d",$renderfor),date("Y",$renderfor));
  
  
	$dayofweek=date('w',$renderfor);
	if($dayofweek==0) $dayofweek=7;// display sundays correctly!
	$startofweek = strtotime("-". $dayofweek + 1 ." days",$renderfor);	// start of this week
  
	if($wkoffset>-2)
	{
  		// rendering specific week
		$xsow=$startofweek;
		$startofweek =  mktime (0,0,0,date("m",$week0) ,date("d",$week0)+7*$wkoffset,date("Y",$week0));
		if($startofweek<$xsow && $ignorepast) return;
	}

	$currentday = $startofweek;
	$row_term = whatTerm ($currentday, true); // Warning: row_term is potentially rewritten here
	$week0 = strtotime($row_term['startdate']);
  
	$weekno=date("W",$startofweek)-date("W",$week0);
	$endofweek = mktime (0,0,0,date("m",$currentday) ,date("d",$currentday)+6,date("Y",$currentday));
	$events = getAllEvents ($startofweek, $endofweek, $show_blacklist);
  
	putDiaryTableHeader ($weekno, $row_term, $currentday, $renderfor);
	
	$buildtext = "";
	for($n=1;$n<=7;$n++)
	{
		$dayAud = makeAuditionsAndDeadlines ($events, $currentday);
		if ($dayAud != "")
		{
			$buildtext .= "<td class=\"diaryevents applications\">$dayAud</td>\n";
			$audOrAppFound = 1;
		}
		else
		{
			$buildtext .= "<td></td>\n";
		}

		$currentday = mktime (0,0,0,date("m",$currentday) ,date("d",$currentday)+1,date("Y",$currentday));
	}
	if (isset($audOrAppFound))
	{
		echo "<tr>$buildtext</tr>\n";
	}
	if (count($events['events']) > 0)
	{
		print "<tr>\n";
		$currentday = $startofweek;
		for($n=1;$n<=7;$n++)
		{
			$buildtext = "";
			foreach ($events['events'] as $key=>$event)
			{
				if (strtotime($event['date']) == $currentday && !isset($event['displayed']))
				{
					$events['events'][$key]['displayed']=1;
					$buildtext.="<div class=\"event\">";
					if(hasEquivalentToken('society',$event[socid]))
						$buildtext.="<span class=\"editme\">".startLink(92,array("editid"=>$event[id]))."<img src=\"$currentbase/furniture/".$theme."/editme.gif\" width=\"10\" height=\"10\" alt=\"Edit ".htmlspecialchars($event[text])."\"></a></span>";
					$t=$event['text'];
					$t="$t";
					if($event['starttime']<>"00:00:00")
					{
						$st=timeFormat(strtotime($event['starttime']),strtotime($event['endtime']));
						$buildtext.="<b>$st</b><br>";
					}
					if($event['linkid']<0)
						$t=startLink(-$event['linkid']).$t."</a>";
					else
						$t=startLink(112,array("eventid"=>$event['id'])).$t."</a>";
					$buildtext.="$t<br></div>";
				}
			}
			if ($buildtext != "")
			{
				print "<td class=\"diaryevents event\">$buildtext</td>\n";
			}
			else
			{
				print "<td></td>\n";
			}

			$currentday = mktime (0,0,0,date("m",$currentday) ,date("d",$currentday)+1,date("Y",$currentday));
		}
		print "</tr>\n";
	}
	
	$times = getShowTimesArray ($events, $startofweek, $endofweek);
	$first = true;
	$max_ev_id = 0;
	$total_end = 0;
	/* Generate each row of the diary. Rows comprise of one or more shows at a
     * particular time of day.
     */
    $column_index = 0;
    $column_date = $startofweek;
    foreach ($times as $time)
	{
        $column_date = $startofweek + ($column_index * 86400);

        $run_start_date = max($startofweek, strtotime($time['startdate']));
        $run_end_date = min($endofweek, strtotime($time['enddate']));
        // Conditional prevents an infinite loop being possible.
        if (strtotime($time['startdate']) <= ($startofweek + (6 * 86400)))
        {
            while ($column_date != $run_start_date)
            {
                if ($column_index == 0)
                {
                    echo "<tr>\n";
                }
                echo "<td></td>\n";
                $column_index++;
                if ($column_index > 6)
                {
                    // Wrap to a new line.
                    echo "</tr>\n";
                    
                    $column_index = 0;
                }
                $column_date = $startofweek + ($column_index * 86400);
            }
        }
        $colspan = (($run_end_date - $run_start_date) / 86400) + 1;

        if ($column_index == 0)
        {
            // Start of new table row.
            echo "<tr>\n";
        }
        
        echo "<td colspan=\"$colspan\" class=\"diaryevents show\">\n";
        echo "\t<div class=\"show\" itemscope itemtype=\"http://data-vocabulary.org/Event\">\n";
		if (hasEquivalentToken('show', $time['show']['id']))
        {
	    // Commented out by Stumo - not sure what this does, $create is never used anywhere!
            // $create .= "\t\t<span class=\"editme\">".startLink(79,array("showid"=>$time['show']['id']))."<img src=\"$currentbase/furniture/".$theme."/editme.gif\" width=\"10\" height=\"10\" alt=\"Edit ".htmlspecialchars($time['show']['title'])."\"></a></span>";
        }
        
        // Add the show's start time
		if ($time['time'] != "00:00:01")
        {
		    $displayed_start_time =timeFormat(strtotime($time['time']),0);
        }
        else
        {
            $displayed_start_time = "time unknown";
        }
        echo '<strong><time itemprop="startDate" datetime="'
            .date(DATE_RFC3339, strtotime($time['time'], $column_date)).'">'
            ."$displayed_start_time</time></strong><br />\n";
        
        // Add the show's venue
		if ($time['venue'])
        {
			$venue = '<span class="diary_venue" itemprop="location">' . $time['venue'] . "</span><div class='clear'>&nbsp;</div>\n";
		}
		else
		{
			$venue = "";
		}
		echo substr(startLink(104,array(),true,0,"foo",false,$time['show']['ref'],array("showid"=>$time['show']['id'])), 0, -1).' itemprop="url">'
            .'<span itemprop="summary">'.$time['show']['title']."</span></a>".$venue;
        echo "\t</div>\n</td>\n";

        $column_index += $colspan;
        if ($column_index > 6)
        {
            echo "</tr>\n";
            $column_index = 0;
        }
     }
    // Make sure that the last row in the table is complete
    if ($column_index > 0)
    {
        while ($column_index < 7)
        {
            echo "<td></td>\n";
            $column_index++;
        }
    }

	putDiaryTableFooter ();
}

/**
  * Display a week, deciding which style to use
  * @param $row_term Term to display week for
  * @param $wkoffset Current week to display
  * @param $renderfor Date to highlight (normally Today)
  * @param $ignorepast Whether or not to display shows in the past
  * @param $style Style to use - if unspecified, use $_GET['style'], defaulting to style 0
  * @param $show_blacklist Shows to ignore
  * @return none
  * @note This function forwards to style 0 or style 1 depending on preference 
*/
function putWeek($row_term,$wkoffset,$renderfor,$ignorepast=false,$style=-1,$show_blacklist = array())
{
		putWeek_style1 ($row_term, $wkoffset, $renderfor, $ignorepast, $show_blacklist);
//		putWeek_style0 ($row_term, $wkoffset, $renderfor, $ignorepast, $show_blacklist);
}
