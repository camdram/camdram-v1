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

require_once ("library/gen.php"); 
require_once ("library/page.php");
require_once ("library/cache.php");

LoadCaches ();

require_once ("library/text.php");
require_once ("library/diaryfuns.php");
require_once ("library/editors.php");
require_once ("library/emailGen.php");
require_once ("library/log.php");
require_once ("library/user.php");
require_once ("library/forums.php");
require_once ("library/kb.php");
require_once ("library/mailinglists.php");
require_once ("library/showfuns.php");
require_once ("library/table.php");
require_once ("library/datetime.php");
require_once ("library/upload.php");
require_once ("library/support.php");
require_once ("library/ajax.php");
require_once ("data/cache.php");
require_once("config.php");

// camdram.net general purpose libary
// Copyright (c) Andrew Pontzen 2003/4

// This code not in a function as needs to be run on every page
// Process POST forms (get rid of white space at end, and convert to UNIX format)

if (!get_magic_quotes_gpc())
{
	die("Magic quotes must be enabled");
}

foreach ($_POST as $postkey=>$postvalue) {
	$_POST[$postkey]=rtrim($postvalue);
	$_POST[$postkey]=str_replace(chr(13),"",$_POST[$postkey]);
}

global $base, $securebase, $currentbase;
if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
	{
	$currentbase = $securebase;
	}
else
	{
	$currentbase = $base;
	}

global $loginsuccess;
if(isset($_GET['loginsuccess']))
	$loginsuccess=$_GET['loginsuccess'];
unset($_GET['loginsuccess']);

// Prevent logins from non camdram referers - easiest way to do this is to
// unset the password in the POST variable

global $base;
global $securebase;

$url = preg_quote($base);
$url = str_replace("/", "\\/", $url);
$regexp1 = '/^'."$url".'/';
$url = preg_quote($securebase);
$url = str_replace("/", "\\/", $url);
$regexp2 = '/^'."$url".'/';

if( !isset($_SERVER{'HTTP_REFERER'}) ||  preg_match($regexp1, $_SERVER{'HTTP_REFERER'}) == 0 && preg_match($regexp2, $_SERVER{'HTTP_REFERER'}) == 0)
{
	unset($_POST['pass']);
}

?>
