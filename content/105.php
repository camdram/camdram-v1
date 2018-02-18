<?php 
require_once("library/showfuns.php");
require_once("facebook/fcam.php");

global $people,$row_people;

if(!isset($row_people)) {
  
  inputFeedback("Person not specified");

} else {

$showid=$row_people['sid'];
$person=$row_people['name'];

$query_Recordset1 = "SELECT acts_shows.*,acts_shows_refs.ref,MAX(acts_performances.enddate) AS enddate FROM acts_shows_refs,acts_shows,acts_performances WHERE (acts_shows.id=$showid";
$showid=$row_people['sid'];
$showroles[$showid]=$row_people['role'];

while($row_people=mysql_fetch_assoc($people))
{
	$showid=$row_people['sid'];
	if(isset($showroles[$showid])) $showroles[$showid].=", ".$row_people['role']; else $showroles[$showid]=$row_people['role'];
	$query_Recordset1.=" OR acts_shows.id=$showid";
}
$query_Recordset1.=") AND acts_performances.sid=acts_shows.id AND refid=primaryref";
$query_Recordset1.=" GROUP BY acts_shows.id ORDER BY acts_performances.enddate DESC";

$searchresults = sqlquery($query_Recordset1) or die(mysql_error());
$row_searchresults = mysql_fetch_assoc($searchresults);
$totalRows_searchresults = mysql_num_rows($searchresults);

if(isset($_GET["refshow"])) 
{
	$refshow=$_GET['refshow'];
	$reftitle=$_GET['reftitle'];
}

setStackTitle("shows involving: <i>".$person."</i>");
?>
                
                      <p>We have a record of <?=$person." ".linkIfIKnow($person) ?> in the following
                        shows: </p><?php
echo("<ul>");
do { 
	?>
                        <li><?php makeLink(104,"<b>".$row_searchresults['title']."</b>",array (), true, "", false, $row_searchresults['ref'], array("showid"=>$row_searchresults['id']));
						 if($row_searchresults['author']!="") {?> by <i><?php echo $row_searchresults['author']; ?></i><?php } ?><br /> 
                            <?php if($showroles[$row_searchresults['id']]!="") echo("under <b>".$showroles[$row_searchresults['id']]."</b><br />"); ?>
                            <?php if($row_searchresults['author']!="") { ?>
   
    <?php }
                 echo nl2br(showPerfs($row_searchresults));
			   ?>
                        </li>

                      <?php } while ($row_searchresults = mysql_fetch_assoc($searchresults)); 
		  echo("</ul>");
  
 
 mysql_free_result($people);

mysql_free_result($searchresults);

backStack();

}

?>
                      
