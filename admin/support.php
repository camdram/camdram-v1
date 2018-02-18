<h1>Support Manager</h1>

<?php	
// Items assigned to user;

$userid = $_SESSION['userid'];

// Do actions;
if ($_GET['action']=="reply")
{
	$issueid=$_GET['viewissue'];
	$name=getuserName($userid);
	foreach ($_POST as $postkey=>$postvalue) $_POST[$postkey]=stripslashes($postvalue);
	$from="$name <support-$issueid@camdram.net>";
	$to=$_POST['to'];
	$cc=$_POST['cc'];
	$bcc=$_POST['bcc'];
	$bcc=$_POST['bcc'];
	$subject=$_POST['subject'];
	$message=str_replace(chr(13),"",$_POST['message']);
	if (checkSubmission()) {
		$emailsent=mailTo($to,$subject,$message,"",$cc,$bcc,$from);
		$afrom=addslashes(htmlspecialchars($from));
		$ato=addslashes(htmlspecialchars($to));
		$acc=addslashes(htmlspecialchars($cc));
		$asubject=addslashes(htmlspecialchars($subject));
		$abody=addslashes($message);
		$query="INSERT INTO acts_support (`from`,`to`,`cc`,`subject`,`body`,`supportid`,`datetime`) VALUES('$afrom','$ato','$acc','$asubject','$abody','$issueid',NOW());";
		sqlQuery($query,$adcdb) or die(sqlEr());
	}

}

if ($_GET['action']=="assign")
{
	$issueid=$_GET['issue'];
	$query="UPDATE acts_support SET state='assigned', ownerid = '$userid' WHERE id='$issueid' AND state='unassigned' AND supportid=0 LIMIT 1";
	sqlQuery($query) or die(sqlEr());
	
	if (mysql_affected_rows()==0) {
		inputFeedback("Couldn't assign issue","Maybe someone else assigned it");
	}
	
}

if ($_GET['action']=="rejectassign")
{
	$issueid=$_GET['issue'];
	$query="UPDATE acts_support SET state='unassigned', ownerid = NULL WHERE id='$issueid' AND state='assigned' AND supportid=0 AND ownerid='$userid' LIMIT 1";
	sqlQuery($query) or die(sqlEr());
}

if ($_GET['action']=="forcerejectassign")
{
	$issueid=$_GET['issue'];
	$query="UPDATE acts_support SET state='unassigned', ownerid = NULL WHERE id='$issueid' AND state='assigned' AND supportid=0 AND ownerid!='$userid' LIMIT 1";
	sqlQuery($query) or die(sqlEr());
}

if ($_GET['action']=="delete")
{
	$issueid=$_GET['issue'];
	$query="UPDATE acts_support SET state='closed' WHERE id='$issueid' AND state='unassigned' AND supportid=0 LIMIT 1";
	sqlQuery($query) or die(sqlEr());
}

if ($_GET['action']=="resolve")
{
	$issueid=$_GET['issue'];
	$query="UPDATE acts_support SET state='closed' WHERE id='$issueid' AND state='assigned' AND supportid=0 AND ownerid='$userid' LIMIT 1";
	sqlQuery($query) or die(sqlEr());
}

if ($_GET['action']=="reopen")
{
	$issueid=$_GET['issue'];
	$query="UPDATE acts_support SET state='unassigned', ownerid=0 WHERE id='$issueid' AND state='closed' AND supportid=0 LIMIT 1";
	sqlQuery($query) or die(sqlEr());
}

if (isset($_GET['viewissue'])) {
	makeLink(0,"<< Back to Issue Viewer",array(),true);
	$issueid=$_GET['viewissue'];
	$query="SELECT * FROM acts_support WHERE id='$issueid' AND supportid=0";
	$result=sqlQuery($query) or die(sqlEr());
	if ($row=mysql_fetch_assoc($result))
	{
		echo "<h2>Viewing issue $issueid</h2>";
		$state=$row['state'];
		switch($state)
		{
			case "unassigned":
			{
				echo "<p>State: Unassigned</p>";
				makeLink(0,"Assign",array("action"=>"assign","issue"=>$row["id"]));
				echo " | ";
				makeLink(0,"Delete (Resolve)",array("action"=>"delete","issue"=>$row["id"]));
				break;
			}
			case "assigned":
			{
				$ownername=getUserName($row['ownerid']);
				if ($row['ownerid']==$userid)
				{
					$ownername="You";
				}
				echo "<p>State: Assigned to $ownername</p>";
				if ($row['ownerid']==$userid)
				{
					makeLink(0,"Reject assignment",array("action"=>"rejectassign","issue"=>$row["id"]));
					echo " | ";
					makeLink(0,"Resolve",array("action"=>"resolve","issue"=>$row["id"]));
				}
				else {
					makeLink(0,"Reject assignment",array("action"=>"forcerejectassign","issue"=>$row["id"]));
				}	
				break;
			}
			case "closed":
			{
				echo "<p>State: Closed</p>";
				makeLink(0,"Reopen",array("action"=>"reopen","issue"=>$row["id"]));
			}
		}
		echo "<h2>Emails</h2>";
		showEmail($row);
		$mailquery="SELECT * FROM acts_support WHERE supportid='$issueid' ORDER BY id";
		$mailresult=sqlQuery($mailquery) or die(sqlEr());
		while ($mailrow=mysql_fetch_assoc($mailresult))
		{
			showEmail($mailrow);
		}
		// Code to reply;
		global $site_support_email;
		$name=getuserName($userid);
		echo "<h2>Post Reply to this issue</h2><form method=\"POST\" action=\"".linkTo(0,array("CLEARALL"=>"CLEARALL","viewissue"=>"$issueid","action"=>"reply"))."\">\n";
		echo "<input type=\"hidden\" value=\"".allowsubmission()."\" name=submitid>";

		echo "<table border=0><tr><td>To:</td><td><input type=\"text\" name=\"to\" size=60 value=\"".htmlspecialchars(unhtmlentities($row['from']))."\"></td></tr>";
		echo "<tr><td>CC:</td><td><input type=\"text\" name=\"cc\" size=60></td></tr>";
		echo "<tr><td>BCC:</td><td><input type=\"text\" name=\"bcc\" size=60></td></tr>";
		echo "<tr><td>Subject:</td><td><input type=\"text\" name=\"subject\" size=60 value=\"Re: ".htmlspecialchars(unhtmlentities($row['subject']))."\"></td></tr>";
		echo "<tr><td valign = top>Message:</td><td><textarea rows=\"20\" cols=\"80\" wrap=\"virtual\" name=\"message\" id=\"message\">\n\n\n--\nSent by $name on behalf of camdram.net websupport\nFor further correspondence relating to this email, contact support-$issueid@camdram.net\nFor new enquries, contact $site_support_email.</textarea></td></tr>";
		echo "</table>";
		echo "<input type=\"submit\" value=\"Send\">";
		echo "</form>";
		
	}
	
}
else
{
	
	$query = "SELECT * FROM acts_support WHERE state='assigned' AND ownerid = '$userid' AND supportid=0";
	
	$result=sqlQuery($query) or die(sqlEr());
	
	if (mysql_num_rows($result)>0)
	{
		echo "<h2>Issues assigned to you</h2>";
		maketable($result,
			array(
					"ID"=>"id",
			"From"=>"from",
			"Subject"=>"NONE"
			),
			array(
					"Subject"=>'makeLink(0,($row["subject"]!="") ? $row["subject"] : "(No Subject)",array("viewissue"=>$row["id"]),true);'
			),
			'
				makeLink(0,"Reject assignment",array("action"=>"rejectassign","issue"=>$row["id"]));
				echo " | ";
				makeLink(0,"Resolve",array("action"=>"resolve","issue"=>$row["id"]));
			'
					
			);
		$displayed=true;
	}
	
	// Unassigned items;
	
	$query = "SELECT * FROM acts_support WHERE state='unassigned' AND supportid=0";
	
	$result=sqlQuery($query) or die(sqlEr());
	
	if (mysql_num_rows($result)>0)
	{
		echo "<h2>Unassigned issues</h2>";
		maketable($result,
			array(
					"ID"=>"id",
					"From"=>"from",
					"Subject"=>"NONE"
			),
			array(
					"Subject"=>'makeLink(0,($row["subject"]!="") ? $row["subject"] : "(No Subject)",array("viewissue"=>$row["id"]),true);'
			),
			'
					makeLink(0,"Assign",array("action"=>"assign","issue"=>$row["id"]));
					echo " | ";
					makeLink(0,"Delete (Resolve)",array("action"=>"delete","issue"=>$row["id"]));
			'
					
			);
		$displayed=true;
	}
	
	// Other users' items;
	
	$query = "SELECT * FROM acts_support WHERE state='assigned' AND ownerid != '$userid' AND supportid=0";
	
	$result=sqlQuery($query) or die(sqlEr());
	
	if (mysql_num_rows($result)>0)
	{
		echo "<h2>Issues assigned to other users</h2>";
		maketable($result,
			array(
					"ID"=>"id",
			"From"=>"from",
			"Subject"=>"NONE"
			),
			array(
			      	"Subject"=>'makeLink(0,($row["subject"]!="") ? $row["subject"] : "(No Subject)",array("viewissue"=>$row["id"]),true);'
			),
			'
					makeLink(0,"Reject assignment",array("action"=>"forcerejectassign","issue"=>$row["id"]));
			'		  
			);
		$displayed=true;
	}
	
	if (!isset($displayed)) {
		echo "There are no issues to display at this time";
	}
	// Search for resolved items;
}

?>
