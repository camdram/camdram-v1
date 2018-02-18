<?php
global $adcdb;
$searchstring=mysql_real_escape_string($_POST['searchtext']);
$bits=preg_split("/\s+/",$searchstring);
$type=$_POST['type'];
$scope=$_POST['scope'];
if ($type =="phrase") {
	if ($scope=="all") {
		$where="p.fulltitle LIKE '%$searchstring%' OR k.kw LIKE '%$searchstring%' OR p.help LIKE '%$searchstring%'";
	}
	else {	
		$where="p.fulltitle LIKE '%$searchstring%' OR k.kw LIKE '%$searchstring%'";
	}
}
if ($type=="and") {
	$first=1;
	foreach ($bits as $bit) {
		if ($first==0) $where=$where." AND";
		$first=0;
		if ($scope=="all") {
			$where=$where." (p.fulltitle LIKE '%$bit%' OR k.kw LIKE '%$bit%' OR p.help LIKE '%$bit%') ";
		}
		else {	
			$where=$where." (p.fulltitle LIKE '%$bit%' OR k.kw LIKE '%$bit%') ";
		}
	}
}
if ($type=="or") {
	$first=1;
	foreach ($bits as $bit) {
		if ($first==0) $where=$where." OR";
		$first=0;
		if ($scope=="all") {
			$where=$where." (p.fulltitle LIKE '%$bit%' OR k.kw LIKE '%$bit%' OR p.help LIKE '%$bit%') ";
		}
		else {	
			$where=$where." (p.fulltitle LIKE '%$bit%' OR k.kw LIKE '%$bit%') ";
		}
	}
}
$query="SELECT DISTINCT p.id,p.fulltitle,p.parentid FROM `acts_pages` p LEFT OUTER JOIN `acts_keywords` k ON p.id=k.pageid WHERE p.mode='filtered' AND p.parentid>=0 AND ($where)";
$result=sqlquery($query,$adcdb) or die(mysql_error());
$count=mysql_num_rows($result);
if ($count>0) {
	echo "<p>Found $count pages matching your search</p><ol>";
	while ($row=mysql_fetch_assoc($result)) {
		echo "<li>";
		makelink($row['id'],$row['fulltitle'],array("CLEARALL"=>"CLEARALL"));
		if ($row['parentid'] !=0) {
			$prow=getpagerow($row['parentid']);
			echo " (under ";
			makelink($row['parentid'],$prow['fulltitle'],array("CLEARALL"=>"CLEARALL"));
			echo ")";
		}
		echo "</li>";
	}
	echo "</ol>";
}
else {
	echo "<p>Sorry, your search did not match any pages</p>";
}
echo "<p><form method=\"POST\" action=\"/infobase/search\">";
echo "Search again: <br />";
echo "<input type=\"text\" name=\"searchtext\" value=\"$searchstring\"> ";
echo "<input type=\"submit\" value=\"search\"><br />";
echo "<input type=\"radio\" name=\"type\" value=\"and\"";
if ($type=="and") echo " checked";
echo ">All Words ";
echo "<input type=\"radio\" name=\"type\" value=\"or\"";
if ($type=="or") echo " checked";
echo ">Any Word ";
echo "<input type=\"radio\" name=\"type\" value=\"phrase\"";
if ($type=="phrase") echo " checked";
echo ">Phrase<br />";
echo "<input type=\"radio\" name=\"scope\" value=\"limited\"";
if ($scope=="limited") echo " checked";
echo ">Search title &amp; keywords only ";
echo "<input type=\"radio\" name=\"scope\" value=\"all\"";
if ($scope=="all") echo " checked";
echo ">Search title &amp;, keywords and content";
echo "</form></p>\n";
