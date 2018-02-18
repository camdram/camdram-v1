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

require_once ("layout/cascading_menu.php");

global $page_in_div;
$page_in_div=true;

function do_theme()
{
 global $me;
  // select the image to use... 
 if ($me != 1)
 {
   echo "<div id=\"navbox\">";
   div_thispage();
   div_navbar();
   div_links();
   div_help ();
   echo "</div>";
 }
 div_account_horiz();
 div_admin_horiz();
 div_rss_horiz();
 global $currentbase;
 echo "<div id=\"toplinks\">";
 makeTab("shows","shows",array("forthcoming"=>67,"archives"=>118));
 makeTab("in touch", "in touch", array("find&nbsp;a&nbsp;person"=>"contact person", "post&nbsp;to&nbsp;email&nbsp;lists"=>"contact","ACTS&nbsp;societies&nbsp;list"=>"societies"));
 makeTab("positions","vacant positions",array("actors"=>"actors","production team"=>152,"directors/producers"=>205));
 makeTab(119,"infobase");
 if($_SESSION[userid]>0) makeTab(78,"admin");
?>
</div>
<div id="banner" onclick="window.location.href='/';"></div>
<div id="logo"></div>
<?php
}
?>
