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
require_once('config.php');
require_once('library/adcdb.php');
require_once('library/lib.php');
require_once('library/editors.php');
require_once('library/user.php');
require_once('library/emailGen.php');

function SendEmailToRequestShowAuthorization($showid){
	global $mail_alsomail, $site_support_email, $adcdb;
	$row=getShowRow($showid);
	$socid=$row['socid'];
	$venid=$row['venid'];
	$to = "";
	if ($row['socid']!=0) {
		$socadminsquery="SELECT u.email AS email FROM acts_access AS a ,acts_users AS u WHERE a.type='society' AND a.rid='$socid' AND a.uid=u.id AND revokeid IS NULL";
		$socadminres=sqlQuery($socadminsquery,$adcdb) or die(mysql_error());
		while($row_admin=mysql_fetch_assoc($socadminres)) {
			$adminemail=usertoemail($row_admin['email']);
			$to="$to$adminemail,";
		}
	}
	else {
		$societies=societies($showid);
		if($societies){
			foreach($societies as $socid) {
				$socadminsquery="SELECT u.email AS email FROM acts_access AS a ,acts_users AS u WHERE a.type='society' AND a.rid='$socid' AND a.uid=u.id AND revokeid IS NULL";
				$socadminres=sqlQuery($socadminsquery,$adcdb) or die(mysql_error());
				while($row_admin=mysql_fetch_assoc($socadminres)) {
					$adminemail=usertoemail($row_admin['email']);
					$to="$to$adminemail,";
				}
			}
		}
	}
	$venadminsquery="SELECT u.email AS email FROM acts_access AS a ,acts_users AS u WHERE a.type='society' AND a.rid='$venid' AND a.uid=u.id AND revokeid IS NULL";
	$venadminres=sqlQuery($venadminsquery,$adcdb) or die(mysql_error());
	while($row_admin=mysql_fetch_assoc($venadminres)) {
		$adminemail=usertoemail($row_admin['email']);
		$to .= $adminemail.",";
	}
	$to .= $mail_alsomail;

	$added_by = "";
	if(isset($_SESSION['userid'])){
		$added_by = getUserName($_SESSION[userid]) . " (" . getUserEmail($_SESSION[userid]) . ")";
	}else{
		$whoaddedquery = "SELECT name, email FROM acts_users, acts_access WHERE acts_access.type = 'show' AND acts_access.rid = $showid AND acts_users.id = acts_access.uid and revokeid IS NULL";
		$whoaddedres = sqlQuery($whoaddedquery,$adcdb) or die(mysql_error());
		while($whoadded = mysql_fetch_assoc($whoaddedres)){
			if($added_by != ""){
				$added_by .= " and ";
			}
			$added_by .= $whoadded['name'] . " (" . $whoadded['email'] . ")";
		}
	}

	$message="A new show has been added to ".getConfig('site_name')." by $added_by and must be manually authorized before it is shown in any public areas. This process ensures all shows are checked over by the producing society or venue before they are advertised. In particular, please check the following details are correct:\n * Dates \n * Venue \n * Start Time \nbefore authorizing.\n\nA summary of the show is given below. You can edit, authorize or delete the show as appropriate by visiting:  http://".getConfig("site_url")."/administration/edit_show?showid=".$showid."\n\nIf you have any queries, please contact ".$site_support_email.".\n\nThank you,\n\nThe ".getConfig("site_name")." team.\n\n***** Show details follow... *****\n\n";
	$message=$message.generateInfoItem($showid);

	// Process the $to list to remove any duplicates (should be a comma separated list)
	$originalToAddresses = explode(",",$to);
	$newToAddresses = array();
	foreach($originalToAddresses as $addr) {
		if (!in_array($addr,$newToAddresses))
			$newToAddresses[] = $addr;
	}
	$to = implode(",",$newToAddresses);
	mailTo($to,"New show needing authorization on ".getConfig('site_name'),$message,"","","","camdram.net <$site_support_email>");	  
}

function SendEmailsForAllUnauthorizedShows(){
	global $adcdb;
	$query = "SELECT id from acts_shows WHERE authorizeid IS NULL and entered = 1";
	$shows = sqlQuery($query, $adcdb) or die(SqlEr());
	while($row = mysql_fetch_assoc($shows)){
		SendEmailToRequestShowAuthorization($row['id']);
	}
}

?>
