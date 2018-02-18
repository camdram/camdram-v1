<?php
require_once("library/showfuns.php");
global $adcdb,$site_support_email,$mode;


$query="SELECT DISTINCT acts_access.uid FROM acts_techies,acts_access WHERE expiry< NOW() AND remindersent=0 AND acts_access.rid=acts_techies.showid AND acts_access.type='show' AND acts_access.revokeid IS NULL";
$result=sqlquery($query,$adcdb) or die(mysql_error());
$i=0;
while ($row=mysql_fetch_assoc($result)) {
	if ($i>0) {
		$email=$email.",";
	}
	$email=$email.getuserEmail($row[uid]);
	$i++;
}
if ($i>0) {
  $message="You had an advert listed on camdram.net, which has just expired. If you are still looking for crew, please go to http://www.camdram.net to renew the advert.\n\nAny questions, please email ".$site_support_email;
  mailTo("","Production team advert expired on camdram.net",$message,"","",$email,"camdram.net <".$site_support_email.">");
}
$query="UPDATE acts_techies SET remindersent=1 WHERE expiry< NOW()";
sqlquery($query,$adcdb) OR die(mysql_error());
    $techstuff= includer("techies");
    if ($techstuff!="") echo "<h2>This week</h2>$techstuff";
echo "<h2>Production Team Vacancies</h2>";

if(isset($_GET['fulldetails'])) $fulldetails=1; else $fulldetails=0;

echo "<p> Display: ";
if($fulldetails==0) makelink(0,"full details",array("fulldetails"=>"on"));
else echo "<strong>full details</strong>";
echo " | ";
if($fulldetails==1) makeLink(0,"summary",array("fulldetails"=>"NOSET"));
else echo "<strong>summary</strong>";
echo "</p>";

add_page_link(215,"How to list your advert on this page");

if (!isset($_GET['adid'])) {
	if (isset($_GET['socid']) && is_numeric($_GET['socid'])) {
		$query_soc="SELECT * FROM acts_societies WHERE id='".$_GET['socid']."'";
		$res=sqlquery($query_soc) or die(mysql_error());
		if ($row=mysql_fetch_assoc($res)) {
			$socname=$row['name'];
			$shortname=$row['shortname'];
		}
		$extraquery= " AND (acts_shows.socid=".$_GET['socid']." OR acts_shows.society LIKE \"%".addslashes($socname)."%\" OR acts_shows.society LIKE \"%".addslashes($shortname)."%\")";
	}
	
	$ad_query="SELECT positions, acts_techies.id AS adid, acts_techies.showid, techextra,acts_techies.contact, acts_techies.deadline, acts_techies.expiry,acts_techies.deadlinetime,MAX(IF(acts_performances.id IS NULL, 2034-01-01, acts_performances.enddate)) AS enddate,MIN(IF(acts_performances.id IS NULL, 2034-01-01,acts_performances.startdate)) AS startdate, acts_shows_refs.ref as uref FROM acts_shows_refs,acts_techies,acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_techies.showid=acts_shows.id AND acts_techies.expiry>=NOW() AND acts_shows.authorizeid>0 AND acts_shows.entered=1 AND refid=primaryref $extraquery GROUP BY acts_shows.id, acts_shows_refs.ref ORDER BY startdate,enddate,title,society;";
	$ads=sqlquery($ad_query,$adcdb) or die(mysql_error());
	if (mysql_num_rows($ads)>0) {
		while ($ad=mysql_fetch_assoc($ads)) {
			$showfields=getShowRow($ad['showid']);
			$showid=$ad['showid'];
			$showref=$ad['uref'];
			$adid=$ad['adid'];
			echo "<a name=\"$adid\"></a>";
			$dates=nl2br(showPerfs($showfields,true));
			$title=$showfields['title'];
			$society=societyName($showfields);
			$venue=venueName($showfields);
			$showinfo="<h3>$title";
			if ($society !="") $showinfo.=" - $society";
			$showinfo.="<br/><span class=\"timeplace\">$dates";
			$showinfo.="</span></h3><p>";
			echo $showinfo;
			$positions=str_replace("/"," / ",$ad['positions']);
			$positions=str_replace("."," . ",$positions);
			$positions=ucwords(strtolower($positions));
			$positions=str_replace(" / ","/",$positions);
			$positions=str_replace(" . ",".",$positions);
			$positions=str_replace("Lx","LX",$positions);
			$positions=str_replace("Asm","ASM",$positions);
			$positions=str_replace("Dsm","DSM",$positions);
			$positions=str_replace("\r","", $positions);	
			$a_positions = explode("\n",$positions);
			
			echo "<strong>Looking for</strong></p><ul>";
			foreach($a_positions as $position)
			  {
			    echo "<li>".$position;
			    $link = lineToKb($position);
			    if(!is_numeric($link) && $mode != 3) {
			      echo " <small>[".$link."what's this?</a>]</small>";
			    }
			    echo "</li>";
			  }
			echo "</ul>";
			if($fulldetails==1)
			  {
			      echo "<p>";
			      echo preprocess($ad[techextra]);
			      echo "</p>";
			      if($ad[contact]!="") {
			      	$contact=$ad[contact];
				$pattern= "/([a-z0-9\-\_\.\+]+@[a-z0-9\.\-]+[a-z0-9])/i";
				$contact=preg_replace($pattern,'<a href=mailto:$1>$1</a>',$contact);
				$pattern= "/([a-z][a-z]+[0-9]+)([^@a-z0-9]|$)/i";
				$contact=preg_replace($pattern,'<a href=mailto:$1@cam.ac.uk>$1</a>$2',$contact);
				echo "<p><strong>Contact</strong> $contact</p>";
			      }
			      if($ad[deadline]==1) {
			      	echo "<p><strong>Deadline for applications: </strong>";
				      echo date("l jS F",strtotime($ad[expiry]));
				      if ($ad[deadlinetime]!="") echo ", $ad[deadlinetime]";
				      echo "</p>";
				}
			  }
			echo "<p>";
			makeLink(104,"more info",array("techextra"=>"true"),true,"", false, $showref, array("showid"=>$showid));
			
			if (hasEquivalentToken("show",$showid)) {
				echo " | ";
				makeLink(151,"edit this advert",array("showid"=>$showid),true);
			}
			echo "</p>";
		}
	}
	else {
		echo "<p><b>Sorry, we don't have details of any techie vacancies at the moment.</b></p>";
		
	}
}
?>
