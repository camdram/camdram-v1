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

require_once('library/user.php');
require_once('library/editors.php');
require_once('library/table.php');
require_once('library/mailinglists.php');

if(hasToken('security',-1))
{
if (!isset($_GET['sortby'])) {
	$_GET['sortby']="id";
	$_GET['order']="up";
}

if(isset($_GET['type']))
{
	$type=$_GET['type'];
} else $type='orphans';

if(isset($_GET['password']))
{
	$rsid=$_GET['rsid'];
	$ret=allocPassword($rsid);
	if($ret!=false)
		echo("<p><strong>Password sent to ".$ret."</strong></p>");
	else
		echo("<p><strong>Error in password allocation</strong></p>");
	unset($_GET['password']);
	unset($_GET['rsid']);
	
}
if(isset($_GET['delid']) && isset($_GET['confirmed']))
{
	$delid=$_GET['delid'];
	
	$query_upd="DELETE FROM acts_users WHERE id=$delid LIMIT 1";
	$p = sqlQuery($query_upd, $adcdb) or sqlEr();
	inputFeedback("Deleted!");

	unset($_GET['delid']);
	unset($_GET['confirmed']);
}

$query_people="SELECT * FROM acts_users WHERE 1=1";
if($_GET['uid']!="")
	$query_people.=" AND (email='".$_GET['uid']."' OR id='".$_GET['uid']."')";

if($_GET['username']!="")
	$query_people.=" AND name LIKE '%".$_GET['username']."%'";
$query_people.=order();	
$people = sqlQuery($query_people, $adcdb) or die(mysql_error());
$num = mysql_num_rows($people);

$page = $_GET['page'];
$maxresults = 10;
$maxpage = splitResults($people,$maxresults,$page);
?><h4>Filter</h4><p>Leave blank to list all users</p><form name="form2" method="get" action="">
  <table class="editor"><th>
  user ID (email)</th><td><input name="uid" type="text" id="uid" value="<?=$_GET['uid']?>">
  </td></tr><tr><th>
  name</th><td>
  <input name="username" type="text" id="username" value="<?=$_GET['username']?>">
  <input name="id" type="hidden" id="id" value="108">
</td><tr><td>&nbsp;</td><td>
  <input type="submit" name="Submit" value="Search">  
  </td></tr></table></form><h4>Results</h4><?php
	if($num>0)
{
if($maxpage>1) { ?>
<p><?=displayRangeField($page,$maxpage)?></p>
<?php } 

//$row_people = mysql_fetch_assoc($people);
?>
</p>
<form name="form1" method="get" action="<?php echo thisPage(); ?>">
<?php $continue=true;
	$count=0; 
	 $pid=-1;
maketable($people,array(
	"id"=>"id",
	"name"=>"name",
	"tel"=>"NONE",
	"email/login"=>"email",
	"Registered"=>"registered",
	"Last Login"=>"login",
	"Shw"=>"NONE",
	"Soc"=>"NONE",
	"Eml"=>"NONE",
	"Sec"=>"NONE",
	"Lists"=>"NONE",
	"U-E"=>"dbemail",
	"U-T"=>"dbphone",
	"F-P"=>"publishemail",
	"F-N"=>"forumnotify"
),array(
	"Registered"=>'echo(dateFormat(strtotime($row[\'registered\']),0,true));',
	"Last Login"=>'if($row[\'login\']!="0000-00-00") echo(dateformat(strtotime($row[\'login\']),0,true)); 
			else echo("<b>never</b>");',
	"tel"=>'if($row[\'tel\']=="") echo "-"; else echo $row[\'tel\'];',
	"Shw"=>'$notokens=true;
		  $q="SELECT id,revokeid FROM acts_access WHERE uid=".$row[\'id\']." AND type=\'show\'"; 
			$res = sqlQuery($q) or die(mysql_error());
			$n=mysql_num_rows($res);
			$m=0;
			while($idr = mysql_fetch_assoc($res))
				if($idr[\'revokeid\']==0) $m++;
			echo ($m==0)?"-":$m;
			if($n!=$m) echo " (".$n.")";
			if($n>0) $notokens=false;
			mysql_free_result($res);',
	"Soc"=>'$q="SELECT id,revokeid FROM acts_access WHERE uid=".$row[\'id\']." AND type=\'society\'"; 
			$res = sqlQuery($q) or die(mysql_error());
			$n=mysql_num_rows($res);
			$m=0;
			while($idr = mysql_fetch_assoc($res))
				if($idr[\'revokeid\']==0) $m++;
			echo ($m==0)?"-":$m;
			if($n!=$m) echo " (".$n.")";
			if($n>0) $notokens=false;
			mysql_free_result($res);',
	"Eml"=>'$q="SELECT id,revokeid FROM acts_access WHERE uid=".$row[\'id\']." AND type=\'email\'"; 
			$res = sqlQuery($q) or die(mysql_error());
			$n=mysql_num_rows($res);
			$m=0;
			while($idr = mysql_fetch_assoc($res))
				if($idr[\'revokeid\']==0) $m++;
			echo ($m==0)?"-":$m;
			if($n!=$m) echo " (".$n.")";
			if($n>0) $notokens=false;
			mysql_free_result($res);',
	"Sec"=>'$q="SELECT * FROM acts_access WHERE uid=".$row[\'id\']." AND type=\'security\'";
			$res = sqlQuery($q) or die(mysql_error());
			$n=mysql_num_rows($res);

			if($n>0)
			{
				$notokens=false;
				$r=mysql_fetch_assoc($res);
				
				echo(" [".describeToken($r));
				
				while ($r=mysql_fetch_assoc($res))
					echo("; ".describeToken($r));
									
				echo("]");
			} else echo "-";
			
			mysql_free_result($res);',
	"Lists"=>'$userlists=getuserlists($row[\'id\']);
			$first=1;
			foreach ($userlists as $list) {
				if ($first==1) $first=0; else echo ", ";
				echo $list;
			}
			if (sizeof($userlists)==0) echo "-";',
	"F-P"=>'if($row[\'publishemail\']==1) echo("y"); else echo("-");',
	"F-N"=>'if($row[\'forumnotify\']==1) echo("y"); else echo("-");',
	"U-E"=>'if($row[dbemail]==1) echo "y"; else echo "-";',
	"U-T"=>'if($row[dbphone]==1) echo "y"; else echo "-";'
),'
    if($notokens) {
        echo "<a href=\"".thisPage(array("delid"=>$row[\'id\']))."\" ".confirmer("delete the user " . $row[\'name\']).">delete</a> | ";
        }
        makeLink(110,"access",array("retid"=>108,"uid"=>$row[\'id\']));
      echo " | <a href=\"".thisPage(array("password"=>"reset", "rsid"=>$row[\'id\']))."\" ".confirmer("randomise the password for " . $row[\'name\']).">reissue password</a> | ";
      makelink(164,"mailing lists",array("uid"=>$row[\'id\'], "CLEARALL"=>"CLEARALL"));

',$maxresults);
?>
<p>
  <?php makeLink(109,"Create User",array("retid"=>108)); ?>
</p>
</form>

<p>&nbsp;</p>
<?php
} else echo("<strong>No results</strong>");
mysql_free_result($people);
} else inputFeedback();
?>
