<?php


function putTopLeft() 
{
  $left = array("camdram_0.jpg","camdram_1.jpg","camdram_2.jpg",
		"camdram_3.jpg","camdram_4.jpg");
  $right = array("greetings.jpg","none","bee.jpg","bee.jpg",
		 "none");
  $n = count($left);
  $select = (int) rand(0,$n-1);
  $left = $left[$select];
  $right = $right[$select];
?>
 <td height="94" class="left"><a href="/">
<img src="/furniture/summer/<?=$left?>" alt="camdram.net" width="430" height="94" border=0></a><?php
if($right!="none") { ?></td><td height="94" class="left"><img src="/furniture/summer/<?=$right?>" alt="Spring 2005" width="243" height="94"><?php } ?></td>
<?php 
       }
?>
