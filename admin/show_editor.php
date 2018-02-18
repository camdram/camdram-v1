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
$showid=$_GET['showid'];
$display=false;
$maxtechlines=getconfig("techies_info_max_lines");
	if(canEditShow($showid))
	{
		$query_show = "SELECT * FROM acts_shows WHERE acts_shows.id=$showid ";
		$show = sqlQuery($query_show, $adcdb) or die(mysql_error());
		$row_show = mysql_fetch_assoc($show);
		mysql_free_result($show);
		$display=true;
	}


if($display)
{
    editorLinks($showid,"Information Editor");
    ?>
<form name="form1" method="post" action="<?=linkTo(77,array("CLEARALL"=>"CLEARALL"))?>">
<input name="submitid" type="hidden" id="submitid" value="<?=allowSubmission()?>">
<input name="showid" type="hidden" value="<?=$showid ?>">
  <h4>Non-optional fields</h4>
  <p>You must provide us with the following details about your show:   </p>
  <table class="editor" width="100%">
      
    <tr bordercolor="#CCCCCC">
      <th width="15%">Title</th>
      <td><input name="title" type="text" id="title3" value="<?php echo($row_show['title']);?>" size="30">
      </td>
			     </tr> 
    <tr bordercolor="#CCCCCC">
      <th>Author</th>
      <td><input name="author" type="text" id="author2" value="<?php echo($row_show['author']);?>" size="30">
        <br>
      (you may leave this blank only if there is no author or the author is unknown)</td>
    </tr>
    <tr bordercolor="#CCCCCC">
      <th>Society</th>
      <td><?php
	 if(hasEquivalentToken('security',-3))
	 {?>
        <p> It is essential you pick the correct society, as the Committee
          of that society will be asked to authorize your show before it will
          become available to the public. If the society is not displayed in
          the drop down list, they do not have an account with us and your show
          will be passed to ACTS. <?php if(hasEquivalentToken('society',0)) echo("Please note that if you select a society for which you are an administrator, the show will automatically be authorized.");?>
<script language="JavaScript" type="text/JavaScript">
//<!--
function updateFormState(societies,societytext,autoselect) 
{
	if(societies.options[societies.selectedIndex].value==0)
	{
		societytext.disabled=false;
		if(autoselect) societytext.focus();
	} else {
		societytext.value="";
		societytext.disabled=true;
	}
}

//-->
</script>
              <br>
              <select name="socid" id="societies" onChange="updateFormState(societies,societytext,true); ")>
                <option value="-1"></option>
                <?php
	$query = "SELECT name,id FROM acts_societies WHERE type=0 ORDER BY name";
	$r=sqlQuery($query);
	if($r>0)
	{
		while($soc = mysql_fetch_assoc($r))
		{
			echo('<option value="'.$soc['id'].'"');
			if($soc['id']==$row_show['socid']) echo(" selected");
			echo ('>'.htmlspecialchars($soc['name'])."</option>");
		}
		mysql_free_result($r);
	}
	?>
                <option value="0" <?php if($row_show['socid']==0) echo("selected");?>>Other (please specify)
              </select>		  
              <br>
        Select from list above, or choose &quot;other&quot; and specify below:<br>
        <input name="society" type="text" id="societytext" value="<?php echo($row_show['society']);?>" size="30">
        <br>
        </p>
		<?php
		} else {
			if(	$row_show['socid']>0)
			{
				$query = "SELECT name,id FROM acts_societies WHERE id=".$row_show['socid']." ORDER BY name";
				$r=sqlQuery($query);
				if($r>0)
				{
					$soc=mysql_fetch_assoc($r);
					mysql_free_result($r);
					echo("<strong>".$soc['name']."</strong>");
				} else echo("<strong>Unknown</strong>");
			} else {
				echo("<strong>".$row_show['society']."</strong>");
			}
			echo("<br>(It is not possible to change the society after creating the show.)");
		} ?>
      </td>
    </tr>
    <tr bordercolor="#CCCCCC">
      <th>Main Venue</th>
      <td>The venue where all or the majority of performances will be held.<br/>
      <select name="venid" id="venid" onChange="updateFormState(venid,venue,true);")>
        <option value="-1"></option>
        <?php
	$query = "SELECT name,id FROM acts_societies WHERE type=1 ORDER BY name";
	$r=sqlQuery($query);
	if($r>0)
	{
		while($soc = mysql_fetch_assoc($r))
		{
			echo('<option value="'.$soc['id'].'"');
			if($soc['id']==$row_show['venid']) echo(" selected");
			echo ('>'.htmlspecialchars($soc['name'])."</option>");
		}
		mysql_free_result($r);
	}
	?>
        <option value="0" <?php if($row_show['venid']==0) echo("selected");?>>Other
        (please specify)
      </select>
        <br>
Select from list above, or choose &quot;other&quot; and specify below:<br>
<input name="venue" type="text" id="venue" value="<?php echo($row_show['venue']);?>" size="30">
        <br>
        (only leave this field blank if a venue has not yet been confirmed and
      you are opening applications/auditions)</td>
    </tr>
  </table>
  <h4>Performance Information</h4>

<div id="showperftab">    
<?php
echo showPerfTable($showid);
?></div><p>To alter these details, you need to go to the <?=makeLinkText("show_times.php","performance editor",array("showid"=>$showid),true)?></p>

  <h4>Optional Fields</h4>
  <p>You may also want to provide the following details:</p>
  <table width="100%" class="editor">

    <tr>
      <td width="200"><strong>Prices</strong></td>
      <td><input name="prices" type="text" id="prices" value="<?php echo(htmlspecialchars($row_show['prices']));?>" size="30">
      </td>
    </tr>
    <tr>
      <td width="200"><strong>Show Category</strong><br/>This information is not used
by camdram.net but is available to external websites from our feeds.</td><td><?php genericSelect("acts_shows","category",$row_show[category], array('drama','comedy', 'musical', 'opera', 'dance', 'other')); ?></td></tr>
    <tr>
      <td><strong>Description</strong><br>
          <em>This field is
          <?php makeLink(107,"translated"); ?>
      .</em><br />Only the first paragraph will appear in emails created for auditions and technical position email lists</td>
      <td><textarea name="desc" cols="80" rows="10" wrap="VIRTUAL" id="textarea"><?php echo($row_show['description']);?></textarea>
  <p><input type=checkbox name="unwrap" value=1 checked> Strip carriage returns (useful when copying from emails)</p>
      </td>
    </tr>
    <tr>
      <td width="200"><strong>Photo</strong></td>
      <td><?php if($row_show['id']<1) echo("Can't upload photo until the show is created. Please return to the show editor once you have created the show and upload the file then."); else {
	  	if($row_show['photourl']!="") {
			echo("There is currently a photo uploaded for this show. You can overwrite it with another by ");
			makeLink(100,"uploading",array("retid"=>76, "uploadtype"=>"show"));
			echo(" or you can delete it by ticking the box below.<br /><br />");
			?> <input name="deletephoto" type="checkbox" id="deletephoto" value="checkbox"> delete photo <?php
		} else makeLink(100,"upload photo",array("retid"=>76, "uploadtype"=>"show"));
		
	  } ?></td>
    </tr>
    <tr> 
      <td><strong>Online Booking URL</strong></td>
    <td><input name='onlinebookingurl' type='text' id='onlinebookingurl' value='<?php echo(htmlspecialchars($row_show['onlinebookingurl'])) ?>' size='80' /></td>
    </tr>
    <tr> 
      <td><strong>Facebook URL</strong></td>
      <td><input name='facebookurl' type='text' id='facebookurl' value='<?php echo(htmlspecialchars($row_show['facebookurl'])) ?>' size='80' /></td>
    </tr>
    <tr> 
      <td><strong>Website URL</strong></td>
      <td><input name='otherurl' type='text' id='otherurl' value='<?php echo(htmlspecialchars($row_show['otherurl'])) ?>' size='80' /></td>
    </tr>
  </table>
  <p>
  <input name="create" type="submit" value="Edit">
  <br />
  </p>
<script language="JavaScript" type="text/JavaScript">
//<!--
updateFormState(document.forms[0].societies,document.forms[0].societytext,false);
updateFormState(document.forms[0].venid,document.forms[0].venue,false);
//-->
</script>	
</form>
<p>&nbsp;</p>
<?php 
}?>
