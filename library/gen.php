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

global $jsload;

$jsload = "";

function js_header() {
  // basic javascript functions factored out of index.php as they also appear in micro.php
  global $jsload;
?>
<script language="JavaScript" type="text/javascript">
<!--
function popup(url,width,height) {
var new_window = window.open(url,null ,'toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=' + width + ',height=' + height); 
new_window.focus();} 

function confirmLink(toconfirm, message)
{   var is_confirmed = confirm( message);
    if (is_confirmed) {
         toconfirm.href += '&confirmed=true';
    }
    return is_confirmed;
}

function hideElms(elmTag) {
  document.getElementById(elmTag).style.visibility = "hidden";
  
}

function showElms(elmTag) {
  document.getElementById(elmTag).style.visibility = "visible";
}

// from dynamic-tools.net, to determine whether a mouseover/mouseout event
// is genuinely from leaving/entering the area, or just from subtag activity

function isMouseLeaveOrEnter(e, handler)
{	 			
				if (e.type != 'mouseout' && e.type != 'mouseover') return false;
				var reltg = e.relatedTarget ? e.relatedTarget :
				e.type == 'mouseout' ? e.toElement : e.fromElement;
				while (reltg && reltg != handler) reltg = reltg.parentNode;
				return (reltg != handler);
}

// window.onload = function() {
	      <?php echo $jsload; ?>
// }


//-->
</script>
<?php
}

function addJSLoad($run) {
	 global $jsload;
	 $jsload.=$run."\n";
}

function ultraAdvert($text) {
  // Added by AP 24/04/07
  echo "<div id='ultraadvert'>$text</div>";
}


function setStackTitle($title) {
  global $this_stack_ref,$mode;
  if($mode==2) {
    $_SESSION['stacktitle'][$this_stack_ref] = $title;
  }
}

function backStack() {
  global $this_stack_ref,$mode;
  if($mode==2) {
    if(isset($_SESSION['prevstackref'][$this_stack_ref])) {
      echo "<small>";
      echo '<a href="'.$_SESSION['stackurl'][$_SESSION['prevstackref'][$this_stack_ref]].'">Back';
      if(isset($_SESSION['stacktitle'][$_SESSION['prevstackref'][$this_stack_ref]])) echo " to ".$_SESSION['stacktitle'][$_SESSION['prevstackref'][$this_stack_ref]];
      echo '</a>';
      echo "</small>";
      $_SESSION['nextstackref'][$_SESSION['prevstackref'][$this_stack_ref]]=$this_stack_ref;
    } else echo "&nbsp;";
  }
}

function hint($hint,$line,$distress=false)
{
  $class="hide";
  if($distress==true) $class.=" distress";
  return "<span class=\"$class\">[$hint] <div id=\"layer\" class=\"floater\">".$line."</div></span>";
}


function mailTo($to="",$subject="",$message="",$additional_headers="",$cc="",$bcc="",$rpath="")
{
  global $site_support_email;
  global $mail_redirect;
  if(isset($mail_redirect)) {
    $message="*** REDIRECTED MESSAGE ***\n\nThe configuration has specified this message be sent to this address. The message would have had the following paramaters:\n\nTo: $to\nCC: $cc\nBCC: $bcc\n\n".$message;
    $to=$mail_redirect;
    $cc="";
    $bcc="";
  }
  return mail($to,$subject,$message,"From: $rpath\nCc:$cc\nBcc:\n$additional_headers","-r $site_support_email");
}

function sqlQuery($query)
{
  global $verbose;
  
  global $count;
  $count++;
  //if ($count==200) die($query); // Uncomment this line for debugging endless loops
  
  $result = mysql_query($query);
  $message = "SQL Query<br/><i>$query</i>";
  if($result==0) $message.="<br/>MySQL Error:".mysql_error();
  
  
  if($verbose>0) echo hint("SQL",$message,($result==0));
  return $result;
}


function sanityCheck($st,$expecting)
{
  if(strstr($expecting,"!")) 
  {
  
    $expecting = str_replace("!","",$expecting);
    if($st=="") return "sanityCheck_fail";
  }
  
  switch($expecting)
    {
    case 'any':
      return $st;
      break;
    case 'int':
      if(is_numeric($st)) return $st;
      else return "sanityCheck_fail";
      break;
    case 'str4sql':
      return $st;   // no magic quotes needs to be accounted for...
      break;
    case 'str':
      return stripslashes($st);
      break;
    }
  
}

function traceUnloadedVariables()
{
  global $_GET, $_GET_ORIG, $_POST, $_POST_ORIG,$currentid;
  $getwarning = "";
  foreach($_GET_ORIG AS $key=>$value)
    {
      if(!isset($_GET[$key])) $getwarning.="; ".$key."=".$value;
    }
  if($getwarning!="") actionlog("Page $currentid warning: unloaded GET variables".$getwarning);
  $postwarning = "";
  foreach($_POST_ORIG AS $key=>$value)
    {
      if(!isset($_POST[$key])) $postwarning.="; ".$key."=".$value;
    
    }
  if($postwarning!="") actionlog("Page $currentid warning: unloaded POST variables".$postwarning);
  if($getwarning!="" || $postwarning!="") echo("<p><strong>Warning: </strong>some variables which were passed to this page have been discarded $getwarning $postwarning");
}

function getconfig($field) {
	global $actsini;
	global $adcdb;
	if (!isset($actsini)) {
		$query="SELECT * FROM acts_config";
		$result=sqlQuery($query,$adcdb) or die(mysql_error());
		while($row=mysql_fetch_assoc($result)) {
			$actsini[$row['name']]=$row['value'];
		}
	}
	return $actsini[$field];	
}
function includer($name) {
	global $adcdb;
	$query="SELECT * FROM acts_includes WHERE name='$name' LIMIT 1";
	$q=sqlQuery($query,$adcdb) or die(mysql_error());
	if ($row=mysql_fetch_assoc($q)) return preprocess($row['text']);
	return "";
}

function getStoreRow($storeid)
{
	global $storecache;
	if (isset($storecache[$storeid])) return $storecache[$storeid];
	// straight-forward db query
	$query = "SELECT * FROM acts_stores WHERE id=$storeid";
	$result = sqlQuery($query);
	if($result >0)
	{
		$store=mysql_fetch_assoc($result);
		mysql_free_result($result);
		$storecache[$id]=$store;
		return $store;
	}
	return false;
}

/** URL Of the current page. 

@param $params If true, includes any GET parameters in the URL. Otherwise, no parameters are included

*/

function curPageURL($params = false) {
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://" . $_SERVER['SERVER_NAME'];
	if ($_SERVER["HTTPS"] == "on") {
		if($_SERVER["SERVER_PORT"] != 443 ){
			$pageURL .= ':' . $_SERVER["SERVER_PORT"];
		}
	}else if ( $_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= ':' . $_SERVER["SERVER_PORT"];
	}
	if($params){
		$pageURL .=$_SERVER["REQUEST_URI"];
	}else{
		$pageURL .=$_SERVER["SCRIPT_NAME"];
	}
	return $pageURL;
}


// Not sure where best to put this...
date_default_timezone_set("Europe/London");

?>