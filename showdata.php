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
require_once('library/lib.php');
require_once('library/showfuns.php');

$show;
$mode;

if($_GET['type']==='xml' || $_GET['type']==='json'){
	$mode = $_GET['type'];
}

if(isset($mode) && isset($_GET['showid']) && $show = getShowRow($_GET['showid'])){
			//Do nothing - allow to run on...
}else{
	header("Content-type:text/plain;");
	?>
Show not found or not specified, or invalid type.
  URL paramaters:
    showid=<id number>
    type=<type>
Where <type> is one of xml, json
<?php
	exit;
}

$show = ShowDetails($show);

if($mode === 'xml'){
	require_once("library/xml.php");
	header("Content-type: text/xml");
	print xml_encode($show, "show");
	print "<!-- Any clients using this data should be robust to more fields being added at a later date -->";
	exit;
}else if($mode === 'json'){
	header("Content-type: application/json");
	echo json_encode($show);
	exit;
}

?>