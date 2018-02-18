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
require_once('library/user.php');
require_once('library/emailGen.php');
global $mail_alsomail, $site_support_email;
$param_list=array('prices','title','author','society','desc', 'showurl', 'venue', 'showid', 'socid', 'venid','category');

if(checkSubmission())
{

	foreach($param_list as $param)
	{
			 @$$param=removeEvilTags($_POST[$param]);
			 if(@$$param!=$_POST[$param]) $warn.="<li>Removing some HTML tags from field $param</li>";
			 $val=$$param;
	}
	$startdate = "$startyear-$startmonth-$startday";
	$enddate = "$endyear-$endmonth-$endday";
	$excludedate = "$excludeyear-$excludemonth-$excludeday";
	if ($tbc=="1") {
		$startdate="2034-01-01";
		$enddate="2034-01-01";
	}
	$time = "$hour:$minute:00";
		
	$keywords=array("adc"=>"ADC Theatre","playroom"=>"Corpus Christi Playroom",
		"play room"=>"Corpus Christi Playroom","octagon"=>"St Chad's Octagon","octogon"=>"St Chad's Octagon",
		"new cellars"=>"Pembroke New Cellars","arts theatre"=>"Cambridge Arts Theatre", "fitzpat"=>"Fitzpatrick Hall, Queens' College");
	foreach($keywords as $key=>$value)
	{
		$key=addslashes($key);
		$value=addslashes($value);
		if(stristr($venue,$key) && $venue!=$value)
		{	
			
			$warn.="<li>Venue name standardised to <i>".stripslashes($value)."</i></li>";
			$venue=$value;
		}
	}
	$desc=str_replace("\r","",$desc);
	if (isset($_POST['unwrap'])) {
		$desc=str_replace("\n\n","\r\r",$desc);
		$desc=str_replace("\n"," ",$desc);
		$desc=str_replace("\r","\n",$desc);
		
	}
	$urls = array("onlinebookingurl" => $_POST['onlinebookingurl'],
                      "facebookurl"=>$_POST['facebookurl'],
                      'otherurl' => $_POST['otherurl']);
	foreach (array_keys($urls) as $key)
	{
	        $urls[$key] = trim($urls[$key]);
                if(preg_match("#^https?://#i",$urls[$key])){
		         // Do nothing
		}else{
		         $urls[$key] = "http://".$urls[$key];
                }
         						      
		$urls[$key] = filter_var($urls[$key], FILTER_SANITIZE_URL);
		if(preg_match("#^https?://\$#i",$urls[$key])){
		         $urls[$key] = "";
		}
		$urls[$key] = mysql_real_escape_string($urls[$key]);
	}
	$bookingcode=$_POST['bookingcode'];
	$showstuff=getshowrow($showid);
	if (!is_numeric($venid) || $venid == 0)
		$venid = "NULL";
	$sql_queryvenues="UPDATE acts_performances SET venid=$venid, venue='$venue' WHERE sid=$showid AND venid='".$showstuff['venid']."' AND venue='".addslashes($showstuff['venue'])."'";

	$sql_query="UPDATE acts_shows SET title='$title', bookingcode='$bookingcode', author='$author', prices='$prices', description='$desc', venue='$venue',venid=$venid,category='$category',onlinebookingurl='$urls[onlinebookingurl]',facebookurl='$urls[facebookurl]',otherurl='$urls[otherurl]' ";
	if(isset($_POST['deletephoto'])) $sql_query.=",photourl=''";
	if(isset($_POST['socid']))
	{
		if (!is_numeric($socid) || $socid == 0)
			$socid = "NULL";
		$sql_query.=",socid=$socid";
		if($society=="") $sql_query.=",society=NULL"; else $sql_query.=",society='$society'";
	}
	$sql_query.=" WHERE id=$showid LIMIT 1";
	

	if(canEditShow($showid))
	{
		$q = sqlQuery($sql_query, $adcdb);
		$q=sqlQuery($sql_queryvenues,$adcdb);
        upgradeShowToV2($showid);
		showuniqueref(getshowrow($showid),true);
	}	
	actionlog("edited show $showid (".$title.")");
	if($warn!="") inputFeedback("Warning","<ul>".$warn."</ul>",true);
	if($q>0)
	{	
		$newshowid=$showid;
		$query_shows = "SELECT * FROM acts_shows WHERE acts_shows.id=$newshowid";
		$shows = sqlQuery($query_shows, $adcdb) or die(mysql_error());
		$row_shows = mysql_fetch_assoc($shows);
		$totalRows_shows = mysql_num_rows($shows);
		mysql_free_result($shows);
		
		
		echo("<fieldset><legend>Standard Show Display</legend>");
		showDispFrameless($row_shows,true);
		echo("</fieldset>");
	
		
	} else {
		sqlEr($sql_query);
		echo("<p>");
		makeLink(79,"show manager"); 
		echo("</p>");
	}
} else {
	$newshowid=$_POST['showid'];
}
echo("<p>");
makeLink(79,"show manager"); 
if($newshowid>0)
{
	echo(" | ");
	makeLink(76,"show editor",array("showid"=>$newshowid));
	echo(" | ");
	makeLink(74,"auditions",array("showid"=>$newshowid));
	echo(" | ");
	makeLink(151,"techies",array("showid"=>$newshowid));
	echo(" | ");
	makeLink("82","people",array("showid"=>$newshowid));
}
echo("</p>");
?>
</p>
