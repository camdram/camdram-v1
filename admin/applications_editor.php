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

global $adcdb;
require_once("library/editors.php");
require_once("library/showfuns.php");
if (isset($_GET['showid']) && hasEquivalentToken("show",$_GET['showid'])) {
	$field="showid";
	$showsoc="show";
	$value=mysql_real_escape_string($_GET['showid']);
	$showrow=getShowrow($value);
	$showname=$showrow['title'];
	editorLinks($showrow,'Applications');
	inputFeedback("Adverts entered on this page are limited to those for directors, producers, choreographers, musical directors and similar. In other cases you are advised to use the production team or auditions page as appropriate.");
}
if (isset($_GET['socid']) && hasEquivalentToken("society",$_GET['socid'])) {
	$field="socid";
	$value=mysql_real_escape_string($_GET['socid']);
	$showsoc="society";
	$socrow=getSocrow($value);
	$socname=$socrow['name'];
	echo "<h3>Applications for $socname";
	echo "<div class=\"headerbuttons\">";
	makeLink("socs_manager.php","&lt; back to Society Manager"); 
	echo "</div></h3>";
	
}
if (isset($_POST['unwrap'])) {
	$desc=str_replace("\n\n","\r\r",$_POST['furtherinfo']);
	$desc=str_replace("\n"," ",$desc);
	$_POST['furtherinfo']=str_replace("\r","\n",$desc);
}
if ($_POST['submit']=="Create" && checkSubmission()) {
	inputfeedback("Created advert");
	$deadlinedate=$_POST['deadlineyear']."-".$_POST['deadlinemonth']."-".$_POST['deadlineday'];
	$query="INSERT INTO acts_applications(`$field`,`text`,`furtherinfo`,`deadlinedate`,`deadlinetime`) VALUES ('$value','".mysql_real_escape_string($_POST[text])."','".mysql_real_escape_string($_POST[furtherinfo])."','".mysql_real_escape_string($deadlinedate)."','".mysql_real_escape_string($_POST[deadlinetimehour]).":".mysql_real_escape_string($_POST[deadlinetimeminute])."')";
	sqlQuery($query,$adcdb) or die(sqler());
	actionlog("Created applications advert for $showsoc $value");
}
if ($_POST['submit']=="Edit" && checkSubmission()) {
	inputfeedback("Updated advert");
	$deadlinedate=$_POST['deadlineyear']."-".$_POST['deadlinemonth']."-".$_POST['deadlineday'];
	$query="UPDATE acts_applications SET text='".mysql_real_escape_string($_POST[text])."',furtherinfo='".mysql_real_escape_string($_POST[furtherinfo])."',deadlinedate='".mysql_real_escape_string($deadlinedate)."',deadlinetime='".mysql_real_escape_string($_POST[deadlinetimehour]).":".mysql_real_escape_string($_POST[deadlinetimeminute])."' WHERE $field='$value'";
	sqlQuery($query,$adcdb) or die(sqler());
	actionlog("Edited applications advert for $showsoc $value");
}
if ($_POST['submit']=="Delete" && checkSubmission()) {
	inputfeedback("Deleted Advert");
	$query="DELETE FROM acts_applications WHERE $field='$value'";
	sqlQuery($query,$adcdb) or die(sqler());
	actionlog("Deleted applications advert for $showsoc $value");
}

$query="SELECT * FROM acts_applications WHERE $field='$value'";
$result=sqlQuery($query,$adcdb) or die(sqlEr());
if ($row=mysql_fetch_assoc($result)) {
	$deadlinedate=$row['deadlinedate'];
	$deadlinetime=$row['deadlinetime'];
	$text=$row['text'];
	$furtherinfo=$row['furtherinfo'];
}
echo "\n<form method=\"POST\" action=\"".thisPage()."\">";
echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
?>
<table class="editor" width="100%">
<tr bordercolor="#CCCCCC">
<td><strong>Brief Description</strong>
         
</td>
<td><input name="text" wrap="virtual" size="80" maxchars="200" value="<?=addslashes(htmlspecialchars($text))?>"/>
<br /><?php 

if($field=="socid") echo "e.g. <em>Applications to Direct in Easter 2010</em> - your society name will be included automatically."; else echo "e.g. <em>Applications to Direct</em> - your show name will be included automatically.";
?></td></tr>
<tr bordercolor="#CCCCCC">
<td><strong>Further Information</strong>
          <br /><em>This field is
          <?php makeLink(107,"translated"); ?></em>
</td>
<td><textarea name="furtherinfo" wrap="virtual" rows=8 cols=80><?=$furtherinfo;?></textarea>
<br />You should include full information of how to apply here - any contact details, website addresses, etc.
<p><input type=checkbox name="unwrap" value=1 checked> Strip Carriage Returns (useful when copying from emails)</p></td>
</tr>
<tr bordercolor="#CCCCCC">
<td><strong>Deadline date</strong></td>
<td><?php DateFieldSQL($deadlinedate,"deadline");?></td>
</tr>
<tr bordercolor="#CCCCCC">
<td><strong>Deadline time</strong></td>
<td><?php timeFieldSQL($deadlinetime,"deadlinetime"); ?></td>
</tr>
</table>
<?php
if (!isset($row['id'])) {
	echo "<input type=\"submit\" name=\"submit\" value=\"Create\">";
}
else {
	echo "<input type=\"submit\" name=\"submit\" value=\"Edit\">";
	echo " <input type=\"submit\" name=\"submit\" value=\"Delete\">";
}
?>
</form>
