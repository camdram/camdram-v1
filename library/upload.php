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

function uploadImage($tokentype = "", $tokenrid = 0, $locate = "", $imageid = -1, $updatestring = "", $maxsize = 20000, $x = 75, $y = 75)
/* tokentype/tokenrid: Obvious access control information
   locate: Where to put the files, eg. "images/shows/"
   id: identifier for the file, eg 1234.
   updatestring: SQL to run on success.
   $maxsize: Max picture size in kb
   $x, $y: Max picture dimensions in px
*/
{
	if ($tokentype == "" || $locate == "" || $imageid == -1 || $updatestring == "")
		die ();
	if (!hasEquivalentToken ($tokentype, $tokenrid))
		die ("Access denied");
	
	$type = "jpg";
	$mimetype[0]="image/jpeg";
	$mimetype[1]="image/pjpeg";
	
	$uploaded=false;
	
	if(isset($_FILES['userfile']))
	{
		$okmt=false;
		foreach($mimetype as $thismt)
		{
			$okmt=$okmt || ($thismt==$_FILES['userfile']['type']);
		}
		if(!$okmt)
		{
			echo("<b>File rejected</b> - this doesn't appear to be a ".$type."; reported type is ".$_FILES['userfile']['type']);
			actionlog("upload fail - incorrect type");
		} else {
			list($width, $height, $type_img, $attr) = getimagesize($_FILES['userfile']['tmp_name']);
			if(($width>$x || $height>$y))  {
				$imgname=$_FILES['userfile']['tmp_name'];
				$simg=imagecreatefromjpeg($imgname);
				if ($height>($width*$y/$x)) {
		        	        $zoom=$y/$height;
		        	        $newheight=$y;
		        	        $newwidth=$width*$zoom;
		        	}
		        	else {
		        	        $zoom=$x/$width;
		        	        $newwidth=$x;
		        	        $newheight=$height*$zoom;
				}
				$dimg=imagecreatetruecolor($newwidth,$newheight);
				imagecopyresized($dimg,$simg,0,0,0,0,$newwidth,$newheight,$width,$height); 
				echo("<b>File resized to $newwidth x $newheight</b>");
				$i=80;
				imagejpeg($dimg,$_FILES['userfile']['tmp_name'],$i);
				$i--;
				while(filesize($_FILES['userfile']['tmp_name'])>$maxsize && $i>0) {
					$i--;
					imagejpeg($dimg,$_FILES['userfile']['tmp_name'],$i);
				}
			}
			if(move_uploaded_file($_FILES['userfile']['tmp_name'],"$locate$imageid.$type"))
			{
				echo("<p><b>File uploaded</b></p><p>$locate$imageid.$type created successfully</p>");
				actionlog("uploaded $locate$imageid.$type");
				if(!sqlQuery($updatestring))
					inputFeedback("Error","Cannot create correct references to this file in database.");
				if(isset($_GET['retid']))
				{
					echo("<p>Click ");
					makeLink($_GET['retid'],"here",array("retid"=>"NOSET","uploaded"=>"$id.jpg"));
					echo(" to continue</p>");
				}
				$uploaded=true;
			} else {
				echo("<p>Unknown upload error</p><p><pre>");
				print_r($_FILES);
				echo("</pre></p>");
			}
		}
	}

if(!$uploaded) {?>
<form enctype="multipart/form-data" action="<?php echo thisPage(); ?>" method="post">
 <p>
<?php if(isset($id)) {?>Filename: <strong><?=$id?>.<?=$type?></strong><br><?php } ?>
Uploading to: <strong><?=$locate?></strong><br>
Types allowed: <strong><?=$type?></strong><br>
Size limit: <strong><?php echo(($maxsize/1000)."K"); ?></strong><br>
<?php if($image) {?>
Allowed Dimensions: <strong>up to <?=$x?>x<?=$y?> pixels</strong><?php }?>
<br />
<b>You can upload any jpeg and it will be resized to fit these parameters</b>
<br>
<?php 
makeLink($_GET['retid'],"Abort Upload",array("retid"=>"NOSET","uploadtype"=>"NOSET","id"=>"NOSET")); ?> </p><p><?php
if($dateupload) { ?>
 DD
    <input name="day" type="text" size="3" maxlength="2">
  MM
  <input name="month" type="text" size="3" maxlength="2">
  YY
  <input name="year" type="text" size="3" maxlength="2">
  <br>
  Comment
  <input type="text" name="comment">
  (e.g. &quot;AGM&quot;)<br>
  <?php } ?>
  Upload file: 
    <input name="userfile" type="file" />
    <input type="submit" value="Upload" />
  </p>
</form>
<?php
}

}
?>
