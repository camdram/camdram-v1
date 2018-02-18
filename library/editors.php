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

require_once('library/table.php');
global $editors_allowed_submission;
$editors_allowed_submission=false;

// GENERIC FORMS & TABLES

function genericDelete($tablename,$deleteid)
{
  $query = "DELETE FROM $tablename WHERE id=$deleteid LIMIT 1";
  $r = sqlQuery($query) or sqlEr($query);
  if($r>0) inputFeedback("Deleted!");

}

function genericSelect($tablename,$field,$current,$assocarray=array())
{
 
  if($assocarray==array()) {
    
  
    $query = "DESCRIBE $tablename";
    $res = sqlQuery($query);
    if($res>0)
      {
	while($row=mysql_fetch_assoc($res))
if(stristr($row[Field],$field))
	    {
	      
	      $choosefrom = explode("','",substr($row[Type],6,-2));
	      
	    }
	mysql_free_result($res);
      }
    foreach($choosefrom as $val) $assocarray[$val]=$val;
  }
  
  echo '<select name="'.$field.'" id="'.$field.'">';
  if($current=="") $current=".";
  foreach($assocarray AS $key=>$val)
  {
    echo '<option value="'.$key.'"';
    if(stristr($key,$current))
      echo ' selected';
    echo '>'.$val.'</option>';
  }
  echo '</select>';
}

function genericSubmission($tablename,$expecting)
{
  foreach($expecting as $key=>$value)
    {
      if(is_integer($key))
	{
	  unset($expecting[$key]);
	  $expecting[$value]="";
	}
    }
  $query = "DESCRIBE $tablename";
  $res = sqlQuery($query) or sqlEr($query);
  if($res>0)
    {
      while($row=mysql_fetch_assoc($res)) {
	if(isset($expecting[$row[Field]]) && $expecting[$row[Field]]=="")
	  {
	    switch($row[Type]) {
	    case 'date':
	      $type = "date";
	      break;
	    default:
	      $type = "text";
	    }
	    $expecting[$row[Field]] = $type;
	  }
      }
    }

  $edit = $_POST['ip_editid'];
  if($edit<0) {
    $query = "INSERT INTO $tablename () VALUES ()";
    $r = sqlQuery($query) or sqlEr();
    $edit = mysql_insert_id();
  }
  $query = "UPDATE $tablename SET ";
  $started = 0;
 
  foreach($expecting as $kw=>$type)
    {
      
      if($started==1) $query.=", ";
      $started = 1;
      switch($type) {
      case 'text':
	$query.="`".$kw."`='".mysql_real_escape_string($_POST['ip_text_'.$kw])."'";
	break;
      case 'date':
	$query.="`".$kw."`='".mysql_real_escape_string($_POST['ip_date_'.$kw."_year"]."-".$_POST['ip_date_'.$kw.'_month']."-".$_POST['ip_date_'.$kw.'_day'])."'";
	break;
      
      }
    }
  
  if($edit>0) {
      $query.=" WHERE id=$edit LIMIT 1";
      $r = sqlQuery($query) or die(sqlEr());
  } else inputFeedback("Unable to find table row to update");
  
}


function genericEditor($tablename,$editid,$field_describe,$field_editor=array())
{
  if($field_describe==array()) $alldata=true;
  $query = "DESCRIBE $tablename";
  $res = sqlQuery($query) or sqlEr($query);
  if($res>0) {
    while($row = mysql_fetch_assoc($res)) {
      if($alldata) $field_describe[$row[Field]] = $row[Field];
      if(!isset($field_editor[$row[Field]])) {
	$field_editor[$row[Field]] = 'text';
	
	if($row[Type]=='date') $field_editor[$row[Field]] = 'date';
	if(substr($row[Type],0,7)=='tinyint' || substr($row[Type],0,3)=='int') $field_editor[$row[Field]]='smalltext';
	if($row[Field]=='id') $field_editor[$row[Field]]='hidden';
      }
    }
    mysql_free_result($res);
  }
  
 

  if($editid!=-1) {
    $q = "SELECT * FROM ".$tablename." WHERE id=".$editid;
    $res = sqlQuery($q) or sqlEr($q);
    $row = mysql_fetch_assoc($res);
    mysql_free_result($res);
  }
?>   <h3>Edit Entry<div class="headerbuttons"><?=makeLink(0,"cancel")?></div></h3><form name="editform" method="post" action="<?=linkTo(0)?>"><table class="editor">
<?php
    
	global $verbose;
      foreach($field_describe as $field=>$description) {
        if($field_editor[$field]!='hidden') echo "<tr><td>".$description."</td><td>";
	if($verbose>0) echo hint("E",$field_editor[$field]);
	switch($field_editor[$field])
	  {
	  case 'smalltext':
        ?><input name="ip_text_<?=$field?>" id="ip_text_<?=$field?>" value="<?=$row[$field]?>" size="4" maxlength="4"/><?php
	     break;
	  case 'text':
	    ?><input name="ip_text_<?=$field?>" id="ip_text_<?=$field?>" value="<?=$row[$field]?>" /><?php
		 break;
	  case 'hidden':
	    	    ?><input type="hidden" name="ip_internal_<?=$field?>" id="ip_internal_<?=$field?>" value="<?=$row[$field]?>" /><?php
		 break;
	  case 'date':
	    dateFieldSQL($row[$field],"ip_date_".$field."_");
	    break;

	  default:
	    eval($field_editor[$field]);
	  }
	if($field_editor[$field]!='hidden') echo "</td></tr>";
      }
    
?><tr><td><input type="hidden" name="ip_editid" value="<?=$editid?>" />
&nbsp;</td><td align="right"><input type="submit" value="Edit" name="Submit" id="Submit" /></td></tr></table></form><?php
		  
  
  
}


function genericEditorTable($tablename,$selectwhere,$orderby)
{
  genericTable($tablename,$selectwhere,$orderby,'makeLink(0,"edit",array(\'editid\'=>$row[\'id\'])); echo " | <a href=\"".linkTo(0,array(\'deleteid\'=>$row[\'id\']))."\" class=\"confirmable\" ".confirmer("delete this item").">delete</a>"; ');
}

function genericTable($tablename,$selectwhere,$orderby,$actionbuttons="",$tabletitle="")
{
  $query = "SELECT * FROM $tablename $selectwhere $orderby";
  $res = sqlQuery($query) or sqlEr();
  if($res>0) {
    if($tabletitle=="") $tabletitle=$tablename." - generic editor";
    echo "<h3>$tabletitle</h3>";
    echo "<p><strong>+ ";
    makeLink(0,"Create New Entry",array("editid"=>-1));
    echo "</strong></p>";
    $nrows = mysql_num_rows($res);
    if($nrows>0) {
      makeTable($res,array(),array(),$actionbuttons);
    } else echo "<p>Your query returned <strong>no results</strong>.</p>";
    mysql_free_result($res);
  }
}

// GENERIC EDITING FUNCTIONS
function continueButton()
{	?><form action="<?=thisPage()?>" method="post"><input name="nocleverness" type="submit" value="Continue"><?php
	foreach($_POST as $key=>$value)
	{
		?> <input name="<?=$key?>" type="hidden" value="<?=htmlspecialchars(stripslashes($value));?>"><?php
	}?> </form> <?php
}

function canEditSoc($showid)
{
	if(!hasEquivalentToken('society',$showid))
	{
		inputFeedback("You do not appear to have access to this society.","Cannot display page. If you are encountering problems, please contact us.");
		return false;
	} else {
		return true;
	}
}

function canEditBuilderEmail($emailid)
{
	if(!hasEquivalentToken('builderemail',$emailid))
	{
		inputFeedback("You do not appear to have access to this email.","Cannot display page. If you are encountering problems, please contact us.");
		return false;
	} else {
		return true;
	}
}

function getUsersSelectString($fieldname,$accessname,$foruser=0)
{
  if($foruser>0 && hasEquivalentToken('security',-3))
    $useid = $foruser;
  else
    $useid = $_SESSION[userid];

  $q = "SELECT * FROM acts_access WHERE uid=".$useid." AND type='".$accessname."' AND revokeid IS NULL";
  $res = sqlQuery($q);
  if($res>0) {
    $string="";
    while($row = mysql_fetch_assoc($res)) {
      if($string!="") $string.=" OR ";
      $string.="`".$fieldname."` = '".$row[rid]."'";
    }
    mysql_free_result($res);
    return $string;
  } else die(sqlEr($q));
}

function canEditShow($showid)
{
	if(!hasEquivalentToken('show',$showid))
	{
		inputFeedback("You do not appear to have access to this show.","Cannot display page. If you are encountering problems, please contact us.");
		return false;
	} else {
		return true;
	}
}
function checkSubmission()
{
	global $editors_subisok;
	if($editors_subisok) return true;
	if(isset($_POST['submitid']))
		$sid=$_POST['submitid'];
	else
		$sid=$_GET['submitid'];
	unset ($_GET['submitid']);	
	if(!isset($_SESSION['submithash'][$sid]))
	{
		inputFeedback("Could not process your request","You may have refreshed the page after performing an action, inadvertently resubmitting data which has already been processed.");
		return false;
	} else {
		unset($_SESSION['submithash'][$sid]);
		return true;
	}
}

function allowSubmission()
{
	global $editors_subisok;
	global $editors_allowed_submission;
	if(!$editors_allowed_submission)
	{
		if(isset($_GET['submitid']) || isset($_POST['submitid']))
		{
			if(isset($_GET['submitid']))
				$sid=$_GET['submitid'];
			else
				$sid=$_POST['submitid'];
			if(isset($_SESSION['submithash'][$sid]))
			{
				unset($_SESSION['submithash'][$sid]);
				$editors_subisok=true;
			} else $editors_subisok=false;
		}
	
		$submitid=md5(rand());
		$_SESSION['submithash'][$submitid]=true;
		$_GET['submitid']=$submitid;	// in case we are recreating submissions on page
		$_POST['submitid']=$submitid;
		$editors_allowed_submission=true;
	} else $submitid=$_GET['submitid'];
	
	return $submitid;
	
}

function confirmer($whattoconfirm = "")
{
	if ($whattoconfirm == "")
		return "onclick=\"return confirmLink(this, 'Are you sure?')\" class=\"confirmable\"";
	else
		return "onclick=\"return confirmLink(this, 'Are you sure you want to $whattoconfirm?')\" class=\"confirmable\"";
}

function displaySocField($idfield,$target,$otherfield,$contents,$type)
{
 ?>
	<select name="<?=$idfield?>" id="<?=$idfield?>" onChange="updateFormState(<?=$idfield?>,venue,true);")>
            <option value="-1"></option>
            <?php
	$query = "SELECT name,id FROM acts_societies WHERE type=$type ORDER BY name";
	$r=sqlQuery($query);
	if($r>0)
	{
		while($soc = mysql_fetch_assoc($r))
		{
			echo('<option value="'.$soc['id'].'"');
			if($soc['id']==$target) echo(" selected");
			echo ('>'.htmlspecialchars($soc['name'])."</option>");
		}
		mysql_free_result($r);
	}
	?>
            <option value="0" <?php if($target==0) echo("selected");?>>Other
            (please specify)
          </select>
            <br>
Select from list above, or choose &quot;other&quot; and specify below:<br>
<input name="<?=$otherfield?>" type="text" id="<?=$otherfield?>" value="<?php echo($contents);?>" size="30">
<?php
} 

function displayRangeField($page,$of,$offset=0,$tagname="page")
{
  if($page==$offset) $page=1+$offset;
?><form name="jumplist"> 
<script language="JavaScript" type="text/JavaScript">
<!--
function gotopage( list ) {
eval("parent.location='"+list.options[list.selectedIndex].value+"'");
}
//-->
</script>
<?php
  global $currentid;
  if($of>1) {
    if($page>1+$offset) 
     makeLink($currentid,"&lt; back",array($tagname=>$page-1));
     else
     echo("&lt; back");
  echo(" | page ");
?><select name="menu" onChange="gotopage(this)"><?php
for($n=1;$n<=$of;$n++)
{
echo("<option value=\"".thisPage(array($tagname=>$n+$offset))."\" ".(($n+$offset==$page)?"selected":"").">".$n."</option>");
}?>
</select>
  <?php
  echo(" of ".$of." | ");
if($page<$of+$offset)
     makeLink($currentid,"next &gt;",array($tagname=>$page+1));	
		else
	echo("next &gt;");
} else echo "(Page 1 of 1)";
?> </form> <?php
	}

function perPage($perpage)
{ ?>
<select name="perpage" id="perpage">
<option value="10" <?php if($perpage==10) echo("selected"); ?>>10</option>
<option value="20" <?php if($perpage==20) echo("selected"); ?>>20</option>
<option value="40" <?php if($perpage==40) echo("selected"); ?>>40</option>
<option value="0" <?php if($perpage==0) echo("selected"); ?>>All</option>
</select>
<?php
}
			
function splitResults($results,$perpage,$page)
{
	if($perpage>1)
	{
		$totalRows_results = mysql_num_rows($results);
		$maxpage=ceil($totalRows_results/$perpage);
		
		if($totalRows_results>$perpage)
		{
			if($page<1 || $_GET['page']>$maxpage)
				$page=1;
			$start=($page-1)*$perpage;
			for($n=0;$n<$start;$n++)
			{
				mysql_fetch_assoc($results);
			}
		}
		return $maxpage;
	} else return 1;
	
}

function timeField($hour,$minute,$tag="")
{$hour = substr("00",0,2-strlen($hour)).$hour;
$minute = substr("00",0,2-strlen($minute)).$minute;
?>
<input name="<?=$tag?>hour" type="text"  value="<?php echo($hour); ?>" size="4" maxlength="2">
:
<input name="<?=$tag?>minute" type="text"  value="<?php echo($minute); ?>" size="4" maxlength="2">
(24 hour clock)<?php
}

function generateSelect($select_name,$values_array,$selected,$default,$ids=array(),$extratag="")
{

  if($selected=="") $selected=$default;
  ?><select name="<?=$select_name?>" id="<?=$select_name?>"<?=$extratag?>>
<?php
       foreach($values_array as $id=>$value) {
         echo "<option value=\"".(isset($ids[$id])?$ids[$id]:$value)."\"";
         if($value==$selected) echo " selected";
         echo ">$value</option>";
       }
  echo "</select>";
}

function timeFieldSQL($SQLtime,$tag="")
{
	$tm=strtotime($SQLtime);
	timeField(date("H",$tm),date("i",$tm),$tag);
}

function termCalculator()
{
?><script language="JavaScript" type="text/JavaScript">
//<!--

function updatePerfDates(term,week,dayofweek,day,month,year)
{
 
  var datestring = term.options[term.selectedIndex].value;
  var yy=datestring.substring(0,4);  
  datestring = datestring.substring(5,100);
  var mm=datestring.substring(0,datestring.indexOf('-'));
  var dd=datestring.substring(datestring.indexOf('-')+1,100);
  var rdate = new Date(yy,mm-1,dd);
  rdate.setDate(rdate.getDate()+(7*week.value)+dayofweek.selectedIndex);
  
  day.value=rdate.getDate();
  month.selectedIndex = rdate.getMonth();
  var insyear = rdate.getYear();
  if(insyear<1900) insyear+=1900;  // some browsers return an offset?
  year.value=insyear;
}


 function showBox() {
   // We have IE to thank for the wonderousness of the following three lines...
   hideElms("startmonth");
   hideElms("endmonth");
   hideElms("excludemonth");
   hideElms("venid");
   showElms("datecal");
 }

 function hideBox() {
   // more code for IE
   showElms("startmonth");
   showElms("endmonth");
   showElms("excludemonth");
   showElms("venid");
   hideElms("datecal");
   
 }
//-->
</script>
<?php
    echo "<p>A <a href=\"#\" onClick='showBox(); return false;'>date calculator</a> is available to work out the performance dates from the term and week number.</p>";
    echo "<div class=\"secretlayer\" id=\"datecal\">";
  $update1 = "updatePerfDates(term,startweek,startdow,startday,startmonth,startyear);";
  $update2 = "updatePerfDates(term,endweek,enddow,endday,endmonth,endyear);";
  echo "<table class=\"editor\"><tr><td colspan=\"2\"><strong>Calculate Dates by Term</strong><br/>N.B. Theatre weeks run from Mon-Sun, not Thu-Wed</td></tr><tr><th>term</th><td><select name=\"term\">";
  $q = "SELECT * FROM acts_termdates ORDER BY enddate ASC";
  $res = sqlQuery($q);
  $sel = 0;
  while($row=mysql_fetch_assoc($res))
    {
      echo "<option value=\"".$row[startdate]."\"";
      if($sel==1) {
	echo " selected";
	$sel=2;
      }
      echo ">".$row[name]."</option>";
      if(strtotime($row[enddate])<time())
	$sel=1;
    }
  mysql_free_result($res);
  
  $daysofweek = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
  $dayoffsets = array(0,1,2,3,4,5,6);
  $currentweek = whatWeek(time());
  if($currentweek<0 || $currentweek>8) $currentweek=1;
  echo "</select></td></tr><tr><th>from</th><td>";
  
  generateSelect('startdow',$daysofweek,"Tue","Tue",$dayoffsets);
  echo " in week ";
  echo "<input id=\"startweek\" value=\"$currentweek\" maxchars=\"2\" size=\"2\">";
  echo "</td></tr><tr><th>to</th><td>";
  generateSelect('enddow',$daysofweek,"Sat","Sat",$dayoffsets);
  echo " in week ";
  echo "<input id=\"endweek\" value=\"$currentweek\" maxchars=\"2\" size=\"2\">";
    ?></td></tr><tr><td>&nbsp;</td><td>you must click<br/><input type="button" name="calculate dates" value="calculate dates" onClick='<?=$update1?> <?=$update2?> hideBox();'>
<br/>before continuing<br/><small><a href="" onClick='hideBox(); return false;'>or click here to close without calculating</a></small></td></tr></table></div>
<?php
}


function dateField($year,$month,$day,$tag="")
{
?><input name="<?=$tag?>day" id="<?=$tag?>day" type="text"  value="<?php if($day==0) echo(date("d")); else echo($day);?>" size="4" maxlength="2">
<select name="<?=$tag?>month" id="<?=$tag?>month">
<?php $aim=($month==0)?date("m"):$month;?>
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
<input name="<?=$tag?>year" id="<?=$tag?>year" type="text" value="<?php if($year==0) echo(date("Y")); else echo($year);?>" size="7" maxlength="4">

<?php
}

function dateFieldSQL($SQLdate,$tag="")
{
	if (!isset($SQLdate)) {
		$tm=time();
	}
	else {
		$tm=strtotime($SQLdate);
	}
	dateField(date("Y",$tm),date("m",$tm),date("d",$tm),$tag);
}

function inputFeedback($title="",$fb="", $realHTML=false)
{
	if($title=="") 
	{
		$title="An error occured in processing your request";
		$fb="Please try again.";
	}
	if(!$realHTML)
	{
		$title=htmlspecialchars($title);
		$fb=htmlspecialchars($fb);
		$title=nl2br($title);
		$fb=nl2br($fb);
	}
?><p><table border="0" cellspacing="1" cellpadding="0" class="inputfeedback">
  <tr>
    <td><p><strong><?=$title?></strong></p><?php if($fb!="") echo("<p>".$fb."</p>"); ?></td>
  </tr>
</table></p>
<?php
}

function sqlEr($q="")
{
	global $adcdb;
	$er=htmlspecialchars(mysql_error($adcdb));
	if($q!="") $er.="<br><br>".htmlspecialchars($q);
	if(hasEquivalentToken('security',-3,$_SESSION[userid]) || hasEquivalentToken('security',-3,$_SESSION[ghostid]))
		inputFeedback("SQL Error",$er,true);
	else
		inputFeedback();
}
?>
