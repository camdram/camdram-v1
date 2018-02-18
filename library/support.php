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
function addSupport($from,$to,$cc,$subject,$body,$issue="NEW")
{
	if ($issue == "")
	{
		$issue = "NEW";
	}

	// Avoid creating a spam loop from gmail webteam bounces
	// (this is a bit of a hack, but it's the simplest fix I can think of!)
	$googleWebteamEmails = array("pahoyes@gmail.com", "stumo@stumo.org.uk", "dstansby@gmail.com", "jamesterjlrb@gmail.com");
	if ($issue == "NEW" && (strpos($from, "MAILER-DAEMON@mail.loho.co.uk") !== false || strpos($from, "Mailer-Daemon@ppsw.cam.ac.uk") !== false))
	{
		foreach($googleWebteamEmails as $wte)
		{
			if (strpos($body, $wte) !== false)
				return;
		}
	}

	global $adcdb;
	$type="send";
	global $mail_alsomail;
	$issue=mysql_real_escape_string($issue);
	if (preg_match("/^reply-(.*)/i",$issue,$matches)) {
		$issue=$matches[1];
		$query="SELECT * FROM acts_support WHERE id='$issue'";
		$result=sqlQuery($query,$adcdb) or die(sqlEr());
		if ($row=mysql_fetch_assoc($result))
		{
			$to=unhtmlentities($row['from']);
		}
		else
		{
			return;
		}
		$type="reply";
	}
	
	$issue=issueToId($issue);	
	$afrom=mysql_real_escape_string(htmlspecialchars($from));
	$ato=mysql_real_escape_string(htmlspecialchars($to));
	$acc=mysql_real_escape_string(htmlspecialchars($cc));
	$asubject=mysql_real_escape_string(htmlspecialchars($subject));
	$replybody=getMainPart($body,false,true);
	$abody=getMainPart($body);
	$body=getMainPart($body,true);
	$abody=mysql_real_escape_string(strip_tags($abody));
	$query="INSERT INTO acts_support (`from`,`to`,`cc`,`subject`,`body`,`supportid`,`datetime`, `state`) VALUES('$afrom','$ato','$acc','$asubject','$abody','$issue',NOW(), 'unassigned');";
	sqlQuery($query,$adcdb) or die(sqlEr());
	if ($type=="reply") {
		mailTo($to,$subject,$replybody,"","","","camdram.net Support <support-$issue@camdram.net>");
	}
	else 
	{
		$newid=mysql_insert_id($adcdb);
		
		$query="SELECT * FROM acts_support WHERE id='$issue'";
		$result=sqlQuery($query,$adcdb) or die(sqlEr());
		if ($row=mysql_fetch_assoc($result))
		{
			$link=linkTo(260,array("CLEARALL"=>"CLEARALL","viewissue"=>"$issue"));
			$ownerid=$row['ownerid'];
			if ($ownerid!=0) {
				$email=getUserEmail($ownerid);	
			}
			
			$mailstart="A new email has been sent concerning issue $issue\n\nTo reply, visit $link\n\n";
		}
		else
		{
			$link=linkTo(260,array("CLEARALL"=>"CLEARALL","viewissue"=>"$newid"));
			$mailstart="A new camdram support issue has been created.\n\nTo investigate, visit $link\n\n";
		}
		if (!isset($email)) {
			$email=$mail_alsomail;	
		}
		$mailsubject="$subject";
		$mailtext="!ADDED\n\n".$mailstart."Email details as follows:\nFrom: $from\nTo: $to\nCC: $cc\n\n$body";
		mailTo($email,$mailsubject,$mailtext,"","","","camdram.net Support <support-reply-$newid@camdram.net>");
	}
}

function issueToId($issue)
{
	if ($issue=="NEW") $issue=0;
	
	$query="SELECT * FROM acts_support WHERE id='$issue' AND state != 'closed'";
	
	$result=sqlQuery($query,$adcdb) or die(sqlEr());
	if ($row=mysql_fetch_assoc($result))
	{
		if ($row['supportid']!=0)
		{
			$issue=$row['supportid'];
		}
	}
	else
	{
		$issue=0;
	}
	
	
	return $issue;
}

function getMainPart($text,$addendmarker=false,$removemarked=false) {
	$endmarker = "";
	if ($addendmarker) {
		$endmarker="!END ADDED";
	}
	if ($removemarked) {
		$pattern='/!ADDED.*?!END ADDED/ms';
		$text=preg_replace($pattern,"",$text);
	}
	$pattern = '/^--\S+\n^Content-Type:.*?\n\n(\S+.*?)^--\S+\n^Content-Type:.*?\n\n/ms';
	preg_match($pattern,$text,$matches);
	if (!isset($matches[1])) {
		return "$endmarker\n\n$text";
	}
	return "Attachments were removed from this message - check gmail account\n\n$endmarker\n\n".$matches[1];
}

function showEmail($row) {
	echo "<h3>";
	echo "From: ",$row['from'],"<br />\n";
	echo "To: ",$row['to'],"<br />\n";
	if ($row['cc'] !="") 
	{
		echo "CC: ",$row['cc'],"<br />\n";
	}
	echo "Subject: ",$row['subject'],"<br />\n";
	echo "Received: ",date("d/m/Y, H:i",strtotime($row['datetime']));
	echo "</h3>";
	echo nl2br($row['body']);
	echo "<hr>";
}
?>
