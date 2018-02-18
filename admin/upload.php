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

	/* Check for nasty sql */
	if ($_GET['uploadtype'] == 'show')
	{
		$showid = $_GET['showid'];
		$query = "SELECT id FROM acts_shows WHERE id=$showid";
		$r = mysql_query($query);
	
		if (mysql_num_rows($r) != 1)
			die ();
	
		uploadImage ("show", $showid, "images/shows/", $showid, "UPDATE acts_shows SET photourl='" . $showid . "' WHERE id=" . $showid . " LIMIT 1");
	}
	elseif ($_GET['uploadtype'] == 'society')
	{
		$socid = $_GET['editid'];
		$query = "SELECT id FROM acts_societies WHERE id=$socid";
		$r = mysql_query($query);
	
		if (mysql_num_rows($r) != 1)
			die ();
	
		uploadImage ("society", $socid, "images/societies/", $socid, "UPDATE acts_societies SET logourl='" . $socid . "' WHERE id=" . $socid . " LIMIT 1", 30000, 100, 100);
	}
?>
