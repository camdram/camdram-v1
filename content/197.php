<h3>Which email list do I want to select?</h3>
<?php
$query = "SELECT * FROM acts_email WHERE public_add>0";
$r = sqlquery($query);
if($r>0)
{
  echo "<table class=\"editor\">";
  while($row = mysql_fetch_assoc($r)) {
    echo "<tr><td><strong>".$row[title]."</strong></td><td>".$row[summary]."</td></tr>";
  }
  echo "</table>";
  mysql_free_result($r);
  
}
