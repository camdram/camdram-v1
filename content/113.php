<?php 
require_once('library/showfuns.php'); 

global $adcdb;
$query_closeshows = "SELECT * FROM acts_shows WHERE (acts_shows.enddate>=NOW() AND acts_shows.linkid IS NULL AND acts_shows.society IS NULL AND authorizeid>0 AND acts_shows.entered=1) ORDER BY acts_shows.startdate ASC,acts_shows.time ASC LIMIT 5";
$closeshows = sqlquery($query_closeshows, $adcdb) or die(mysql_error());
$row_closeshows = mysql_fetch_assoc($closeshows);
$totalRows_closeshows = mysql_num_rows($closeshows);
$ed=$row_closeshows['enddate'];
?>
<div align="left">

        <?php 
		
		do { 
		
			if(strtotime($row_closeshows['startdate'])<=strtotime($ed)+86400)	// another show starting within a day of the end of this one
			{

				showDisp($row_closeshows);
				if($ed>$row_closeshows['enddate']) $ed=$row_closeshows['enddate'];
			}
				

		
		 } while ($row_closeshows = mysql_fetch_assoc($closeshows)); ?>

</div>

<?php

mysql_free_result($closeshows);
?>
