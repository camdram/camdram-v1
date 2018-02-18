<?php
require_once("library/user.php");
require_once("library/editors.php");
require_once("library/mailinglists.php");

?>

<p>Get an email list:</p>
<?php
$lists=getlists(); 
$first=1;
foreach ($lists as $listid=>$listname) {
	if ($first==1) $first=0; else echo " | ";
	makeLink(147,$listname,array("query"=>$listid)); 
}
?>
<p>
<?php

if (isset($_GET['query'])) {
	$addresses=getaddresses($_GET['query']);
	$first=1;
	foreach ($addresses as $address) {
		if ($first==1) $first=0; else echo "; ";
		echo $address;
	}
}


?>
</p>
