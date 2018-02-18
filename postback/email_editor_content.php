<?php
$item = intval($_GET['itemid']);
$email = intval($_GET['emailid']);
$text = ($_POST['value']);
if(canEditBuilderEmail($email)) {
   $upd = "UPDATE acts_email_items SET text='$text' WHERE id=$item AND emailid=$email";

   $q=sqlQuery($upd,$adcdb) or die(sqlEr());
   if($text!="")
      echo stripslashes($text);
   else
      echo "[Click to enter content]\n";
}

?>
