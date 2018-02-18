<?php
require_once('library/editors.php');
require_once("library/table.php");
$perpage=5;
if (isset($_POST['search'])) $_GET['search']=$_POST['search'];
if (!isset($_GET['sortby'])) {
	$_GET['sortby']="description";
	$_GET['order']="up";
}
if (!isset($extra_page_info[0])) {
	$type=1;
}
elseif ($extra_page_info[0]=="all") {
	$type=2;
	$filter="";
}
else {
	$type=2;
	$filter=$extra_page_info[0];
} 
?>
<?php if ($type==2) { ?>

<?php
if ($filter!="") {
$query="SELECT * FROM acts_stores WHERE shortname='$filter'";
$query=sqlQuery($query,$adcdb) or die(sqlEr());
if ($store=mysql_fetch_assoc($query)) 
{
	echo("<h2>".$store['name'])."</h2>";
	?><p><?php 
global $adcdb;
$query = "SELECT acts_access.*, acts_users.* FROM acts_access, acts_users WHERE type='store' AND acts_access.rid=".$store['id']." AND acts_access.revokeid IS NULL AND acts_users.id=acts_access.uid AND acts_access.contact=1";
$res = sqlquery($query,$adcdb) or die(mysql_error());
if($res>0)
{
	$num=mysql_num_rows($res);
	if($num>0)
	{
		?>For enquiries about this store, please contact <?php
		$n=1;
		while($row = mysql_fetch_assoc($res))
		{
			if($n>1)
				if($n==$num) echo(" or "); else echo(", ");
			echo("<a href=\"mailto:".userToEmail($row['email'])."\">".$row['name']."</a>");
			$n++;
		}
		
	}
	mysql_free_result($res);
}	
} else echo("<h2>Can't find store</h2><p>Sorry, we don't have any details about this store.</p>");

} ?></p>
<form method="post" action="<?php echo thispage(array(),$extra_page_info);?>">
Search for: <input type="text" name="search" value="<?php echo $_GET['search'];?>">
<input type="submit" value="Search">
</form>

<?
$extra="";
if ($filter!="") $extra="AND acts_stores.shortname='".$filter."' ";
$query="SELECT description,acts_catalogue.id catid,name,acts_stores.id storeid, acts_stores.shortname shortname FROM acts_catalogue,acts_stores WHERE acts_catalogue.storeid=acts_stores.id $extra AND description LIKE '%".mysql_real_escape_string($_GET[search])."%'".order();
$items=sqlQuery($query) or sqlEr();

$totalRows = mysql_num_rows($items);
$maxpage=ceil($totalRows/$perpage);

if($totalRows>$perpage)
{
	$page=$_GET['page'];
	if($page<1 || $_GET['page']>$maxpage)
		$page=1;
	$start=($page-1)*$perpage;
	for($n=0;$n<$start;$n++)
	{
		mysql_fetch_assoc($items);
	}
}
if($maxpage>1) { ?><p><?=displayRangeField($page,$maxpage)?></p><?php } 

$arraya=array(
	"Item Description"=>"description",
	"Picture"=>"NONE");
if ($filter=="") $arraya["Store"]="name";
maketable($items,$arraya
,array(
	"Picture"=>'if (file_exists("images/catalogue/".$row[\'catid\'].".jpg")) { 
		echo "<img src=\"images/catalogue/".$row[\'catid\'].".jpg\">"; 
		} 
		else 
		{ 
		echo "(no picture)"; 
		}',
	"Store"=>'makelink(229,$row[name],array(),true,"",false,$row[\'shortname\']);'
	),'',$perpage);

if($maxpage>1) { ?><p><?=displayRangeField($page,$maxpage)?></p><?php } 
?>

<?php } 
else {
	echo "<h2>Stores</h2>";
	echo "Plase choose a store to browse or click \"All Stores\" to search over all our stores";
	echo "<ul><li>";
	makeLink(229,"All Stores",array(),true,"",false,"all");
	echo "</li>";
	$query="SELECT * FROM acts_stores";
	$q=sqlQuery($query,$adcdb);
	while($row=mysql_fetch_assoc($q)) {
		echo "<li>";
		makeLink(229,$row['name'],array(),true,"",false,$row['shortname']);
		echo "</li>";
	}
	echo "</ul>";
}
?>
