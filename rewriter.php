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

$original_url = string_to_url ($_SERVER['REQUEST_URI']);

if ($original_url == "/techies")
{
	$newurl = "/positions/production";
}
else if ($original_url == "/actors")
{
	$newurl = "/positions/actors";
}
else if ($original_url == "/diary")
{
	$newurl = "/shows/diary";
}
else if ($original_url == "/archives")
{
	$newurl = "/shows/archives";
}


if (isset($newurl))
{
	global $currentbase;
	$querystring = $_SERVER['QUERY_STRING'];
	if ($querystring != "")
	{
		$newurl .="?$querystring";
	}
	Header("Location: $currentbase$newurl");	
}
else
{
	$page = niceurl_to_page_info ($original_url);
	$currentid = $page['id'];
	$extra_page_info = $page['page_extra_location'];
	$extra_page_info_string = $page['page_extra_location_string'];
	$mode_title = $page['mode_title'];
	// Nasty hack:
	//	if($currentid==292)
	//	$currentid= 104;

	include ("index.php");
}
?>
