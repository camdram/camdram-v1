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

require_once ("layout/common.php");

/** Prints the page name.
 * @protected
 * @param $page The full row from acts_pages of the page in question.
 */
function print_page_name ($page)
{ 
	echo htmlspecialchars($page['fulltitle']);
}

/** Prints out a "navbar" div (the main cascading menu).
 * @public
 */
function div_navbar()
{
	global $section, $me, $mode_title,$adcdb;
	if ($me != -1)
	{
		echo "<div id=\"navbar\">";
		/* find the expanded tree */
		$curpage = $me;
		$expand_tree = array();
		do {
			$q = sqlquery ("SELECT parentid FROM acts_pages WHERE id=" . $curpage, $adcdb) or die (mysql_error());
			$row = mysql_fetch_assoc ($q);
			$expand_tree[$curpage] = 1;
			$curpage = $row['parentid'];
		} while ($curpage != 0);
		
		print_menu ($section, $me, $expand_tree);
		echo "</ul>";
		echo "</div>";
	}
}

/** Prints the navigation menu inside a relevant <div>.
 * @protected
 * @param $section The page ID of the root element
 * @param $me The page ID of the current page, which should be selected. -2 means that the current page is not selected.
 * @param $expand_tree An array of page IDs which should be expanded. When the $expand_tree[id]=1, it is expanded as normal; when $expand_tree[id]=2 it is made ready to expand using javascript. 
 */
function print_menu ($section, $me, $expand_tree)
{
	global $adcdb;
?>
<script type="text/javascript">
//<!--
function showCascM(id) {
   new Effect.BlindDown('submen'+id, {duration: 0.25} );
   $("menli"+id).className = "navdropdown";
}  

function hideCascM(id) {
   new Effect.BlindUp('submen'+id, {duration: 0.25} );
   $('menli'+id).className = '';
}

function toggleCascM(id) {
   if($('menli'+id).className=='navdropdown')
      hideCascM(id);
   else
      showCascM(id);
}

function initCascM(id) { 
   $('menli'+id).className='';
   $('submen'+id).hide();
}

//-->
</script>
<?php 
    echo "<ul>";
	if ($section == 1)
		$newroot = 0; /* bodged */
	else
		$newroot = $section;
	$q = sqlquery ("SELECT * FROM acts_pages WHERE parentid=" . $newroot . " AND ghost <> 1 AND param_parser <> 1 ORDER BY sortcode, fulltitle, title", $adcdb) or die (mysql_error());
	print_menu2 ($me, $expand_tree, 0, $q);
}

/** Prints one level of the navigation hierarchy. Called by print_menu only.
 * @private
 * @param $me The page ID of the current page, which should be selected. -2 means that the current page is not selected.
 * @param $expand_tree An array of page IDs which should be expanded 
 * @param $offset How many spacers to insert
 * @param $current_query The page query to insert
 */
function print_menu2 ($me, $expand_tree, $offset, $current_query)
{
	global $mode_active, $mode_title, $currentid,$adcdb;
	while ($row = mysql_fetch_assoc ($current_query))
	{
		$access_function = create_function ("", $row['access_php']); /* isn't PHP pointer syntax nice? */
		if ($row['access_php'] == "" || $access_function()) /* no, it isn't */
		{
			$q = sqlquery ("SELECT * FROM acts_pages WHERE parentid=" . $row['id'] . " AND ghost <> 1 AND param_parser <> 1 ORDER BY sortcode, fulltitle, title", $adcdb) or die (mysql_error());

			if(!isset($expand_tree[$row['id']])){
				$expand_tree[$row['id']] = 0;
			}
			if($row['mode']=='menuitem' && $expand_tree[$row['id']]==0) $expand_tree[$row['id']]=2; // expand with JS
			if($row['mode']=='menuitem' && $expand_tree[$row['id']]==1) $expand_tree[$row['id']]=3; // expand with no link

			if ($row['id'] == $me)
			  if ($expand_tree[$row['id']] ==1 && mysql_num_rows ($q) > 0)
			    echo "<li class=\"navdropdownselected\"";
			  else
			    echo "<li class=\"navselected\"";
			elseif ($expand_tree[$row['id']] >= 1 && mysql_num_rows ($q) > 0)
			  echo "<li class=\"navdropdown\" id=\"menli".$row['id']."\"";
			else
			  echo "<li";

			if($row['id']==$currentid && isset($_GET['edit']))
			   echo "id='newpagetit'";
			   
			echo ">";	
			 
			if($expand_tree[$row['id']]!=3) {
			if ($row['id'] != $me || $mode_title != "" || $mode_active != "") {
			  if($expand_tree[$row['id']]!=2)
			    echo startLink($row['id']);
			  else {
			    ?><a href="#" onclick="toggleCascM('<?=$row['id']?>'); return false;"><?php 
			    addJSload("initCascM('".$row['id']."');");
			  }
			}
			}
			print_page_name($row);
			if (($row['id'] != $me || $mode_title!="" || $mode_active!="") && $expand_tree[$row['id']]!=3)
			  echo "</a>";
			
			if ((($expand_tree[$row['id']] == 1 || $expand_tree[$row['id']]==3) && mysql_num_rows($q)>0)
			    || (isset($_GET['create']) && $currentid==$row['id']))
			  {
			    // echo "<li class=\"navboring\"><ul>";
			    echo "<ul>";
			    print_menu2 ($me, $expand_tree, $offset + 1, $q);
			    
			    if(isset($_GET['create']) && !isset($_POST['title']) && $row['id']==$currentid) {
			        echo ("<li class=\"add navselected\" id=\"newpagetit\">Adding page here</li>");
		            } else if($row['allowsubpage']>0 && canusekb() ) {
			    	echo "<li class=\"add\">";
				makeLink($row[id],"Add page here",array("create"=>"create"));
				echo "</li>";
			    }

			    echo "</ul>";
			  }

			if (($expand_tree[$row['id']]==2) && mysql_num_rows($q)>0) {
			   echo "<ul id='submen".$row['id']."'>";
			   print_menu2 ($me, $expand_tree, $offset + 1, $q);
			    if($row['allowsubpage']>0 && canusekb() && !isset($_GET['create'])) {
			    	echo "<li class=\"add\">";
				makeLink($row['id'],"Add page here",array("create"=>"create"));
				echo "</li>";
			    }

			   echo "</ul>";    
			}
			echo "</li>";
		}
	}
}

/** Prints out a "thispage" div (the big bold heading to the cascading menu - not actually the current page any more).
 * @public
 * @param $show_parent If set to true, a [back to Parent] link will be shown.
 */
function div_thispage($show_parent = true)
{
	global $currentid, $row_page, $section, $me, $mode_active, $mode_title, $adcdb, $currentbase;
	if ($me == 1) {
	   echo "<div id=\"thispage\">Where now?</div>";
	   return ;
	}
	$q = sqlquery("SELECT * FROM acts_pages WHERE id=" . $section, $adcdb) or die (mysql_error());
	$r = mysql_fetch_assoc ($q); 
	echo "<div id=\"thispage\">";
	if ($r['id'] == 1)
		$parent = 0;
	elseif ($r['parentid'] == 0)
		$parent = 1;
	else
		$parent = $r['parentid'];
	if ($show_parent && ($parent != 0))
	{
		$parent_query = "SELECT * FROM acts_pages WHERE acts_pages.id = " . $parent;
		$parent_q = sqlquery ($parent_query, $adcdb) or die (mysql_error());
		$parent_row = mysql_fetch_assoc ($parent_q);
		
		echo "<p class=\"parent\">";
		if($parent!=$currentid) echo startLink($parent);
		
		print_page_name ($parent_row);
		echo "</a>";
		if($parent!=$currentid) echo " &gt; </p><p>";
	}
    else
    {
        echo "<p>";
    }

	if ((($me > 0 && $me != $section) || $mode_active != "" || $mode_title != "")  && $r['mode']!='menuitem')
    {
		echo startLink ($section, array("CLEARALL"=>"CLEARALL"),false,0,"",false, "", array(), true);
	    print_page_name ($r);
		echo "</a>";
    }
    else
    {
        print_page_name($r);
    }
	echo "</p>";

	echo "</div>";
}

?>
