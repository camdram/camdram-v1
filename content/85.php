
<?php  ?>
<?php


$query_index = "SELECT * FROM acts_menu WHERE acts_menu.parentid>=0 AND acts_menu.secure=0 ORDER BY acts_menu.fulltitle";
$index = sqlquery($query_index, $adcdb) or die(mysql_error());
$row_index = mysql_fetch_assoc($index);
$totalRows_index = mysql_num_rows($index);
$c=substr($row_index['fulltitle'],0,1); 
echo("<h2>$c</h2>"); 
?>
<ul>
  <?php 
  do { 
  if(substr($row_index['fulltitle'],0,1)!=$c) 
  	{ 
		$c=substr($row_index['fulltitle'],0,1); 
		echo("</ul><h2>$c</h2><ul>"); 
	}
	?>
  <li><?php makeLink($row_index['id'],$row_index['fulltitle']); ?></li>  
  <?php } while ($row_index = mysql_fetch_assoc($index)); ?>
</ul>
<?php
mysql_free_result($index);
?>
