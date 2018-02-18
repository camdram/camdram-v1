<?php 
global $adcdb,$site_support_email;
$gid=$_GET['helpid'];
$query_retmenu = "SELECT * FROM acts_pages WHERE acts_pages.id=$gid";
$retmenu = sqlquery($query_retmenu, $adcdb) or die(mysql_error());
$row_retmenu = mysql_fetch_assoc($retmenu);
mysql_free_result($retmenu);
	
?>
<h2>Help on <?=$row_retmenu['fulltitle'];?></h2>
<p><?php if(trim($row_retmenu['help'])=="") echo("Sorry, no help is available on this item.");
	else echo(preprocess($row_retmenu['help'])); ?>
  <p><strong>Support available via</strong><br>
  <a href="mailto:<?=$site_support_email?>"><?=$site_support_email?></a> </p>
