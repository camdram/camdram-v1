<?php
/*
    This is another test commit
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
$item = addslashes(preg_replace('/\\//','',$_GET['item']));
$q = "SELECT * FROM acts_pages WHERE title='".$item."'";
$r = sqlquery($q) or die(mysql_error());
$row = mysql_fetch_assoc($r);
mysql_free_result($r);
unset($_GET['item']);
if($row[id]>0)
     $_GET['id'] = $row[id];
     else
     $_GET['id'] = 119;
require("index.php");
?>
