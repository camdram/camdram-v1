<?php

if (isset ($extra_page_info) && sizeof ($extra_page_info) == 1)
{
	$query = "SELECT id, shortname FROM acts_societies";
	$table = sqlquery($query) or die (mysql_error());
	while ($soc = mysql_fetch_assoc ($table)) {
		if (string_to_url (noslashes($soc['shortname'])) == $extra_page_info[0]) {
			show_society ($soc['id']);
		}
	}
}
elseif (!isset($_GET['socid']))
{
	$query = "SELECT acts_societies.* FROM acts_societies WHERE type=0 AND affiliate=1 ORDER BY college, name";
	$table = sqlquery($query) or die(mysql_error());
	while($soc = mysql_fetch_assoc($table))
		show_society ($soc['id'], true);
}
else {
	show_society ($_GET['socid']);
}

function show_society ($id, $short = false)
{
  global $adcdb, $currentbase;
	if ($soc = getSocRow($id))
	{
		if (!$short)
		{
			echo("<h2>".$soc['name']);
			if ($soc['college'] != "")
				echo (" (" . $soc['college'] . ")");
			echo ("</h2>");
		}
		setStackTitle("society: ".$soc['name']);
		if (!$short)
			set_mode_title ("Viewing <i>$soc[name]</i>");
		$text = preprocess($soc['description']);
		
		$query = "SELECT acts_access.*, acts_users.* FROM acts_access, acts_users WHERE type='society' AND acts_access.rid=".$soc['id']." AND acts_access.revokeid IS NULL AND acts_users.id=acts_access.uid AND acts_access.contact=1";
		$res = sqlquery($query,$adcdb) or die(mysql_error());
		if($res>0)
		{
			$num=mysql_num_rows($res);
			if($num>0)
			{
				$text .= "<p>If you have any queries about this ";
				if($soc['type']==1) $text .= "venue"; else $text .= "society";
				$text .= " please contact ";
				$n=1;
				while($row = mysql_fetch_assoc($res))
				{
					if($n>1)
						if($n==$num) $text .= " or "; else $text .= ", ";
					$text .= "<a href=\"mailto:".userToEmail($row['email'])."\">".$row['name']."</a>";
					$n++;
				}		
			}
			mysql_free_result($res);
		}
		$text .= "</p>";
		echo "<table><tr><td valign=\"top\">";
		if ($short)
		{
			echo "</td><td valign=\"top\">";
			echo("<p><b>");
			makeLink (116, $soc['name'], array("CLEARALL" => "CLEARALL"), false, "", false, string_to_url (noslashes($soc['shortname'])), array("socid" => $soc['id']), true);
			if ($soc['college'] != "")
				echo (" (" . $soc['college'] . ")");
			echo ("</b></p>");
		}
		else
		{
			echo($text);
			echo "</td><td valign=\"top\">";
			if ($soc['logourl'] != "")
				echo "<img src=\"" . $currentbase . "/images/societies/" . $soc['logourl'] .".jpg\" alt=\"" . $soc['name'] . "\">";
		}
		echo "</td></tr></table>";
	} else echo("<h2>Can't find society</h2><p>Sorry, we don't have any details about this society.</p>");
}
?>
