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

require_once("library/editors.php");

add_page_link ('user_update.php',"Change your password and preferences",array("retid"=>78));

$q = "SELECT * FROM acts_shows, acts_access WHERE ".securityQuery('society','acts_shows.`socid`')." AND acts_shows.authorizeid IS NULL AND acts_shows.entered=1";
$r = sqlQuery($q);
if($r>0)
{
	$n=mysql_num_rows($r);
	mysql_free_result($r);
	if($n!=0)
	{
		$extra="Please take a look via the show manager";
		if($n==1) inputFeedback("There is one show which has been created, but which is not visible.",$extra);
		else inputFeedback("There are ".$n." shows which have been created, but which are not visible. ",$extra);
	}
}?>
</p>
<?php
if(isset($_POST['su'])) {
	su($_POST['su']);
}
if(hasEquivalentToken('security',-1) || hasEquivalentToken('security',-1,$_SESSION['ghostid']))
{
?> 
<form name="form1" method="post" action="<?=thisPage()?>">
  <div align="left">
    <strong>Emulate User</strong> 
    
    <input name="su" type="text" size="10">
  <input type="submit" name="submit" value="Go">
    </div>
</form>
<?php
}
?>
<h4>Content Editing</h4>
<ul>
  <li><?php makeLink('show_manager.php',"Show Manager"); ?> (includes show blurb,
    audition times, production team adverts, programme entry)</li>
<?php if(hasEquivalentToken('society',0)) { ?><li><?php makeLink('socs_manager.php',"Societies/Venues Manager"); ?></li>
<li><?php makeLink('event_editor.php',"Events Editor"); ?> (events appear on the diary page)</li><?php } ?>
<?php if(hasEquivalentToken('include',0)) { ?><li><?php makeLink('includes_editor.php',"Includes Editor"); ?></li><?php } ?>
<?php if(hasEquivalentToken('store',0)) { ?><li><?php makeLink('stores_manager.php',"Stores Manager"); ?></li><?php } ?>
</ul>
<?php if(hasEquivalentToken('security',-2)) { ?>
<h4>ACTS Managers</h4>

<ul>
<?php if(hasEquivalentToken('security',-1)) { ?>
<li><?php makeLink('support.php','Support Manager'); ?></li>
<?php } ?>
<li><?php makeLink('heirarchy_editor.php','Content Editor'); ?></li>
<li><?php makeLink('season_manager.php','Term Editor'); ?></li>
<li><?php makeLink('config_edit.php','Configuration Editor'); ?></li>
<li><?php makeLink('people_manager.php','People Manager'); ?> (for problem-solving - for editing lists for shows, go via the show editor)</li>
</ul>
<?php } ?>
<h4>Security &amp; User Management</h4>
<ul>
  <li><?php makeLink('user_update.php',"Change your password and preferences",array("retid"=>78)); ?></li>
<?php if(hasEquivalentToken('security',-1)) { ?><li><?php makeLink('user_manager.php','User Manager'); ?></li><?php } ?>
<?php if(hasEquivalentToken('security',-1)) { ?><li><?php makeLink('log_viewer.php','View Log',array("logmode"=>0)); ?></li><?php } ?>
<?php if(hasEquivalentToken('security',-1)) { ?><li><?php makeLink('log_viewer.php','View Action Log'); ?></li><?php } ?>
<?php if(hasEquivalentToken('security',-1)) { ?><li><?php makeLink('cvsupdate.php','Update from CVS',array()); ?></li><?php } ?>
</ul>
<?php if (hasEquivalentToken('society',0)) { ?>
<h4>Emails</h4>
<ul>
<li><?php makeLink('email_builder.php','Email Builder'); ?></li>
<?php if(hasEquivalentToken('security',-2)) { ?>
 <li><?php makeLink('list_manager.php','Mailing List Manager'); ?> (for creating/editing lists - to add/remove users from a mailing list, use the user manager)</li>

<li><?php makeLink('list_retrieve.php','Retrieve Email Address List'); ?> </li>
									      <?php } ?>
</ul>
<?php }?>
<p align="left"><strong>camdram.net<br/></strong>
&copy; 2003-2012 Andrew Pontzen, John Dilley, Andrew Sobala &amp; Alex Brett.<br/>
</p>
