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

function putDateRange($performance)
{
  echo("<daterange id=\"".$performance[id]."\">");
  echo("<startdate>");
  echo $performance['startdate'];
  echo("</startdate>");
  echo("<enddate>");
  echo $performance['enddate'];
  echo("</enddate>");
  if($performance['time']!="00:00:00")
    {
      echo("<time>");
      echo $performance['time'];
      echo("</time>");
    } else echo ("<time />");
  echo "<venue>".htmlspecialchars(venueName($performance) )."</venue>";
  echo("</daterange>");
  
}
require_once("config.php");
require_once("library/adcdb.php");

require_once("library/lib.php");


// a simple program to output an XML document with shows matching the given query

require_once("library/showfuns.php");
header('Content-type: text/xml');
$mode=3; // external

// BUILD FILTER

$filter="";

$lt=$_GET['fetchtype'];

$param_list=array('startday','startmonth','startyear','endday','endmonth','endyear', 'title', 'perpage', 'page','category','syndicateid','socid');

	
if(!isset($defaults))
{
	$defaults['startyear']=1995;
	$defaults['startmonth']=1;
	$defaults['startday']=1;
	$defaults['endyear']=2012;
	$defaults['endmonth']=1;
	$defaults['endday']=1;
	$defaults['perpage']=10;
	$defaults['socid']=0;
	$defaults['category']='';
}

foreach($param_list as $param)
{
	if(isset($_GET[$param]))
	{
		@$$param=mysql_real_escape_string($_GET[$param]);
		$defaults[$param]=mysql_real_escape_string($_GET[$param]);
	} else {
		@$$param=$defaults[$param];
		$_GET[$param]=$defaults[$param];
	}
}

$start="$startyear-$startmonth-$startday";
$end="$endyear-$endmonth-$endday";
$filter="WHERE acts_performances.enddate>'$start' AND acts_performances.enddate<'$end' AND acts_shows.authorizeid>0 AND acts_shows.entered=1 AND acts_performances.sid=acts_shows.id";

// if(md5($syndicateid)!="3121a4c07785191b2a2b62ae63de2d2d" && md5($syndicateid)!="a1b27f00bbe99010ac0d0a40a2945501") $filter.=" AND id=0";
if(isset($title)) $filter.=" AND(title LIKE '%$title%' OR society LIKE '%$title%' OR acts_societies.shortname LIKE '%$title%' OR author LIKE '%$title%') ";
if(strlen($category)>0) $filter.=" AND category LIKE '%$category%' ";
if($socid>0) {
	$query_soc="SELECT * FROM acts_societies WHERE id='".$socid."'";
	$res=sqlquery($query_soc) or die(mysql_error());
	if ($row=mysql_fetch_assoc($res)) {
		$socname=$row['name'];
		$shortname=$row['shortname'];
	}
	$filter.=" AND (socid=$socid OR society LIKE '%".addslashes($socname)."%' OR society LIKE '%".addslashes($shortname)."%')";
	
}
$select = "SELECT acts_shows.id,MAX(acts_performances.enddate) AS enddate,acts_performances.startdate AS startdate,acts_performances.time AS time FROM acts_shows,acts_performances";
$query_shows = "$select $filter GROUP BY acts_shows.id ORDER BY acts_performances.`enddate` DESC";


if($lt=="forthcoming")
{
	$query_shows = "SELECT acts_shows.id,MAX(acts_performances.enddate) AS enddate,acts_performances.startdate AS startdate, acts_performances.time FROM acts_shows,acts_performances WHERE acts_shows.id=acts_performances.sid AND (acts_performances.enddate>=NOW()";
	if($socid>0) {
		$query_soc="SELECT * FROM acts_societies WHERE id='".$socid."'";
		$res=sqlquery($query_soc) or die(mysql_error());
		if ($row=mysql_fetch_assoc($res)) {
			$socname=$row['name'];
			$shortname=$row['shortname'];
		}
		$query_shows.=" AND (acts_shows.socid=$socid OR society LIKE '%$socname%' OR society LIKE '%$shortname%')";
	}
	$query_shows.=" AND authorizeid>0 AND acts_shows.entered=1) GROUP BY acts_shows.id ORDER BY acts_performances.startdate ASC,acts_performances.time ASC LIMIT 15";
	
}
$shows = sqlquery($query_shows) or die(mysql_error());
microLog("query for $syndicateid");

echo("<?xml version=\"1.0\" encoding=\"ISO8859-1\" ?".">");
echo("<queryresults>");
echo("<queryparamaters>");
foreach($param_list as $param)
     echo("<$param>".@$$param."</$param>");
echo("</queryparamaters>");
$performance = mysql_fetch_assoc($shows);
$ed=$performance['enddate'];
if(mysql_num_rows($shows)>0)
{
	do
	{	
	  
	  $show = getShowRow($performance['id']);
	  
		if((strtotime($performance['startdate'])<=strtotime($ed)+86400) || $lt!="forthcoming")	
				// another show starting within a day of the end of this one
		{
			$ed=$performance['enddate'];
			
			$n++;
			echo("<show id=\"".$show['id']."\">"); 
			
			$query = "SELECT * FROM acts_performances WHERE sid=".$show['id'];
			$res_lk = sqlquery($query);
			if($res_lk>0)
			  {
			    while($row = mysql_fetch_assoc($res_lk))
			      {
				putDateRange($row);
				
			      }
			    mysql_free_result($res_lk);
			  }
			echo("<friendlydates>");
			echo htmlspecialchars(nl2br(substr(showPerfs($show,false,true),0,-1)));
			echo("</friendlydates>");
			echo("<category>".$show[category]."</category>");
			echo("<title>");
			echo htmlspecialchars($show['title']);
			echo("</title>");
			if(isset($show['author']))
			{
				echo("<author>");
				echo htmlspecialchars($show['author']);
				echo("</author>");
			}
			echo("<society>");
			echo htmlspecialchars(societyName($show));
			echo("</society>");
			if($show['photourl']!="")
				echo("<imageurl>http://www.camdram.net/images/shows/".$show['photourl']."</imageurl>");
			
			echo("<mainvenue>");
			echo htmlspecialchars(venueName($show) );
			echo("</mainvenue>");
			if($show['description']!="")
			{
				echo("<description>&lt;p&gt;");
				ob_start();
				echo preprocess($show['description']);
				$out=htmlspecialchars(ob_get_contents());
				ob_end_clean();
				echo $out;
				echo("&lt;/p&gt;</description>");
			}
			echo("<link>".htmlspecialchars("http://www.camdram.net/index.php?id=104&showid=").$show['id']."</link>");	
			echo("</show>");
		}
		
	 } while ($performance = mysql_fetch_assoc($shows));
}
echo("</queryresults>");
mysql_free_result($shows);
microlog("queryxml.php");
?>
