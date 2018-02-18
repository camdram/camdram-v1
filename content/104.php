<?php 
require_once('library/showfuns.php');
require_once('library/editors.php');
global $site_support_email, $id;

if($id<0)
{
	global $adcdb;
	$query = "SELECT acts_shows.id FROM acts_shows,acts_performances WHERE acts_shows.id=acts_performances.sid AND acts_shows.authorizeid>0 AND acts_performances.enddate<NOW() AND acts_shows.entered=1 ORDER BY rand() LIMIT 1";
	$x = sqlquery($query,$adcdb) or die(mysql_error());
	$row_closeshows=mysql_fetch_assoc($x);
	mysql_free_result($x);
	$id=$row_closeshows['id'];
} 

if (!is_numeric($id))
	die();

$row_closeshows = getShowRow($id);
$name=$row_closeshows['title'];
set_mode_title ("Viewing <i>$name</i>");
?> 
<?php 
global $mode;

if (isset($_GET['deletereview']) && checkSubmission()) {
	if (hasEquivalentToken("security",-3) && logindetails(true,true,' | ',true)) {
		$query_deletereview="DELETE FROM acts_reviews WHERE id='$_GET[deletereview]'";
		$result=sqlquery($query_deletereview,$adcdb) or die(mysql_error());
		unset($_GET['deletereview']);
	}
}

$norevs=false;
showDispFrameless($row_closeshows,!(isset($_GET['techextra']) || isset($_GET['audextra'])),$mode==3,($norevs || $mode==3)); 
                                       // row, fulldetails, links (e.g. socs), reviews
setStackTitle("<i>".$row_closeshows['title']."</i>");
if(isset($_GET['techextra'])) {
	$techquery="SELECT * FROM acts_techies,acts_shows WHERE acts_techies.showid=acts_shows.id AND acts_shows.id='$id' ORDER BY lastupdated DESC LIMIT 1";
	$q=sqlquery($techquery,$adcdb) or die(sqlEr());
	$techrow=mysql_fetch_assoc($q);
	$positions=$techrow['positions'];
	$positions=str_replace("/"," / ",$positions);
	$positions=str_replace("."," . ",$positions);
	$positions=str_replace(" / ","/",$positions);
	$positions=str_replace(" . ",".",$positions);
	$positions=ucwords(strtolower($positions));
	$positions=str_replace("Lx","LX",$positions);
	$positions=str_replace("Asm","ASM",$positions);
	$positions=str_replace("\n","</li><li>",$positions);	
	$positions=str_replace(chr(13),"", $positions);	
	$contact=$techrow['contact'];
	$pattern= "/([a-z0-9\-\_\.\+]+@[a-z0-9\.\-]+[a-z0-9])/i";
	$contact=preg_replace($pattern,'<a href=mailto:$1>$1</a>',$contact);
	$pattern= "/([a-z][a-z]+[0-9]+)([^@a-z0-9]|$)/i";
	$contact=preg_replace($pattern,'<a href=mailto:$1@cam.ac.uk>$1</a>$2',$contact);
	$deadline=$techrow['deadline'];
	echo "<h4>Technical Positions Vacant</h4>";
	echo "<ul><li>$positions</li></ul>\n";
	echo "<p><strong>Contact:</strong> $contact";
	if ($techrow['deadlinetime']!="") $deadlinetime=", $techrow[deadlinetime]";
	if ($deadline == 1) echo "<br /><br /><strong>Deadline for applications: </strong>",date("l jS F",strtotime($techrow['expiry'])),$deadlinetime,"</p>";
	echo "</p>";
	if ($techrow['techextra'] !="") {
		echo("<p><strong>Further information:</strong><br />");
		echo(preprocess($techrow['techextra']));
	}
}
if(isset($_GET['audextra']) && $row_closeshows['audextra']!="")
{
  echo("<h3>Further audition information:</h3>");
  echo preprocess($row_closeshows['audextra']);
}
if (hasEquivalentToken("show",$id)) {
	echo "<p align=\"right\" class=\"smallgrey\">";
	makeLink('show_manager.php',"edit this show...",array("showid"=>$id),true);
	echo "</p>";
}

if($mode!=3) { 
backStack();?>
<p align="right" class="smallgrey">This entry is administered by 
<?php
   $time = strtotime("-3 months",time());
   $sixmonthsback = date('Y',$time)."-".date('m',$time)."-".date('d',$time);
 
   // if the user's haven't logged in for 3 months,
   // revert responsibility to the society

   $query = "SELECT acts_access.*, acts_users.* FROM acts_access, acts_users WHERE type='show' AND acts_access.rid=".$id." AND acts_access.revokeid IS NULL AND acts_users.id=acts_access.uid AND acts_access.contact=1";
 $r = sqlQuery($query);
 $first = true;
 if($r>0) {
   $n_tot = mysql_num_rows($r);
   if($n_tot>0) {
     $n=0;
     while($row = mysql_fetch_assoc($r)) {
       $n++;
       if($n>1 && $n<$n_tot) echo ", ";
       if($n_tot==$n && $n>1) echo " and ";
       echo "<strong>";
	 //       if ($row['publishemail'] == 1)
         echo "<a href=\"mailto:".userToEmail($row['email'])."\">";
	   echo $row['name'];
	   // if ($row['publishemail'] == 1)
         echo "</a>";
       echo "</strong>";
       $first=false;
     }
   }
   mysql_free_result($r);
 }

 if($row_closeshows['socid']>0) {
   

   $query = "SELECT * FROM acts_societies WHERE id=".$row_closeshows['socid'];
   $res = sqlQuery($query,$adcdb);
   if($res>0)
     {
       $row = mysql_fetch_assoc($res);
       $soc = $row['name'];
       $shortsoc = $row['shortname'];
       mysql_free_result($res);

       if(!$first)
		echo " on behalf of ";
       $param_array = array("socid" => $row_closeshows['socid']);
       if(isset($currentid)){
	 $param_array['retid'] = $currentid;
       }
       makeLink(116,$soc,$param_array, false, "", false, string_to_url (noslashes($shortsoc)));
       $first = false;
       
     }
 } 
 
 
 if(!$first) echo ".<br/>If there are problems with this entry, you can also contact ";
?>
<a href="mailto:<?=$site_support_email?>"><?=$site_support_email?>
</a>.</p>
<?php
} ?>
