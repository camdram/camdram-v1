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

global $adcdb;


function fromField($from,$uid) {
?>
 
  <select name="from">
		<option value="0" <?php if ($from==0) echo "selected";?>>
		<?php echo getUserName($_SESSION['userid']);?>
		(<?php echo getUserEmail($_SESSION['userid']) ?>)</option>
		<?php
		$query_froms="SELECT * FROM acts_email_aliases WHERE uid=$uid OR id='$from'";
		$result=sqlQuery($query_froms,$adcdb) or die(mysql_error());
		while($row=mysql_fetch_assoc($result)) {
			$name=$row['name'];
			$email=$row['email'];
			$id=$row['id'];
			$fullemail=($name=="")?$name:"$name ($email)";
			echo "<option value=\"$id\"";
			if ($from==$id) echo " selected";
			echo ">$fullemail</option>\n";
		}
 ?></select><?php 
}

function generateTechItem($showid) { // Takes a Show ID and returns an email article with the production team advert.
	global $adcdb;
	$query_show="SELECT * FROM acts_shows WHERE id=$showid";
	$q=sqlQuery($query_show) or die(mysql_error());
	if ($show=mysql_fetch_assoc($q)) {
	  $startDate=getEarliestDate($show);
	  $endDate=getLatestDate($show);
		$termweek=datesByTerm($startDate,$endDate);
		
		if ($termweek!="Out of term") $build="$termweek\n";
		$build=$build.showHeader($show);
		$query_techies="SELECT * FROM acts_techies WHERE showid=$showid AND expiry>=CURDATE()";
		$q=sqlQuery($query_techies) or die(mysql_error());
		if ($techie=mysql_fetch_assoc($q)) {
			$build="$build\n\nSeeks:\n".strtoupper(unhtmlentities($techie['positions']))."\n";
			if ($show['description'] !="") {
				$description=explode("\n\n",str_replace("\r","",trim($show['description'])));
				$build=$build."\n";
				foreach ($description as $line) {
					$build=$build.$line."\n\n";
					if (strlen($line) > 150) break;
				}
				$build=rtrim($build)."\n";
			}
			if ($techie['techextra'] !="") $build="$build\n".$techie['techextra']."\n";
			if ($techie['contact'] !="") $build="$build\n".$techie['contact']."\n";
			if ($techie[deadlinetime]!="") $deadlinetime=", $techie[deadlinetime]";
			if ($techie['deadline'] ==1) $build="$build\nDeadline for applications is ".date("l jS F",strtotime($techie[expiry])).$deadlinetime."\n";
			$build=strip_tags(preprocess($build));
		}
	}
	$build=rtrim($build);
	$build=unhtmlentities($build);
	return $build;
}

function generateInfoItem($showid) { // Takes a Show ID, and returns an email article to advertise it
	global $adcdb;
	$query_show="SELECT * FROM acts_shows WHERE id=$showid";
	$q=sqlQuery($query_show) or die(mysql_error());
	if ($show=mysql_fetch_assoc($q)) {
		$build=showHeader($show);
		if ($show['prices'] !="") $build=$build."\nTickets ".$show['prices']."\n";
		if ($show['description'] !="") $build="$build\n\n".$show['description']."\n";
  		if(strtotime(getLatestDate($show))>time() && $show['bookingcode']!="") $build=$build."\nOnline booking available for this show at http://www.adctheatre.com/show.asp?code=".$show[bookingcode]."\n"; 
		$build=strip_tags(preprocess($build));
	}
	$build=rtrim($build);
	$build=unhtmlentities($build);
	return $build;
}

function generateActorItem($showid) { // Takes a Show ID, and returns an email article detailing auditions
	global $adcdb;
	$query_show="SELECT * FROM acts_shows WHERE id=$showid";
	$q=sqlQuery($query_show) or die(mysql_error());
	if ($show=mysql_fetch_assoc($q)) {
		 $startDate=getEarliestDate($show);
	  $endDate=getLatestDate($show);
	  $termweek=datesByTerm($startDate,$endDate);
	  
	  if ($termweek!="Out of term") $build="$termweek\n";
	  $build=$build.showHeader($show)."\n\n";
	  $query_auditions="SELECT * FROM acts_auditions WHERE showid=$showid AND date>=CURDATE() ORDER BY nonscheduled, date,starttime,endtime,location";
	  $q=sqlQuery($query_auditions) or die(mysql_error());
	  $publish_dates = 0;
	  $auditiondates="";
	  while ($audition=mysql_fetch_assoc($q)) {
	    if ($audition['nonscheduled']==0) {
	      $auditiondates=$auditiondates.date("D jS M",strtotime($audition['date']))." ".date("g:ia",strtotime($audition['starttime']))." - ".date("g:ia",strtotime($audition['endtime'])).", ".$audition['location']."\n";
		  $publish_dates = 1;
	    }
	    else {
			if(preg_match("/\S/",$audition['location'])){				
					$auditiondates=$auditiondates."At " . $audition['location']."\n";  // just plain locations for non-scheduled auditions;
					$publish_dates =1;
			}

	    }
	  }	
	  if($publish_dates){  // we generated at least one non-whitespace character in the above
		  $build=$build . "Is holding auditions:\n$auditiondates";
	  }
	  if ($show['audextra'] !="") $build="$build\n".$show['audextra']."\n";
	  if ($show['description'] !="") {
	    $description=explode("\n\n",str_replace("\r","",trim($show['description'])));
	    $build="$build\n".rtrim($description[0])."\n";
	  }
	  $build=strip_tags(preprocess($build));
	}
	$build=rtrim($build);
	$build=unhtmlentities($build);
	return $build;
}

function generateEventItem($eventid) { // Takes an event ID, and returns text for an email to advertise an event 
	$query_event="SELECT * FROM acts_events WHERE id=$eventid";
	$q=sqlQuery($query_event) or die(mysql_error());
	if ($event=mysql_fetch_assoc($q)) {
		$query_events="SELECT * FROM acts_events WHERE linkid=$eventid AND id<>$eventid";
		$q=sqlQuery($query_events) or die(mysql_error());
		$build=strtoupper(unhtmlentities($event['text']))."\n".date("D jS M", strtotime($event['date']))." ".date("g:ia",strtotime($event['starttime']))." - ".date("g:ia",strtotime($event['endtime']))."\n";
		while ($subevent=mysql_fetch_assoc($q)) {
			$build=$build.strtoupper(unhtmlentities($subevent['text']))."\n".date("D jS M", strtotime($subevent['date']))." ".date("g:ia",strtotime($subevent['starttime']))." - ".date("g:ia",strtotime($subevent['endtime']))."\n";
			$descripbuild=$descripbuild."\n\n".$subevent['description'];
		}
		$build=$build."\n".$event['description'];
		$build=strip_tags(preprocess($build));
	}
	$build=rtrim($build);
	$build=unhtmlentities($build);
	return $build;
}

function generateApplicationItem($appid) {
	$query_application="SELECT * FROM acts_applications WHERE id=$appid";
	$q=sqlQuery($query_application) or die(mysql_error());
	if ($app=mysql_fetch_assoc($q)) {
		if ($app['showid']>0) {
			$build=showHeader(getshowrow($app['showid']));
		}
		else {
			$socrow=getsocrow($app['socid']);
			$build=$socrow['name'];
		}
		$build=$build."\n\n".$app['text']."\n\n";
		$build=$build.preprocess($app['furtherinfo']);
		if ($app[deadlinetime]!="") $deadlinetime=", $techie[deadlinetime]";
		$build="$build\n\nDeadline for applications is ".date("l jS F",strtotime($techie[expiry])).$deadlinetime."\n";
		$build=strip_tags($build);
		return $build;
	}
}

function showHeader($show) {  // Generates a header for an email item - $show is an associative array with data from the acts_shows table
	$socname=societyName($show);
	$build = "";
	if ($socname!="") {
		$build .= $socname." ".present($socname);
	}
	if ($build!="") $build="$build\n";
	$build=$build.strtoupper(unhtmlentities($show['title']))."\n";
	if ($show['author']!="") $build=$build."by ".$show['author']."\n";
	$build=$build.nl2br(showPerfs($show, false, false, "", "", false));  //Defaults - except for suppress venue links

	$build=$build."\n";
	
	$build=rtrim($build);
	$build=unhtmlentities($build);
	return $build;
}

function toc($emailid) {
	global $adcdb;
	$query="SELECT title FROM acts_email_items WHERE emailid=$emailid AND title<>'' ORDER BY orderid";
	$q=sqlQuery($query,$adcdb) OR die(mysql_error());
	$i=0;
	while($row=mysql_fetch_assoc($q)) {
		$i++;
		if ($i > 1) {
			$return .= "\n";
		}
		$return.="$i. ". $row['title'];
	}
	return $return;
}

?>
