<?php


require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");

global $cache_link_id_to_nice_url;
global $mode;

$mode = 1; // Links will be used from main pages

$query = "SELECT acts_shows.title, acts_shows.id AS showid, acts_shows_refs.ref AS url, MAX(acts_performances.enddate) as enddate, MIN(acts_performances.startdate) as startdate, (1000000 - ABS(to_days(MAX(acts_performances.enddate))-to_days(NOW()))) AS sindex, acts_shows.venid AS venid, acts_shows.venue AS venue FROM acts_shows LEFT JOIN acts_performances ON acts_shows.id=acts_performances.sid INNER JOIN acts_shows_refs ON acts_shows.primaryref = acts_shows_refs.refid WHERE acts_shows.authorizeid IS NOT NULL AND enddate<'2034-01-01' GROUP BY acts_shows.id";

$shows = sqlQuery($query, $adcdb) or die(SqlEr());

$query = "SELECT acts_people_data.name AS name, acts_people_data.id AS id, COUNT(DISTINCT(acts_shows_people_link.sid)) AS numshows, MIN(acts_performances.startdate) AS start, MAX(acts_performances.enddate) AS end, (TO_DAYS(MAX(acts_performances.enddate))-TO_DAYS(NOW()))/5+COUNT(DISTINCT(acts_shows_people_link.sid)) AS sindex FROM acts_people_data INNER JOIN acts_shows_people_link ON acts_shows_people_link.pid=acts_people_data.id INNER JOIN acts_shows ON acts_shows_people_link.sid = acts_shows.id INNER JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_shows.authorizeid IS NOT NULL GROUP BY acts_shows_people_link.pid HAVING numshows > 0";

$people = sqlQuery($query, $adcdb) or die(SqlEr());

$query = "SELECT id,fulltitle FROM acts_pages WHERE mode = 'filtered' AND parentid != -1";

$infobase = sqlQuery($query, $adcdb) or die(SqlEr());

$query = "SELECT id,fulltitle FROM acts_pages WHERE mode != 'filtered' AND searchable = 1 AND parentid != -1";

$pages = sqlQuery($query, $adcdb) or die(SqlEr());

SqlQuery("DELETE FROM acts_search_cache WHERE obsolete = 1") or die(SqlEr());
SqlQuery("UPDATE acts_search_cache SET obsolete = 1") or die(SqlEr());
// SqlQuery("ALTER TABLE acts_search_cache ADD COLUMN (linkcode VARCHAR(2000))") or die(sqlEr());
$insertquery = "INSERT INTO acts_search_cache (keyword, text, type, url, linkcode, sindex) VALUES ";
$i=0;

while($row = mysql_fetch_assoc($shows)) {
	$keyword = $row['title'];
	$text = "";
	if(strtotime($row['enddate'])>time() and strtotime($row['startdate'])>time())
	{
		$text.=datesByTerm($row['startdate'],$row['enddate']);
	}
	else if(strtotime($row['enddate'])>time())
	{
		$text.= "Finishes ".dateFormat(strtotime($row['enddate']));
	}
	else
	{
		$text.= date("M Y",strtotime($row['enddate']));
	}
	$text.= "<br/>".venueName($row);
	$url = "/shows/".$row['url'];
	$sindex = $row['sindex'];
	if ($i > 0)
	{
		$insertquery .=" ,";
	}
	$i++;
	$keyword = addslashes($keyword);
	$url = addslashes($url);
	$text = addslashes($text);
	$linkcode = addslashes(startLink(104, array('showid'=>$row['showid'], 'PHPSESSID'=>'NOSET'), True));
	$insertquery.= "('$keyword', '$text', 'show', '$url', '$linkcode', '$sindex')";
	if ($i > 100)
	{
		echo htmlspecialchars($insertquery)."<br />";
		SqlQuery($insertquery, $adcdb) or die(SqlEr());
		$insertquery = "INSERT INTO acts_search_cache (keyword, text, type, url, linkcode, sindex) VALUES ";
		$i=0;
	}
}
while($row = mysql_fetch_assoc($people))
{
	$keyword = $row['name'];
	$text = "";
	if(strtotime($row['end'])>strtotime("-6 months")) $text.= "Active (Shows: ".$row['numshows'].")";
	else
	{
		$begin = date("M y",strtotime($row['start']));
		$end = date("M y",strtotime($row['end']));
		if($begin!=$end)
		{
			 $text.= "was active ".$begin." - ".$end."<br/> (Shows: ".$row['numshows'].")";
		}
		else
		{
			$text.= "involved ".$end;
		}
	}
	$url = "/person?person=".$row['id'];
	$sindex = $row['sindex'];
	if ($i > 0)
	{
		$insertquery .=" ,";
	}
	$i++;
	$url = addslashes($url);
	$keyword = addslashes($keyword);
	$text = addslashes($text);
	$linkcode = addslashes(startLink(105, array("person"=>$row['id'],"PHPSESSID"=>"NOSET"), True));
	$insertquery.= "('$keyword', '$text', 'person', '$url', '$linkcode', '$sindex')";
	if ($i > 100)
	{
		echo htmlspecialchars($insertquery)."<br />";
		SqlQuery($insertquery, $adcdb) or die(SqlEr());
		$insertquery = "INSERT INTO acts_search_cache (keyword, text, type, url, linkcode, sindex) VALUES ";
		$i=0;
	}
}

while($row = mysql_fetch_assoc($pages))
{
	$keyword = $row['fulltitle'];
	$text = "";
	$sindex = 0;
        if (!isset($cache_link_id_to_nice_url[$row['id']]))
		continue;
	$url = $cache_link_id_to_nice_url[$row['id']];
	
	if ($i > 0)
	{
		$insertquery .=" ,";
	}
	$i++;
	$url = addslashes($url);
	$keyword = addslashes($keyword);
	$text.='camdram.net'.$cache_link_id_to_nice_url[$row['id']];
	$text = addslashes($text);
	$linkcode = addslashes(startLink($row['id'], array("PHPSESSID"=>"NOSET"), True));	
	$insertquery.= "('$keyword', '$text', 'page', '$url', '$linkcode', '$sindex')";
	if ($i > 100)
	{
		echo htmlspecialchars($insertquery)."<br />";
		SqlQuery($insertquery, $adcdb) or die(SqlEr());
		$insertquery = "INSERT INTO acts_search_cache (keyword, text, type, url, linkcode, sindex) VALUES ";
		$i=0;
	}
}

while($row = mysql_fetch_assoc($infobase))
{
	$keyword = $row['fulltitle'];
	$text = "";
	$sindex = 0;
	$url = $cache_link_id_to_nice_url[$row['id']];
	
	if ($i > 0)
	{
		$insertquery .=" ,";
	}
	$i++;
	$url = addslashes($url);
	$keyword = addslashes($keyword);
	$text = addslashes($text);
	$text.="[infobase] camdram.net".$url;	
	$linkcode = addslashes(startLink($row['id'], array("PHPSESSID"=>"NOSET"), True));	
	$insertquery.= "('$keyword', '$text', 'infobase', '$url', '$linkcode', '$sindex')";
	if ($i > 100)
	{
		echo htmlspecialchars($insertquery)."<br />";
		SqlQuery($insertquery, $adcdb) or die(SqlEr());
		$insertquery = "INSERT INTO acts_search_cache (keyword, text, type, url, linkcode, sindex) VALUES ";
		$i=0;
	}
}

if ($i > 0)
{
	SqlQuery($insertquery, $adcdb) or die(SqlEr);
}


	
?>
