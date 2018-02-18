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

require_once('library/page.php');

function showPerfTable($showid) {
  $ret = "";
  $row_show = array("id"=>$showid);
  $query = "SELECT * FROM acts_performances WHERE sid=$showid";
  $r = sqlQuery($query) or die(sqlEr($query));    
  if(mysql_num_rows($r)>0) {
    $ret.= "<p>Currently the following performances are known to the system:</p>";
    
    $ret.=makeTableText($r,array("Dates"=>"NONE","Time"=>"NONE","Venue"=>"NONE"),
	      array("Dates"=>'return dateFormat(strtotime($row[startdate]),strtotime($row[enddate]));',
		    "Time"=>'return timeFormat(strtotime($row[time]));',
		    "Venue"=>'return venueName($row);'));
    $ret.= "<p> and the following summary will be displayed to users:<br/><b>".nl2br(showPerfs($row_show))."</b></p>";
  } else $ret.= "<p><b>Currently no performances are known to the sytem.</b></p>";
  mysql_free_result($r);
  return $ret;
}

/** 

Function to compare two performance objects, returning -1 if $a is before $b, 1 if it's after, or 0 if they're at the same time.

Used by allPerformances()

*/

function cmpPerformances($a, $b){
	if($a['date'] < $b['date']){
		return -1;
	}else if($a['date'] > $b['date']){
		return 1;
	}else if($a['time'] < $b['time']){
		return -1;
	}else if($a['time'] > $b['time']){
		return 1;
	}else{
		return 0;
	}
}

/**

Classes to extend DateTime objects so that they have a default string representation

Once we do an upgrade to PHP 5.4 then adding this interface would be a good idea

http://www.php.net/manual/en/class.jsonserializable.php

Which would mean you could make this a lot nicer

*/

class StringDateTime extends DateTime  {
	public function __toString(){
		return $this->format($this->defaultFormat);
	}

	protected function updateTextValue(){
		$this->value = $this->__toString();
	}
   
	public function __construct(){
		$args = func_get_args();
		call_user_func_array(array($this, 'parent::__construct'), $args);
		$this->updateTextValue();
	}

	public function SetDefaultFormat($format){
		$this->defaultFormat = $format;
		$this->updateTextValue();
	}

	public function modify(){
		$args = func_get_args();
		call_user_func_array(array($this, 'parent::modify'), $args);
		$this->updateTextValue();
	}

	public function JustDate(){
		$this->SetDefaultFormat('Y-m-d');
	}

	public function JustTime(){
		$this->SetDefaultFormat('H:i:s P');
	}

	public function TimeAndDate(){
        //RFC 2822 formatted date
		$this->SetDefaultFormat('r');
	}
   
	private $defaultFormat = 'r';  /* Format for the PHP date function */

	public $value = 'b';  /*This gets replaced in the constructor, and again each time 
							SetDefaultFormat is called, to be the current date according 
							to the default format - this means it'll get output when you use encode_json on it */
}

/** 

Returns an array of performances, in ascending date order, with the following fields set:
    date  => Performance Date
    time  => Performance Time
    venue => Venue (string)

date and time are descended from php DateTime objects

(The reason we don't use normal DateTime objects is so that they can be used with
JSON output; you should be able to just pretend they are)

*/
function allPerformances($showid) {
	if(! is_numeric($showid)){
		return null;
	}
	$query = "SELECT * FROM acts_performances WHERE sid=$showid";
	$r = sqlQuery($query);
	$ret = array();
	while($perf = mysql_fetch_array($r)){
		$current_day = new StringDateTime($perf['startdate'] . " " . $perf['time']);
		$end_day = new StringDateTime($perf['enddate'] . " " . $perf['time']);
		$exclude = new StringDateTime($perf['excludedate'] . " " . $perf['time']);
		$venue = venueName($perf);
		if($venue === "" || !$venue){
			$venue = venueName(getShowRow($showid));
		}
		while($current_day <= $end_day){
			if($current_day != $exclude){
				array_push($ret, array( 'date' => $current_day, 'venue' => $venue ));
			}
			$current_day = clone $current_day;
			$current_day->modify('+1 day');
		}
	}
	mysql_free_result($r);
	usort($ret, 'cmpPerformances');
	return $ret;
}


function markShowUpdate($showid) {
  if(is_numeric($showid)) {
    $query = "UPDATE acts_shows SET `timestamp`=NOW() WHERE `id`='$showid' LIMIT 1";
    $r = sqlQuery($query);
    if($r==0) sqlEr($query);
  }
  
}

function getPerformanceRow($id)
{
  $query = "SELECT * FROM acts_performances WHERE id=$id LIMIT 1";
  $res = sqlQuery($query);
  if($res>0) {
    $row=mysql_fetch_assoc($res);
    mysql_free_result($res);
  }
  return $row;
}

function getShowRow($id,$clearcache=false) // shortcut to mysql query for a show
{
	if(! is_numeric($id)){
		//SQL Injection attack? return null
		return null;
	}
	global $adcdb;
	global $showcache;
	if ($clearcache==false && isset($showcache[$id])) return $showcache[$id];
	$query_show = "SELECT * FROM acts_shows WHERE id=$id LIMIT 1";
	$show = sqlQuery($query_show, $adcdb) or die(mysql_error());
	$row_show=mysql_fetch_assoc($show);
	mysql_free_result($show);
	$showcache[$id]=$row_show;
	return $row_show;
}

function getEventRow($id) // shortcut to mysql query for a show
{
	global $adcdb;
	
	$query_show = "SELECT * FROM acts_events WHERE acts_events.id=$id LIMIT 1";
	$show = sqlQuery($query_show, $adcdb) or die(mysql_error());
	$row_show=mysql_fetch_assoc($show);
	mysql_free_result($show);
	return $row_show;
}

function getAppRow($id) // shortcut to mysql query for a show
{
	global $adcdb;
	
	$query_show = "SELECT * FROM acts_applications WHERE id=$id LIMIT 1";
	$show = sqlQuery($query_show, $adcdb) or die(mysql_error());
	$row_show=mysql_fetch_assoc($show);
	mysql_free_result($show);
	return $row_show;
}

// SOCIETY FUNCTIONS

function getSocRow($socid)
{
	global $soccache;
	if (isset($soccache[$socid])) return $soccache[$socid];
	// straight-forward db query
	$query = "SELECT * FROM acts_societies WHERE id=$socid";
	$result = sqlQuery($query);
	if($result >0)
	{
		$soc=mysql_fetch_assoc($result);
		mysql_free_result($result);
		$soccache[$socid]=$soc;
		return $soc;
	}
	return false;
}


function societyAccessString($field="socid")
{
	// build an expression for SQL which compares the specified field
	// with all the societies the current user has access to
	
	if(!hasEquivalentToken('society',-1))
	{

		global $adcdb;
		
		$uid = $_SESSION['userid'];
		$query = "SELECT * FROM acts_access WHERE `type`='society' AND `uid`='$uid' AND `revokeid` IS NULL";
		$result = sqlQuery($query,$adcdb);
		$continuation=false;
		if($result>0)
		{
			while($rw = mysql_fetch_assoc($result))
			{
				if($continuation) $ret.=" OR ";
				$ret.=$field."=".$rw['rid'];
				$continuation=true;
			}
			mysql_free_result($result);
			if(!$continuation) $ret="1=0";	// no societies available
		}
		return $ret;
	} else return "1=1"; // all societies available
}

function editorLinks($currentshow,$current)
{
  global $currentid;
 
  if(is_array($currentshow)) $st=$currentshow; else $st = getShowRow($currentshow);
  echo "<h3>".$st[title].": ".$current."</h3>";
  $par = array('showid'=>$st[id]);
  
  set_mode_title("Editing <i>".$st[title] . "</i>");
  
  /* these only need to be links, because:
   * 1. We've already explicitly got a mode from set_mode_title
   * 2. They're set to not display as a link if they're this page
   */
  
  add_page_link('show_manager.php','show manager',$par,true,"",true);

  add_page_link('show_editor.php','details',$par,true,"",true);
  
  add_page_link('show_audition.php','auditions',$par,true,"",true);
  
  add_page_link('show_techies.php','production team',$par,true,"",true);
  
  add_page_link('applications_editor.php','other applications',$par,true,"",true);
  
  add_page_link('show_cast.php','cast/crew',$par,true,"",true);
  
  add_page_link(104,"view",$par,true,"",true);
}


function getEarliestDate($show)
{
  if(is_array($show)) $show=$show[id];
  $q = "SELECT startdate FROM acts_performances WHERE sid='$show' ORDER BY startdate ASC LIMIT 1";
  $r = sqlQuery($q);
  if(mysql_num_rows($r)>0)
    {
      $row = mysql_fetch_assoc($r);
      mysql_free_result($r);
      if($row[startdate]>0) return $row[startdate];
    }
  return "2034-01-01";
}

function getLatestTime($show, $venue, $venid)
{
{
  if(is_array($show)) $show=$show[id];
  $q = "SELECT time FROM acts_performances WHERE sid='$show' AND venue='".addslashes($venue)."'";
  if ($venid != "") $q .= " AND venid='$venid'";
  $q .= " ORDER BY time DESC LIMIT 1";
  $r = sqlQuery($q);
  if($r>0)
    {
      $row = mysql_fetch_assoc($r);
      mysql_free_result($r);
      return $row['time'];
    }
  return "00:00:01";
}
}

function authorizeWarning($row_thisshow)
{
  if($row_thisshow['authorizeid']<=0) inputFeedback("Warning","This show has not yet been checked by the society it is entered under. Therefore, any information you enter here will not be available publically until the society authorizes it.");
}

function getLatestDate($show)
{
  if(is_array($show)) $show=$show['id'];
  $q = "SELECT enddate FROM acts_performances WHERE sid='$show' ORDER BY enddate DESC LIMIT 1";
  $r = sqlQuery($q);
  if(mysql_num_rows($r)>0)
    {
      $row = mysql_fetch_assoc($r);
      mysql_free_result($r);
      if($row['enddate']>0) return $row['enddate'];
    }
  return "2034-01-01";
}

function showPerfs($show,$weeknos=false,$supressvenues=false,$startdate="", $enddate="", $links=true)
{
  
  
  $ret = "";
  if ($startdate!="") {
	$sqlDate1 = date ("Y/m/d", $startdate);
	$sqlDate2 = date ("Y/m/d", $enddate);
  	$query = "SELECT * FROM acts_performances WHERE sid=$show[id] AND (venid IN (SELECT venid FROM acts_performances WHERE startdate<='$sqlDate2' AND enddate >='$sqlDate1') OR venue IN (SELECT venue FROM acts_performances WHERE startdate<='$sqlDate2' AND enddate >='$sqlDate1')) ORDER BY startdate";
  }
  else {
  	$query = "SELECT * FROM acts_performances WHERE sid=$show[id] ORDER BY startdate";
  }
  $r = sqlQuery($query);
  if($r>0) {
    if(mysql_num_rows($r))
      while($z = mysql_fetch_assoc($r))
      {
	$ret1=showDateTimePlace($z,!$links,$weeknos,$supressvenues)."\n";
	if ($ret1 != "\n")
		$ret .= $ret1;
      }
    else
      $ret="Dates to be confirmed";
  } else $ret="Dates to be confirmed";
  if ($ret=="Dates to be confirmed") {
	if (venuename($show,$links)!="") {
		$ret=$ret." @ ".venuename($show,$links);
	}
	else $ret="Dates &amp; venue to be confirmed";
  }
  return $ret;
}

function venueName($show,$link=false)
{
	if($show['venid']>0)
	{
		global $adcdb;
		$query = "SELECT * FROM acts_societies WHERE id=".$show['venid'];
		$res = sqlQuery($query,$adcdb);
		if($res>0)
		{
			$row = mysql_fetch_assoc($res);
			mysql_free_result($res);
			if ($link==true && $row['affiliate']==1) return makeLinkText(116,$row['name'],array("CLEARALL"=>"CLEARALL","socid"=>$row['id']));
			return $row['name'];
		} else return "Unknown Venue";
	} else return $show['venue'];
}

function societyName($show)
{
	if($show['socid']>0)
	{
		global $adcdb;
		$query = "SELECT name FROM acts_societies WHERE id=".$show['socid'];
		$res = sqlQuery($query,$adcdb);
		if($res>0)
		{
			$row = mysql_fetch_assoc($res);
			mysql_free_result($res);
			return $row['name'];
		} else return "Unknown Society";
	} else return $show['society'];
}

function friendlyDates($show,$weeknos=false)
{
	$disp = "";
	if(!isset($show['dates']) || $show['dates']=="")
	{
		if ($show['enddate']=="2034-01-01") {
			return "Dates to be confirmed";
		}
		else {
			if($show['time']!="00:00:01")
			{
				$latest = getLatestTime($show['sid'], $show['venue'], $show['venid']);
				if (($latest != "00:00:01") && (strtotime ("2004-01-01 ".$latest) - 3*60*60 > strtotime ("2004-01-01 " .$show['time'])) && $show['time'] < "17:00:00")
				{
					if ($show['startdate'] == $show['enddate'])
						$disp .= "Matinee ";
					else
						$disp .= "Matinees ";
				}
				$disp .= timeFormat(safestrtotime($show['time'])).", ";
			}
			# Is this a one-off?
			if (safestrtotime($show['startdate']) == safestrtotime($show['enddate']))
			{
				# yes it is, try to amalgamate
				$r = sqlQuery("SELECT * FROM acts_performances WHERE time='" . $show['time']
					. "' AND startdate=enddate AND UNIX_TIMESTAMP(startdate) < " . safestrtotime($show['startdate']) . " AND venid=" . $show['venid'] . " AND venue='" . addslashes($show['venue']) . "' AND sid=" . $show['sid']);
				if ($r > 0 && mysql_num_rows ($r) > 0)
					return "";
				
				$r = sqlQuery("SELECT * FROM acts_performances WHERE time='" . $show['time']
					. "' AND startdate=enddate AND UNIX_TIMESTAMP(startdate) > " . safestrtotime ($show['startdate']) . " AND venid=" . $show['venid'] . " AND venue='" . addslashes($show['venue']) . "' AND sid=" . $show['sid']);
				if ($r > 0 && mysql_num_rows ($r) > 0)
				{
					$disp = preg_replace ("/Matinee /", "Matinees ", $disp);
					$disp.=dateFormat(safestrtotime($show['startdate']),safestrtotime($show['enddate']));
					while($z = mysql_fetch_assoc($r))
						$disp.=", " . dateFormat(safestrtotime($z['startdate']),safestrtotime($z['enddate']));					
					return $disp;
				}
			}
			$disp.=dateFormat(safestrtotime($show['startdate']),safestrtotime($show['enddate']));
			if($show['excludedate']>=$show['startdate'] && $show['excludedate']<=$show['enddate'])
			if($weeknos==true)
			  $disp.=" except ".date('jS',safestrtotime($show['excludedate']));
			else
			   $disp.=" (except ".date('jS',safestrtotime($show['excludedate'])).")";
			if($weeknos==true)
			  $disp.=" (".datesByTerm($show['startdate'],$show['enddate']).")";
		      
			return $disp;
		}
	} else return $show['dates'];
}
function showDispBasics($row_closeshows,$nolinks=false)
{
  global $adcdb;
  echo "<h3>";

  if($row_closeshows['socid']!=0)
  {
    $query = "SELECT * FROM acts_societies WHERE id=".$row_closeshows['socid'];
    $res = sqlQuery($query,$adcdb);
    if($res>0)
    {
      $row = mysql_fetch_assoc($res);
      $soc = $row['name'];
      $shortsoc = $row['shortname'];
      mysql_free_result($res);
    }
  } else if ($row_closeshows['society']!="")
  {
    $soc=$row_closeshows['society'];
  }

 
      		
  if(isset($soc) && $soc!="")
  {
    echo "<div class=\"society\">";
    if($row_closeshows['socid']!=0 && !$nolinks && $row['affiliate']==1)
    {
      global $currentid;
      makeLink(116,$soc,array("retid"=>$currentid), false, "", false, string_to_url (noslashes($shortsoc)),array("socid"=>$row_closeshows['socid']));
    } else echo("<i>$soc</i>");
    echo " ",present($soc),"<br/></div>";
  } else {
    if($row_closeshows['venid']<1 && $row_closeshows['venue']=="") echo "<div class=\"society\"><span class=\"attention\">Preapplication Show</span> (not associated with any society or venue)</div>";
  }

  echo $row_closeshows['title'];
  echo " <span class=\"byline\">";
  if($row_closeshows['author']!="") {  
    if(!(stristr($row_closeshows['author'],"by") || stristr($row_closeshows['author'],":"))) echo "by ";
echo $row_closeshows['author']; ?></span><?php 
										}
  echo "<div class=\"timeplace\">";
  echo nl2br(showPerfs($row_closeshows));
  if($row_closeshows['prices']!="" && strtotime(getLatestDate($row_closeshows))>time()) echo "<br/>".$row_closeshows['prices'];
  if(! is_null($row_closeshows['otherurl']) &&  $row_closeshows['otherurl']!="") echo "<br/><a href='$row_closeshows[otherurl]' target='_blank'>Website</a>";
  if(! is_null($row_closeshows['facebookurl']) && $row_closeshows['facebookurl']!="") echo "<br/><a href='$row_closeshows[facebookurl]' target='_blank'>Facebook Page</a>";
  echo "</div></h3>";
				 
				 
}

function showDateTimePlace($show,$nolinks=false,$weeknos=false,$novenues=false)
{
	$ret= friendlyDates($show,$weeknos);
	if ($ret == "")
		return "";
	$ven = venueName($show, !$nolinks);
	if($ven!="" && $novenues==false) {
		$ret.=" @ ". $ven; 

	}
	if($ven=="" && $novenues==false) $ret=$ret.",Venue to be confirmed"; 
	return $ret;
}

function bookingDetails($row_closeshows)
{
  if(strtotime(getLatestDate($row_closeshows))>time() && $row_closeshows['onlinebookingurl']!="")  
    return "<p class=\"smallgrey\">[<a href=\"".$row_closeshows['onlinebookingurl']."\" target=\"_blank\">book online</a>]</p>"; 
  else 
    return "";
}

function fetchOthers($row_role)
{

}

/**
   Returns an array with the following fields set:

   cast => List of cast members
   orchestra => List of orchestra members
   prod => List of production team members

   Each element of the above arrays consists of an array with the following fields:

   name => Person's name
   role => Person's role
   url  => URL that links to that person's show record

   @param showid The Show Id
   @param dedup If true, then each person will only be listed once; multiple roles will be concatentated. If a person is listed
                in more than one of cast, orchestra and prod, they will now only appear in whichever of those came first (in that order)
*/

function getPeople($showid, $dedup = false){
	if(! is_numeric($showid)){
		return null;
	}
    $query_people = "SELECT * FROM acts_shows_people_link,acts_people_data                                                                                                  
        WHERE                                                                                                                                                               
            acts_shows_people_link.sid=$showid AND acts_shows_people_link.pid=acts_people_data.id                                                                           
        ORDER BY acts_shows_people_link.type DESC,acts_shows_people_link.order";
    $people = sqlQuery($query_people,$adcdb) or die(mysql_error());

	$cast = array();
	$orchestra = array();
	$prod = array();

	while($row = mysql_fetch_assoc($people)){
		global $base;
		$person = array('name' => html_entity_decode(trim($row['name'])), 'role'=> html_entity_decode(trim($row['role'])), 'url'=>"$base/shows/view/person?person=".$row['id']."&from_show=".$showid );
		switch($row['type']){
		case 'cast':
			array_push($cast, $person);
			break;
		case 'band':
			array_push($orchestra, $person);
			break;
		case 'prod':
			array_push($prod, $person);
			break;
		}
	}

	mysql_free_result($people);
	if($dedup){
		$dups = array();
		$lists = array($cast, $orchestra, $prod);
		foreach ($lists as &$list){
			for($i = 0; $i < sizeof($list); $i++){
				if(array_key_exists($list[$i]['name'], $dups)){
					$dups[ $list[$i]['name'] ]['role'] .= ', ' . $list[$i]['role'];
					array_splice($list, $i, 1);
					$i--;  // We've just deleted an element, so we need to go round again.
				}else{
					$dups[ $list[$i]['name'] ] =  $list[$i];
				}
			}
		}
	}

	$ret = array('cast' => $cast, 'prod' => $prod, 'orchestra' => $orchestra);
	return $ret;
}

function showDispFrameless($row_closeshows,$alldetails=false,$nolinks=false,$noreviews=true)
{
?><div align="left">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr><td width="100%"> <?php
			
  showDispBasics($row_closeshows,$nolinks);

echo bookingDetails($row_closeshows);
?></td><td width="0"><?php 
     if($row_closeshows['photourl']!="")
       {
	 $imagefile=$row_closeshows['photourl']?>
	 <img src="<?php echo("http://".getConfig("site_url")."/images/shows/$imagefile") ?>" align="right" alt="<?=$row_closeshows['title']?>">
	 <?php }?></td></tr>
  </table>
   <?php
	echo "<p>";
 echo preprocess(($row_closeshows['description']));
   echo "</p>";?>
     
			<br />

              
	      
<?php 
if(strlen($row_closeshows['description'])>80) echo bookingDetails($row_closeshows);

if($alldetails)
{	
	
	global $adcdb;
	// 
	$showid=$row_closeshows['id'];
	$query_people = "SELECT * FROM acts_shows_people_link,acts_people_data 
		WHERE 
			acts_shows_people_link.sid=$showid AND acts_shows_people_link.pid=acts_people_data.id 
		ORDER BY acts_shows_people_link.type != 'cast', acts_shows_people_link.type != 'band', acts_shows_people_link.type != 'band', acts_shows_people_link.`order`";
	$people = sqlQuery($query_people,$adcdb) or die(mysql_error());
	$n = mysql_num_rows($people);
	$lastrole="/";
	if($n>0)
	{
		$lasttype="";
		while($row_people=mysql_fetch_assoc($people))
		{
			if($row_people['type']!=$lasttype)
			{
 switch($row_people['type']) {
 case 'cast': $type='Cast'; break;
 case 'prod': $type='Production Team'; break;
 case 'band': $type="Band"; break;
 }
 $needlb=false;
				echo("</p><h4>$type</h4><p>");
			}
			$role=$row_people['role'];
			$link= lineToKB($role);
			global $mode;
			if(!is_numeric($link) && $mode!=3 && $row_people['type'] == "prod")
			  $whatisit = " <small>[".$link."what's this?</a>]</small>";
			else
			  $whatisit = "";
			if($role!=$lastrole && $role!="") echo(($needlb?"<br />":"")."$role$whatisit - "); else if($role==$lastrole) echo(", "); else echo("<br>");
			$n=0;
			  $needlb = true;
			$name=$row_people['name'];
			$name=trim($name);
			//if($n>0) echo(", ");
			$uname=urlencode($name);
			$pid=$row_people['pid'];
			echo("<i>");
			makeLink(105,$name,array("CLEARALL"=>"CLEARALL","person"=>$pid,"refshow"=>$showid,"reftitle"=>$row_closeshows['title']));
			echo("</i>");
			//$n++;
	
			$lasttype=$row_people['type'];
			$lastrole=$role;
		}
	}	
	mysql_free_result($people);
	if(!$noreviews)
	{
		$query_reviews="SELECT * FROM acts_reviews WHERE acts_reviews.showid=$showid";
		$reviews=sqlQuery($query_reviews,$adcdb) or die(mysql_error());
		$candel = false;
		if (hasEquivalentToken('security',-3)) {
			if (logindetails(false,false,false)) {
				$candel=true;
			}
		}
		while($row_reviews=mysql_fetch_assoc($reviews))
		{
			echo("</p><hr><p>");
			$ts = strtotime($row_reviews['created']);
			echo("Comment from <strong>".getUserName($row_reviews['uid'])."</strong> ");
			if($candel) echo ("(".getUserEmail($row_reviews['uid']).")");
			echo (" on ".dateFormat($ts)." at ".timeFormat($ts)."<br />");
			echo preprocess($row_reviews['review']);
			if ($candel) {
				$submitid=allowSubmission();
				echo "<div align=\"right\">";
				makelink(104,"Delete Comment",array("deletereview"=>$row_reviews['id'],"showid"=>$showid,"submitid"=>$submitid),true,confirmer("delete this comment"));
				echo "</div>";
			}
		}
		echo("</p>");
		echo("<p align=\"right\" class=\"smallgrey\">");
		makeLink(143,"comment on this show...",array("showid"=>$showid),true);
	}
}		  
			?>  
			  

			  
				</div>

                <?php
}


function showDisp($row_closeshows,$alldetails=false)
{
?><table width="500" cellpadding="5">
          <tr>
            <td class="tablerow1">
			<?php showDispFrameless($row_closeshows,$alldetails); ?>
			</td>
          </tr>
  </table>
<?php
}

function societies($showid) {
	global $adcdb;
	$query="SELECT acts_societies.id FROM acts_societies,acts_shows WHERE (STRCMP(society,'') !=0 AND (LOCATE(shortname,society) OR LOCATE(name,society))) AND affiliate=1 AND acts_shows.id=$showid";
	$result=sqlQuery($query,$adcdb) or die (mysql_error());
	$i=0;
	$ret=array();
	while ($row=mysql_fetch_assoc($result)) {
		$ret[$i]=$row['id'];
		$i++;
	}
	return $ret;
}

function allsocs($socname) {
	global $adcdb;
	$query="SELECT acts_societies.id FROM acts_societies WHERE (STRCMP('$socname','') !=0 AND (LOCATE(shortname,'$socname') OR LOCATE(name,'$socname'))) AND affiliate=1";
	$result=sqlQuery($query,$adcdb) or die (mysql_error());
	$i=0;
	while ($row=mysql_fetch_assoc($result)) {
		$ret[$i]=$row['id'];
		$i++;
	}
	return $ret;
}

function show_unique_ref_exists ($ref,$showid=0)
{
	$q = sqlQuery("SELECT refid FROM acts_shows_refs WHERE ref='$ref' AND showid!='$showid' LIMIT 1") or die(sqlEr());
	if (mysql_num_rows($q) == 0)
		return false;
	else
		return true;
}

function create_show_add_numbers ($ref,$showid=0)
{
	for ($number = 2; show_unique_ref_exists ($ref . "_" . $number,$showid); $number++);
	return $ref . "_" . $number;
}

function create_show_unique_ref ($title, $year=-1,$showid=0)
{
	$adding_numbers = 0;
	
	/* let's try the ultra simple way */
	$t =preg_replace ("/^\s{0,}the\s{1,}/i", "", $title);
	$t= preg_replace ("/^\s{0,}a\s{1,}/i", "", $t);
	if ($year != -1)
	{
		$test = $year . "/" . string_to_url (noslashes($t));
	}
	else
	{
		$test = string_to_url (noslashes($t));
	}	
	
	if (strlen ($test) < 30)
		if (! show_unique_ref_exists($test,$showid))
			return $test;
		else
			return create_show_add_numbers ($test,$showid);
	else
	{
		preg_match ("/.*?[\s](.{5,27})$/", $t, $matches);
		$t = $matches[0];
		$t =preg_replace ("/^\s{0,}of\s{1,}/i", "", $t);
		$t =preg_replace ("/^\s{0,}and\s{1,}/i", "", $t);
		$t =preg_replace ("/^\s{0,}the\s{1,}/i", "", $t);
		$t= preg_replace ("/^\s{0,}a\s{1,}/i", "", $t);
		if ($year != -1)
		{
			$test = $year . "/" . string_to_url (noslashes($matches[1]));
		}
		else
		{
			$test = string_to_url (noslashes($matches[1]));
		}
		if (!show_unique_ref_exists($test,$showid))
			return $test;
		else
			return create_show_add_numbers ($test,$showid);
	}	
}

function showuniqueref($showrow) {
	$showid=$showrow['id'];
	$query="SELECT MAX(enddate) end, MIN(startdate) start FROM acts_performances,acts_shows WHERE acts_shows.id=sid AND sid='$showid' GROUP BY sid";
	$r=sqlQuery($query) or die(sqlEr());
	if ($row=mysql_fetch_assoc($r))
	{
		$startyear=date("Y",strtotime($row['start']));	
		$endyear=date("Y",strtotime($row['end']));	
		for ($i=$startyear;$i<=$endyear;$i++)
		{
			$year=$i % 100;
			if ($year<10) $year="0$year";
			$ref=create_show_unique_ref($showrow['title'],$year,$showid);
			if (!show_unique_ref_exists ($ref))
			{
				$query="INSERT INTO acts_shows_refs (showid,ref) VALUES ($showid,'$ref')";
				sqlQuery($query,$adcdb) or die(sqlEr());
			}
		}
		$startyear=date("y",strtotime($row['start']));	
		$ref=create_show_unique_ref($showrow['title'],$startyear,$showid);
		$query="SELECT * FROM acts_shows_refs WHERE ref='$ref'";
		$r=sqlQuery($query,$adcdb) or die(sqlEr());
		if ($row=mysql_fetch_assoc($r))
		{
			$query="UPDATE acts_shows SET primaryref=$row[refid] WHERE id=$showid";
			sqlQuery($query,$adcdb) or die(sqlEr());
		}
	}
	else
	{
		$ref=create_show_unique_ref($showrow['title'],-1,$showid);
		if (!show_unique_ref_exists ($ref))
		{
			$query="INSERT INTO acts_shows_refs (showid,ref) VALUES ($showid,'$ref')";
			sqlQuery($query,$adcdb) or die(sqlEr());
		}
		$query="SELECT * FROM acts_shows_refs WHERE ref='$ref'";
		$r=sqlQuery($query,$adcdb) or die(sqlEr());
		if ($row=mysql_fetch_assoc($r))
		{
			$query="UPDATE acts_shows SET primaryref=$row[refid] WHERE id=$showid";
			sqlQuery($query,$adcdb) or die(sqlEr());
		}
	}
							
}


/**

Returns an array with the following fields:
    title       => Show Title
    author      => Show Author
    id          => Show id
    singlevenue => True if all performances are at the same venue, otherwise false
    venue       => When singlevenue is true, this contains the venue name
    performances=> Array of performances, in ascending date order. more details below.
    cast        => Array of cast, more details below
    prod        => Array of production team, more details below
    orchestra   => Array of orchestra, more details below
    onlinebookingurl => URL for online booking
    facebookurl => URL for facebook event or similar
    otherurl    => URL for other website

Each entry in the performances array has the following fields:
    date        => The performance date
    time        => The performance time
    venue       => The performance venue

Each entry in the cast, crew or orchestra arrays is as follows:
    name        => The person's name
    role        => The person's role

Where a name is associated with more than one role, all roles are concatenated.
Where a name appears in more than one of the cast, production team and orchestra lists, they are
listed in whichever comes first from cast,orchestra,production team.
 
 */

function ShowDetails($show){
	if(! $show){
		return null;
	}
	$show_row = 0;
	if(gettype($show) === 'array'){
		$show_row = $show;
	}else if(gettype($show) === 'integer'){
		$show_row = getShowRow($show);
	}else{
		return null;
	}

	$sql_ref = "SELECT * FROM acts_shows_refs WHERE showid=$show_row[id]";

	global $adcdb;

	$query_ref = sqlQuery($sql_ref, $adcdb);

	$ref = mysql_fetch_assoc($query_ref);

	$ret = getPeople($show_row['id'], true);
	$ret['title'] = $show_row['title'];
	$ret['author'] = $show_row['author'];
	$ret['venue'] = venueName($show_row);
	$ret['performances'] = allPerformances($show_row['id']);
	$ret['singlevenue'] = true;
	$ret['id'] = $show_row['id'];
	$ret['onlinebookingurl'] = $show_row['onlinebookingurl'];
	$ret['facebookurl'] = $show_row['facebookurl'];
	$ret['otherurl'] = $show_row['otherurl'];

	global $base;
	if(isset($ref)){
	  $ret['camdramshowurl'] = $base . '/shows/'. $ref['ref'];
	}else{
	  $ret['camdramshowurl'] = '';
	}
	foreach($ret['performances'] as $performance){
		if($ret['venue'] != $performance['venue']){
			$ret['singlevenue'] = false;
			break;
		}
	}

	return $ret;
}

function upgradeShowToV2($id) {
    global $v2_base_url;
    $url = $v2_base_url.'/shows/'.$id.'/upgrade.json';
    $context =
        array('http' =>
            array('method' => 'PATCH',
                'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
                    "User-Agent: Camdram v1\r\n"));
    $contextId=stream_context_create($context);
    $sock=fopen($url, 'r', false, $contextId);
    if ($sock) {
        $result='';
        while (!feof($sock))
            $result.=fgets($sock, 4096);
        fclose($sock);
    }
}

function upgradePersonToV2($id) {
    global $v2_base_url;
    $url = $v2_base_url.'/people/'.$id.'/upgrade.json';
    $context =
        array('http' =>
            array('method' => 'PATCH',
                'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
                    "User-Agent: Camdram v1\r\n"));
    $contextId=stream_context_create($context);
    $sock=fopen($url, 'r', false, $contextId);
    if ($sock) {
        $result='';
        while (!feof($sock))
            $result.=fgets($sock, 4096);
        fclose($sock);
    }

}

?>
