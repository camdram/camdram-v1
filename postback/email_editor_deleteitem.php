<?php
$item = intval($_GET['delete']);
$email = intval($_GET['emailid']);
$title = ($_POST['value']);
if(canEditBuilderEmail($email)) {
   $upd = "DELETE FROM acts_email_items WHERE id=$item AND emailid=$email LIMIT 1";
   $q=sqlQuery($upd,$adcdb) or die(sqlEr());
   require_once("email_editor_toc.php");
}

?>
