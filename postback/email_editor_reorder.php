<?php

$email = intval($_GET['emailid']);

if(canEditBuilderEmail($email)) {
   $ord = explode('.',$_GET['order']);
   $i=0;
   foreach($ord as $j) {
      sqlQuery("UPDATE acts_email_items SET orderid=".$i." WHERE emailid=$email AND id=$j");
      $i++;
   }	
   require_once("postback/email_editor_toc.php");  
}

?>
