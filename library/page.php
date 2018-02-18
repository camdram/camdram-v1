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

function reconstructURL() {
     global $this_stack_ref;
     $url = "";
     $url.= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
     if($_SERVER['QUERY_STRING']>' ') {
       $url.= '?'.$_SERVER['QUERY_STRING'];
     } else {
       $url.='?stackref='.$this_stack_ref;
     }
     return $url;
}

function getpagerow($pageid) {
	global $adcdb;
	$query="SELECT * FROM acts_pages WHERE id=$pageid";
	$result=sqlQuery($query,$adcdb) or die(mysql_error());
	$page=mysql_fetch_assoc($result);
	return $page;
}

function backTrace($currentPage)
{
	// writes "hierarchichal" view of location
	// $currentPage is SQL entry for the page
	
	if($currentPage['parentid']==0)
	{
		return;	
	}
	else
	{
		$query="SELECT * FROM acts_pages WHERE id=".$currentPage['parentid'];
		global $adcdb;
		$q=sqlQuery($query,$adcdb);
		if($q>0)
		{
			$row=mysql_fetch_assoc($q);
			mysql_free_result($q);
			backTrace($row);
		} else backTrace(array("parentid"=>0));
		?> &gt; <?php 

		$makeLink = ($row['ghost']==0);
		if($row['intertitle']!="")
			 eval($row['intertitle']);
		else
		{
			if($makeLink==true) makeLink($row['id'],$row['fulltitle']); else echo($row['fulltitle']);
		}
	}
}

function loadPage($loadid,$micro=false)
{
	global $adcdb, $extra_page_info, $showing_knowledgebase;
	$query = "SELECT * FROM acts_pages WHERE acts_pages.id = $loadid";
	$item = sqlQuery($query, $adcdb);
	if($item>0)
	{
		$row = mysql_fetch_assoc($item);
		mysql_free_result($item);
		$loadtype=$row['mode'];
		$continue=true;
		$sec_row = $row;
		while(true)
		{
			if ($sec_row['secure'] > 0 || $sec_row['access_php'] != "")
			{
				if(!logindetails(false,false,false)) {
				      	require_once("library/editors.php");
			    
				      	logindetails(true,false,false);
				      	$continue = false;
			      		break;
			    	}
			    	else
			    	{
			    		if ($sec_row['access_php'] != "")
		    			{
			    			$access_function = create_function ("", $sec_row['access_php']);
		    				if (!($access_function ()))
			    			{
					    		echo "You do not have permission to view this page.";
			    				$continue = false;
		    					break;
		    				}
					}
		    		}
		    	}

			
			/* traverse the tree upwards */
			if ($sec_row ['parentid'] == 0)
				break;
			$query = "SELECT * FROM acts_pages WHERE acts_pages.id = " . $sec_row['parentid'];
			$item = sqlQuery ($query) or die (mysql_error());
			$sec_row = mysql_fetch_assoc ($item);
		
		}
		if($continue)
		{
			echo("\n\n<!-- start include of page $loadid -->\n\n");
			if($loadtype=="include")
			{
				$PX_PX_row = $row;   // silly way to do it 
				if(!is_file("admin/".$row['usepage']))
					include("content/$loadid.php");
				else
					include("admin/".$row['usepage']);
				echo preprocess($PX_PX_row['help'],false,false,true); // process further text allowing calls to be made ([CALL:])
			}
			else if($loadtype=="normal")
			{
				if(file_exists("content/$loadid.php"))
				{
					$fp=fopen("content/$loadid.php",'r');
					$t=(fread($fp,filesize("content/$loadid.php")));
					echo(preprocess($t,false));
				}
				else
				{
					echo(preprocess($row['help']));
				}
			}
			else if($loadtype=="filtered" || $loadtype=="menuitem")
			{
				$showing_knowledgebase = 1;
				knowledgeBasePage($row,$micro);
			}
			echo("\n\n<!-- end include of page $loadid -->\n\n");
		} 
		else 
			echo("<!-- failed include -->");
	}
}

function setAnchorPoint()
{
	actionLog("Call to deprecated function setAnchorPoint()");
}

function refAnchorPoint()
{
	actionLog("Call to deprecated function refAnchorPoint()");
}

function idToPage($id)	// returns page title from id
{
	global $adcdb;
	$query_currentmenuitem = "SELECT * FROM acts_pages WHERE acts_pages.id = $id";
	$currentmenuitem = sqlQuery($query_currentmenuitem, $adcdb) or die(mysql_error());
	$row_currentmenuitem = mysql_fetch_assoc($currentmenuitem);
	$name=$row_currentmenuitem['fulltitle'];
	if($name=="") $name=$row_currentmenuitem['title'];
	mysql_free_result($currentmenuitem);
	return $name;
}


function pageToId($name)	// returns page id from title
{
	global $adcdb;
	$query_currentmenuitem = "SELECT * FROM acts_pages WHERE acts_pages.title = '$name'";
	$currentmenuitem = sqlQuery($query_currentmenuitem, $adcdb) or die(mysql_error());
	$row_currentmenuitem = mysql_fetch_assoc($currentmenuitem);
	mysql_free_result($currentmenuitem);
	return $row_currentmenuitem[id];
}

// LINKING FUNCTIONS

function linkTo($id,$overrideParam=array(),$framer="/index.php",$parseid=false, $extra_url="",$addbase=true)
{
	// returns a suitable URL to link to page $id in frame $framer
	// GET variables get carried automatically except where $overrideParam contradicts
	// in $overrideParam set array($key=>"NOSET") to ensure a specified value is not passed
	//						 array("CLEARALL"=>"CLEARALL") to clear all values
	//						 array("OVERRIDESTRING"=>$string) to append $string as a literal GET string
	//						 array($key=>$value) to add on URL encoded variable $key
	global $mode,$currentid,$cache_link_id, $cache_link_virtual, $cache_link_parser,$adcdb;
	if($id==0) $id=$currentid;
	$nosep = false;
	$logout = 0;
	$loginbox = "";
	if ($framer == "/index.php" || $framer == "")
	{
		if ($cache_link_virtual["$id"] == 0 && $cache_link_parser["$id"] == 0)
		{
			$st = id_to_url ($id);
			if ($extra_url != "")
				$st .= "/" . $extra_url;
			$nosep = true;
		}
		else
		{
			$oldid = $id;
			$q = sqlquery ("SELECT parentid FROM acts_pages WHERE id='$id'", $adcdb) or die (mysql_error());
			$row = mysql_fetch_assoc ($q) or die("Database error: Cannot understand virtual/parser on page $id");
			$id = $row['parentid'];
			$st = id_to_url ($id);
			if ($extra_url != "")
				$st .= "/" . $extra_url;
			if ($cache_link_virtual["$oldid"] == 1)
			{
				$overrideParam['type']="NOSET";
				$st .= "?type=" . string_to_url ($cache_link_id["$oldid"]);
				$nosep = false;
			}
		}
	}
	else
	{
		if(stristr($framer,"?"))
		{
			if(substr($framer,strlen($framer)-1,1)!="&")
				$framer.="&amp;";   
		}
		else
			$framer.="?";
				  
		if($mode!=3)
			$st=$framer."id=$id";
		else
		$st=$framer;
	}
	foreach($overrideParam as $key=>$value)
	{
		if($value=="0" || ($value!="NOSET" && $key!="BASENAME" && $key!="OVERRIDESTRING" && ($key!="id" || $parseid) && $key!="eid" && $key!="CLEARALL" && ($key!="frame" || $mode!=3)))
		{
			if ($nosep==true)
				$st .= "?";
			else
				$st .= "&amp;";
			$nosep = false;
			$st.=htmlspecialchars(urlencode($key))."=".htmlspecialchars(urlencode($value));
		}
		if ($key == "loginbox" && $value == "display")
		{
			$loginbox = 1;
		}
		if ($key == "logout")
		{
			$logout = 1;
		}
	}
	if(isset($overrideParam['OVERRIDESTRING']) && $overrideParam['OVERRIDESTRING'] != "")
	{
		if ($nosep)
			$st .= "?";
		else
			$st .= "&amp;";
		$nosep = false;
		$st.=$overrideParam['OVERRIDESTRING'];
	}
	if(!isset($overrideParam["CLEARALL"]))
	{
		if(isset($_GET))
		{
			foreach($_GET as $key=>$value)
			{
				if(!isset($overrideParam[$key]) && ($key!="id" || ($parseid && $mode!=3)) && $key!="PHPSESSID" && ($key!="frame" || $mode!=3) && substr($key,0,3)!="ovr")
				{
					if ($nosep==true)
						$st .= "?";
					else
						$st .= "&amp;";
					$nosep = false;
					$st.=htmlspecialchars(urlencode($key))."=".htmlspecialchars(urlencode($value));
				}
				if ($key == "loginbox" && $value == "display")
				{
					$loginbox = 1;
				}
			}
		}
	}
	
	if(isset($overrideParam["BASENAME"]))
		$st.="#".urlencode($overrideParam["BASENAME"]);
	$st=str_replace("&amp;&amp;","&amp;",$st);
	$st=str_replace("?&amp;","?",$st);
	
	$q = sqlQuery("SELECT secure FROM acts_pages WHERE id = '$id'") or die(mysql_error());

	$row = mysql_fetch_assoc($q);
	if (!$logout && ($row['secure'] ==1 || $loginbox))
	{
		global $securebase;
		$w_base = $securebase;
	}
	else
	{
		global $base;
		$w_base = $base;
	}
	if($w_base[strlen($w_base)-1]=='/')
		$w_base = substr($w_base,0,strlen($base)-1);
	if ($addbase)
	{
		$st=$w_base.$st;
	}
	return $st;
}

function startPopupLink($id,$overrideParam=array(),$parseid=false, $extra_url="",$main="/index.php",$micro="/micro.php",$microOnlyParam=array())
{
	// start a java-script popup link, along with backup to main window
	// if opening fails. Can override page frame by specifying 
	// $main and $micro. $link contains the parameters to pass
	$linkmain = linkTo($id,$overrideParam,$main,$parseid,$extra_url);
	$linkmicro = linkTo($id,array_merge ($overrideParam, $microOnlyParam),$micro,$parseid);
	if(session_id() && !isset($overrideParam['PHPSESSID']))
	{
		if (stristr($linkmicro, "?"))
			$linkmicro.="&amp;".session_name().'='.session_id();
		else
			$linkmicro.="?".session_name().'='.session_id();
	}

	if(stristr($main,"?") && substr($linkmain,0,1)=="?")
		$linkmain=substr($linkmain,1,strlen($linkmain)-1);
	return "<a href=\"$linkmain\" onclick='popup(\"$linkmicro\",700,300);return false'>";
}	

function popupLinkStr($id,$overrideParam=array(),$parseid=false, $extra_url="",$text, $microOnlyParam=array())
{
	// complete popup link
	return startPopupLink($id,$overrideParam,$parseid, $extra_url, $microOnlyParam)."$text</a>";
}

function popupLink($id,$overrideParam=array(),$parseid=false, $extra_url="",$text, $microOnlyParam=array())
{
	// echo complete popup link
	echo popupLinkStr($id,$overrideParam,$parseid, $extra_url,$text, $microOnlyParam);
}

function startLink($id,$overrideParam=array("CLEARALL"=>"CLEARALL"),$clear=false,$row_retmenu=0,$tagpram="",$forcepu=false, $extra_url="", $microOnlyParam=array(), $nomicro = false)
{
	// returns the HTML opening tag for linking to page $id, taking full
	// account of the current rendering environment. For description of
	// $overrideParam see function linkTo. Set $clear to true to
	// add CLEARALL to overrideParam. 
	
	// You can parse the menu entry to $row_retmenu to avoid excess SQL queries
	
	// $tagpram allows you to add in arbitrary HTML <a> tag parameters
	
	global $adcdb,$mode,$currentid,$cache_link_micro,$cache_link_id_to_nice_url,$extra_page_info;
	
	if($clear)
		$overrideParam["CLEARALL"]="CLEARALL";
	if($id==0)
	{
		$id=$currentid;
		if ($extra_url == "")
		{
			if (sizeof ($extra_page_info) > 0)
			{
				for ($x = 0; $x < sizeof ($extra_page_info); $x++)
				{
					$extra_url .= $extra_page_info [$x];
					if ($x != sizeof ($extra_page_info) - 1)
						$extra_url .= "/";
				}
			}
		}
	}
					
	
	if($mode==3)
	{
		
		if(isset($_GET['ovr'.$id]))
		{
			$frame = $_GET['ovr'.$id];
			if(isset($_GET['ovrp'.$id]))
				$framepu = $_GET['ovrp'.$id];
			if(($cache_link_micro[$id]>0 || $forcepu == true) && !$nomicro)
			{
				if(isset($framepu))
				{
					return startPopupLink($id,array_merge($overrideParam,$microOnlyParam),false,$extra_url,$frame,$framepu);
				}
				
			}
			return ("<a href=\"".linkTo($id,array_merge($microOnlyParam,$overrideParam),$frame,false, $extra_url)."\" $tagpram>");
		}
		else
		{
			if (isset($cache_link_id_to_nice_url[$id]))
			{
	    			$st = $cache_link_id_to_nice_url[$id];
			}
	  		else
			{
	    			$st = "/index.php?id=$id&";
			}
			return("<a href=\"".linkTo($id,array_merge($microOnlyParam,$overrideParam),$st, "/index.php", false, $extra_url)."\" target=\"_blank\" $tagpram>");
		}
	}
	
	if(($cache_link_micro[$id]>0 || $forcepu) && !$nomicro)
	{
		if($mode==1)
			return startPopupLink($id,$overrideParam, false, $extra_url,"/index.php", "/micro.php", $microOnlyParam);
		else
		{
			$overrideParam['stackref'] = $_GET['stackref'];
			return ("<a href=\"".linkTo($id,array_merge ($overrideParam, $microOnlyParam),"/micro.php", false, $extra_url)."\" $tagpram>");
		}
	}
	else
	{
		if($mode==1)
		{
			return ("<a href=\"".linkTo($id,$overrideParam, "/index.php", false, $extra_url)."\" $tagpram>");
		}
		else
			return ("<a href=\"".linkTo($id,$overrideParam, "/index.php", false, $extra_url)."\" target=\"_parent\" onclick=\"opener.location='".linkTo($id,$overrideParam)."';self.close(); return false\" $tagpram>");
	}
}

function makeLinkContents($id, $text="")
{
	if ($text != "")
		return $text;
	
	if (!is_numeric($id))
		$id = $cache_link_title[$id];
	
	return $cache_link_id[$id];
}

function makeLinkText($id,$text="",$overrideParam=array("CLEARALL"=>"CLEARALL"),$clear=false,$tagpram="",$nolinkifthispage=false, $extra_url="", $microOnlyParam = array(), $nomicro = false)
{
	// makeLink creates an entire HTML link to page $id, along with text
	// $text is the link text - if the string is empty, the page (short) title is used in its place
	// $overrideParam - see linkTo parameter description
	// $clear, $tagpram - see startLink parameter description

	$parsedref = $id;
	$fail = false;
	global $adcdb,$verbose,$currentid, $cache_link_id, $cache_link_title, $cache_link_usepage;
	if(!isset($id)){
		die(var_export(debug_backtrace()));
	}
	if(!is_numeric($id))
	{
		// You get error notices if $cache_link_title[$id] etc. is not set; The logic here is
		// a bit complicated to fix those nicely, so I'm just going to disable those errors 
		// for now 	     - Stumo, July 2012
		$error_report_level = error_reporting();
		error_reporting($error_report_level & ~E_NOTICE );

		if ($cache_link_title[$id] != "" && $cache_link_usepage[$id] != "")
			$fail = true;
		if ($cache_link_title[$id] == "" && $cache_link_usepage[$id] == "")
			$fail = true;
		if ($cache_link_title[$id] != "")
		{
			if ($text == "")
				$text = $id;
			$id = $cache_link_title[$id];
		}
		else
		{
			$id = $cache_link_usepage[$id];
			if ($text == "")
				$text = $cache_link_id[$id];
		}
		error_reporting($error_report_level);
	}
	else if($text=="")
	{
		$text = $cache_link_id[$id];
		if ($text == "")
		$fail = true;
	}

	$prelink = "";
  
	if(isset($verbose)) {
		$result = "Link to: $id";
		if($fail==true) $result.=" <font color=\"#ff0000\">failed</font>";
		$prelink = hint("L",$result,$fail);
	}
	if($currentid!=$id || $nolinkifthispage==false)
		return($prelink.startLink($id,$overrideParam,$clear,0,$tagpram, false, $extra_url, $microOnlyParam, $nomicro).$text."</a>");
	else
		return "<strong>".$text."</strong>";
}

function makeLink($id,$text="",$overrideParam=array("CLEARALL"=>"CLEARALL"),$clear=false,$tagpram="",$nolinkifthispage=false, $extra_url="", $microOnlyParam = array(), $nomicro = false)
{
	echo(makeLinkText($id,$text,$overrideParam,$clear,$tagpram,$nolinkifthispage, $extra_url, $microOnlyParam, $nomicro));
}

function thisPage($overrideParam=array(),$pageextra=array())
{
	// get a URL for this page
	global $currentid;
	global $mode;
	$extra="";
	foreach ($pageextra as $extraitem)
	{
		$extra.="$extraitem/";
	} 
	switch($mode)
	{
	case 1:
		$framer="/index.php";
		break;
	case 2:
		$framer="/micro.php";
		break;
	case 3:
		if(isset($_GET['ovr'.$currentid]))
			$framer=$_GET['ovr'.$currentid];
		else
			$framer="http://".getConfig('site_url')."/";
		break;
	}
	return linkTo($currentid,$overrideParam,$framer,false,$extra);
}

function string_to_url ($string)
/* This function is for use by the rewriter. It loses some information out of a string, but turns it into
something that can be easily typed in as a URL. It's behaviour is standardised, please don't meddle. */
{
	$ret = strtolower ($string);
	$ret = preg_replace ("/\?(.*)/", "", $ret);
	$ret = preg_replace ("/[^a-z0-9_\/\s]/", "", $ret);
	$ret = preg_replace ("/\s{2,}/", " ", $ret);
	$ret = preg_replace ("/\s/", "_", $ret);
	return $ret;
}

function noslashes($string)
{
	$ret = preg_replace ("/\//", "_", $string);
	return $ret;
}

function id_to_url ($id)
{
	$currentid = $id;
	$return = "";	
	while ($currentid != 0)
	{
		$qu = "SELECT parentid, title FROM acts_pages";
		if ($currentid == 0)
			$qu .= " WHERE id<=0";
		else
			$qu .= " WHERE id=" . $currentid;
		$q = mysql_query ($qu);
		if (mysql_num_rows($q) == 0)
			return "/"; /* You lose */
		$row = mysql_fetch_assoc ($q);
		$title = preg_replace ("/\//", "_", $row['title']);
		if ($return != "")
			$return = string_to_url($title) . "/" . $return;
		else
			$return = string_to_url($title);
		$currentid = $row['parentid'];
	}
	
	$return = "/" . $return;
	return $return;
}

function niceurl_to_page_info ($string)
/* This function again is probably most useful by the rewriter and other library functions. Works out what page to return from a particular URL. */
{
	global $cache_link_niceurls;
	global $rewriter_base;

	if(strtolower(substr($string,0,strlen($rewriter_base))) == strtolower($rewriter_base))
		$string = substr($string,strlen($rewriter_base)-strlen($string));     // base may contain slashes, remove it!

	$token = strtok($string, "/");
	$page_extra_location = array();
	$page_extra_location_string = "";
	$currenthit = 0;
	$tokens_so_far = "";
	while ($token != false)
	{
		$tokens_so_far .= "/$token";
		
		if (isset($cache_link_niceurls[$tokens_so_far]) && $cache_link_niceurls[$tokens_so_far] != "")
			$currenthit = $cache_link_niceurls[$tokens_so_far];
		else
		{
			$missed = true;
			break;
		}
		
		$token = strtok("/");
	}
	
	if ($token != false)
	{
		while ($token != false)
		{
			array_push ($page_extra_location, $token);
			$page_extra_location_string .= "/$token";
			$token = strtok("/");
		}
	}

			
	if ($currenthit == 0)
		$currenthit = 172; /* 404. FIXME: do a proper 404 here. */
	else
	{
		$q = sqlquery ("SELECT id, title, param_parser, virtual, fulltitle FROM acts_pages WHERE parentid=$currenthit AND (virtual=1 OR param_parser=1) ORDER BY virtual DESC");
		while ($row = mysql_fetch_assoc ($q))
		{
			if (isset($_GET['type']) && $_GET['type'] != "" && ($_GET['type'] == string_to_url ($row['title'])) && ($row['virtual'] == 1))
			{
				$currenthit = $row['id'];
				set_mode_title ($row['fulltitle']);
				break;
			}
			if ($row['param_parser'] == 1)
			{
				if ((sizeof ($_GET) > 0) || (sizeof ($page_extra_location) > 0))
				{
					$currenthit = $row['id'];
					set_mode_title ($row['fulltitle']);
					break;
				}
			}
		}
	}
	
	/* FIXME:
	 * This is a hack to avoid adding incompatible hierarchy changes before the branch is merged.
	 * Andrew needs to fix it fairly soon after the branch has been merged.
	 */
	if ($currenthit == 104 && sizeof ($_GET) == 0 && sizeof ($page_extra_location) == 0)
		$currenthit = 67;
	
	global $mode_title;

	$return = array('id' => $currenthit, 'page_extra_location' => $page_extra_location, 'page_extra_location_string' => $page_extra_location_string, 'mode_title' => $mode_title);
	return $return;
}

/**
  * This adds an available mode for the page, along with how to link to it. The parameters are the same as makeLink, apart from $modeid.
  * @param $modeid - A string identifying this mode for use in setActiveMode.
  */
function add_page_mode($modeid, $id,$text="",$overrideParam=array("CLEARALL"=>"CLEARALL"),$clear=false,$tagpram="",$nolinkifthispage=false, $extra_url="", $microOnlyParam = array(), $nomicro = false)
{
	global $modes;
	$modes[] = array ('modeid'=>$modeid, 'id'=>$id, 'text'=>$text, 'overrideParam'=>$overrideParam, 'clear'=>$clear, 'tagpram'=>$tagpram, 'nolinkifthispage'=>$nolinkifthispage, 'extra_url'=>$extra_url, 'microOnlyParam'=>$microOnlyParam, 'nomicro'=>$nomicro);
}

/**
 * This adds a generic link to the links section. Parameters are the same as makeLink.
 */
function add_page_link($id,$text="",$overrideParam=array("CLEARALL"=>"CLEARALL"),$clear=false,$tagpram="",$nolinkifthispage=false, $extra_url="", $microOnlyParam = array(), $nomicro = false)
{
	add_page_link_html ("<li>" . makeLinkText($id, $text, $overrideParam, $clear, $tagpram, $nolinkifthispage, $extra_url, $microOnlyParam, $nomicro) . "</li>");
}

/**
  * This adds a generic item to the links section.
  * @param $html - HTML to add. It should be a <li> item.
  */
function add_page_link_html($html) {
	global $sidelinks;
	$sidelinks.=$html;
}

/**
 * Sets the mode title of this page.
 * @param $title The title.
 */
function set_mode_title ($title)
{
	global $mode_title;
	$mode_title = $title;
}

/**
 * Sets the currently active mode.
 * @param $modeid The identifier of the (already added) mode.
 */
function set_active_mode ($modeid)
{
	global $mode_active;
	$mode_active = $modeid;
}

?>
