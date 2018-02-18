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
?>
<html>
<head>
<script language="JavaScript" type="text/javascript">
<!--
function popup(url,width,height) {
var new_window = window.open(url,null ,'toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=' + width + ',height=' + height); 
new_window.focus();} 

function confirmLink(toconfirm, message)
{   var is_confirmed = confirm( message);
    if (is_confirmed) {
         toconfirm.href += '&confirmed=true';
    }
    return is_confirmed;
}
//-->
</script>
<title>Test Harness for external.php</title>
</head>
<body>
<?php require_once("external.php");
?>
</body>
</html>
