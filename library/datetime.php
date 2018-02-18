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

// DATE/TIME FUNCTIONS

function whatWeek($date)
{
  $term = whatTerm($date);
  $termstart=strtotime($row_term['startdate']);
  $offset=$date-$termstart;
  $wkn=date('W',$offset)-1;
  return $wkn;
}


function whatTerm($date, $returnvac=false)	// convert date to term (DB entry array, not text)
{
	if(is_string($date)) $date = strtotime($date);
	$sqlDate=date("Y/m/d",$date);
	$ickle = "SELECT * FROM acts_termdates WHERE acts_termdates.enddate>'$sqlDate' AND acts_termdates.startdate <= '$sqlDate' ORDER BY acts_termdates.enddate-acts_termdates.startdate LIMIT 1";
	$icklequery = sqlQuery($ickle) or die(mysql_error());
	if (mysql_num_rows ($icklequery) != 0) {
		$row_term = mysql_fetch_assoc ($icklequery);
	} else { // We're in a vacation - return vacation if shows remain, otherwise return next term.	
		$go_large = "SELECT * FROM acts_termdates WHERE acts_termdates.enddate > '$sqlDate' ORDER BY acts_termdates.startdate, acts_termdates.enddate, acts_termdates.id LIMIT 1";
		$largequery = sqlQuery ($go_large) or die (mysql_error());
		$query_lastterm="SELECT * FROM acts_termdates WHERE acts_termdates.enddate < '$sqlDate' ORDER BY acts_termdates.startdate DESC, acts_termdates.enddate DESC, acts_termdates.id DESC  LIMIT 1";
		$lastquery= sqlQuery($query_lastterm) or die(sqlEr());
		$row_term = mysql_fetch_assoc($lastquery);
		$row_nextterm = mysql_fetch_assoc ($largequery);
		mysql_free_result ($largequery);
		if (eventCount(date("Y-m-d",time()))==0 || !$returnvac) { // No shows remain, return next term
			$row_term=$row_nextterm;
		}
		else { // Shows return, return vacation. Yes this is crackful
			$row_term['startdate']=date("Y-m-d",strtotime($row_term['enddate'])+24*60*60);
			$row_term['enddate']=date("Y-m-d",strtotime($row_nextterm['startdate'])-24*60*60);
			$row_term['name']=$row_term['vacation'];
			$row_term['friendlyname']=$row_term['vacation'];
			$row_term['displayweek']=0;
			$row_term['firstweek']=0;
			$row_term['lastweek']=floor((strtotime($row_term['enddate'])-strtotime($row_term['startdate']))/(7*24*60*60));
			$row_term['vacation']="";
			$row_term['id']=$row_term['id']."vac";
		}
	}

	mysql_free_result($icklequery);

	return $row_term;
	
}

function getTermRow($termid) {
	if (preg_match("/vac/",$termid)) {
		preg_match("/(.*)vac/",$termid,$id);
		$termid=$id[1];
		$row_term=getTermRow($termid);
		$query_nextterm = "SELECT * FROM acts_termdates WHERE acts_termdates.enddate > '".$row_term['enddate']."' ORDER BY acts_termdates.startdate, acts_termdates.enddate, acts_termdates.id LIMIT 1";
		$nexttermres=sqlQuery($query_nextterm) or die(sqlEr());
		$row_nextterm=mysql_fetch_assoc($nexttermres);
		$row_term['startdate']=date("Y-m-d",strtotime($row_term['enddate'])+24*60*60);
		$row_term['enddate']=date("Y-m-d",strtotime($row_nextterm['startdate'])-24*60*60);
		$row_term['name']=$row_term['vacation'];
		$row_term['friendlyname']=$row_term['vacation'];
		$row_term['displayweek']=0;
		$row_term['firstweek']=0;
		$row_term['lastweek']=floor((strtotime($row_term['enddate'])-strtotime($row_term['startdate']))/(7*24*60*60));
		$row_term['vacation']="";
		$row_term['id']=$row_term['id']."vac";
		return $row_term;
	}
	else {
		$query="SELECT * FROM acts_termdates WHERE id='$termid'";
		$res=sqlQuery($query) or die(sqlEr());
		return mysql_fetch_assoc($res);
	}
}

function dateByTerm($date)	// convert date to term and week number (text)
{

	$row_term=whatTerm($date);
	$termstart=strtotime($row_term['startdate']);
	$showstart=strtotime($date);
	$offset=$showstart-$termstart;
	$wkn=floor($offset/604800);
	$ret = "";
	if ($row_term['displayweek'] == 1) $ret = "Week " . $wkn . " ";
	$ret .= $row_term['friendlyname'];
	return $ret;
}
function datesByTerm($startdate,$enddate)	// convert dates to term and week number (text)
{
	$srow_term=whatTerm($startdate);
	$erow_term=whatTerm($enddate);
	if (isset($srow_term['startdate']) && ($srow_term['startdate']==$erow_term['startdate'])) {
		$termstart=strtotime($srow_term['startdate']);
		$showstart=strtotime($startdate);
		$offset=$showstart-$termstart;
		$swkn=floor($offset/604800);
		$offset=strtotime($enddate)-$termstart;
		$ewkn=floor($offset/604800);
		if (($swkn<0) or ($ewkn>9)) $ret="Out of term";
		else if ($srow_term['displayweek']==0) {
			$ret=$srow_term['friendlyname'];
		}
		else {
			if ($swkn!=$ewkn) $weektext="Weeks $swkn to $ewkn";
			else $weektext="Week $swkn";
			$ret=$weektext." ".$srow_term['friendlyname'];
		}
	}
	elseif (isset($srow_term['startdate']) && isset($erow_term['startdate']))
		$ret = dateByTerm($startdate) . " to " . dateByTerm($enddate);
	else
		$ret="Out of term";
	return $ret;
}



function dateFormat($date1,$date2=0,$shorten=false)    // format date or date range (date2=0 for single)
{
	
	if($date2==0 || date("jS M Y",$date1)==date("jS M Y",$date2))
	{
		$r=($shorten==true?date("jS M",$date1):date("D jS M",$date1));
		if(abs($date1-time())>5184000 && (date("Y",$date1)!=date("Y") || date("n")>11)) { 
		  // i.e. 1 month either way from now means the year goes without saying, otherwise it has to be in the same year
		  if($shorten==true) $r=date("M Y",$date1);
		  else $r.=date(" Y",$date1);
		  
		}
		
		return $r;
	} else {
		$dfs = "D jS";
		if(date("M",$date2)!=date("M",$date1))
			$dfs.=" M";
			
		if(date("Y",$date2)!=date("Y",$date1))
			$dfs.=" Y";
			
		$r=date($dfs,$date1)." - ".date("D jS M",$date2);
		if(date("Y",$date2)!=date("Y")) $r.=date(" Y",$date2);
		return $r;
	} 
		
}


function timeFormat($time1,$time2=0)	// format time or time range (time2=0 for single)
{
	if($time2==0 || date("Gi",$time2)==0)
	{
		if(date("i",$time1)>0) $st=date("g.ia",$time1);
			else $st=date("ga",$time1);
		if($st=="12pm") $st="noon";
	} else {
		if(date("a",$time1)<>date("a",$time2))
			$format="a"; else $format="";
		if(date("i",$time1)>0) $format="g.i$format";
			else $format="g$format";
		
		$st1=date($format,$time1);
		$st2=timeFormat($time2,0);
		$st="$st1 - $st2";
	}
	return $st;
}

function safestrtotime ($s) {
	$basetime = 0;
	if (preg_match ("/(\d\d\d\d)/", $s, $m) && ($m[1] < 1970)) {
		if( $m[1] < 1902 ) {
			return -1;
		}
		$s = preg_replace ("/19\d\d/", $m[1]+68, $s);
		$basetime = 0x80000000 + 1570448;
	}
	$t = strtotime( $s );
	return $t == -1 ? -1 : $basetime + $t;
}

?>
