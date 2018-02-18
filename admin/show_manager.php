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
require_once('library/user.php');
require_once('library/table.php');
global $adcdb, $currentid;

$query="DELETE FROM acts_shows WHERE entered=0 AND entryexpiry<CURDATE()";
sqlQuery($query,$adcdb) or die(sqler());
if(isset($_GET['clearquery'])) {
  
  foreach($_GET AS $key=>$value)
    if($key!='id') unset($_GET[$key]);
  
  unset($_SESSION['showdisplayparams']);
}

$jumpto=$_GET['jumpto'];
$showsearch = $_GET['showsearch'];
if (isset($_POST['showid'])) $_GET['showid']=$_POST['showid'];
$thisshow = $_GET['showid'];
if(isset($_GET[showid])) $_GET[searchbyid]=1;
unset($_GET['showid']);
unset($_GET[jumpto]);
unset($_GET[showsearch]);

// REQUEST HANDLING

if (!isset($_GET['sortby'])) {
	$_GET['sortby']="enddate";
	$_GET['order']="down";
}

if(isset($_GET['deleteid']) && isset($_GET['confirmed']))
{
	if(checkSubmission())
	{
		$d=$_GET['deleteid'];
		$query="DELETE FROM acts_performances WHERE sid=$d";
		$r=sqlQuery($query);
		$query="DELETE FROM acts_shows WHERE id=$d LIMIT 1";
		$r=sqlQuery($query);
		if($r!=0) inputFeedback("Deleted show $d!");
			else inputFeedback("Could not delete",mysql_error());
		actionLog("delete show $d");
		unset($_GET['deleteid']);
		unset($_GET['confirmed']);
	}
}

if(isset($_GET['authorizeid']) && isset($_GET['confirmed']))
{
	if(checkSubmission())
	{
		if(authorizeShow($_GET['authorizeid'])) inputFeedback("Show authorized","All entries associated with this show are now live! You may wish to check they are all as you expect and make corrections if necessary.");
		else inputFeedback();
        upgradeShowToV2($_GET['authorizeid']);

		unset($_GET['authorizeid']);
		unset($_GET['confirmed']);
	}
}


allowSubmission();

// BUILD FILTER

$filter="";
$param_list=array('startday','startmonth','startyear','endday','endmonth','endyear', 'title', 'perpage', 'page');
	
if(!isset($_SESSION['showdisplayparams']))
{
	$_SESSION['showdisplayparams']['startyear']=1900;
	$_SESSION['showdisplayparams']['startmonth']=1;
	$_SESSION['showdisplayparams']['startday']=1;
	$_SESSION['showdisplayparams']['endyear']=2050;
	$_SESSION['showdisplayparams']['endmonth']=1;
	$_SESSION['showdisplayparams']['endday']=1;
	$_SESSION['showdisplayparams']['perpage']=10;
}

if(!isset($_GET['editonly']) && isset($_GET['title'])) $editonly=""; 
else if(isset($_GET['title'])) $editonly=$_GET['editonly'];
else $editonly="true"; //default

foreach($param_list as $param)
{
	if(isset($_GET[$param]))
	{
 		@$$param=$_GET[$param];
		$_SESSION['showdisplayparams'][$param]=$_GET[$param];
	} else {
		@$$param=$_SESSION['showdisplayparams'][$param];
		$_GET[$param]=$_SESSION['showdisplayparams'][$param];
	}
}

$_GET['editonly']=$editonly; // special case


$start="$startyear-$startmonth-$startday";
$end="$endyear-$endmonth-$endday";
$filter="WHERE ((acts_performances.enddate>'$start' AND acts_performances.enddate<'$end') OR acts_performances.enddate IS NULL OR acts_performances.enddate = '0000-00-00') ";
if(isset($title)) $filter.=" AND (title LIKE '%$title%' OR society LIKE '%$title%' OR acts_societies.shortname LIKE '%$title%' OR author LIKE '%$title%')";
$uid=$_SESSION['userid'];

$showaccessquery="SELECT * FROM acts_access WHERE uid=$uid AND type='show' AND revokeid IS NULL";
$result=sqlQuery($showaccessquery,$adcdb) or die(mysql_error());
$i=0;
while ($row=mysql_fetch_assoc($result)) {
	if ($i>0) $showaccess=$showaccess." OR ";
	$showaccess=$showaccess."acts_shows.id=".$row['rid'];
	$i++;
}

$socaccessquery="SELECT * FROM acts_access WHERE uid=$uid AND type='society' AND revokeid IS NULL";
$result=sqlQuery($socaccessquery,$adcdb) or die(mysql_error());
while ($row=mysql_fetch_assoc($result)) {
	$socrow=getsocrow($row['rid']);
	if ($i>0) $socaccess=$socaccess." OR ";
	$socaccess=$socaccess."(acts_shows.socid=".$row['rid']." OR LOCATE('".addslashes($socrow['name'])."',acts_shows.society) OR LOCATE('".addslashes($socrow['shortname'])."',acts_shows.society) OR acts_shows.venid=".$row['rid'].")";
	$i++;
}

if(isset($thisshow)) $filter="WHERE acts_shows.id=".$thisshow;
$allaccess=hasEquivalentToken('show',-1);
if ($allaccess || $editonly=="") {
}
elseif ($showaccess=="" && $socaccess=="") {
	$filter.=" AND 1=0";
}
elseif ($showaccess=="") {
	$filter.=" AND ($socaccess)";
}
elseif ($socaccess=="") {
	$filter.=" AND ($showaccess)";
}
else {
	$filter.=" AND ($showaccess $socaccess)";
}
$filter.=" AND acts_shows.entered=1";
$select="SELECT acts_shows.`dates`,`timestamp`,`ref`,`title`,`author`,`prices`,acts_shows.`description`,`photourl`, acts_shows.`id`,`society`,`techsend`,`actorsend`,`audextra`,`socid`,`authorizeid`,acts_shows.venid,acts_shows.venue, acts_societies.shortname AS lk_socname,acts_performances.`id` AS pfid,acts_performances.`sid`,MAX(acts_performances.`startdate`) AS startdate,MAX(IF(acts_performances.`id` IS NULL,'2034-01-01',acts_performances.`enddate`)) AS enddate FROM acts_shows LEFT JOIN acts_societies ON (acts_shows.socid=acts_societies.id) LEFT OUTER JOIN acts_performances ON (acts_shows.id=acts_performances.sid OR acts_performances.sid IS NULL) LEFT JOIN acts_shows_refs ON (acts_shows.primaryref=acts_shows_refs.refid) $filter GROUP BY acts_shows.id";


$query_shows = "$select".order();
$shows = sqlQuery($query_shows);
if($shows==0)
{
  sqlEr($query_shows);
  	die();
}


// PAGE DIVIDING

$maxpage = splitResults($shows,$perpage,$page);

$n=0;
?>
<p><strong>+&nbsp;</strong><?php makeLink("show_wizard.php","Add a Show"); ?><br/>

<h4>Search For...</h4><p>

			
<table class="editor">

<form name="searchform" id="searchform" method="get" action="<?= thisPage(array("page"=>1,"showsearch"=>1)) ?>">
<?php if(!isset($_GET[searchbyid])) { ?>
                <tr>
                  <th>search for </th>
                  <td width="0"><input name="title" type="text" id="title" value="<?=htmlspecialchars(stripslashes($title))?>">                <br>
                  <input type="checkbox" name="editonly" value="true" <?php if($editonly!="") echo("checked"); ?>>
                  Editable shows only
                  </td>
                </tr>
                <tr>
                  <th>between</th>
				      <td><?php dateField($startyear,$startmonth,$startday,'start'); ?>
                  </td>
                </tr>
                <tr>
                  <th>and</th>
				      <td><?php dateField($endyear,$endmonth,$endday,'end'); ?></td>
                </tr>
                <tr>
                  <th>displaying</th>
                  <td><?php perPage($perpage); ?> results per page</td>
                </tr>
                <tr>
                  <td height="25">&nbsp;</td>
                  <td><div align="right">
		    <input name="id" type="hidden" value="79">			    
                    <input type="submit" name="Submit" value="Search"><br/>
	      <small><br/>-&nbsp;<?php makeLink(0,"Clear Search & Display all shows",array("clearquery"=>1));
echo "<br/>-&nbsp;";
makeLink(0,"Search by ID number",array("searchbyid"=>1)); ?></small>
                  </div></td>
                </tr>

<script type="text/javascript">
//<!--

				      document.searchform.title.select();
				      document.searchform.title.focus();

//-->
</script>
												       <?php } else { ?>
<form name="quicky" method="post" action="<?=thisPage()?>">
												       <tr><th>ID number:</th>
<td>
    <input name="showid" type="text" value="<?=$thisshow?>" size="10">
    <input name="Go" type="submit" id="Go" value="Go">
<small><br/>&lt;&nbsp;<?php makeLink(0,"Click for Standard Search",array("searchbyid"=>"NOSET")); ?></small>
    </td>

</form>
   <?php } ?>
              </table>
</p>
<h4>Shows</h4>
<p>

<?php if($maxpage>1) { ?><p><?=displayRangeField($page,$maxpage)?></p><?php }

maketable($shows,
	array(
		"End Date"=>"enddate",
		"Last Modf."=>"timestamp",
		"Title"=>"title",
		"Society"=>"NONE",
		"Authorized"=>"authorizeid",
		"Venue"=>"NONE"
	),array(
		"End Date"=>'if ($row[\'enddate\'] !="2034-01-01") {
			echo dateFormat(strtotime($row[\'enddate\']));
		} else {
			echo "<i>dates tbc</i>";
		}',
		"Last Modf."=>'echo dateFormat(strtotime($row[\'timestamp\']));',
		"Title"=>'echo maxChars($row[\'title\'],30);
					echo " (<i>".$row[\'id\']."</i>";
					
					echo ")";',
		"Society"=>'echo ($row[\'lk_socname\']!="")? $row[\'lk_socname\'] : maxChars($row[\'society\'],18);
					if($row[\'authorizeid\']==0) echo("<i> (pending)</i>");',
		"Authorized"=>'

					if (hasEquivalentToken(\'show\',$row[\'id\'])) {
						if($row[authorizeid]>0) {
							echo getUserEmail($row[\'authorizeid\']);
						}
						else {
							echo "<strong>Pending</strong>";
						}
					}
					else {
						if ($row[\'authorizeid\']>0) {
							echo "Yes";
						}
						else {
							echo "No";
						}
					}
					',
		"Venue"=>'echo maxChars(venueName($row),16);'
	),'
	if(hasequivalenttoken(\'show\',$row[\'id\']) || hasEquivalentToken(\'show\',-1))
	{ 
		$param = array("showid"=>$row[\'id\']);
		
	    if($row[\'linkid\']==0) 
		{   
                        echo "<table class=\"editor\"><tr><th><b>information</b></th><td>";
                        makeLink("show_editor.php","details",$param);
                        echo " |&nbsp;";
                        makeLink("show_cast.php","cast/crew list",$param);
			
			
                        echo "</td></tr><tr><th><b>open positions</b></th><td> ";
			makeLink("show_audition.php","actors",$param);
			echo " |&nbsp;";
			makeLink("show_techies.php","production team",$param);
			echo " |&nbsp;";
			makeLink("applications_editor.php","others",$param);
			echo "</td></tr><tr><th><b>administrative</b></th><td>";
			
                        	
			makeLink("resource_tokens.php","access",$param);
			echo " |&nbsp;";
			echo "<a href=\"".thisPage(array("deleteid"=>$row[\'id\']))."\" ".confirmer("delete this show").">delete</a>";
			echo " |&nbsp;";
		}
		if($row[\'authorizeid\']==0)
		{
			if(hasEquivalentToken("society",$row[\'socid\']) || hasEquivalentToken("society",$row[\'society\']) || hasEquivalentToken("society", $row[\'venid\']))
			{
				echo "<a href=\"".thisPage(array(\'authorizeid\'=>$row[\'id\']))."\" ".confirmer("authorize this show").">authorize</a> |&nbsp;";
			}
		}

		makeLink(104,"view",array(),true,"",false,$row[ref],array("showid"=>$row[id]));
        echo "</td></tr></table>";
    } else 
	makeLink(104,"view",array("showid"=>$row[\'id\']),true);
        
    if(!hasequivalenttoken(\'show\',$row[\'id\']) && !hasEquivalentToken(\'show\',-1))
    {
        echo " |&nbsp;";
        $param = array("showid"=>$row[\'id\']);
        makeLink(201, "request access", $param);
    }
	
	',$perpage
);?>	

</p>
<?php
mysql_free_result($shows);
?>
