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

require_once("editors.php");
require_once("user.php");
if(!isset($_SESSION))
{
	session_start();
}  
/**
 * Check whether user has access to the knowledgebase.
 * Note this does not check the user is logged in
 * @return true if they do have access, false if not
*/

function canusekb() {
	return isset($_SESSION['userid']) && (!hasToken('knowledgebaseblocked',0) or hasEquivalentToken("security",-3));
}

/** Display a knowledgebase page
 * @param $page Associative array of the relevant page taken from acts_pages
 * @param $micro Whether the page is being displayed in micro.php
 * @return none
 */

function knowledgeBasePage($page,$micro=false)
{
	global $base,$adcdb;
	$query="SELECT * FROM acts_knowledgebase WHERE pageid='" . $page['id'] . "' ORDER BY id DESC LIMIT 1";
	$result=sqlQuery($query,$adcdb) or die(sqlEr());
	$currev=mysql_fetch_assoc($result);
	$pagetext=$currev['text'];
	$currevuser=getuserrow($currev['userid']);
	$pagerev="<p class=\"smallgrey\">Last edited <strong>".forumTime($currev['date'])."</strong> by <strong>".$currevuser['name']."</strong></p>";
	$myrev = -1;
	if(!$micro) {
		global $adcdb;
		$query="SELECT child.id FROM `acts_pages` child, `acts_pages` parent WHERE child.parentid=parent.id AND parent.parentid=-1 AND child.mode='filtered'";
		$result=sqlQuery($query,$adcdb) or die (mysql_error());
		while ($row=mysql_fetch_assoc($result)) {
			$childid=$row['id'];
			$query="UPDATE acts_pages SET parentid=-1 WHERE id=$childid";
			sqlQuery($query,$adcdb) or die (mysql_error());
		}
		if(isset($_GET['delete']))
		{
			if(hasEquivalentToken('security',-3) && logindetails(false,false,false,true))
			{
				$del = (int) $_GET['delete'];				
				$query = "UPDATE acts_pages SET parentid=-1 WHERE id=$del";
				$r = sqlQuery($query) or die(sqlEr());
				inputFeedback("Removed page!");


			}
			unset($_GET['delete']);
		}
		if(isset($_GET['deleterev']))
		{
			if(hasEquivalentToken('security',-3) && logindetails(false,false,false,true))
			{
				$del = (int) $_GET['deleterev'];				
				$query = "DELETE FROM acts_knowledgebase WHERE id=$del";
				$r = sqlQuery($query) or die(sqlEr());
				inputFeedback("Removed revision!");
				$query="SELECT * FROM acts_knowledgebase WHERE pageid='" . $page['id'] . "' ORDER BY id DESC LIMIT 1";
				$result=sqlQuery($query,$adcdb) or die(sqlEr());
				$currev=mysql_fetch_assoc($result);
				$pagetext=$currev['text'];
				$currevuser=getuserrow($currev['userid']);
				$pagerev="<p class=\"smallgrey\">Last edited <strong>".forumTime($currev[date])."</strong> by <strong>".$currevuser['name']."</strong></p>";  
			}
			unset($_GET['deleterev']);
		}
		if(((isset($_GET['create']) && $page['allowsubpage']>0 && canusekb()) || isset($_GET['edit'])) && logindetails(true,false,false,true) && ($page['locked']==0 || hasEquivalentToken('security',-3)))
		{
			$displayform=true;
			if(isset($_POST['help']))
			{
				$title = trim(removeEvilTags($_POST['title']));
				$help = trim(removeEvilTags($_POST['help']));
				$template = trim(removeEvilTags($_POST['template']));
				$parent = $page['id'];
				if(strlen($title)<2 || strlen($help)<2)
					inputFeedback("Entry error","You must fill in every field");
				else {
					if(isset($_GET['edit']))
					{
						if(!hasToken('knowledgebaseblocked',0,$alw) or hasEquivalentToken("security",-3))
						{
							unlink("data/cache.php");
							$query = "UPDATE acts_pages SET fulltitle='$title'";
							if (hasEquivalentToken('security',-3)) $query.=", subpagetemplate='$template'";
							$query.=" WHERE `id`=".$page['id']." LIMIT 1";
							$r=sqlQuery($query,$adcdb);
							global $mail_alsomail;
							$uname= getUserName($_SESSION[userid]);
							$uemail=getUserEmail($_SESSION[userid]);

							/* Query for the previous text so that we can diff */
							$query="SELECT * FROM acts_knowledgebase WHERE pageid='$page[id]' ORDER BY id DESC";
							$r=sqlQuery($query, $adcdb) or die (sqlEr());
							$oldrev = mysql_num_rows ($r);
							if ($revrow = mysql_fetch_assoc ($r))
							{
									$text1 = $revrow['text'] . "\n";
							}
							
							$query = "INSERT INTO acts_knowledgebase (pageid,text,userid,date) VALUES('$page[id]','$help','$_SESSION[userid]',NOW())";
							$r=sqlQuery($query,$adcdb);
							
							/* Where are we now? */
							$newid = mysql_insert_id();
							$query="SELECT * FROM acts_knowledgebase WHERE pageid='$page[id] ORDER BY id ASC'";
							$r=sqlQuery($query, $adcdb);
							for ($i = 0; $i < mysql_num_rows ($r); $i++)
							{
								$revrow = mysql_fetch_assoc ($r);
								if ($revrow[id] == $newid)
								{
									$newrev = $i + 1;
									break;
								}
							}

							$mailtext="An infobase page has been edited on ".getConfig('site_name')." by $uname ($uemail).\n\n".linkTo(0,array("CLEARALL"=>"CLEARALL"))."\n\n" . rawdiff ($text1, stripslashes($help) . "\n") . "\n\nYou can view the diff at " . linkTo(0,array("CLEARALL"=>"CLEARALL")) . "?diff1=$oldrev&diff2=$newrev";
							mailTo($mail_alsomail,"[camdram-infobase] Infobase page edited on ".getConfig('site_name'),$mailtext,"","","","infobase@camdram.net");
							
							inputFeedback("Updated!");
							$query="SELECT * FROM acts_knowledgebase WHERE pageid='" . $page['id'] . "' ORDER BY id DESC LIMIT 1";
							$result=sqlQuery($query,$adcdb) or die(sqlEr());
							$currev=mysql_fetch_assoc($result);
							$pagetext=$currev['text'];
							$currevuser=getuserrow($currev['userid']);
							$pagerev="<p class=\"smallgrey\">Edited by ".$currevuser['name']." on ".date("d/m/y H:i",strtotime($currev['date']))."</p>";
							$displayform=false;
							// edit code
						} else inputFeedback("Access Error","You do not appear to have write access to this entry.");
					} else {
						unlink("data/cache.php");
						$query = "INSERT INTO acts_pages (title, fulltitle, parentid, mode, allowsubpage,subpagetemplate) VALUES ('$title','$title',$parent,'filtered',1,'$template')";

						$r=sqlQuery($query,$adcdb) or die(sqlEr());
						$query = "INSERT INTO acts_knowledgebase (pageid,text,userid,date) VALUES(".mysql_insert_id().",'$help','$_SESSION[userid]',NOW())";
						$uname= getUserName($_SESSION[userid]);
						$uemail=getUserEmail($_SESSION[userid]);
						$mailtext="An infobase page has been created on ".getConfig('site_name')." by $uname ($uemail).\n\nVisit ".linkTo(mysql_insert_id(),array("CLEARALL"=>"CLEARALL"))." to see the new page.";
						global $mail_alsomail;
						mailTo($mail_alsomail,"[camdram-infobase] Infobase page created on ".getConfig('site_name'),$mailtext,"","","","infobase@camdram.net");
						$r=sqlQuery($query,$adcdb);
						if($r>0)
						{
							inputFeedback("Page created");
							$displayform=false;
						} else sqlEr();
					}
				}

			}



			if($displayform)
			{
				?><h2><?php if(isset($_GET['edit'])) echo("Edit this page"); else echo("Create a subentry"); ?></h2><form action="<?=thisPage()?>" method="post">
				<table width="100%" class='editor'>
				<?php
				if(!isset($_GET['edit']))
				{
					set_active_mode ("create");
					?>

						<tr><th>Create in</th>
						<td><?=makeLink($page['id'],$page['fulltitle'])?> (<?=id_to_url($page[id])?>)</td>
						</tr><?php
				}
				else
					set_active_mode ("edit");
					?>
				<tr>
				<th>Title</th>
				
				<td><input name="title" type="text" id="title" value="<?php if(isset($_POST['title'])) echo stripslashes($_POST['title']); elseif(isset($_GET['edit'])) echo($page['fulltitle']);?>" onkeyup="$('newpagetit').innerHTML=$('title').value;"></td>
				</tr>
				<tr>
<?php
	addJSload("$('title').focus(); ");
?>
				<th>Page contents (<?=stripslashes(makeLink('translator','limited HTML available'))?></em>)</th>
				<td><textarea name="help" cols="120" rows="20" id="help" wrap="virtual"><?php if(isset($_POST['help'])) echo stripslashes($_POST['help']); elseif(isset($_GET['edit'])) echo($pagetext); else echo $page['subpagetemplate'];?></textarea></td>
				</tr>
				<?php if (hasEquivalentToken("security",-3)) { ?>
				<tr>
				<th>Child page template</th>

				
				<td><textarea name="template" cols="120" rows="20" id="help" wrap="virtual"><?php if(isset($_POST['template'])) echo stripslashes($_POST['template']); elseif(isset($_GET['edit'])) echo($page['subpagetemplate']);?></textarea></td>
				</tr>
				<?php } ?>
				<tr>
				<th></th><td>
								      <strong>Before saving your changes, you must have read and agreed to our <?php echo startLink(237,array(),true,0,"",true); ?>fair usage policy</a></strong><br/>
				<input type="submit" name="Submit" value="I agree, save changes">
				</td>
				</tr>
				</table>
				</form>
				<?php
				add_kb_links($page, $myrev);
				return 0;
			}
		}
		if(isset($_GET['subpage']))
		{
			if(hasEquivalentToken('security',-3) && logindetails(false,false,false,true))
			{
				if($_GET['subpage']==1)
				{
					inputFeedback("Subpage creation on");
					$q = "UPDATE acts_pages SET allowsubpage=1 WHERE id=".$page['id']." LIMIT 1";
					$page['allowsubpage']=1;
				} else {
					inputFeedback("Subpage creation off");
					$q = "UPDATE acts_pages SET allowsubpage=0 WHERE id=".$page['id']." LIMIT 1";
					$page['allowsubpage']=0;
				}
				$r = sqlQuery($q,$adcdb) or sqlEr();
			}
		}
		if(isset($_GET['lock']))
		{
			if(hasEquivalentToken('security',-3) && logindetails(false,false,false,true))
			{
				if($_GET['lock']==0)
				{
					inputFeedback("Page editing allowed");
					$q = "UPDATE acts_pages SET locked=0 WHERE id=".$page['id']." LIMIT 1";
					$page['locked']=0;
				} else {
					inputFeedback("Page editing denied");
					$q = "UPDATE acts_pages SET locked=1 WHERE id=".$page['id']." LIMIT 1";
					$page['locked']=1;
				}
				$r = sqlQuery($q,$adcdb) or sqlEr();
			}
		}
		} else { echo "<h3>camdram.net infobase: ".$page['title']."</h3>"; }
		echo "<h2>".htmlspecialchars($page['fulltitle'])."</h2>";
		if (isset($_GET['chooserev'])) {
			set_active_mode ("revs");
			echo "<h4>Choose a revision to view</h4>";
			unset($_GET['chooserev']);
			$q="SELECT * FROM acts_knowledgebase WHERE pageid=$page[id] ORDER BY id DESC";
			$r=sqlQuery($q,$adcdb) or die(sqlEr());
			$i=mysql_num_rows($r);
			echo "<form action=\"".thisPage()."\" method=\"get\">";
			echo "<table class=\"dataview\">";
			echo "<tr><th align=center>#</th><th align=center><input type=\"submit\" value=\"compare\"></th><th align=center>Editor</th><th align=center>Date</th><th align=center>Action</th></tr>";
			while ($row=mysql_fetch_assoc($r))
			{
				echo "<tr><td>$i</td>";
				echo "<td><input type=\"radio\" name=\"diff1\" value=\"$i\"> ";
				echo "<input type=\"radio\" name=\"diff2\" value=\"$i\"></td>";
				$userrow=getuserrow($row['userid']);
				echo "<td>$userrow[name]</td>";
				echo "<td>".date("d/m/y H:i",strtotime($row[date]))."</td>";
				echo "<td>";
				makelink(0,"View",array("CLEARALL"=>"CLEARALL","rev"=>$i));
				echo "</td></tr>";
				$i--;

			}
			echo "</table></form>";
		}
		else {
			echo("<p>");
			if (isset($_GET['diff1'])) {
				if ($_GET['diff1']==$_GET['diff2']) {
					$_GET['rev']=$_GET['diff1'];
				}
				else {
					if ($_GET['diff1']>$_GET['diff2']) {
						$diff1=$_GET['diff2'];
						$diff2=$_GET['diff1'];
					}
					else {
						$diff1=$_GET['diff1'];
						$diff2=$_GET['diff2'];
					}
					$query="SELECT * FROM acts_knowledgebase WHERE pageid='$page[id] ORDER BY id ASC'";
					$r=sqlQuery($query) or die(sqlEr());
					$rows=mysql_num_rows($r);
					for ($i=1;$i<=$rows;$i++)
					{
						if ($revrow=mysql_fetch_assoc($r))
						{
							if ($i==$diff1)
							{
								$text1=$revrow['text'];
								$date1=$revrow['date'];
								$userid1=$revrow['userid'];
							}
							if ($i==$diff2)
							{
								$text2=$revrow['text'];
								$date2=$revrow['date'];
								$userid2=$revrow['userid'];
							}
						
						}
					}
					$toppagetext="<h3>Viewing differences between revisions $diff1 and $diff2</h3><h4>Text in revision $diff1 and not in revision $diff2 is <red>highlighted in red</red>.<br />Text in revision $diff2 and not in revision $diff1 is <green>highlighted in green</green></h4>";
					set_mode_title ("Comparing revisions $diff1 &amp; $diff2");
					$pagetext=diff($text1,$text2);
					$currevuser=getuserrow($userid1);
					$pagerev="<p class=\"smallgrey\">Revision $diff1 edited by ".$currevuser['name']." on ".date("d/m/y H:i",strtotime($date1))."<br />";
					$currevuser=getuserrow($userid2);
					$pagerev.="Revision $diff2 edited by ".$currevuser['name']." on ".date("d/m/y H:i",strtotime($date2))."</p>";
					
				}
				$myrev=$diff1;
				unset($_GET['diff1']);
				unset($_GET['diff2']);
			}
			if (isset($_GET['rev']))
			{
				$rev=$_GET['rev'];
				$query="SELECT * FROM acts_knowledgebase WHERE pageid='$page[id] ORDER BY id ASC'";
				$r=sqlQuery($query) or die(sqlEr());
				for ($i=1;$i<=$rev;$i++)
				{
					if ($revrow=mysql_fetch_assoc($r))
					{
						$toppagetext="<h3>Viewing revision $i</h3>";
						set_mode_title ("Viewing revision $i");
						$myrev = $revrow['id'];
						$pagetext=$revrow['text'];
						$currevuser=getuserrow($revrow['userid']);
						$pagerev="<p class=\"smallgrey\">Edited by ".$currevuser['name']." on ".date("d/m/y H:i",strtotime($revrow['date']))."</p>";
					}
				}
				unset($_GET['rev']);
				if (mysql_num_rows($r)<=1)
				{
					unset($rev);
				}
			}
			echo("</p>");
			
			echo $toppagetext.preprocess($pagetext,true,true);
			echo $pagerev;
		}
	if(!$micro) { 

		if(hasEquivalentToken('security',-3))
		{
			?><h3>Keywords</h3><p><?php
			if(isset($_GET[kwdelete]) && logindetails(false,false,false,true))
			{
				$q = "SELECT * FROM acts_keywords WHERE id=".$_GET[kwdelete];
				$r = sqlQuery($q) or sqlEr();
				if($r>0)
				{
					$row = mysql_fetch_assoc($r);
					if($row['pageid']==$page['id'])
					{
						$q = "DELETE FROM acts_keywords WHERE id=".$_GET[kwdelete]." LIMIT 1";
						$y = sqlQuery($q) or sqlEr();
						inputFeedback("Deleted keyword!");
					} else inputFeedback("Keyword does not belong to this page.");
					mysql_free_result($r);
				}
			}
			if(isset($_GET[newkw]) && logindetails(false,false,false,true))
			{
				$q = "INSERT INTO acts_keywords (kw,pageid) VALUES ('".$_GET[newkw]."', ".$page[id].")";
				$y = sqlQuery($q) or sqlEr();
				inputFeedback("Inserted keyword!");
			}
	
			$query = "SELECT * FROM acts_keywords WHERE pageid=$page[id] ORDER BY kw";
			$r=sqlQuery($query) or sqlEr();
			if($r>0)
			{
				while($kw=mysql_fetch_assoc($r))
				{
				echo "<br/>".$kw[kw]." [";
					makeLink(0,'delete',array("kwdelete"=>$kw['id']),true);
					echo "]";
				}
				mysql_free_result($r);
			}
		global $currentid;
		?>
		<form action="/index.php" method="get">
		<input name="id" type="hidden" id="id" value="<?=$currentid?>">
		<input name="newkw" type="text" size="30" maxlength="30"><input name="submit" type="submit" value="add">
		</form>
		</p>
		<?php
		}

		?><?php
		$pre=false;
		if($page['parentid']!=0) 
		{
			echo("<p>");
			echo("back to ");
			makeLink($page['parentid']);
			$pre=true;
			echo("</p>");
		}


		echo "<h3>Search</h3>";
		echo "<form method=\"post\" action=\"/infobase/search\">";
		echo "Search the Infobase: <br />";
		echo "<input type=\"text\" name=\"searchtext\" id=\"searchtext\" /> ";
		?>
		<div id="autocomplete_choices"></div>
		<script type="text/javascript">
		//<!--
		new Ajax.Autocompleter("searchtext", "autocomplete_choices", "<?=$base?>/postback.php?type=generic_search&search=infobase", {afterUpdateElement: getSelectionId});

		function getSelectionId(text, li) {
			window.location.href = "<?=$base?>/searchredirect.php?id=" + li.id;
		}


		//-->
		</script>
		<?php
		echo "<input type=\"submit\" value=\"search\" /><br />";
		echo "<input type=\"radio\" name=\"type\" value=\"and\" checked=\"checked\" />All Words ";
		echo "<input type=\"radio\" name=\"type\" value=\"or\" />Any Word ";
		echo "<input type=\"radio\" name=\"type\" value=\"phrase\" />Phrase<br />";
		echo "<input type=\"radio\" name=\"scope\" value=\"limited\" checked=\"checked\" />Search title &amp; keywords only ";
		echo "<input type=\"radio\" name=\"scope\" value=\"all\" />Search title, keywords and content";
		echo "</form>\n";
	} else { echo "<hr /><p>You can browse the infobase by ".makeLinkText(0,"opening this page in a full window")."</p>"; }
	add_kb_links($page, $myrev);
}

function add_kb_links($page, $rev)
{
	global $adcdb;
		if ($rev!=-1)
		{
			$prevtext="other";
		}
		else	
		{
			$r = sqlQuery ("SELECT * FROM acts_knowledgebase WHERE pageid='$page[id]' ORDER BY id DESC LIMIT 1", $adcdb);
			$row = mysql_fetch_assoc ($r);
			$rev = $row ['id'];
			$prevtext="previous";
		}
		$query="SELECT * FROM acts_knowledgebase WHERE pageid='$page[id]'";
		$r=sqlQuery($query,$adcdb);
		$revno=mysql_num_rows($r);
		if ($revno>1) {
			add_page_mode("revs", 0,"View $prevtext revisions",array("CLEARALL"=>"CLEARALL","chooserev"=>"yes"));
		}


		if($page['allowsubpage']>0 && canusekb())
		{
			add_page_mode("create", 0,"Create a subpage",array("create"=>"create","CLEARALL"=>"CLEARALL"));
			$pre=true;
		}


		if(!isset($_SESSION['userid'])) {
			add_page_link(0,"Log in to edit the infobase",array("loginbox"=>"display","CLEARALL"=>"CLEARALL"));
		}
		if(canusekb() && ($page['locked']==0 || hasEquivalentToken("security",-3))) 
		{
			add_page_mode("edit", 0,"Edit this page",array("edit"=>"edit","CLEARALL"=>"CLEARALL"));
			if (hasEquivalentToken("security",-3))
			{
				if($page['allowsubpage']>0)
				{
					add_page_link(0,"Deny subpage creation",array("subpage"=>"0","CLEARALL"=>"CLEARALL"));
				}
				else
				{
					add_page_link(0,"Allow subpage creation",array("subpage"=>1,"CLEARALL"=>"CLEARALL"));
				}
				if ($page['locked']==0)
				{
					add_page_link(0,"Lock this page",array("lock"=>"1","CLEARALL"=>"CLEARALL"));
				}
				else
				{
					add_page_link(0,"Unlock this page",array("lock"=>"0","CLEARALL"=>"CLEARALL"));
				}
				add_page_link($page['parentid'],"Delete this page",array("delete"=>$page['id'],"CLEARALL"=>"CLEARALL"));
				if ($revno > 1)
					add_page_link(0,"Delete this revision",array("deleterev"=>$rev,"CLEARALL"=>"CLEARALL"));
			}
		}	
}

/**
  * Produce word-wise differences between 2 pieces of text.
  * This creates 2 temporary files, then deletes them afterwards
  * @param $text1 First piece of text
  * @param $text2 Second piece of text
  * @param $lines Lines of context to include, defaults 1000000
  * @return diff text, based on the unified dif with - being &lt;span class="kbred"&gt; and + being &lt;span class="kbgreen"&gt;
*/
function diff($text1,$text2,$lines=1000000)
{
	/* The magic default in $lines is Mr Dilley's fault, but
	 * I can't think offhand of a better way of doing it. --aes */
	
	$file1=processandsavefile($text1);
	$file2=processandsavefile($text2);
	$diff=`diff -U $lines $file1 $file2`;
	if ($diff=="") {
		$diff=`cat $file1`;
		$diff=str_replace("\n","\n ",$diff);
	}
	unlink($file1);
	unlink($file2);
	return processdiff ($diff);
}

/**
  * Produce differences between 2 pieces of text.
  * @param $text1 First piece of text
  * @param $text2 Second piece of text
  * @param $lines Lines of context to include, defaults 1000000
  * @return Unified diff
*/
function rawdiff($text1, $text2, $lines=1000000)
{
	$file1 = splitintoseparatelinesandsavefile($text1);
	$file2 = splitintoseparatelinesandsavefile($text2);
	
	$diff=`diff -U $lines $file1 $file2`;
	unlink ($file1);
	unlink ($file2);
	return $diff;
}

/**
  * @protected This function processes text in $text1 and saves it to a temporary file.
  * It is called by diff
  * @param $text Text to process
  * @return filename
  */

function processandsavefile($text)
{
	$text=str_replace("+","&plus;",$text);
	$text=str_replace("-","&minus;",$text);
	$text=str_replace("@","&at;",$text);
	$text=str_replace("\n"," <br> ",$text);
	$textarray=preg_split("/ /",$text);
	$filename="data/temp/".rand();
	$r=fopen($filename,"w");
	foreach ($textarray as $line) {
		fwrite($r,$line."\n");
	}
	fclose($r);
	return $filename;
}

/**
  * @protected This function processes text in $text and saves it to a temporary file.
  * It is called by rawdiff
  * It follows the function naming scheme for kb.php.
  * @param $text Text to process
  * @return filename
  */
function splitintoseparatelinesandsavefile($text)
{
		$text = wordwrap ($text, 70);
		
		$filename="data/temp/".rand();
		$r=fopen($filename,"w");
		fwrite ($r, $text);
		fclose($r);
		return $filename;
}

/**
  * @protected This function processes the output of the diff command.
  * It is called by diff
  * @param $text Output of diff
  * @return Processed text
  */

function processdiff($text) {
	$diffarray=explode("\n",$text);
	$diffarray=array_filter($diffarray,"gooddiffline");
	
	$startplus=-1;
	$startminus=-1;
	foreach ($diffarray as $i=>$line) {
		$start=substr($line,0,1);
		if ($start=="+" || $start=="-") {
			$diffarray[$i]=" ".substr($diffarray[$i],1);
		}
		if ($startplus!=-1) {
			if ($start=="-") {
				$diffarray[$i]="<red>".$diffarray[$i];
				$startminus=1;
			}
			if ($start!="+") {
				$diffarray[$i]="</green>".$diffarray[$i];
				$startplus=-1;
			}
		}
		elseif($startminus!=-1) {
			if ($start=="+") {
				$diffarray[$i]="<green>".$diffarray[$i];
				$startplus=1;
			}
			if ($start!="-") {
				$diffarray[$i]="</red>".$diffarray[$i];
				$startminus=-1;
			}
		}
		else {
			if ($start=="+") {
				$diffarray[$i]="<green>".$diffarray[$i];
				$startplus=1;
			}
			if ($start=="-") {
				$diffarray[$i]="<red>".$diffarray[$i];
				$startminus=1;
			}
		}

	}
	if ($startminus==1) {
		$diffarray[$i]="</red>";
	}
	if ($startplus==1) {
		$diffarray[$i]="</green>";
	}
	$text=implode("",$diffarray);
	$text=str_replace("&plus;","+",$text);
	$text=str_replace("&minus;","-",$text);
	$text=str_replace("&at;","@",$text);
	$text=str_replace(" <br> ","\n",$text);
	return $text;
}

/**
  * @protected Callback function to filter whether the diff line should be kept
  * @param $text Text to examine
  * @return true if yes, false if no
  */

function gooddiffline($text) {
	if (strpos($text,"+++")===0) return false;
	if (strpos($text,"---")===0) return false;
	if (strpos($text,"@@")===0) return false;
	return true;
}

?>
