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
session_id($_GET['sid']);
session_start();


if (isset($_POST['loginid2'])) $_POST['loginid']=$_POST['loginid2'];
$lid=$_POST['loginid'];
if (isset($_SESSION['user'])) {
	$alw=tryAuth($_SESSION['user'],md5($_POST['pass']),1);
}
else $alw=tryAuth($_POST['loginid'],md5($_POST['pass']),1);
unset($_POST['loginid']);
unset($_POST['pass']);
$error=0;
if ($alw>0) {
  unset($_SESSION['preferences']);
  $_SESSION['authlevel']=$alw;
  if (!isset($_SESSION['user'])) {
    $_SESSION['user']=$lid;
    actionlog("successful login - $lid");
  }
  else {
    actionLog("Reauthenticated - ".$_SESSION['user']);
  }
  $_SESSION['userid']=$alw;
  $_SESSION['expire']=time()+600;	
  global $adcdb;
  $upd="UPDATE acts_users SET `login`=NOW() WHERE `email`='$lid' LIMIT 1";
  sqlquery($upd,$adcdb) or die(mysql_error());
  if (isset($_POST['remember'])) {
	$_SESSION["camdramuser"]=$lid;
  }
}
else {
	actionlog("failed login - $lid"); 
	$error=1;
}
foreach ($_POST as $key=>$value) {
	$_SESSION["POST"][$key]=$value;
}
$newaddr=linkTo($_GET['id'],array("mode"=>"NOSET","sid"=>"NOSET","loginbox"=>(($error==1)?"show":"NOSET")),"", false, $_POST['extra_page_info']);
if (stristr($newaddr,"?")) $newaddr.="&"; else $newaddr.="?";
if ($error>0) $newaddr=$newaddr."error=".$error;
if ($error==0) $newaddr=$newaddr."loginsuccess=true";
$newaddr=$newaddr."&".session_name()."=".session_id();
$newaddr=str_replace("&amp;","&",$newaddr);
header("Location:".$newaddr);
?>
