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

/** @file table.php
* Contains functions related to auto-generating tables
*/

/** camdram.net table display function.
* This function displays the table via echo rather than returning a string containing it.
* @param $data The mysql result resource
* @param $fields an associative array header=>field name (If "NONE" is specified for the field name, no sort is displayed, and the contents should be specified in $functions
* @param  $functions an associative array header=>php code (in a string to be passed through eval) - if the header is specified in here (it must also be specified in $fields), instead of just displaying the raw data, the php code is executed. The working data row can be accessed as the assoc. array $row
* @param $buttons string of PHP code to display action buttons. The working data row can be accessed as the associative array $row
* @param $maxrows maximum number of rows of table to display. 0->infinite.
* @return None
*/

function maketable($data,$fields=array(),$functions=array(),$buttons="",$maxrows=0) {
  global $theme,$currentbase;
  if(mysql_num_rows($data)>0) {
    $row=mysql_fetch_assoc($data);
    if($fields==array()) {
      foreach($row as $key=>$value) {
	$fields[$key]=$key;
      }
    }
    
    
    $i=0;
    echo "<table class=\"dataview\">\n";
    echo "<tr>\n";
    foreach ($fields as $name=>$field) {
      echo "<th align=center><b>$name</b>";
      if ($field !="NONE") {
	echo "<br /><a href=\"".thispage(array("sortby"=>$field, "order"=>"up", "page"=>"NOSET"))."\">";
	echo "<img src=\"$currentbase/furniture/$theme/sort_up.gif\" alt=\"/\\\" title=\"Sort Ascending\" border=0 />";
	echo "</a>";
	echo "<a href=\"".thispage(array("sortby"=>$field, "order"=>"down", "page"=>"NOSET"))."\">";
	echo "<img src=\"$currentbase/furniture/$theme/sort_down.gif\" alt=\"\\/\" title=\"Sort Descending\" border=0 />";
	echo "</a>";
      }
      echo "</th>";
    }
    if ($buttons!="") {
      echo "<th><b>Action</b></th>";
    }
    echo "</tr>";
    do {
      $i++;
      echo "<tr>\n";
      foreach ($fields as $name=>$field) {
	if (isset($functions[$name])) {
	  echo "<td>";
	  $func=$functions[$name];
	  echo eval($func);
	  echo "</td>\n";
	}
	else {
	  echo "<td>$row[$field]</td>";
	}
      }
      if ($buttons!="") {
	echo "<td>";
	eval ($buttons);
	echo "</td>\n";
      }
      echo "</tr>\n";
    } while (($i<$maxrows || $maxrows==0) && ($row = mysql_fetch_assoc($data)));
    echo "</table>";
  } else { echo "<p><strong>No matches</strong></p>"; }
}


/** camdram.net table generation function.
* This function generates a table and returns it as a string. It is very similar to maketable() and these two functions
* must ultimately be integrated, but the difference in its $functions and $buttons means this is not a small task.
* @param $data The mysql result resource
* @param $fields an associative array header=>field name (If "NONE" is specified for the field name, no sort is displayed, and the contents should be specified in $functions
* @param  $functions an associative array header=>php code (in a string to be passed through eval) - if the header is specified in here (it must also be specified in $fields), instead of just displaying the raw data, the php code is executed, and the HTML to display is returned. The working data row can be accessed as the assoc. array $row
* @param $buttons string of PHP code to return HTML for action buttons. The working data row can be accessed as the associative array $row.
* @param $maxrows maximum number of rows of table to display. 0->infinite.
* @return None
*/

function maketabletext($data,$fields=array(),$functions=array(),$buttons="",$maxrows=0) {
  global $theme,$currentbase;
  $ret = "";
  if(mysql_num_rows($data)>0) {
    $row=mysql_fetch_assoc($data);
    if($fields==array()) {
      foreach($row as $key=>$value) {
	$fields[$key]=$key;
      }
    }
    
    
    $i=0;
    $ret.= "<table class=\"dataview\">\n";
    $ret.= "<tr>\n";
    foreach ($fields as $name=>$field) {
      $ret.= "<th align=center><b>$name</b>";
      if ($field !="NONE") {
	$ret.= "<br /><a href=\"".thispage(array("sortby"=>$field, "order"=>"up", "page"=>"NOSET"))."\">";
	$ret.= "<img src=\"$currentbase/furniture/$theme/sort_up.gif\" alt=\"/\\\" title=\"Sort Ascending\" border=0 />";
	$ret.= "</a>";
	$ret.= "<a href=\"".thispage(array("sortby"=>$field, "order"=>"down", "page"=>"NOSET"))."\">";
	$ret.= "<img src=\"$currentbase/furniture/$theme/sort_down.gif\" alt=\"\\/\" title=\"Sort Descending\" border=0 />";
	$ret.= "</a>";
      }
      $ret.= "</th>";
    }
    if ($buttons!="") {
      $ret.= "<th><b>Action</b></th>";
    }
    $ret.= "</tr>";
    do {
      $i++;
      $ret.= "<tr>\n";
      foreach ($fields as $name=>$field) {
	if (isset($functions[$name])) {
	  $ret.= "<td>";
	  $func=$functions[$name];
	  $ret.= eval($func);
	  $ret.= "</td>\n";
	}
	else {
	  $ret.= "<td>$row[$field]</td>";
	}
      }
      if ($buttons!="") {
	$ret.= "<td>";
	eval ($buttons);
	$ret.= "</td>\n";
      }
      $ret.= "</tr>\n";
    } while (($i<$maxrows || $maxrows==0) && ($row = mysql_fetch_assoc($data)));
    $ret.= "</table>";
  } else { $ret.= "<p><strong>No matches</strong></p>"; }
  return $ret;
}

/** Produces an "ORDER BY" statement from the sort buttons clicked on the table
* Clicking a sort button on the table will add fields to the $_GET variable, this turns them into an ORDER BY statement which can be appended to the end of a query
* @return ORDER BY statement, with leading space
*/

function order () {
	if (isset($_GET['sortby'])) {
		$query=" ORDER BY ".mysql_real_escape_string($_GET['sortby']);
		if ($_GET['order']=="down") $query.=" DESC";
	}
	return $query;
}
