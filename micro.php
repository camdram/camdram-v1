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

require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");
if(!isset($_SESSION)){
	session_start();
}
ob_start();
$mode = 2;


$currentid = $_GET['id'];
if (!is_numeric($currentid))
  die();
$query = "SELECT * FROM acts_pages WHERE acts_pages.id = $currentid";
$page = sqlquery($query, $adcdb) or die(mysql_error());
$items= mysql_num_rows($page);
if($items==1)
{
	$pagedetails = mysql_fetch_assoc($page);
	$tt=$pagedetails['title'];


?>
<!-- camdram.net v2.01 -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>camdram.net - Association of Cambridge Theatre Societies</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php if(isset($_GET['noindextag'])) echo '<meta name="robots" content="noindex" />'; 


if(is_file("content/".$pagedetails['id']."_header.php")) {
  echo "<!-- Custom headers follow -->";
  require_once("content/".$pagedetails['id']."_header.php");
  echo "<!-- End custom headers -->";
} 


?> 

<link href="furniture/common/camdram.css" rel="stylesheet" type="text/css">
<link href="furniture/<?=$theme?>/camdram.css" rel="stylesheet" type="text/css">
<?=js_header();?>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-37770738-1']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<?php


?>
<body class="micro">
<?php

   global $currentid;
   global $this_stack_ref,$next_stack_ref,$prev_stack_ref;
   $next_stack_ref = md5(rand());

   if(isset($_GET['stackref'])) {
     
     $this_stack_ref = $_GET['stackref'];
   } else {
     $this_stack_ref = md5(rand());
   }
     
   $currenturl = reconstructUrl();
   echo "<div id=\"micronav\">";
   backStack();
   if(isset($_SESSION['nextstackref'][$this_stack_ref])) {
     echo "<small> | ";
     echo '<a href="'.$_SESSION[stackurl][$_SESSION['nextstackref'][$this_stack_ref]].'">Forward</a>';
     echo "</small>";
   } else echo "&nbsp;";
   echo "</div>";
   $_SESSION['prevstackref'][$next_stack_ref]=$this_stack_ref;
   

   
   $_SESSION['stackurl'][$this_stack_ref] = reconstructUrl();
   
   $_GET['stackref'] =$next_stack_ref;
   // echo $next_stack_ref;
     
	if($pagedetails['micro']==0 && false)
	{
		echo("<p>This page is designed for full frame viewing and cannot be displayed here.</p>");
	} else {
		loadPage($pagedetails['id'],true);
	}
	?>
    <div id="copyright">&copy; Association of Cambridge Theatre Societies
      and contributing groups 2004 &#8212; 2012</span>
    
<?php 
} else {
	echo("<p>Could not find the requested page.</p>");
} 
mysql_free_result($page);
?>
</body>
</html>
<?php
microlog("micro.php");
ob_end_flush();
?>
