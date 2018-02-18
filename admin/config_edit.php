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

require_once("library/table.php");

if (!hasEquivalentToken("security",-2)) die("You are not permitted to access this page");

global $adcdb;

if (isset($_POST['name'])) {
	$query="UPDATE acts_config SET value='".mysql_real_escape_string($_POST[value])."' WHERE name='".mysql_real_escape_string($_POST[name])."'";
	sqlQuery($query,$adcdb) or die (mysql_error());
}

if (!isset($_GET['sortby'])) {
	$_GET['sortby']="name";
	$_GET['order']="up";
}
$query="SELECT * FROM acts_config".order();

$result=sqlQuery($query,$adcdb) or die (mysql_error());

maketable($result,array("name"=>"name","value"=>"NONE"),
		array("value"=>'echo "<form action=\"",thisPage(),"\" method=\"POST\"><input type=\"hidden\" value=\"$row[name]\" name=\"name\"><input type=\"text\" value=\"$row[value]\" name=\"value\"> <input type=\"submit\" value=\"Update\"></form>";'));

?>

