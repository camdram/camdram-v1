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
require_once('library/table.php');
global $site_support_email, $mail_alsomail;
$showid=mysql_escape_string($_GET['showid']);

$result = sqlQuery("SELECT socid, title FROM acts_shows WHERE id=$showid", $adcdb) or die(mysql_error());
if (mysql_num_rows ($result) == 0) die("Could not find society");
$row = mysql_fetch_assoc ($result);
$socid = $row['socid'];
$title = $row['title'];

$result = sqlQuery ("SELECT name FROM acts_users WHERE id=" . $_SESSION['userid'], $adcdb) or die (mysql_error());
$row = mysql_fetch_assoc ($result) or die ("show_requestauth.php error A");
$user_normal_name = $row['name'];

$query = "SELECT name, email, uid FROM acts_users, acts_access WHERE acts_access.revokeid IS NULL AND acts_users.id=acts_access.uid AND ((acts_access.type='show' AND acts_access.rid=$showid) OR (acts_access.type='creator' AND acts_access.rid=$showid)";
if ($socid != 0)
	$query .= " OR (acts_access.type='society' AND acts_access.rid=$socid))";
else
	$query .= ")";

$query .= " ORDER BY name";

$result = sqlQuery($query) or die(mysql_error());
sqlQuery ("INSERT INTO acts_access (rid, issuerid, uid, type, creationdate) VALUES ($showid, " . $_SESSION['userid']
	. ", " . $_SESSION['userid'] . ", 'request-show', NOW())") or die (mysql_error());
?>
<p>Your request for access to this show has been sent to the following people:
<p><b>
<?php
$email = "";
while ($row = mysql_fetch_assoc ($result))
{
	print $row['name'] . "<br>";
	if ($email != "")
		$email .= ", ";
	$email .= usertoemail ($row['email']);
}
$email .= ", $mail_alsomail"; # For debugging

mailto ($email, "Show access request on ".getConfig('site_name'), "$user_normal_name has requested access to edit the show $title on ".getConfig('site_name').". Please log in at http://".getConfig('site_url')."/administration/resource_access?showid=$showid to either grant or deny this request at your earliest convenience.

Thank you,

The ".getConfig('site_name')." team <$site_support_email>", "", "", "", "camdram.net <$site_support_email>");
actionlog ("Access request by " . $_SESSION['userid'] . " for show $showid - token ".mysql_insert_id()." created");
?>

</b>
<p>If you do not hear from one of these people in the next few days, please contact <?=$site_support_email?>.
