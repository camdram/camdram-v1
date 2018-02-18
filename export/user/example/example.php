<?php

require_once("../libcamdram.php");

$camdramserver="http://camdram.atlantis.johndilley.me.uk/export/export.php";

$camdram=new camdramimport($camdramserver);

print_r($camdram->getpeople(new CDperson_query("John Dilley","",true)));
?>