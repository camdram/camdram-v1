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
require_once("./library/mailinglists.php");
require_once("./library/table.php");
require_once("./library/user.php");
require_once("./library/editors.php");
$uid=$_GET['uid'];
?>
<h2>Control Mailing List Subscriptions for <i><?=userfromID($uid)?></i></h2>
<?php

if (!isset($_GET['sortby'])) {
	$_GET['sortby']="name";
	$_GET['order']="up";
}

if (hasequivalenttoken('security',-2)) {
	if (isset($_GET['unsubscribe'])) {
		$unsub=$_GET['unsubscribe'];
		unset($_GET['unsubscribe']);
		$unsubquery="DELETE FROM acts_mailinglists_members WHERE uid='$uid' AND listid='$unsub'";
		sqlQuery($unsubquery,$adcdb) or die(mysql_error());
		actionlog("Unsubscribed user ".userfromid($uid)." from mailing list $unsub");
	}
	if (isset($_POST['list']) && checksubmission()) {
		$sublist=$_POST['list'];
		$subquery="INSERT INTO acts_mailinglists_members (uid,listid) VALUES ($uid,$sublist)";
		sqlQuery($subquery,$adcdb) or die(mysql_error());
		actionlog("Subscribed user ".userfromid($uid)." to mailing list $sublist");
	}
	$query="SELECT * FROM acts_mailinglists_members,acts_mailinglists WHERE uid='$uid' AND listid=id".order();
	$result=sqlQuery($query,$adcdb) or die(mysql_error());
	echo "This user is subscribed to the following mailing lists\n";
	maketable($result,array("Mailing List"=>"name"),array(),'makelink(164,"unsubscribe",array("unsubscribe"=>$row[\'id\']));');
	$query="SELECT * FROM acts_mailinglists LEFT OUTER JOIN acts_mailinglists_members ON acts_mailinglists.id=acts_mailinglists_members.listid AND acts_mailinglists_members.uid=$uid WHERE acts_mailinglists_members.uid IS NULL";
	$result=sqlQuery($query,$adcdb) or die(mysql_error());
	if (mysql_num_rows($result)>0) {
		echo "<h3>Subscribe this user to another mailing list</h3>";
		echo "<form method=\"post\" action=\"".thisPage()."\">";
		echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
		echo "<select name=\"list\">\n";
		while ($row=mysql_fetch_assoc($result)) {
			echo "<option value=\"".$row['id']."\">".$row['name']."</option>\n";
		}
		echo "</select> <input type=\"submit\" value=\"Subscribe\"></form>";
	}
}
