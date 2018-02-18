<?php 
require_once('library/showfuns.php');
require_once('library/editors.php');
echo "<h3>Post Comments</h3>";
$dispform=true;
if(!isset($_GET['showid']))
	die("<strong>Error: show not specified</strong>");
$id=$_GET['showid'];
if($id<0)
{
	global $adcdb;
	$query = "SELECT * FROM acts_shows WHERE enddate<NOW() AND linkid IS NULL AND authorizeid>0 AND entered=1 ORDER BY rand() LIMIT 1";
	$x = sqlquery($query,$adcdb) or die(mysql_error());
	$row_closeshows=mysql_fetch_assoc($x);
	mysql_free_result($x);
	$id=$row_closeshows['id'];
} else $row_closeshows = getShowRow($id);
$name=$row_closeshows['title'];
if(isset($_POST['review']) && checkSubmission())
{
	$syntax = "INSERT INTO acts_reviews (`showid`,`review`,`uid`,`created`) VALUES (".$id.",'".removeEvilTags($_POST['review'])."',".$_SESSION['userid'].",NOW())";
	global $adcdb;
	$x = sqlquery($syntax,$adcdb) or sqlEr();
	if($x>0)
	{
		inputFeedback("Show review has been posted");
		showDispFrameless($row_closeshows,true,false,false);
		$dispform=false;
	}

} else {
	showDispBasics($row_closeshows,false);
}

if($dispform)
{?>
<form name="form1" method="post" action="">
<p>Comments:<br>
    <textarea name="review" cols="60" rows="10" wrap="virtual"><?=stripslashes($_POST['review'])?>
    </textarea>
  </p>
<p>
  <input type="submit" name="Submit" value="Submit">
  <input type="hidden" name="submitid" value="<?=allowSubmission()?>">
  <br>
</p>
</form>
<?php } ?>
