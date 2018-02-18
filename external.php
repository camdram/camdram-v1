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
header("Content-type:text/html; charset=utf-8");
require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");

global $mode;
$mode=3;
ini_set('session.save_path','/societies/acts/tmp');
ini_set('session.cookie_path','/acts/');
$currentid = (int) $_GET['eid'];
if($currentid==0) $currentid=1;
microlog("external.php, ".$currentid);
loadPage($currentid); 
echo "<p class=\"smallgrey\" align=\"right\">Data from <a href=\"http://www.camdram.net\" target=\"_blank\">camdram.net</a>.</p>"?>
