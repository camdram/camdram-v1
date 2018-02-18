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

// LOGGING

function microlog($line)
{
  return;
  /*  MICROLOG DISCONTINUED

  global $logpath;
  if(!isset($logpath)) return; 
  $ad=gethostbyaddr($_SERVER['REMOTE_ADDR']);
  $tm=date("r");
    
    $w=fopen("$logpath/microlog.txt",'a');
    $wl = "[$ad] - [$tm] - [$line]";
    
    if(stristr($_SERVER['HTTP_REFERER'],"camdram.net")==false && stristr($_SERVER['HTTP_REFERER'],"~acts")==false)
      $wl.=" - [\"".$_SERVER['HTTP_REFERER']."\",\"".$_SERVER['HTTP_USER_AGENT']."\"]";
    else
      $wl.=" - [continued]";
    fwrite($w,$wl."\n");
    fclose($w);
  */
}

function actionlog($line)
{
  global $logpath;
  if(!isset($logpath)) return;
	if(isset($_SESSION['user']))
		$u = $_SESSION['user'];
	else
		$u = "system";
	$ad=gethostbyaddr($_SERVER['REMOTE_ADDR']);
	$tm=date("r");
	$w=fopen("$logpath/actionlog.txt",'a');
	$wl = "[$ad] - [$tm] - $u:[$line]\n";
	fwrite($w,$wl);
	fclose($w);
}
?>
