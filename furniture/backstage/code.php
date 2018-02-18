<?php

require_once ("layout/common.php");

require_once ("layout/cascading_menu.php");

global $page_in_div;
$page_in_div=true;

function do_theme()
{
 global $me;
  // select the image to use... 

   echo "<div id=\"navbox\">";
   div_thispage();
   div_navbar();
   div_links();
   div_help ();
   echo "</div>";

 div_account_horiz();
 div_admin_horiz();
 div_rss_horiz();
 global $currentbase;
 // echo "<div id=\"toplinks\">";
 //makeTab("shows","shows",array("forthcoming"=>67,"archives"=>118));
 // makeTab("in touch", "in touch", array("find&nbsp;a&nbsp;person"=>"contact person", "post&nbsp;to&nbsp;email&nbsp;lists"=>"contact","ACTS&nbsp;societies&nbsp;list"=>"societies"));
 // makeTab("positions","vacant positions",array("actors"=>"actors","production team"=>152,"directors/producers"=>205));
 // makeTab(119,"infobase");
 // if($_SESSION[userid]>0) makeTab(78,"admin");
 // echo "</div>"
?>
<div id="banner" onclick="window.location.href='/';"></div>
<div id="logo"></div>
<?php
}
?>
