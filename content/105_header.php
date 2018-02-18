<?php 

if(isset($_GET['person'])) {
  
  $person=$_GET['person'];
  if (!is_numeric($person))
    die();

  $query_people = "SELECT * FROM acts_shows_people_link , acts_people_data , acts_shows
	WHERE acts_shows_people_link.pid=$person 
	AND acts_people_data.id=acts_shows_people_link.pid
	AND acts_shows.id=acts_shows_people_link.sid
	AND acts_shows.authorizeid IS NOT NULL";

  $people = sqlquery($query_people, $adcdb) or die(mysql_error());
  
  global $people,$row_people;
  
  $row_people = mysql_fetch_assoc($people);
  $totalRows_people = mysql_num_rows($people);
  
  $robots = $row_people['norobots'];
  if($robots==1) {
    echo '<meta name="robots" content="noindex">'; 
  }
}


?>
                      
