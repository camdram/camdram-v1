<?php
global $id;

if (isset($extra_page_info) && sizeof($extra_page_info)>0)
{
	if (sizeof ($extra_page_info) != 2) {
		if(sizeof($extra_page_info)==1) {
			$q = sqlquery ("SELECT id FROM acts_shows,acts_shows_refs WHERE showid=id AND ref='" . $extra_page_info[0] . "'");
			
		}
		else die ("<strong>Error: show does not exist</strong>");
	}
	else {
		$q = sqlquery ("SELECT id FROM acts_shows,acts_shows_refs WHERE showid=id AND ref='" . $extra_page_info[0] . "/" . $extra_page_info[1] . "'");
	}
	if (mysql_num_rows($q) == 1)
	{
		$row = mysql_fetch_assoc ($q);
		$id = $row['id'];
	}
	else
		die ("<strong>Error: show does not exist</strong>");
}
elseif(!isset($_GET['showid']))
	die("<strong>Error: show not specified</strong>");
else $id=$_GET['showid'];

if (!is_numeric($id))
  die();


  $query_people = "SELECT MAX(acts_people_data.norobots) AS norobots FROM acts_shows_people_link , acts_people_data , acts_shows
	WHERE acts_shows_people_link.sid=$id
	AND acts_people_data.id=acts_shows_people_link.pid
	AND acts_shows.id=acts_shows_people_link.sid GROUP BY acts_shows_people_link.sid";
  $people = sqlquery($query_people, $adcdb) or die(mysql_error());
  
  global $people,$row_people;	
  $row_people = mysql_fetch_assoc($people);
  $robots = $row_people['norobots'];

  if($robots==1) {
    echo '<meta name="robots" content="noindex">'; 
  }


?>
