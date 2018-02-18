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
require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");
require_once("library/showfuns.php");

$mode=3; // external

// BUILD FILTER

$filter="";

$numeric_param_list=array('startday','startmonth','startyear','endday','endmonth','endyear', 'perpage', 'page','syndicateid','socid');
$string_param_list=array('title','category','type','apikey');
$param_list = array_merge($numeric_param_list, $string_param_list);

$apikeys = array('camdramadmin','cambridgetheatrereview-77335');
	
if(!isset($defaults))
{
        $now = getdate();
	$defaults['startyear'] = $now['year'];
	$defaults['startmonth']= $now['mon'];
	$defaults['startday']= $now['mday'];
	$defaults['endyear']=2112;
	$defaults['endmonth']=1;
	$defaults['endday']=1;
	$defaults['perpage']=10;
	$defaults['socid']=0;
	$defaults['category']='';
	$defaults['page'] = 1;
}

foreach($numeric_param_list as $param)
{
  if(isset($_GET[$param]) && is_numeric($_GET[$param]))
	{
		@$$param=$_GET[$param];
	} else {
		@$$param=$defaults[$param];
	}
}

if($perpage>100){
  $perpage = 100;
}
foreach($string_param_list as $param)
{
  if(isset($_GET[$param]))
	{
	        @$$param=mysql_real_escape_string($_GET[$param]);
	} else {
		@$$param=$defaults[$param];
	}
}



if(! in_array($apikey,$apikeys) ){
  header('text/plain');
?>
Specify your api key using the parameter apikey=[yourkey]

Contact websupport@camdram.net for an apikey
<?php
   exit;
}



$start="$startyear-$startmonth-$startday";
$end="$endyear-$endmonth-$endday";
$filter="WHERE acts_performances.enddate>='$start' AND acts_performances.enddate<'$end' AND acts_shows.authorizeid>0 AND acts_shows.entered=1 AND acts_performances.sid=acts_shows.id";

// if(md5($syndicateid)!="3121a4c07785191b2a2b62ae63de2d2d" && md5($syndicateid)!="a1b27f00bbe99010ac0d0a40a2945501") $filter.=" AND id=0";
if(isset($title)) $filter.=" AND(title LIKE '%$title%' OR society LIKE '%$title%' OR acts_societies.shortname LIKE '%$title%' OR author LIKE '%$title%') ";
if(strlen($category)>0) $filter.=" AND category LIKE '%$category%' ";
if($socid>0) {
	$query_soc="SELECT * FROM acts_societies WHERE id='$socid'";
	$res=sqlquery($query_soc) or die(mysql_error());
	if ($row=mysql_fetch_assoc($res)) {
		$socname=$row['name'];
		$shortname=$row['shortname'];
	}
	$filter.=" AND (socid=$socid OR society LIKE '%".addslashes($socname)."%' OR society LIKE '%".addslashes($shortname)."%')";
	
}
$select = "SELECT acts_shows.id as id,acts_performances.enddate AS enddate, acts_performances.startdate AS startdate  FROM acts_shows,acts_performances";

$start_limit = ($page-1) * $perpage;

if($start_limit <= 0){
  $start_limit = 0;
}

$query_shows = "$select $filter GROUP BY acts_shows.id ORDER BY id LIMIT $start_limit, $perpage";

global $adcdb;




$out = array( "queryparameters" => array(), "shows" => array());

foreach($param_list as $param)
  $out['queryparameters'][$param] = @$$param;


$shows = sqlQuery($query_shows,$adcdb) or die(mysql_error());
while($show = mysql_fetch_assoc($shows)){
  $show_details = ShowDetails($show['id'] * 1);
  array_push($out['shows'],$show_details);
}

if(! isset( $out['shows'][0] )){
  $out['shows'] = 'empty';
}

if($type === 'xml'){
  require_once("library/xml.php");
  header("Content-type: text/xml");
  print xml_encode($out, "query");
  print "<!-- Any clients using this data should be robust to more fields being added at a later date -->";
  exit;
}else if($type === 'json'){
  header("Content-type: application/json");
  echo json_encode($out);
  exit;
}else{
  header("Content-type:text/plain;");
?>
  Specify type=<type> as a parameter, 
      Where <type> is one of xml, json
<?php
      exit;
}

?>
