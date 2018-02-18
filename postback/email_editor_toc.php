<?php
require_once("library/emailGen.php");
echo wordwrap(htmlspecialchars(toc($_GET[emailid])));
?>
