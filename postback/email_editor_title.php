<?php
$item = intval($_GET['itemid']);
$email = intval($_GET['emailid']);
$title = ($_POST['value']);
if(canEditBuilderEmail($email)) {
   if($title!="[Click to title]") {
      $upd = "UPDATE acts_email_items SET title='$title' WHERE id=$item AND emailid=$email";
      $q=sqlQuery($upd,$adcdb) or die(sqlEr());
   }
   if($title!="")
      echo stripslashes($title);
   else
      echo "[Click to title]";
}

?>
