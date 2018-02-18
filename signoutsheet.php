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

function OutputHTMLTableRow($row, $header = false){
	echo "<tr>";
	for ($i = 0; $i < sizeof($row); $i++){
		$elt = $row[$i];
		$colspan = 1;
		while($i+1 < sizeof($row) && $row[$i+1] == ''){
			$colspan++;
			$i++;
		}
		if($header){
			echo "<th colspan=$colspan>";
		}else{
			echo "<td colspan=$colspan>";
		}
		echo htmlspecialchars($elt);
		if($elt == " "){
			echo '&nbsp;';
		}
		if($header){
			echo "</th>";
		}else{
			echo "</td>";
		}
	}
	echo "</tr>";
}

$show;

if(isset($_GET['showid']) && $show = getShowRow($_GET['showid'])){
			//Do nothing - allow to run on...
}else{
	header("Content-type:text/plain;");
	print "Show not found";
	exit;
}

$mode = 'html';

if(isset($_GET['mode']) && $_GET['mode'] == 'csv'){
	$mode='csv';
	
	header("Content-type:text/csv; charset=utf-8");
	header("Content-Disposition: inline; filename=signoutsheet.csv");
}

/*
this is the same output stream that print, echo etc. go to - useful for the 
fputcsv function that will escape the strings put to it

*/
$outstream = fopen("php://output",'a');  


$show = ShowDetails($show);

// Title and header
// Note that we use ' ' to represent a cell that should be left as a blank space on its own, and '' to represent a cell that should be merged with the previous one in HTML. It's a bit hacky but it works.

$span_less_than_one_week = false;

$first_perf = $show['performances'][0]['date'];
$last_perf = end($show['performances']);
$last_perf = $last_perf['date'];
$one_week_later = clone $first_perf;
$one_week_later->modify('+7 days');

if($one_week_later >= $last_perf){
	$span_less_than_one_week = true;
}


$title = "Signout sheet for " . $show['title'] . $first_perf->format(' (D j/n/Y - ') . $last_perf->format('D j/n/Y)');
$header1 = array('Name','Role','Tech','','Dress','');
$header2 = array(' '    , ' '   ,'In','Out','In','Out');

$blank = array (' ',' ',' ',' ');  // Array of blank cells that we'll leave for people to tick. 

$performancecount = 2; // Includes Tech, Dress

foreach($show['performances'] as $performance){
	$date = '';
	if($span_less_than_one_week){
		$date = $performance['date']->format('D G:i');
	}else{
		$date = $performance['date']->format('D j/n/y G:i');
	}

	if(! $show['singlevenue']){
		$date .= " " . $performance['venue'];
	}
	array_push($header1, $date ,"");
	array_push($header2, "In", "Out");
	array_push($blank, ' ',' ');
	$performancecount++;
}

switch($mode){
case 'csv':
	fputcsv($outstream, array(' ',$title));
	fputcsv($outstream, $header1);
	fputcsv($outstream, $header2);
	break;
case 'html':
?>
<html>
<head><title>
<?php echo htmlspecialchars($title); ?>
</title></head>
<body><h3>
<?php echo htmlspecialchars($title); ?>
</h3>
<table border=1>
<?php
	OutputHTMLTableRow($header1, true);
    OutputHTMLTableRow($header2, true);
}

$people = array('Cast' => $show['cast'], 'Orchestra' => $show['orchestra'], 'Production Team' => $show['prod']);

foreach($people as $type => $list){
	if(sizeof($list) > 0){
		switch($mode){
		case 'csv':
			fputcsv($outstream,array($type));
			break;
		case 'html':
			$output = array_fill(0,($performancecount+1)*2, '');
			$output[0] = $type;

			OutputHTMLTableRow($output,true);
			break;
		}
		foreach($list as $person){
			$row = array(html_entity_decode($person['name']),html_entity_decode($person['role']));
			$row = array_merge($row,$blank);  
			switch($mode){
			case 'csv':
				fputcsv($outstream, $row);
				break;
			case 'html':
				OutputHTMLTableRow($row);
				break;
			}
		}
	}
}

switch($mode){
case 'html':
?>
</table>
	<p> <a href='<?php echo(curPageURL(false) . '?showid=' . $show['id'] . '&mode=csv') ?>'>Spreadsheet version of this sign out sheet.</a></p>
</body></html>
<?php
	break;
}

?>