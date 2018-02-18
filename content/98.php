<form name="form1" method="post" action="<?php echo linkTo(87,array("CLEARALL"=>"CLEARALL")) ?>">
<?php 
require_once("library/editors.php");
$editid=$_GET['editid'];
$fp=fopen("content/$editid.php",'r');
$t=(fread($fp,filesize("content/$editid.php"))); ?>
<p>
  <input name="submitid" type="hidden" id="submitid" value="<?=allowSubmission()?>">
  <textarea name="updatepage" cols="100%" rows="40" wrap="VIRTUAL" id="updatepage">
  
<?php echo htmlspecialchars($t); ?>
</textarea>
  <input name="updateid" type="hidden" id="updateid" value="<?=$editid?>">
</p>
<p>
  <input name="update" type="submit" id="update" value="Update Page">
</p>
</form>
