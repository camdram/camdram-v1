<?php
require_once("library/showfuns.php");
global $adcdb;
echo "<div align=\"right\">";
add_page_link(213,"How to list applications for your society or show on this page");
echo "</div>";
$extraquery2="1=1";
if(isset($_GET['socid']) && is_numeric($_GET['socid'])) {
   $query_soc="SELECT * FROM acts_societies WHERE id='".$_GET['socid']."'";
   $res=sqlquery($query_soc) OR die(mysql_error());
      if($row=mysql_fetch_assoc($res)) {
         $socname=$row['name'];
         $shortname=$row['shortname'];
      }
   $extraquery= " AND (acts_shows.socid=".$_GET['socid']." OR acts_shows.society LIKE \"%".addslashes($socname)."%\" 
   OR acts_shows.society LIKE \"%".addslashes($shortname)."%\")";
   $extraquery2= "socid=".$_GET['socid'];
   }
$query="SELECT * FROM acts_applications,acts_societies WHERE $extraquery2 AND 
socid=acts_societies.id AND deadlinedate>=CURDATE() ORDER BY deadlinedate";
$result=sqlQuery($query,$adcdb) or die(sqlEr());
if (mysql_num_rows($result)>0) {
	$display=1;
	echo "<h2><a name=\"societies\"></a>Applications to societies/venues</h2>";
	while($row=mysql_fetch_assoc($result)) {
	  echo "<a name=\"".$row[deadlinedate]."\"></a>";
		echo "<h3>".$row['name'].": ".$row['text']."\n";
		$deadline = date("l jS F",strtotime($row[deadlinedate])); 
		if ($row[deadlinetime]!="00:00:00") $deadline.=", ".timeFormat(strtotime($row[deadlinetime]));
		echo "<div class=\"headerbuttons attention\">Deadline: ".$deadline."</div></h3>";		
		echo preprocess($row['furtherinfo']);
		echo "</p>";
	      	echo "<p><strong>Deadline: </strong>";
		echo $deadline;
		
		echo "</p>";
	}
}
$query="SELECT * FROM acts_applications,acts_shows WHERE showid=acts_shows.id $extraquery AND 
acts_shows.authorizeid>0 AND deadlinedate>=CURDATE() ORDER BY title";
$result=sqlQuery($query,$adcdb) or die(sql_Er());
if (mysql_num_rows($result)>0) {
	$display=1;
	echo "<h2>Shows seeking core production team</h2>";
	while($row=mysql_fetch_assoc($result)) {
		showdispbasics(getshowrow($row['showid']));
		echo "<p>";
		echo preprocess($row['text']);
		echo "</p>";
		echo "<strong>Further information:</strong></p><p>";
		echo preprocess($row['furtherinfo']);
		echo "</p>";
	      	echo "<p><strong>Deadline: </strong>";
		echo date("l jS F",strtotime($row[deadlinedate]));
		if ($row[deadlinetime]!="") echo ", ".timeFormat(strtotime($row[deadlinetime]));
		echo "</p>";
	}
}
if ($display!=1) echo "Sorry, we do not have details of any applications at this time\n";
?>

