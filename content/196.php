<?php 
require_once("library/editors.php");
?>
<form method="post" action="<?=thisPage();?>">
<?php
$display=true;
if(isset($_POST[emailid]))
{
  $q = "SELECT emailid,userid,title FROM acts_email WHERE public_add>0 AND emailid=".$_POST[emailid];
  $r = sqlquery($q) or sqlEr($query);
  if($r>0)
  {
    
    $row=mysql_fetch_assoc($r);
    
    $q2 = "SELECT orderid FROM acts_email_items WHERE emailid = ".$_POST[emailid]." ORDER BY orderid DESC LIMIT 1";
    $res2 = sqlQuery($q2) or sqlEr();
    if($res2>0) {
      $row2 = mysql_fetch_assoc($res2);
      $orderid = $row2[orderid]+1;
      mysql_free_result($res2);
    }
    if($row[emailid]!=$_POST[emailid])
      inputFeedback();
    else {
      $q = "INSERT INTO acts_email_items (emailid,text,creatorid,created,orderid) VALUES (".$row[emailid].", '".$_POST[message]."',".$_SESSION['userid'].",NOW(),$orderid)";
      $i_r = sqlquery($q) or sqlEr($q);
      actionLog("Public submission to list ".$row[emailid]);
      $emailto = whoHas('builderemail',$row[emailid]);
      foreach($emailto as $usertoemail) {
	$admin_mail.= getUserEmail($usertoemail).", ";
      }
      
      mailTo($admin_mail,"New Message on camdram.net","A new public submission has been made to the email '".$row[title]."' which you have access to. Quick link to view the email: http://www.camdram.net/administration/email_builder/edit?emailid=".$row[emailid]."\n\nIf you believe you received this email in error, please contact websupport@camdram.net.","From: camdram.net <websupport@camdram.net>"); 
     
      inputFeedback("Message Submitted","Your message has been submitted for moderation. Please note the time delay varies depending on various factors; however the relevent moderators have been informed of your submission and will take action as soon as possible. You will receive confirmation that your submission has been sent, in the form of a copy of the email.");
      $display = false;
    }
    mysql_free_result($r);
  }
  
  echo "<p>&lt; ";
  makeLink(1,"home page");
  echo "</p>";
} 

if($display==true) {
?><table class="editor"><tr><td>Post to:</td><td><?php
  $query = "SELECT * FROM acts_email WHERE public_add>0";
  $r = sqlquery($query) or sqlEr();
  $things=array();
  $vals=array();
  if($r>0)
    { 
      while($row = mysql_fetch_assoc($r)) {
	$things[$row[emailid]]=$row[title];
	$vals[$row[emailid]]=$row[emailid];
      }
      mysql_free_result($r);
    }
  
  
  generateSelect("emailid",$things,$things[0],$things[0],$vals);
  echo "<br/>";
  echo "<small>";
  makeLink(197,"Which one of these do I want?");
  echo "</small>";
    ?></td></tr><td>Your Message</td><td><textarea rows="10" cols="80" wrap="virtual" name="message" id="message"></textarea><br />Please remember to include all relevent contact details.</tr><tr><td>&nbsp;</td><td align="right"><input type="submit" value="Submit" name="Submit"></td></tr></table></form>
<?php
														    } ?>
