<?php
$s = explode(" ",microtime());
require_once("library/datetime.php");
//if (count($_POST) > 0) die("<ul><li>Error<br/><span class=\"informal\">Multiple fields passed</span></li></ul>";
if(isset($_POST['searchtext'])) 
	$t = $_POST['searchtext'];      
else
	$t = $_GET['searchtext'];

$involving = false;

$keywords = explode(" ", $t);
if (strtolower(substr($keywords[0],0,6))=='involv') {
   if(sizeof($keywords)>1) {
      $keywords=array_slice($keywords,1);
   } else {
      $keywords[0]="";
   }
   $involving=true;
}

$makelink = false;
$keywordsearch = "";
$keywordcount = 0;
foreach ($keywords as $keyword)
{
	if ($keywordcount > 0)
	{
		$keywordsearch.= " AND ";
	}
	$keywordcount++;
	$keywordsearch .= "(keyword LIKE '$keyword%' OR keyword LIKE '% $keyword%')";
}


$q = "SELECT id, keyword, text, url, linkcode FROM acts_search_cache WHERE type='show' AND $keywordsearch AND obsolete = 0 ORDER BY sindex DESC LIMIT 10";


$shows = sqlQuery($q) or die("<ul><li>Search error<br/><span class=\"informal\">".mysql_error()."</span></li></ul>");

$q = "SELECT id, keyword, text, url, linkcode FROM acts_search_cache WHERE type='person' AND $keywordsearch AND OBSOLETE = 0 ORDER BY sindex DESC LIMIT 10";

$people = sqlQuery($q) or die("<ul><li>Search error<br/><span class=\"informal\">".mysql_error()."</span></li></ul>");

$q = "SELECT id, keyword, text, url, linkcode FROM acts_search_cache WHERE type='infobase' AND $keywordsearch AND OBSOLETE = 0 ORDER BY sindex DESC LIMIT 10";

$infobase = sqlQuery($q) or die("<ul><li>Search error<br/><span class=\"informal\">".mysql_error()."</span></li></ul>");

$q = "SELECT id, keyword, text, url, linkcode FROM acts_search_cache WHERE (type='infobase' OR type='page') AND $keywordsearch AND OBSOLETE = 0 ORDER BY sindex DESC LIMIT 10";

$pages = sqlQuery($q) or die("<ul><li>Search error<br/><span class=\"informal\">".mysql_error()."</span></li></ul>");

$peoplerows = mysql_num_rows($people);
$showrows = mysql_num_rows($shows);
$infobaserows = mysql_num_rows($infobase);


$searcharray = array();

$liststart = "<ul>";
$listend = "</ul>";
$elementtype = "li";

if ($_GET['search'] == "archive")
{
	if ($showrows < 5)
	{
		$peoplerows = 10 - $showrows;
	}
	elseif ($peoplerows < 5)
	{
		$showrows = 10 - $peoplerows;
	}
	else
	{
		$peoplerows = 5;
		$showrows = 5;
	}
	for ($i = 0; $i < $showrows; $i++)
	{
		$searcharray[] = "show";
	}
	for ($i = 0; $i < $peoplerows; $i++)
	{
		$searcharray[] = "person";
	}
}
elseif ($_GET['search'] == "frontpage")
{
	$liststart = "";
	$listend = "";
	$elementtype="div class=\"searchresult\"";
	$pagerows = 20;
	$showrows = 20;
	$peoplerows = 20;
	$makelink = true;
	
	for ($i = 0; $i < $showrows; $i++)
	{
		$searcharray[] = "show";
	}
	for ($i = 0; $i < $peoplerows; $i++)
	{
		$searcharray[] = "person";
	}	
	for ($i = 0; $i < $pagerows; $i++)
	{
		$searcharray[] = "page";
	}
}
elseif ($_GET['search'] == "infobase")
{
	$infobaserows = 10;
	for ($i = 0; $i < $infobaserows; $i++)
	{
		$searcharray[] = "infobase";
	}
}


echo $liststart;

$xvalue="";
foreach ($searcharray as $value)
{
	if ($value == "page")
	{
		$row = mysql_fetch_assoc($pages);
	}
	if ($value == "infobase")
	{
		$row = mysql_fetch_assoc($infobase);
	}
	if ($value == "show")
	{
		$row = mysql_fetch_assoc($shows);
	}
	if ($value == "person")
	{
		$row = mysql_fetch_assoc($people);
	}
	if($value!=$xvalue && $xvalue!="")
			echo "<!--SPLIT-->";
	$xvalue=$value;
	if ($row)
	{
		echo "<$elementtype id = $row[id]>";
		if($makelink) echo $row['linkcode'];
		echo $row['keyword'];
		if($makelink) echo '</a>';
		echo "<br/><span class=\"informal\">";	
	   	echo "$row[text]";
		echo "</span>";
		echo "</$elementtype>";
		
	}

}

echo $listend;

// $e = explode(" ",microtime());
// echo "<li>Search time<br/><span class=\"informal\">".number_format((($e[0]+$e[1]-$s[0]-$s[1])),2)."s</span></li>";


