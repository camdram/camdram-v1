<?php
/*
    This file is part of Camdram.

    Camdram is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Camdram is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Camdram; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
    Copyright (c) 2006-2012. See the AUTHORS file for a list of authors.
*/

if(logindetails(false,false,false)) { ?> 
<h3>Lookup camdram.net user</h3>
<p>This page allows you to search for contact details of people who
are registered with camdram.net and have granted us permission to
distribute their details. To add yourself, visit the <?php
makeLink(84,'account details'); ?> page; the option to list yourself
is given in section 3.</p>
<p>Please note that to prevent third parties from lifting the information,
we have decided only to match <b>exact names</b>, so if you are searching
for <em>Tom Brown</em> you may like to try other names he could be listed
under, e.g. </em>Thomas Brown</em>.</p>
<?php

if(isset($_POST[search])) {
  $name = $_POST[search];
  unset($_POST[search]);
  $q = "SELECT * FROM acts_users WHERE name='$name' AND (dbemail=1 OR dbphone=1)";
  $r = sqlQuery($q);
  if($r>0) {
    echo "<h4>Results</h4>";
    $n = 0;
    while($row = mysql_fetch_assoc($r)) {
      $n++;
  ?><table class="editor"><tr><th>Name</th><td><?=$row[name]?></td>
	   </tr><tr><th>Email</th><td><?=($row[dbemail]==1)?$row[email]:'withheld'?></td></tr>
	   <tr><th>Phone Number</th><td><?=($row[dbphone]==1 && $row[tel]!="")?$row[tel]:'withheld'?></td></tr></table><?php
	   }
    if($n==0) echo "<p>Sorry, there were no matches. This either means the person you are looking for is not signed up with camdram.net, they have signed up under a different name (e.g. shortening such as Tom for Thomas) or they have not opted to allow any of their information to be revealed on this page.</p>";
    echo "<h4>Search again</h4>";
  }
  
} 
 ?>
<form action="<?=thisPage()?>" method="post">
<table class="editor"><tr><th>Name</th><td><input name="search" id="search" type="text" value="<?=$name?>"></td></tr>
   <tr><th></th><td><input type="submit" name="Submit" value="Submit"></td></tr></table></form>
<?php
   }  else { ?>
<h3>Facility only available to registered users</h3>
   <p>Sorry, but the search users facility is only available to registered
   users of camdram.net. However, registration is free; just <?php
							  makeLink('signup',"click to signup"); ?>!</p> 
<?php
    }

?>