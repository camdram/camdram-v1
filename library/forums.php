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

return;

global $canpost, $canedit;
if (logindetails(false,false,false,true)) {
	$canpost=true;
	if (hasEquivalentToken('security',-3)) {
		$canedit=true;
	}
}

function determineAncestor($message)
{
  $parent = $message[replyid];
  if($parent==0) return 0;
 
  
  $query = "SELECT * FROM acts_forums_messages WHERE id='".$parent."' LIMIT 1";
  $r = sqlQuery($query) or die(sqlEr());
  if($r>0) {
    $row = mysql_fetch_assoc($r);
    mysql_free_result($r);
   
    if($row[replyid]==0) return $row[id]; else return determineAncestor($row);
  }
  return -1;
}

function forumTime($time)
{
  if(is_string($time)) $time = strtotime($time);
  $today = strtotime("00:00 Today");
  $yesterday = $today - 86400;
  $dow = date("w",$today);
  
  $startofweek = $today - 86400*$dow;
  $lastweek = $today - 86400*6;

  if ($time>$today) return timeFormat($time).", Today";
  if ($time>$yesterday) return timeFormat($time).", Yesterday";
  if ($time>$startofweek) return timeFormat($time).", ".date("l",$time);
  if ($time>$lastweek) return timeFormat($time).", last ".date("l",$time);
  return dateFormat($time);
}

function displayMessage($row)
{
  global $canpost, $canedit;
  echo("<table class=\"forumpost\">");
  echo "<tr><td class=\"info\"><strong>".getUserString($row[uid],true)."</strong><br/><small>".forumTime($row[datetime]);
  
  if ($canpost) {
    echo "<br/><br/>[";
    makelink(157,"Click to Reply",array("replyto"=>$row[id]));
    if ($canedit) {
      echo " | ";
      $submitid=allowSubmission();
      makeLink(155,"Delete",array("delid"=>$row[id],"submitid"=>$submitid));
    }
    echo "]";
  }
  
  echo "</small></td><td class=\"post\">";
  echo preprocess($row[message]);
  echo("</td></tr></table>");
	
}
function maketree($parent,$start=0,$number=0,$thread_messages=1) {
	global $adcdb;
	global $forum;
	global $canedit;
	
	if ($number>0) {
		$limittext=" LIMIT $start,$number";
	}
	$query="SELECT t1.datetime AS datetime, t1.lastpost AS lastpost, t1.id AS id, t1.replyid AS replyid, t1.forumid AS forumid, t1.uid AS uid, t1.subject AS subject,t1.message AS message,t2.id AS userid FROM acts_forums_messages AS t1,acts_users AS t2 WHERE uid=t2.id AND replyid=$parent AND forumid=$forum ORDER BY lastpost DESC $limittext";
	$result=sqlQuery($query,$adcdb) or die(mysql_error());
	if (mysql_num_rows($result)>0) {
		if($thread_messages == 1) echo "<ul>\n"; else { 
		  echo "<table class=\"dataview\">";
		  echo "<tr><th>Topic</th><th>Posts</th><th>Most Recent Post</th><th>Started</th></tr>";
		}	
		while ($row=mysql_fetch_array($result)) {
		  if ($row[subject]=="") {
		    $row[subject]="(No Subject)";
		  }
		  if($thread_messages==1) {

			echo "<li><strong>";
			makeLink(155,$row[subject],array("CLEARALL"=>"CLEARALL","forumid"=>$forum,"page"=>$_GET[page],"message"=>$row[id]));
			echo "</strong> - ".getUserName($row[uid]);
			if ($canedit) {
				echo " - ";
				$submitid=allowSubmission();
				makeLink(155,"Delete",array("delid"=>$row[id],"submitid"=>$submitid));
			}
			maketree($row[id],0,0);
			echo "</li>";
		  } else {
		    echo "<tr>";
		    echo "<td>";
		    makeLink(155,$row[subject],array("CLEARALL"=>"CLEARALL","forumid"=>$forum,"page"=>$_GET[page],"message"=>$row[id]));
		    echo "</td>";
		    $others_query = "SELECT * FROM acts_forums_messages WHERE ancestorid = ".$row[id]." ORDER BY datetime DESC";
		    $others_res = sqlQuery($others_query);
		    if($others_res>0) {
		      $num = mysql_num_rows($others_res)+1;
		      echo "<td>$num</td>";
		      if($num>1) {
			$row2 = mysql_fetch_assoc($others_res);
			
			echo "<td>".getUserString($row2[uid])."<br/><small>".forumTime($row2[datetime])."</small></td>";
			
		      } else echo "<td><small>(No replies)</small></td>";
		      echo "<td>".getUserString($row[userid])."<br/><small>".forumTime($row[datetime])."</small></td>";
		      mysql_free_result($others_res);
		    } else echo "<td colspan=\"3\">Error retrieving this thread</td>";
		    echo "</tr>";
		  }
		}
		if($thread_messages==1) echo "</ul>"; else echo "</table>";
	}
}

function userwantsemail($uid) {
	global $adcdb;
	$query="SELECT * FROM acts_users WHERE id=$uid";
	$result=sqlQuery("SELECT * FROM acts_users WHERE id=$uid");
	if ($row=mysql_fetch_assoc($result)) {
		if ($row['forumnotify']==1) return true; else return false;
	}
	else return false;
}
?>
