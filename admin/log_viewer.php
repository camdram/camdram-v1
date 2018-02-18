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
?>
<p>
<?php
// Log viewer - uses standard unix tools (grep, wc, awk, head, tail) to get part of the log file
// John Dilley, 07/2004

// Updated by AP 02/09/04 to check security credentials! Whoops!
if (isset($_POST['search'])) $_GET['search']=$_POST['search'];
if (isset($_POST['regexp'])) $_GET['regexp']=$_POST['regexp'];

add_page_mode ("full", 'log_viewer.php','View full log',array("logmode"=>0));
add_page_mode ("action", 'log_viewer.php','View action log');

require_once('library/editors.php');

if(hasEquivalentToken('security',-1))
{
	// Number of lines per page
	$perpage=100;
	if(isset($_GET['regexp'])) $command = "egrep"; else $command = "grep";
	
	// File to open
	if($_GET['logmode']==1 || !isset($_GET['logmode']))
	{
		$file="../private/actionlog.txt";
		set_active_mode ("action");
	}
	else
	{
		$file="/var/log/apache2/camdram.log";
		set_active_mode ("full");
	}
	
	// *Hopefully* makes grep safe (on admin, so not too much of a security hole if it doesn't!)
	$search=str_replace("\\", "\\\\",$_GET['search']);
	$search=str_replace("\"", "\\\"",$search);
	
	// Count lines
	$totallines=exec("$command -i \"$search\" $file | wc -l | awk '{print \$1}'");
	
	// Work out number of pages
	$maxpage=ceil($totallines/$perpage);
	
	// Work out current page
	if (isset($_GET{'page'})) {
		$page=$_GET{'page'};
	} else {
		$page=$maxpage;
	}
	
	// Work out start and end lines
	$startline=($page-1)*$perpage;
	$endline=$totallines-$startline;
	
	// Display navigation and search
	if ($maxpage>1) {
		displayRangeField($page,$maxpage);
	}
	?>
	</p><p>
	<form method="POST" action="<?=thisPage(array("search"=>"NOSET","regexp"=>"NOSET"))?>">
	Search for <input type="text" value="<?php echo htmlspecialchars($_GET['search']);?>" name="search">
	<input type="checkbox" value="on" name="regexp" <?php if(isset($_GET['regexp'])) echo "checked"; ?> ><label>Regular Expression</label>
	<input type="submit" value="search"></form>
	</p><pre><?php
	
	// Get lines
	passthru("$command -i \"$search\" $file | tail -$endline | head -$perpage");
	?>
	</pre>
	<p>
	<?php
	if ($maxpage>1) {
		displayRangeField($page,$maxpage);
	}
} else echo "You don't have permissions to view this log.";
?>
</p>
