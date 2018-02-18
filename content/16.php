<?php 
require_once('library/showfuns.php');

if (isset($_GET['socid']) && is_numeric($_GET['socid'])) {
	$query_soc="SELECT * FROM acts_societies WHERE id='".$_GET['socid']."'";
	$res=sqlquery($query_soc) or die(mysql_error());
	if ($row=mysql_fetch_assoc($res)) {
		$socname=$row['name'];
		$shortname=$row['shortname'];
	}
	
	$query_closeshows = "SELECT acts_shows.*,MAX(acts_performances.enddate) FROM acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_performances.enddate>=NOW() AND (acts_shows.socid=".$_GET['socid']." OR acts_shows.society LIKE \"%".addslashes($socname)."%\" OR acts_shows.society LIKE \"%".addslashes($shortname)."%\") AND acts_shows.authorizeid>0 AND acts_shows.entered=1 GROUP BY acts_shows.id ORDER BY acts_performances.enddate";
}
else {
	$query_closeshows = "SELECT acts_shows.*,MAX(acts_performances.enddate) FROM acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_performances.enddate>=NOW() AND acts_shows.authorizeid>0 AND acts_shows.entered=1 GROUP BY acts_shows.id ORDER BY acts_performances.enddate";

}
$closeshows = sqlquery($query_closeshows, $adcdb) or die(mysql_error());
$row_closeshows = mysql_fetch_assoc($closeshows);
$totalRows_closeshows = mysql_num_rows($closeshows);
?>
<div align="left">
  <?php do {
  showDispFrameless($row_closeshows,false,true); ?>
  <p><hr size="1" noshade></p>

    <?php } while ($row_closeshows = mysql_fetch_assoc($closeshows)); ?>
</div>
<?php

mysql_free_result($closeshows);
?>
