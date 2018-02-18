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

/** @file mailinglists.php
* Mailing list library functions
*/

/** Get all the mailing lists
* @return an associative array of id=>name of all mailing lists, including auto-generated ones
*/

function getlists() {
	global $adcdb;
	$lists=array("admin"=>"Societies & Administrators","shows"=>"This term's Show Owners","unknownshows"=>"Shows with Unknown Dates");
	$query="SELECT * FROM acts_mailinglists";
	$result=sqlQuery($query,$adcdb) or die(mysql_error());
	while ($row=mysql_fetch_assoc($result)) {
		$lists[$row['id']]=$row['name'];
	}
	foreach ($lists as $id=>$list) if (canemaillist($id)) $accesslists[$id]=$list;
	return $accesslists;
}

/** Get the lists public can sign up for
* @return an associative array of id=>"Name (Description"
*/

function getpubliclistsdescrip() {
	global $adcdb;
	$query="SELECT * FROM acts_mailinglists WHERE public=1";
	$result=sqlQuery($query,$adcdb);
	$out==array();
	while ($row=mysql_fetch_assoc($result)) {
		$text="<strong>".$row['name']."</strong>";
		if ($row['description'] !="") $text=$text." (".$row['description'].")";
		$out[$row['id']]=$text;
	}
	return $out;
}

/** determines whether the current user is allowed to send an email to the specified list
* @param $listid ID of the list to be queried
* @return true if yes, false if no
*/

function canemaillist($listid) {
  switch($listid) {
  case 'admin': 
  case 'shows':
  case 'unknownshows':
    return hasEquivalentToken('security',-2);
    break;
  default:
    return hasEquivalenttoken('email',$listid);
    break;
  }
  return false;
}

/** Get addresses for a specified list
@param $listid ID of the list to be queried
@return an array of adddress for the list
*/

function getaddresses($listid) {
	global $adcdb;
	$i=0;
	if (canemaillist($listid)) {
		if ($listid=="-1") { // Blank List
			$addresses=array();
			$query="SELECT * FROM acts_access WHERE 0";
			$res = sqlQuery($query,$adcdb) or die(mysql_error());
		}
		elseif ($listid=="admin") { // Administrator's List
			$query = "SELECT DISTINCT uid FROM acts_access WHERE `type`='security' OR `type`='society' AND revokeid IS NULL";
			$res = sqlQuery($query,$adcdb) or die(mysql_error());
			
		} elseif ($listid=="shows" || $listid=="unknownshows") { // this term's show owners
		  // first get the relevent shows
		  if($listid=="unknownshows") {
		    $query_perfs = "SELECT acts_shows.id AS sid FROM acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_performances.id IS NULL";
		    $res = sqlQuery($query_perfs) or die(sqlEr($query_perfs));
		  } else {
		    $thisterm = whatTerm(time());
		    $startDate = $thisterm[startdate];
		    $endDate = $thisterm[enddate];
		    $query_perfs = "SELECT acts_performances.* FROM acts_performances WHERE (acts_performances.startdate<='$endDate') AND (acts_performances.enddate>='$startDate')";
		    $res = sqlQuery($query_perfs,$adcdb) or die(sqlEr($query_perfs));
		  }
		  $query = "SELECT DISTINCT uid FROM acts_access WHERE `type`='show' AND (";
		  while($row=mysql_fetch_assoc($res)) {
		    
		    $query.="`rid`=".$row['sid']." OR";
		  }
		  $query.=" 1=0)";
		  mysql_free_result($res);
		  
		  $res = sqlQuery($query) or die(sqlEr($query_performances));
		  
		  
		} else {
		  $query = "SELECT * FROM acts_mailinglists_members,acts_users WHERE listid='$listid' AND uid=id";
		  $res = sqlQuery($query,$adcdb) or die(mysql_error());
		  
		}
		while($row=mysql_fetch_assoc($res)) {
		  $addresses[$i]=getUserEmail($row['uid']);
		    $i++;
		}
		mysql_free_result($res);
		return $addresses;
	}
	else {
	  return array();
	}
}

/** Gets the lists that a specified user is subscribed to.
@param $uid User to be queried
@return associative array listid=>shortname
*/
function getuserlists($uid) {
	global $adcdb;
	$query="SELECT * FROM acts_mailinglists_members,acts_mailinglists WHERE uid=$uid AND acts_mailinglists.id=acts_mailinglists_members.listid";
	$res=sqlQuery($query,$adcdb);
	$lists=array();
	while ($row=mysql_fetch_assoc($res)) {
		$lists[$row['id']]=$row['shortname'];
	}
	return $lists;
}

/** Get the list that are subscribed by default
* @return associative array id=>shortname of the default subscriptions
*/

function getdefaultlists() {
	global $adcdb;
	$query="SELECT * FROM acts_mailinglists WHERE defaultsubscribe=1 AND public=1";
	$res=sqlQuery($query,$adcdb);
	$lists=array();
	while ($row=mysql_fetch_assoc($res)) {
		$lists[$row['id']]=$row['shortname'];
	}
	return $lists;
}

/** Get the row in acts_mailinglists corresponding to the specified list
* @param $listid ID to be queried
* @return Assosciative array of the database row
*/

function getListRow($listid) {
	global $adcdb;
	$query="SELECT * FROM acts_mailinglists WHERE id=$listid";
	$res=sqlQuery($query,$adcdb);
	return mysql_fetch_assoc($res);
}

?>
