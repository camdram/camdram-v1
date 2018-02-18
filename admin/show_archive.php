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
require_once('library/table.php');
global $adcdb, $currentid;
global $base;
// BUILD FILTER

$filter="";
$param_list=array('startday','startmonth','startyear','endday','endmonth','endyear', 'searchtext', 'perpage', 'page');
if (!isset($_GET['sortby'])) {
	$_GET['sortby']="enddate";
	$_GET['order']="down";
}


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

foreach($param_list as $param)
{
	// For those curious on the $$ syntax below, see 
	// http://www.php.net/manual/en/language.variables.variable.php
	// So, here - if $param = "startday" then we are setting
	// $startday = $_GET[$param] (or $_SESSION['showdisplayparams][$param] )
	// The @ suppresses warnings/errors from that line

	if(isset($_GET[$param]))
	{
 		@$$param=mysql_real_escape_string($_GET[$param]);
		$_SESSION['showdisplayparams'][$param]=mysql_real_escape_string($_GET[$param]);
	} else if (isset($_SESSION['showdisplayparams'][$param])) {
		@$$param=$_SESSION['showdisplayparams'][$param];
		$_GET[$param]=$_SESSION['showdisplayparams'][$param];
	} else {
	       $_SESSION['showdiplayparams'][$param] = "";
	       @$$param = "";
	       $_GET[$param] = "";
	}
}


$start="$startyear-$startmonth-$startday";
$end="$endyear-$endmonth-$endday";
$filter=" acts_performances.enddate>'$start' AND acts_performances.enddate<'$end' AND acts_shows.authorizeid>0 AND acts_performances.enddate<NOW() AND acts_shows.entered=1";
if(isset($searchtext) && strlen($searchtext) > 1) {
  $filter.=" AND (acts_shows.title LIKE '%$searchtext%' OR acts_shows.society LIKE '%$searchtext%' OR acts_shows.author LIKE '%$searchtext%'";

  // see if any societies match our string
  $query = "SELECT acts_societies.id FROM acts_societies WHERE name LIKE '%$searchtext%' OR shortname LIKE '%$searchtext%'";
  $r=sqlQuery($query);
  if($r>0) {
    while($row = mysql_fetch_assoc($r))
      $filter.=" OR acts_shows.socid=$row[id]";
    mysql_free_result($r);
}

  // see if any people match our string
  $query = "SELECT acts_shows_people_link.sid FROM acts_people_data, acts_shows_people_link WHERE acts_people_data.name LIKE '%$searchtext%' AND acts_shows_people_link.pid=acts_people_data.id";
  $r=sqlQuery($query);
  if($r>0) {
    while($row = mysql_fetch_assoc($r))
      $filter.=" OR acts_shows.id=$row[sid]";
    mysql_free_result($r);
  }
 $filter.=")";

  
  
}

$extraquery = "";

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
$select = "SELECT acts_shows.id,acts_shows_refs.ref,acts_shows.title,acts_shows.socid,acts_shows.society,acts_shows.venid,acts_shows.venue,MAX(acts_performances.enddate) AS enddate, MIN(acts_performances.startdate) AS startdate FROM acts_shows_refs,acts_shows LEFT JOIN acts_performances ON (acts_shows.id=acts_performances.sid)";

$query_shows = "$select WHERE $filter $extraquery AND acts_shows_refs.refid=acts_shows.primaryref GROUP BY acts_shows.id".order();

$shows = sqlQuery($query_shows) or sqlEr();


// PAGE DIVIDING

if($perpage>5)
{
	$totalRows_shows = mysql_num_rows($shows);
	$maxpage=ceil($totalRows_shows/$perpage);
	
	if($totalRows_shows>$perpage)
	{
		if($page<1 || $_GET['page']>$maxpage)
			$page=1;
		$start=($page-1)*$perpage;
		for($n=0;$n<$start;$n++)
		{
			mysql_fetch_assoc($shows);
		}
	}
}
		
$n=0;
?>
<form name="searchform" id="searchform" method="get" action="<?= thisPage(array("page"=>1)) ?>">
			
              <table class="editor">
                <tr>
                  <td valign="top">search for </td>
                  <td width="0"><input name="searchtext" type="text" id="searchtext" value="<?=htmlspecialchars(stripslashes($searchtext))?>">
</td><div id="autocomplete_choices"></div>
<script type="text/javascript">
//<!--
new Ajax.Autocompleter("searchtext", "autocomplete_choices", "<?=$base?>/postback.php?type=generic_search&search=archive", {afterUpdateElement: getSelectionId});

function getSelectionId(text, li) {
	window.location.href = "<?=$base?>/searchredirect.php?id=" + li.id;
}


//-->
</script>
                </tr>
                <tr>
                  <td width="88">between</td>
                  <td width="0">
                    <input name="startday" type="text" id="startday2" value="<?=$startday?>" size="4" maxlength="2">
                    <select name="startmonth" id="select3">
                      <?php $aim=$startmonth;
		?>
                      <option value="1" <?php if($aim==1) echo("selected");?>>Jan
                      <option value="2" <?php if($aim==2) echo("selected");?>> Feb
                      <option value="3" <?php if($aim==3) echo("selected");?>>Mar
                      <option value="4" <?php if($aim==4) echo("selected");?>>Apr
                      <option value="5" <?php if($aim==5) echo("selected");?>>May
                      <option value="6" <?php if($aim==6) echo("selected");?>>Jun
                      <option value="7" <?php if($aim==7) echo("selected");?>>Jul
                      <option value="8" <?php if($aim==8) echo("selected");?>>Aug
                      <option value="9" <?php if($aim==9) echo("selected");?>>Sep
                      <option value="10" <?php if($aim==10) echo("selected");?>>Oct
                      <option value="11" <?php if($aim==11) echo("selected");?>>Nov
                      <option value="12" <?php if($aim==12) echo("selected");?>>Dec
                    </select>
                    <input name="startyear" type="text" id="startyear2" value="<?=$startyear?>" size="7" maxlength="4">
                  </td>
                </tr>
                <tr>
                  <td width="88" height="25"><div align="left">and</div>
                  </td>
                  <td width="0"><input name="endday" type="text" id="endday2" value="<?=$endday?>" size="4" maxlength="2">
                      <select name="endmonth" id="select4">
                        <?php $aim=$endmonth;
		?>
                        <option value="1" <?php if($aim==1) echo("selected");?>>Jan
                        <option value="2" <?php if($aim==2) echo("selected");?>> Feb
                        <option value="3" <?php if($aim==3) echo("selected");?>>Mar
                        <option value="4" <?php if($aim==4) echo("selected");?>>Apr
                        <option value="5" <?php if($aim==5) echo("selected");?>>May
                        <option value="6" <?php if($aim==6) echo("selected");?>>Jun
                        <option value="7" <?php if($aim==7) echo("selected");?>>Jul
                        <option value="8" <?php if($aim==8) echo("selected");?>>Aug
                        <option value="9" <?php if($aim==9) echo("selected");?>>Sep
                        <option value="10" <?php if($aim==10) echo("selected");?>>Oct
                        <option value="11" <?php if($aim==11) echo("selected");?>>Nov
                        <option value="12" <?php if($aim==12) echo("selected");?>>Dec
                      </select>
                      <input name="endyear" type="text" id="endyear2" value="<?=$endyear?>" size="7" maxlength="4" >
                      <input name="id" type="hidden" value="<?php global $mode; if($mode!=3) echo $currentid; else echo 18; // special hack for ADC website?>"> 
					  
                  </td>
                </tr>
                <tr>
                  <td height="25">displaying</td>
                  <td><?php perPage($perpage); ?> results per page</td>
                </tr>
                <tr>
                  <td height="25">&nbsp;</td>
                  <td><div align="right">
                    <input type="submit" name="Submit" value="View">
                  </div></td>
                </tr>
              </table>
</form>

<script type="text/javascript">
//<!--


document.searchform.title.select();
document.searchform.title.focus();

//-->
</script>
<?php if($maxpage>1) { ?><p><?=displayRangeField($page,$maxpage)?></p><?php } ?>

<?php
maketable($shows,
	array(
		"End Date"=>"enddate",
		"Title"=>"title",
		"Society"=>"NONE",
		"Venue"=>"NONE"
	),array(
		"End Date"=>'echo dateFormat(safestrtotime($row[\'enddate\']));',
		"Title"=>'echo maxChars($row[\'title\'],30);',
		"Society"=>'echo maxChars(societyName($row),18);',
		"Venue"=>'echo maxChars(venueName($row),16);'
	),
	'makeLink(104,"View",array(),true,"",false,$row["ref"],array("showid"=>$row["id"]));'
	,$perpage);
?>
<?php
mysql_free_result($shows);
?>
