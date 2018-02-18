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

require_once('library/editors.php');
require_once('library/table.php');

if(hasEquivalentToken('security',-2))
{
 
if(isset($_GET['type']))
{
	$type=$_GET['type'];
} else $type='orphans';

 if(isset($_GET['robotson']))
   {
     $query_upd = "UPDATE acts_people_data SET norobots=0 WHERE id='".$_GET['robotson']."' LIMIT 1";
     $p = sqlQuery($query_upd, $adcdb) or die(sqlEr());
     echo ("<b>Robots record updated!</b>");
     unset($_GET['robotson']);
   }

 if(isset($_GET['robotsoff']))
   {
     $query_upd = "UPDATE acts_people_data SET norobots=1 WHERE id='".$_GET['robotsoff']."' LIMIT 1";
     $p = sqlQuery($query_upd, $adcdb) or die(sqlEr());
     echo ("<b>Robots record updated!</b>");
     unset($_GET['robotsoff']);
   }


if(isset($_GET['mergeinto']))
{
	$mergeinto=$_GET['mergeinto'];
	$mergefrom=$_GET['merge'];
	
	$query_upd="UPDATE acts_shows_people_link SET pid=$mergeinto WHERE pid=$mergefrom";
	$p = sqlQuery($query_upd, $adcdb) or die(mysql_error());
	echo("<b>Updated!</b>");
	
	if($_GET[map]=='on') {
	  $query_upd  = "UPDATE acts_people_data SET mapto=$mergeinto WHERE id=$mergefrom LIMIT 1";
	  $p = sqlQuery($query_upd);
	  echo " & mapped";
	  
	}
	unset($_GET['merge']);
	unset($_GET['mergeinto']);
	
}

if(isset($_GET['delid']))
{
	$delid=$_GET['delid'];
	$query_upd="DELETE FROM acts_people_data WHERE id=$delid LIMIT 1";
	$p = sqlQuery($query_upd, $adcdb) or die(mysql_error());
	echo("<b>Deleted!</b>");
	unset($_GET['delid']);
}

if($type=='orphans') $query_people = "SELECT acts_people_data.norobots, acts_people_data.id, acts_people_data.mapto, acts_people_data.name, 0 AS shows FROM acts_people_data LEFT OUTER JOIN acts_shows_people_link ON acts_people_data.id=acts_shows_people_link.pid WHERE acts_shows_people_link.pid IS NULL";
if($type=='range') {
	if($_GET['loid']>$_GET['hiid'])
	{
		$s=$_GET['loid'];
		$_GET['loid']=$_GET['hiid'];
		$_GET['hiid']=$s;
	}
	if($_GET['hiid']>0) $query_people = "SELECT acts_people_data.norobots, acts_people_data.id, acts_people_data.mapto, acts_people_data.name, COUNT(acts_shows_people_link.sid) AS shows FROM acts_people_data LEFT OUTER JOIN acts_shows_people_link ON acts_people_data.id=acts_shows_people_link.pid WHERE acts_people_data.id>=".$_GET['loid']." AND acts_people_data.id<=".$_GET['hiid']." GROUP BY acts_people_data.id,acts_people_data.name";
	else $query_people="SELECT acts_people_data.id, acts_people_data.name, acts_people_data.mapto, COUNT(acts_shows_people_link.sid) AS shows FROM acts_people_data LEFT OUTER JOIN acts_shows_people_link ON acts_people_data.id=acts_shows_people_link.pid GROUP BY acts_people_data.id,acts_people_data.name";
}
if($type=="person")
{
	$person=$_GET['person'];
	$query_people="SELECT acts_people_data.norobots, acts_people_data.id, acts_people_data.mapto, acts_people_data.name, COUNT(DISTINCT acts_shows_people_link.sid) AS shows FROM acts_people_data, acts_shows_people_link WHERE acts_people_data.id=acts_shows_people_link.pid AND acts_people_data.name LIKE '%$person%' GROUP BY acts_people_data.id,acts_people_data.name";
}
if (!isset($_GET['sortby'])) {
	$_GET['sortby']="id";
	$_GET['order']="up";
}
$query_people.=order();
$merge=$_GET['merge'];

global $adcdb;
$people = sqlQuery($query_people, $adcdb) or die(sqlEr());

?>
  <script language="JavaScript" type="text/JavaScript">
<!--
function confirmLink(theLink, theQuery)
{
    var is_confirmed = confirm( theQuery);
    return is_confirmed;
}
//-->
  </script>
</p>
<form name="form1" method="get" action="<?php echo thisPage(); ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td height="15">
      <p><strong>Search for:<br>
      </strong>
        <input <?php if (!(strcmp("$type","orphans"))) {echo "CHECKED";} ?> type="radio" name="type" value="orphans">
        Orphans (people who exist in the database but are not connected to any show) </p>
      </td>
  </tr>
  <tr>
    <td height="30"><input <?php if (!(strcmp("$type","range"))) {echo "CHECKED";} ?> type="radio" name="type" value="range">
      ID range from
        <input name="loid" type="text" value="<?= $_GET['loid']?>" size="4">
      to
      <input name="hiid" type="text" value="<?= $_GET['hiid']?>" size="4">
    </td>
  </tr>
  <tr>
    <td height="30">
      <input <?php if (!(strcmp("$type","person"))) {echo "CHECKED";} ?> type="radio" name="type" value="person">
      Specific person
      <input name="person" type="text" id="person" value="<?=$person?>">
    </td>
  </tr>
  <tr>
    <td>
        <input name="Display" type="submit" id="Display3" value="Display">
      </td>
  </tr>
</table>
<p>
  <input type="hidden" name="id" value="95">
</p>
<?php 



$continue=true;
	$count=0; 
	 $pid=-1;

maketable($people,array(
	"ID"=>"id",
	"Name"=>"name",
	"Show Count"=>"NONE",
	"Robots"=>"norobots",
	"Map"=>"NONE"
	),array("Robots"=>'echo ($row[\'norobots\']==1)?"No Index":"Normal";',"Show Count"=>'echo $row[\'shows\'];',"Map"=>'echo $row[\'mapto\']==0?"-":$row[mapto];'),'
	echo "<a name=\"".$row[\'id\']."\"></a>";
        makeLink(105,"view",array("person"=>$row[id]),true);
        echo " | ";
        $rtxt = ($row[\'norobots\']==1)?"robotson":"robotsoff";
        makeLink(0,"robots",array($rtxt=>$row[id]));
        echo " | ";
	if(isset($_GET[\'merge\'])) {
		if($_GET[\'merge\']==$row[\'id\']) {
			echo("<a href=\"".thisPage(array("merge"=>"NOSET"))."#".$row[\'id\']."\">abort merge</a>"); 
		}
		else {
			echo("<a href=\"".thisPage(array("mergeinto"=>$row[\'id\'],"map"=>"off"))."#".$row[\'id\']."\">merge into here</a>");
                        echo(" | <a href=\"".thisPage(array("mergeinto"=>$row[\'id\'],"map"=>"on"))."#".$row[\'id\']."\">merge & map</a>");
		}
	} else {
		if($row[\'shows\']==0) {
			echo "<a href=\"".thisPage(array("delid"=>$row[\'id\']))."\" onclick=\"return confirmLink(this, \'Are you sure you want to delete?\nThis action cannot be undone.\')\">delete</a>";
		} else { 
			echo "<a href=\"".thisPage(array("merge"=>$row[\'id\']))."#".$row[\'id\']."\">merge</a>";
		}
	} 
	'
);
?>

</form>

<p>&nbsp;</p>
<?php
mysql_free_result($people);
} else inputFeedback();
?>
