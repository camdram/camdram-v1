<?php
function otherAuditions($tit,$id, $date)
{
	global $adcdb;
	
	$tit=addcslashes($tit,"\"'");
	$query_auditions = "SELECT * FROM acts_auditions WHERE (acts_auditions.text='$tit' AND acts_auditions.display=0 AND acts_auditions.id<>$id) ORDER BY acts_auditions.`date`,acts_auditions.starttime";
	$auditions = sqlquery($query_auditions, $adcdb) or die(mysql_error());
	$row_auditions = mysql_fetch_assoc($auditions);
	$totalRows_auditions = mysql_num_rows($auditions);
	
	if($totalRows_auditions>0)
	{
		$dt=date("D d M",strtotime($row_auditions['date']));
		if($date==$row_auditions['date']) $dt="Other times on this day";
		$dtf=$row_auditions['date'];
		echo("<span class=\"smallgrey\"><br>Other auditions for this show: <a href=\"#$dtf\">$dt</a>");
		while($row_auditions = mysql_fetch_assoc($auditions))
		{
			$dt=date("D d M",strtotime($row_auditions['date']));
			$dtf=$row_auditions['date'];
			echo(", <a href=\"#$dtf\">$dt</a>");
		}
		echo("</span>");
	}
	
	mysql_free_result($auditions);
		
}

if(isset($_GET['orderby'])) $orderby = $_GET['orderby']; else $orderby=1;

   if(isset($_GET['socid']) && is_numeric($_GET['socid'])) {
   $query_soc="SELECT * FROM acts_societies WHERE id='".$_GET['socid']."'";
   $res=sqlquery($query_soc) OR die(mysql_error());
      if($row=mysql_fetch_assoc($res)) {
         $socname=$row['name'];
         $shortname=$row['shortname'];
      }
   $extraquery= " AND (acts_shows.socid=".$_GET['socid']." OR acts_shows.society LIKE \"%".addslashes($socname)."%\" 
   OR acts_shows.society LIKE \"%".addslashes($shortname)."%\")";
   }
if($orderby==0) $query_auditions = "SELECT * FROM acts_auditions,acts_shows  WHERE acts_shows.id=acts_auditions.showid AND acts_auditions.`date`>=CURDATE() AND acts_auditions.display=0 AND acts_shows.authorizeid>0 AND acts_auditions.nonscheduled=0 $extraquery ORDER BY acts_auditions.`date`,acts_auditions.starttime";
else $query_auditions = "SELECT acts_auditions.*,acts_shows.id,acts_shows.title,acts_shows_refs.ref FROM acts_auditions, acts_shows,acts_shows_refs WHERE primaryref=refid AND acts_shows.id=acts_auditions.showid AND acts_auditions.`date`>=CURDATE() AND acts_auditions.display=0 AND acts_shows.authorizeid>0 AND acts_shows.entered=1 $extraquery ORDER BY acts_shows.title,acts_auditions.date,acts_auditions.starttime";
$auditions = sqlQuery($query_auditions) or die(sqlEr());
$row_auditions = mysql_fetch_assoc($auditions);
$totalRows_auditions = mysql_num_rows($auditions);
?>  
<p>Order by: 
  <?php if($orderby!=0) makeLink(69,"Audition Date",array("orderby"=>"0")); else echo("<b>Audition Date</b>"); ?> 
  | 
  <?php if($orderby!=1) makeLink(69,"Show",array("orderby"=>"1")); else echo("<b>Show</b>");?> 
</p>
<div align="right">
<?php
add_page_link(212,"How to list your auditions on this page");

echo "</div>";
 if($totalRows_auditions>0) {	
  do { 
  
  if($orderby==0)
  {
		if($row_auditions['date']!=$last_date)
		{
			$adcs=true;
			$last_date=$row_auditions['date'];
			$dt=date("D d M",strtotime($row_auditions['date']));
	?> <a name="<?php echo $row_auditions['date'] ?>"></a> <?php
			echo("<br><h3>$dt</h3>");
		}
			if($row_auditions['text']=="") echo($row_auditions['title']);
				else echo($row_auditions['text']);
			 if($row_auditions['society']!="")
	  {
	  ?> (<i><?php echo($row_auditions['society']); ?></i>) <?php }
		if($row_auditions['showid']!=0)
					{
						$id=$row_auditions['showid'];
						$ref=$row_auditions['ref'];
						$qu=startLink(104,array("audextra"=>"true", "CLEARALL"=>"CLEARALL"), false, 0, "", false, $ref, array("showid"=>$id))."show information";
						if($row_auditions['audextra']!="") $qu.=" & more audition details";
						$qu.="</a>";
						echo " [$qu]";
						if (hasequivalenttoken("show",$id)) {
							echo " [";
							makelink(74,"edit auditions for this show",array("CLEARALL"=>"CLEARALL","showid"=>$id));
							echo "]";
						}
						
					}echo("<br>");
		if($row_auditions['starttime']!=0)
				{
					
					$st=timeFormat(strtotime($row_auditions['starttime']),strtotime($row_auditions['endtime']));
					echo "<b>$st</b> ";
		
				} 
				?>
	
		  
	  <?php echo $row_auditions['location']; ?>
	  <?php 
			if($row_auditions['info']!="") 
				{   echo "<br>";
					echo $row_auditions['info']; 
					
				  }
				//  otherAuditions($row_auditions['text'],$row_auditions['id'],$row_auditions['date']);
				  echo "<br><br>";
				  
 } 
 else // next bit is for sorting by show rather than by date
 {
			
			$tit=($row_auditions['text']=="")?$row_auditions['title']:$row_auditions['text'];
			$showid=$row_auditions['showid'];

			// Changed Mar 2013 to use showid not title, to decide when to start a new block in the audition list
			if($showid!=$xshowid) 
			{
				if($xshowid!="") echo("</ul>");
				echo("<br><br><b>$tit</b>");
				if($row_auditions['society']!="")
	  {
	  ?> (<i><?php echo($row_auditions['society']); ?></i>) <?php }
		if($row_auditions['showid']!=0)
					{
						$id=$row_auditions['showid'];
						$qu=startLink(104,array("showid"=>$id,"audextra"=>"true"))."show information";
						if($row_auditions['audextra']!="") $qu.=" & more audition details";
						$qu.="</a>";
						echo " [$qu]";
						if (hasequivalenttoken("show",$id)) {
							echo " [";
							makelink(74,"edit auditions for this show",array("CLEARALL"=>"CLEARALL","showid"=>$id));
							echo "]";
						}
						
					}echo("<br>");
					echo("<ul>");
				}
			$xshowid=$showid; // save the just-parsed showid so, on the next loop around, can tell if it's a new show or same one
			$dt=date("D d M",strtotime($row_auditions['date']));
			$st=timeFormat(strtotime($row_auditions['starttime']),strtotime($row_auditions['endtime']));
			$loc=$row_auditions['location'];
			if($row_auditions['info']="")
			{
				$info="";
			} else {
				$info=$row_auditions['info'];
				$info.="<br>";
			}
			$stf=($row_auditions['starttime']!=0)?" $st,":"";
			if($row_auditions['nonscheduled']==0) echo("<li>$dt,$stf $loc $info</li>");
			else {
				$qu=startLink(104,array("showid"=>$id,"audextra"=>"true"));
				$pattern= "/([a-z0-9\-\_\.\+]+@[a-z0-9\.\-]+[a-z0-9])/i";
				$loc=preg_replace($pattern,'<a href=mailto:$1>$1</a>',$loc);
				$pattern= "/([a-z][a-z]+[0-9]+)([^@a-z0-9]|$)/i";
				$loc=preg_replace($pattern,'<a href=mailto:$1@cam.ac.uk>$1</a>$2',$loc);
				if($loc!="") echo $loc; else echo("<li>No scheduled dates available; click $qu here</a> for more information</li>");
			}
			$pendinfo=$row_auditions['info'];
			
			
		}
		
	} while ($row_auditions = mysql_fetch_assoc($auditions));
  } else echo("<p><b>Sorry, we don't have details of any forthcoming auditions at the moment.</b></p>"); ?>

  <?php
mysql_free_result($auditions);
?>
</p>
