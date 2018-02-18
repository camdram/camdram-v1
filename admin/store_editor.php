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

require_once("library/table.php");
require_once("library/editors.php");
$storeid=$_GET['storeid'];
if (hasEquivalentToken('store',$storeid)) {
	$q="SELECT * FROM acts_stores WHERE id='$storeid'";
	$result=sqlquery($q) or die(sqler());
	if ($row=mysql_fetch_assoc($result)) {
		echo "<h2>".$row['name']."</h2>";
		if (isset($_GET[deleteid])) {
			$q="DELETE FROM acts_catalogue WHERE id='$_GET[deleteid]'";
			sqlquery($q);
			unlink("images/catalogue/$_GET[deleteid].jpg");
		}
		if (isset($_GET[doedit]) && checksubmission()) {
			$q="UPDATE acts_catalogue SET description='$_POST[description]' WHERE id='$_GET[doedit]'";
			sqlquery($q);
			$newid=$_GET[doedit];
			$destfile="images/catalogue/$newid.jpg";
			if (isset($_POST[delpic])) {
				unlink($destfile);
			}
			if ($_FILES['picture']['name']!='') {
				if($_FILES['picture']['type']!='image/jpeg') {
					inputfeedback("File must be a JPEG image");
				}
				elseif($_FILES['picture']['size']>20000) {
					inputfeedback("Image must be less than 20kB");
				}
				else {
					move_uploaded_file($_FILES['picture']['tmp_name'], $destfile);
				}
			}
		}
		if (isset($_GET[insert]) && checksubmission()) {
			$q="INSERT INTO acts_catalogue(`storeid`,`description`) VALUES ('$storeid','$_POST[description]')";
			sqlquery($q);
			$newid=mysql_insert_id();
			if ($_FILES['picture']['name']!='') {
				$destfile="images/catalogue/$newid.jpg";
				if($_FILES['picture']['type']!='image/jpeg') {
					inputfeedback("File must be a JPEG image");
				}
				elseif($_FILES['picture']['size']>20000) {
					inputfeedback("Image must be less than 20kB");
				}
				else {
					move_uploaded_file($_FILES['picture']['tmp_name'], $destfile);
				}
			}
		}
		$q="SELECT * FROM acts_catalogue WHERE storeid='$storeid' ORDER BY description";
		$result=sqlquery($q) or die(sqler());
		maketable($result,array("Item"=>"NONE"),array("Item"=>'
				echo $row[description];
				if (file_exists("images/catalogue/$row[id].jpg")) echo " (<a href=\"images/catalogue/$row[id].jpg\" target=\"blank\">picture</a>)";'),'
			makelink(228,"edit",array("CLEARALL"=>"CLEARALL","editid"=>$row[id],"storeid"=>$row[storeid]));
			echo " | ";
			makelink(228,"delete",array("CLEARALL"=>"CLEARALL","deleteid"=>$row[id],"storeid"=>$row[storeid]));
		');
		if (isset($_GET[editid])) {
			$editid=$_GET[editid];
			$q="SELECT * FROM acts_catalogue WHERE id='$editid'";
			$result=sqlquery($q) or dir(sqler());
			if ($row=mysql_fetch_assoc($result)) {
				$submitid=allowsubmission();
				echo "<form enctype=\"multipart/form-data\" action=\"".thisPage(array("CLEARALL"=>"CLEARALL","submitid"=>$submitid,"doedit"=>$editid,"storeid"=>$storeid))."\" method=\"post\">";
				echo "<h3>Edit Item</h3>";
				echo "<p>Description: <input type=\"text\" value=\"$row[description]\" name=\"description\"></p>";
				if (file_exists("images/catalogue/$row[id].jpg")) {
					echo "<p><input type=\"checkbox\" name=\"delpic\"> Delete Existing Picture</p>";
				}
				else {
					echo "<p>Picture: <input type=\"file\" name=\"picture\"><br />JPEG, Max 20kB</p>";
				}
				echo "<input type=\"submit\" value=\"Edit Item\">";
				echo "</form>";
			}
		}
		else {
			$submitid=allowsubmission();
			echo "<form enctype=\"multipart/form-data\" action=\"".thisPage(array("CLEARALL"=>"CLEARALL","submitid"=>$submitid,"insert"=>"yes","storeid"=>$storeid))."\" method=\"post\">";
			echo "<h3>Add Item</h3>";
			echo "<p>Description: <input type=\"text\" value=\"$row[description]\" name=\"description\"></p>";
			echo "<p>Picture: <input type=\"file\" name=\"picture\"><br />JPEG, Max 20kB</p>";
			echo "<input type=\"submit\" value=\"Add Item\">";
			
		}
	}
}
