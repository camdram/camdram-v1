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

global $adcdb;


if (!isset($_GET['term'])) {
	$row_term=whatTerm(time(),true);
	$ignorepast=true;
}
else {
	$row_term=getTermRow($_GET['term']);
	$ignorepast=false;
}
for( $n=$row_term['firstweek'];$n<=$row_term['lastweek'];$n++)
{
	echo "<br/>";
	putWeek($row_term,$n,time(),$ignorepast);
}

$query = "SELECT * FROM acts_termdates ORDER BY startdate, enddate, id";
$res=sqlQuery($query,$adcdb) or die (sqlEr());

echo "<form method = \"get\" action=\"".linkTo(0,array("CLEARALL"=>"CLEARALL"))."\">Jump to term: <select name=\"term\">";

while ($row=mysql_fetch_assoc($res)) {
        if (isset($prevrow) && eventCount($prevrow['enddate'], $row['startdate'])>0) {
	  echo "<option value=\"".$prevrow['id']."vac\"";
	  if ($row_term['id']==$prevrow['id'].'vac')
	    {
	      echo ' selected="selected"';
	    }
	  echo ">$prevrow[vacation]</option>\n";
	}
	echo "<option value=\"$row[id]\"";
	if ($row_term['id']==$row['id'])
    {
        echo ' selected="selected"';
    }
	echo ">$row[name]</option>\n";
	$prevrow=$row;
}

echo "</select> <input type=\"submit\" value=\"Go\" /></form>";

?>
