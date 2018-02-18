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
require_once("library/user.php");
require_once("library/kb.php");

/**
 * @protected Internal Menu handling function.
*/

function putMenuItem($id, $text, $class, $level, $link, $aslink=true)	// nothing clever, just puts the code for an individual item
{ ?>
	<tr><td class="<?php echo "$class";?>"><table border="0" cellspacing="0" cellpadding="0"><tr><td class="<?php echo "$class";?>"><?php 
		for($n=0;$n<$level;$n++) echo "&nbsp;&nbsp;";   // space out   
	if($aslink) {
		if($link=="") {
			print startLink($id); ?><span class="<?=$class?>lk"><?php echo $text; 
		}
		else {
			print "<a href=\"$link".$id."\">"; ?><span class="<?=$class?>lk"><?php echo $text; 
		}
	} else echo $text; ?></span></a></td></tr></table></td></tr><?php 
} 

/**
 * @protected Internal Menu handling function
*/

function getSelItem($endid, $topid)		
{

	global $adcdb;

	$query_retmenu = "SELECT * FROM acts_pages WHERE acts_pages.id=$endid AND acts_pages.parentid=$topid ORDER BY fulltitle";

	$retmenu = sqlQuery($query_retmenu, $adcdb) or die(mysql_error());

	$totalRows_retmenu = mysql_num_rows($retmenu);
	if($totalRows_retmenu==0)			// i.e. the menu item to select is not in the query
	{

		//1. Find out parent of this item

		mysql_free_result($retmenu);
		$query_retmenu = "SELECT * FROM acts_pages WHERE acts_pages.id=$endid";
		$retmenu = sqlQuery($query_retmenu, $adcdb) or die(mysql_error());
		$row_retmenu = mysql_fetch_assoc($retmenu);
		$parentid=$row_retmenu['parentid'];

		//2. Get candidate

		mysql_free_result($retmenu);
		$query_retmenu = "SELECT * FROM acts_pages WHERE acts_pages.id=$parentid";
		$retmenu = sqlQuery($query_retmenu, $adcdb) or die(mysql_error());
		$totalRows_retmenu = mysql_num_rows($retmenu);
		if($totalRows_retmenu==0)
			return 0;

		// try each in turn if more than one (shouldn't be)
		while($row_retmenu = mysql_fetch_assoc($retmenu))
		{
			$r = getSelItem($row_retmenu['id'],$topid);	// recurse
			if($r!=0) 
			{
				mysql_free_result($retmenu);
				return $r;
			}
		}
		mysql_free_result($retmenu);
		return 0;
	}

	return $retmenu;
}

/**
 * @protected Internal Menu function
*/

function getSelID($endid, $parentid)
{
	$query=getSelItem($endid,$parentid);
	if($query!=0)
	{
		$row=mysql_fetch_assoc($query);
		$idr=$row['id'];
		mysql_free_result($query);

		return $idr;
	}
	else return -1;
}

/**
* Display menu
* You need to call putMenu(0,$currentid,0); to display the menu
* @param $parentid Will create a menu of the children of this pageid
* @param $targetid This pageid will be highlighted
* @param $level Sets the indentation of the submenu being displayed
* @param $link Someone please explain this
* @param $allowOrphans Creates a menu entry for orphans
* @return none
*/

function putMenu($parentid,$targetid,$level,$link="",$allowOrphans=false)
{
	global $adcdb;

	if($targetid!=-1)
		$thislevel_sel_id=getSelID($targetid, $parentid);
	else
		$thislevel_sel_id=-1;


	$query_menu = "SELECT * FROM acts_pages WHERE acts_pages.parentid = $parentid ORDER BY acts_pages.fulltitle";
	$menu = sqlQuery($query_menu, $adcdb) or die(mysql_error());

	while ($row_menu = mysql_fetch_assoc($menu)) { 
		if ($thislevel_sel_id==$row_menu['id']) 
		{
			if($thislevel_sel_id==$targetid) $class="menucellselected";  else $class="menucellgroup";
		} else $class="menucell";
		if(($row_menu['id']>0 && $row_menu['parentid']>=0 && ($row_menu['ghost']==0 || $thislevel_sel_id==$row_menu['id'])) || $allowOrphans)
		{	
			putMenuItem($row_menu['id'],$row_menu['fulltitle'],$class,$level,$link,$row_menu['ghost']==0 || $allowOrphans);
		}
		if ($thislevel_sel_id==$row_menu['id'])
		{
			// put submenu
			if($thislevel_sel_id==$targetid)
				putMenu($thislevel_sel_id,-1,$level+1,$link,$allowOrphans);	
			// for the case when it's open because you're at the top
			else
				putMenu($thislevel_sel_id,$targetid,$level+1,$link,$allowOrphans);
			// for the case when it's open because you're underneath

		}
	} 
	if($allowOrphans && $parentid==0)
	{
		// Emulate an entry for orphans
		// (Adding one to the database screws things up a bit)
		$row_menu['id']=-1;
		$row_menu['parentid']=0;
		$row_menu['fulltitle']="orphans";
		if ($thislevel_sel_id==$row_menu['id']) 
		{
			if($thislevel_sel_id==$targetid) $class="menucellselected";  else $class="menucellgroup";
		} else $class="menucell";
		putMenuItem($row_menu['id'],$row_menu['fulltitle'],$class,$level,$link);
		if ($thislevel_sel_id==$row_menu['id'])
		{
			// put submenu
			if($thislevel_sel_id==$targetid)
				putMenu($thislevel_sel_id,-1,$level+1,$link,true);
			else
				putMenu($thislevel_sel_id,$targetid,$level+1,$link,true);

		}
	}
	mysql_free_result($menu);
}

if(!hasEquivalentToken('security',-2))
{
	inputFeedback("Cannot load the page","This page could not be loaded");
} else {
if(isset($_GET['virtualid'])) $virtualid=$_GET['virtualid'];	
	else $virtualid=1;

if(isset($_GET['drop']))
{
	if(checkSubmission())
	{
		$p=$_GET['pick'];
		$d=$_GET['drop'];
		$query="UPDATE acts_pages SET parentid=$d WHERE id=$p LIMIT 1";
		sqlQuery($query);
		unset($_GET['drop']);
		unset($_GET['pick']);
	}
}
if(isset($_GET['deleteid']))
{
	if(checkSubmission())
	{
		$d=$_GET['deleteid'];
		$query="DELETE FROM acts_pages WHERE id=$d";
		sqlQuery($query);
		$query="UPDATE acts_pages SET parentid=-1 WHERE parentid=$d";
		sqlQuery($query);
		unset($_GET['delete']);
		unset($_GET['deleteid']);
		inputFeedback("Deleted!");
		actionlog("delete acts_pages page $d");
	}
}
if(isset($_POST['submit']))
{
	if(checkSubmission())
	{
		if($_POST['submit']=="Edit")
		{
			$t=$_POST['title'];
			$ft=$_POST['fulltitle'];
			$sc=$_POST['sortcode'];
			$help=$_POST['help'];
			$mode=$_POST['mode'];
			$rssfeeds=$_POST['rssfeeds'];
			$usepage = $_POST['usepage'];
			$micro=0;
			$sec=0;
			$kbid = $_POST[kbid];
			$ghost=0;
			$virtual=0;
			$param_parser=0;
			$allowsubpage=0;
			if($_POST['micro']!="") $micro=1;
			if($_POST['secure']!="") $sec=1;
			if($_POST['ghost']!="") $ghost=1;
			if($_POST['virtual']!="") $virtual=1;
			if($_POST['param_parser']!="") $param_parser=1;
			if($_POST['allowsubpage']!="") $allowsubpage=1;
			$query="UPDATE acts_pages SET kbid='$kbid', rssfeeds='$rssfeeds', title='$t', fulltitle='$ft', sortcode='$sc', secure=$sec, allowsubpage=$allowsubpage, micro=$micro, ghost=$ghost, virtual=$virtual, param_parser=$param_parser, help='$help', mode='$mode', usepage='$usepage' WHERE id=$virtualid LIMIT 1";
			$cache_dirty = 1;
			$z=sqlQuery($query);
			if($z>0)
				inputFeedback("Updated!");
			else
				sqlEr($query);
			actionlog("edit acts_pages page $virtualid");
		}
		if($_POST['submit']=="Create")
		{
			$t=$_POST['title'];
			$ft=$_POST['fulltitle'];
			$pi=$_POST['parentid'];
			$query="INSERT INTO acts_pages (title, fulltitle, parentid) VALUES ('$t','$ft','$pi')";
			$cache_dirty = 1;
			echo($query);
			$z=sqlQuery($query);
			if($z==0)
				sqlEr($query);
			else {
				$virtualid=mysql_insert_id($adcdb);
				$_GET['virtualid']=$virtualid;
				if($virtualid==0) 
				{
					inputFeedback("Unknown creation error");
				} else {
					// try to write the file
					if ($handle = fopen("content/".$virtualid.".php", 'wb+')) {
						fwrite($handle,"<p><b>Page under construction</b></p>");
						fclose($handle);
						inputFeedback("Created!");
					} else {
						 inputFeedback("Menu item is created but couldn't open file to write contents.");
					}
			 
				}
			}
		}
	}
	
}

if(isset($_POST['updateid']))
{
	if(checkSubmission())
	{
		$query_checkupdate = "SELECT * FROM acts_pages WHERE acts_pages.id=".$_POST['updateid'];
		$upd = sqlQuery($query_checkupdate, $adcdb) or die(mysql_error());
		$updrow=mysql_fetch_assoc($upd);
		mysql_free_result($upd);
		if(hasEquivalentToken("security",modeToSecurity($updrow['mode'])))
		{
			$wid=$_POST['updateid'];
			$cts=stripslashes($_POST['updatepage']);		// remove magic quotes
			$virtualid=$wid;
			if ($handle = fopen("content/".$wid.".php", 'wb+')) {
				fwrite($handle,$cts);
				fclose($handle);
				actionlog("Update page $wid");
				echo("<p><b>Page updated!</b></p>");
			} else {
				actionlog("Failed attempting to update page $wid");
				 echo("<p><b>Couldn't write page contents</b></p>");
			}
		} else inputFeedback();
		
		unset($_POST['updateid']);
		unset($_POST['updatepage']);
	}
}

if ($cache_dirty == 1)
	RegenerateCaches();

allowSubmission();
// some information for our own use about the current page
$query_retmenu = "SELECT * FROM acts_pages WHERE acts_pages.id=$virtualid";
$retmenu = sqlQuery($query_retmenu, $adcdb) or die(mysql_error());
$row=mysql_fetch_assoc($retmenu);
mysql_free_result($retmenu);

 ?>
<table width="815" border="0" cellpadding="10" cellspacing="5" bordercolor="#000000" bgcolor="#000000">
  <tr>
    <td width="189" bgcolor="#FFFFFF"><table width="150" border="0" cellspacing="1" cellpadding="2">
<?php 
$clickParam['virtualid']="NOSET";
putMenu(0,$virtualid,0,thisPage($clickParam)."&virtualid=",true);


?>
</table></td>
    <td width="603" bordercolor="#FFFFFF" bgcolor="#FFFFFF">
	<?php if($virtualid!=-1) { 
	$securitynecessary=modeToSecurity($row['mode']);
	$allaccess = hasEquivalentToken("security",$securitynecessary);
	if($allaccess || $row['secure']==0)
	{
		if(!$allaccess)
			inputFeedback("Access to this page is restricted because it contains executable code");
		?><p><strong>For currently selected page:</strong><br><?php
		$pickParam['pick']=$virtualid;
		?><a href="<?=thisPage($pickParam)?>">Pick</a><?php 
	 	if(isset($_GET['pick']))
		{
			if($_GET['pick']==$virtualid)
			{
				$dropParam['drop']=0;
				?>| <a href="<?=thisPage($dropParam)?>&drop=0">Drop page to root level</a> <?php
			} else {
				$dropParam['drop']=$virtualid;
				?>| <a href="<?=thisPage($dropParam)?>">Drop page <?=$_GET['pick']?> under
		this heading</a> <?php
			}
		}  
		if($allaccess)
		{
			$deleteParam['deleteid']=$virtualid; $deleteParam['virtualid']=$row['parentid']; 
			if ($deleteParam['virtualid']==0) $deleteParam['virtualid']=1; ?>
			| <a href="<?=thisPage($deleteParam)?>" <?=confirmer("delete " . $row['fulltitle'])?>>Delete</a></p>	</p>
			<p>
			  <?php
		} ?>
			</p>
			<form name="form2" method="post" action="<?=thisPage()?>">
    	
    	<h4>Edit This Page (ID: <?=$virtualid?>
) </h4>
    	<table class="editor">
    	  <tr>
    	    <th><label>URL:</label> </th>
    	    <td><?php if($allaccess) { ?><input name="title" type="text" id="title" value="<?=$row['title']?>"><?php } else echo "<strong>".$row['title']."</strong>"; ?></td>
  	    </tr>
    	  <tr>
    	    <th><label>Title:</label> </th>
    	    <td><?php if($allaccess) { ?><input name="fulltitle" type="text" id="fulltitle" value="<?=$row['fulltitle']?>"><?php } else echo "<strong>".$row['fulltitle']."</strong>"; ?></td>
  	    </tr>
    	  <tr>
    	    <th><label>Sortcode:</label> </th>
    	    <td><?php if($allaccess) { ?><input name="sortcode" type="text" id="sortcode" value="<?=$row['sortcode']?>"><?php } else echo "<strong>".$row['sortcode']."</strong>"; ?></td>
  	    </tr>
																							 <tr><th><label>Knowledgebase Help Link: </label></th><td><input name="kbid" type="text" id="kbid" value="<?=$row[kbid]?>"></td></tr><tr><th><label>Relevent RSS feeds: </label></th><td><input name="rssfeeds" type="text" id="rssfeeds" value="<?=$row[rssfeeds]?>">&nbsp;<small>separate with semicolon</small></td></tr>
  	 
          <tr>
            <th><label>Microcontent:</label></th>
            <td><?php if($allaccess) { ?><input name="micro" type="checkbox" id="micro" value="checkbox" <?php if($row['micro']>0) echo("checked"); ?>><?php } else echo "<strong>".(($row['micro']>0)?"yes":"no")."</strong>"; ?> (select to open page in a smaller popup window)</td>
          </tr>
          <tr>
            <th><label>Ghost:</label></th>
            <td><?php if($allaccess) { ?><input name="ghost" type="checkbox" id="ghost" value="checkbox" <?php if($row['ghost']>0) echo("checked"); ?>><?php } else echo "<strong>".(($row['ghost']>0)?"yes":"no")."</strong>"; ?> (select to stop page being visible on any menus)</td>
          </tr>
          <tr>
            <th><label>Virtual:</label></th>
            <td><?php if($allaccess) { ?><input name="virtual" type="checkbox" id="virtual" value="checkbox" <?php if($row['virtual']>0) echo("checked"); ?>><?php } else echo "<strong>".(($row['virtual']>0)?"yes":"no")."</strong>"; ?> (select to make page use its parent's URL)</td>
          </tr>
          <tr>
            <th><label>Parameter parser:</label></th>
            <td><?php if($allaccess) { ?><input name="param_parser" type="checkbox" id="param_parser" value="checkbox" <?php if($row['param_parser']>0) echo("checked"); ?>><?php } else echo "<strong>".(($row['param_parser']>0)?"yes":"no")."</strong"; ?> (select to make the parent delegate parameter-parsing to this page)</td>
          </tr>
          <tr>
            <th><label>Protect:</label>
            </th>
            <td><?php if($allaccess) { ?>
              <input name="secure" type="checkbox" value="checkbox" <?php if($row['secure']>0) echo("checked"); ?>>
		 <?php } else echo "<strong>".(($row['secure']>0)?"yes":"no")."</strong>"; ?> (prevents page being accessed by non logged in users, as a first security measure - rarely enough in itself!)
            </td>
          </tr>
          <tr>
            <th><label>Allow create:</label></th>
            <td><?php if($allaccess) { ?><input name="allowsubpage" type="checkbox" id="allowsubpage" value="checkbox" <?php if($row['allowsubpage']>0) echo("checked"); ?>>
              <?php } else echo "<strong>".(($row['secure']>0)?"yes":"no")."</strong>"; ?>              (allows
              creation of pages below this one by any &quot;knowledgebase&quot; user)</td>
          </tr>
          <tr>
            <th><label>Include mode:</label></th>
											   <td><?php if($allaccess) { ?><select name="mode">
            <?php if(hasEquivalentToken("security",modeToSecurity('filtered'))) { ?><option value="filtered" <?php if($row['mode']=="filtered") echo("selected"); ?>>Knowledgebase</option><?php } ?>
<?php if(hasEquivalentToken("security",modeToSecurity('filtered'))) { ?><option value="menuitem" <?php if($row['mode']=="menuitem") echo("selected"); ?>>No content (just menu)</option><?php } ?>
            <?php if(hasEquivalentToken("security",modeToSecurity('normal')))  { ?><option value="normal" <?php if(!isset($row) || $row['mode']=="normal") echo("selected"); ?>>Standard</option><?php } ?>
			<?php if(hasEquivalentToken("security",modeToSecurity('noprocess'))) { ?><option value="noprocess" <?php if(!isset($row) || $row['mode']=="noprocess") echo("selected"); ?>>No processing</option><?php } ?>
            <?php if(hasEquivalentToken("security",modeToSecurity('include'))) { ?><option value="include" <?php if($row['mode']=="include") echo("selected"); ?>>Include</option><?php } ?>
            </select><?php } else echo ("<strong>".$row['mode']."</strong>"); ?>
            </td>
	    <tr><th><label>Use page:</label></th><td><?php
		$path = "admin";
		$dir_handle = @opendir($path) or die("Unable to open $path");
		$possibilities[0]="";
		$n=1;
		//running the while loop
		while ($file = readdir($dir_handle)) {
		  if($file!="." && $file!=".." && $file!="CVS")
		    {
		      $possibilities[$n]=$file;
		      $n++;	
		    }
		}

		//closing the directory
		closedir($dir_handle);
		generateSelect('usepage',$possibilities,$row['usepage'],""); ?></td>
          </tr>
	  
		  <tr>
		  <th><label>Page Contents:</label></th><td><?php 
		  if($allaccess) { ?><textarea name="help" cols="80" rows="5" id="help"><?=htmlspecialchars($row['help'])?></textarea>
		  			<?php } else echo($row['help']); ?>
<br/>The page contents may be defined by the relevent file if the page mode
								    is set to <em>include</em>. This field is <?=makeLink('translator','translated')?>.
		  </td>
		  </tr>
        </table>
		  
	  </p>	
	  <input type="submit" name="submit" value="Edit">
 </form>
    
    <p><br>
      <?php 
	} else inputFeedback("This page is not editable.","It is part of the infrastructure of the website and changing its code may be a security risk");
	} else inputFeedback("This is a virtual container for pages which have a parent ID of -1 and so do not appear
	on the menu. It is not editable.");?>    
    <form name="form1" method="post" action="<?=thisPage()?>">
  
        <h4>Insert a Page Underneath this Heading (Parent
        ID:
        <?=$virtualid?>
        ) </h4><p>
        <input name="parentid" type="hidden" id="parentid2" value="<?=$virtualid?>">
    <br>
    <table border="0" cellspacing="1" cellpadding="0">
      <tr>
        <td>Title on <strong>menu:</strong></td>
        <td><input name="title" type="text" id="title"></td>
      </tr>
      <tr>
        <td>Title on <strong>page:</strong></td>
        <td><input name="fulltitle" type="text" id="fulltitle"></td>
      </tr>
    </table>
    <input type="submit" name="submit" value="Create">
       
    </form>
    <?php
	if(isset($_GET['pick']))
	{
			$dropParam['drop']=-1
			?>
	    You can, however, <a href="<?=thisPage($dropParam)?>">drop page <?=$_GET['pick']?> under this heading</a>	    <?php
	} 
	?>
    </td>
  </tr>
</table>
<p>&nbsp;</p>
<?php } ?>
