<?php
/*
    This file is part of Camdram.

    Camdram is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Camdram is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Camdram; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
    Copyright (c) 2006-2012. See the AUTHORS file for a list of authors.
*/

require_once('library/showfuns.php');
require_once('library/editors.php');

function allocName($name)
{
	global $adcdb;
	$query_people = "SELECT * FROM acts_people_data WHERE acts_people_data.name LIKE \"$name\"";
	$people = sqlQuery($query_people, $adcdb) or die(mysql_error());
	$row_people = mysql_fetch_assoc($people);
	$totalRows_people = mysql_num_rows($people);
	if($totalRows_people>0) {
	  if($row_people[mapto]>0) $r=$row_people[mapto]; else $r=$row_people[id];
	} else 
	{
		
		$create = "INSERT INTO acts_people_data (name) VALUES (\"$name\")";
		$z = sqlQuery($create, $adcdb);
		$r=mysql_insert_id($adcdb);
		actionlog("Created person $r - $name");
		if($r==0) die(mysql_error());
        upgradePersonToV2($r);
	}
	mysql_free_result($people);
	return $r;
}


$showid=$_GET['showid'];
if(canEditShow($showid))
{
	

  $pickid=$_GET['pickid'];
  $picktype=$_GET['picktype'];

  editorLinks($showid,"Cast/Crew Listings");

  if($_POST['add']=="Add All")
    {
      if(checkSubmission())
	{
	  $order=1000;
	  $lines=explode("\n",$_POST['input']);
	  $type=$_POST['type'];
	  $separator=$_POST['separator'];
	  if($separator=="") $separator=":";
	  if(isset($_POST['nocleverness'])) $contextchecking=false; else $contextchecking=true;
	  $problem=false;
	  if($contextchecking)
	    {
	      foreach($lines as $line)
		{
		  $probline=false;
		  $lsplt=explode($separator,$line);
		  if($_POST['reverse']=="reversed")
		    {
		      $role=strtolower(trim($lsplt[1]));
		      $name=strtolower(trim($lsplt[0]));
		    } else {
		      $role=strtolower(trim($lsplt[0]));
		      $name=strtolower(trim($lsplt[1]));
		    }
		  $role=htmlspecialchars($role);
		  $name=htmlspecialchars($name);
		  $line=htmlspecialchars($line);
		  if(isset($lsplt[2]))
		    {
		      $probline=true;
		      $desc.="<li>Your separator character ($separator) appears more than once in the line <strong>$line</strong></li>";
		    }
		  if(strstr($role,",")!=false && !$probline)
		    {
		      $probline=true;
		      $desc.="<li>Comma expected as separator but found in role instead of name in the line <strong>$line</strong></li>";
		    }
				
		  $knownroles = array("sound","lighting","designer","lx","operator","costume","props","asm","dsm","stage","manager");
		  foreach($knownroles as $knownrole)
		    {
		      if(strstr($name,$knownrole)!=false && !$probline)
			{
			  $probline=true;
			  $desc.="<li>You have entered a <b>name</b> ('$name') that looks more like a <b>role</b> (statement <strong>$line</strong>)</li>";
			  break;
			}
		      if(strstr($role,$knownrole)!=false && $type!="prod" && !$probline)
			{
			  $probline=true;
			  $desc.="<li>You have entered a role ('$role') as a <strong>cast member</strong>, but it looks like a <strong>production team post</strong> (statement <strong>$line</strong>)</li>";
			  break;
			}
		    }
				
		  if(strstr($name," and ")!=false && !$probline)
		    {
		      $probline=true;
		      $desc.="<li>You have entered a <b>name</b> ('$name') with 'and' in it; you must separate each name with a comma or it will count as one name (statement <strong>$line</strong>)</li>";
		    }
		  if(($role=="" || $name=="") && $line!="" && !$probline)
		    {
		      $probline=true;
		      $desc.="<li>Couldn't find the separation between name and role in line <strong>$line</strong></li>";
		    }
		  if(strstr($name," ")==false && !$probline)
		    {
		      $desc.="<li>You have entered a <b>name</b> ('$name') which has only one word in it (statement <strong>$line</strong>)";
		      $probline=true;
		    }
		  $problem=$problem | $probline;
		}
	    } else actionLog("   user over-rode warnings");
		
	  if(!$problem)
	    {
	      foreach($lines as $line)
		{
		  $lsplt=explode($separator,$line);
		  if($_POST['reverse']=="reversed")
		    {
		      $role=trim($lsplt[1]);
		      $lsplt[0] = str_replace("&",",",$lsplt[0]);
		      $names=explode(",",trim($lsplt[0]));
		    } else {
		      $role=trim($lsplt[0]);
		      $lsplt[1] = str_replace("&",",",$lsplt[1]);
		      $names=explode(",",trim($lsplt[1]));
		    }
		  $role=htmlspecialchars($role);
				
		  if(count($names)<=0 || strlen($role)<=0) 
		    {
		      // errors supressed
		    } else {
		      foreach($names as $name) 
			{
			  $name=htmlspecialchars($name);
			  $order++;
			  $name=trim($name);
			  $pid=allocName($name);
			  $query = "INSERT INTO `acts_shows_people_link` (`type`, `role`, `pid`, `sid`, `order`) VALUES ('$type', '$role', $pid, $showid, '$order')";
			  $r = sqlQuery($query,$adcdb) or die(mysql_error());
			  markShowUpdate($showid);
              upgradePersonToV2($pid);
			}
		    }
		}
	      inputFeedback("Added");
	      actionlog("Bulk added people to show $showid");
	    } else {
	      inputFeedback("There appear to be problems with your bulk entry","<ul>".$desc."</ul></p><p>If you wish to ignore these potential problems and try processing the names list anyway, please click <b>continue anyway</b>. Otherwise please correct the form below.</p>",true);
	      actionLog("Detected Bulk People Entry Error and paused submission");
	      allowSubmission();
	      continueButton();

	    }
	}
    }
  if($_POST['add']=="Add")
    {
      if(checkSubmission())
	{
	  $type=$_POST['type'];
	  $role=$_POST['role'];
	  $name=$_POST['name'];
	  $email=$_POST['email'];
	  $order=$_POST['order'];
		
	  $names=explode(",",$name);
		
	  foreach($names as $name){
	    $name=trim($name);
	    $pid=allocName($name);
	    $query = "INSERT INTO `acts_shows_people_link` (`type`, `role`, `pid`, `sid`, `order`) VALUES ('$type', '$role', $pid, $showid, '$order')";
	    $r = sqlQuery($query,$adcdb) or die(mysql_error());
	    markShowUpdate($showid);
        upgradePersonToV2($pid);
	  }
	  inputFeedback("Added");
	  actionlog("Added people to show $showid");
	}
    }

  if($_GET['action']=="sort")
    {
      if(checkSubmission())
	{
	  $sortcode=$_GET['sortcode'];
	  $origsort=$_GET['origsort'];
		
	  if($origsort!=$sortcode)
	    {

	      $query = "UPDATE `acts_shows_people_link` SET `order`=$sortcode WHERE id=$pickid";
	      $r = sqlQuery($query,$adcdb) or die(mysql_error());

	    }
	  $xpickid=$pickid;
	  $pickid=0;
	  actionlog("Changed people ordering show $showid");
	  markShowUpdate($showid);
	}
      unset($_GET['action']);
      unset($_GET['sortcode']);
      unset($_GET['origsort']);
      unset($_GET['pickid']);
    }

  if($_GET['action']=="delete")
    {
      if(checkSubmission())
	{
	  $associd=$_GET['associd'];
	  $query = "DELETE FROM `acts_shows_people_link` WHERE id=$associd LIMIT 1";
	  $r = sqlQuery($query,$adcdb) or die(mysql_error());
	  inputFeedback("Deleted");
	  actionlog("Deleted people from show $showid");
	}
      unset($_GET['action']);
      unset($_GET['associd']);
    }

  allowSubmission();

  $row_closeshows=getshowrow($showid);

  authorizeWarning($row_closeshows);

  $query_people = "SELECT * FROM acts_people_data,acts_shows_people_link 
	WHERE acts_shows_people_link.sid=$showid
		AND acts_people_data.id=acts_shows_people_link.pid
		ORDER BY acts_shows_people_link.`type`,acts_shows_people_link.`order`";

  $people = sqlQuery($query_people,$adcdb) or die(mysql_error());
  $n = mysql_num_rows($people);
  $maxorder=-100;

  function hiddenElement($posid,$element,$content) {
    return "<td><span id=\"".$element."_drop".$posid."\" style=\"visibility:hidden; color: #aaaaaa; \">$content</span></td>";
  }

  function putDropPosition($posid,$row) {
  ?></td></tr><tr><?=hiddenElement($posid,"type",$row[type])?>
<?=hiddenElement($posid,"role",$row[role])?>
<?=hiddenElement($posid,"person",$row[name])?>

<td><a href="<?php echo(thisPage(array("sortcode"=>$posid,"action"=>"sort")));?>" onMouseOut="hideElms('type_drop<?=$posid?>'); hideElms('role_drop<?=$posid?>'); hideElms('person_drop<?=$posid?>'); showElms('type_dropreal'); showElms('role_dropreal');showElms('person_dropreal'); " onMouseOver="showElms('type_drop<?=$posid?>'); showElms('person_drop<?=$posid?>');showElms('role_drop<?=$posid?>'); hideElms('type_dropreal'); hideElms('role_dropreal');hideElms('person_dropreal'); ">drop here</a><?php
  }


  if($pickid!=0) {
    $q_pick = "SELECT acts_shows_people_link.type, acts_shows_people_link.role, acts_people_data.name FROM `acts_shows_people_link` LEFT JOIN `acts_people_data` ON acts_people_data.id=acts_shows_people_link.pid WHERE `acts_shows_people_link`.`id`='$pickid'";
    $r_pick = sqlQuery($q_pick) or sqlEr();
    assert(mysql_num_rows($r_pick)==1);
    $picked_row = mysql_fetch_assoc($r_pick);
  }

  if($n>0)
    {
  ?>
 <br>
    <table class="dataview">
    <tr>
    <th>Role type</th>
    <th>Role</th>
    <th>Person</th>
    <th>Action</th>
    </tr>
    <?php
    $new_order=1;
 $first_row_of_ordering = true;
 while($row_people = mysql_fetch_assoc($people)) {
   if($prevtype!=$row_people['type'])
     $prevord=-1000;
   if($pickid!=0 && $picktype==$row_people['type'] && $first_row_of_ordering) {
     $sortcode=$row_people['order'];
     putDropPosition($new_order-1,$picked_row);
     
	   $first_row_of_ordering=false;
	}?>
       <tr>
	  <td><?php 
	  if($pickid==$row_people[id]) echo "<span id=\"type_dropreal\"><strong>"; 
   echo($row_people['type']); 
   if($pickid==$row_people[id]) echo "</strong></span>"; 
?></td>
	  <td><?php 
	  if($pickid==$row_people[id]) echo "<span id=\"role_dropreal\"><strong>"; 
echo($row_people['role']);
   if($pickid==$row_people[id]) echo "</strong></span>"; 
 ?></td>					 <td><?php 
	  if($pickid==$row_people[id]) echo "<span id=\"person_dropreal\"><strong>"; 
   echo($row_people['name']);
   if($pickid==$row_people[id]) echo "</strong></span>"; 
   $uid=$row_people['id'];
   if($pickid==$row_people['id'] || $xpickid==$row_people['id']) 
     { ?><a name="anchor"></a>
<?php 
	    } 
   if($pickid==0) { ?></td><td><a href="<?php echo(thisPage(array("associd"=>$uid,"action"=>"delete"))); ?>">delete</a> | <a href="<?php echo(thisPage(array("pickid"=>$uid,"picktype"=>$row_people['type'],"origsort"=>$new_order)));?>"><?php if($pickid==$row_people['id']) echo("<b>pick</b>"); else echo("pick");?></a> <?php }  elseif($pickid==$row_people['id']) echo "<td>".makeLinkText(0,"drop back here",array("pickid"=>"NOSET"))."</td>";

if($pickid!=0 && $picktype==$row_people['type']) {
  $sortcode=$row_people['order']; 
  if($new_order>$_GET[origsort]+1 || $new_order<$_GET[origsort]-2) putDropPosition($new_order+1,$picked_row);
	
	}
	    echo "</td></tr>";
	    $prevord=$row_people['order'];
	    $prevtype=$row_people['type'];

	    if($row_people['order']>$maxorder) $maxorder=$row_people['order'];
	    
	    $query1 = "UPDATE `acts_shows_people_link` SET `order`=$new_order WHERE id=$uid LIMIT 1";
	    $new_order+=2;
	    $result = sqlQuery($query1) or die(mysql_error());	
 }?>
	
						   </table>
						       <?php
						       mysql_free_result($people);
						     ?>

						       <?php } ?>
      <h4>Add Cast or Crew List</h4>
	 <p>The box below allows you to copy and paste your programme, or type out a list. You need to enter the cast and crew separately.</p>

	 <form name="multiinput" method="post" action="<?php echo thisPage(); ?>">
	 <table class="editor"><tr><td>Ordering</td><td>
	 <input name="reverse" type="radio" id="reverse" value="" <?php if($_POST[reverse]!="reversed") echo("checked"); ?>> Name of role followed by the person (e.g. <em>Sound Designer: James Dooley</em>)<br/>
															       <input name="reverse" type="radio" id="reverse" value="reversed" <?php if($_POST['reverse']=="reversed") echo("checked"); ?>> Name of person followed by their role (e.g. <em>James Dooley: Sound Designer</em>) 

																															       </p></td></tr><tr><td>Separator</td><td> 
																															       <input name="separator" type="text" id="separator" value="<?php echo(isset($_POST['separator'])?$_POST['separator']:":"); ?>" size="4" maxlength="4"><br/>This separates the role from the name, so the separator for <em>James Dooley: Sound Designer</em> is just <em>:</em> whereas for <em>James Dooley :- Sound Designer</em> you need to enter <em>:-</em>
																																																																			       </td></tr>
																																																																			       <tr><td>List</td><td>
																																																																			       <textarea name="input" cols="80" rows="10"><?=$_POST['input']?></textarea>
																																																																			       </td></tr>
																																																																			       <tr><td>
																																																																			       Type</td><td>
																																																																			       <?php genericSelect('acts_showspeoplelink','type',$_POST['type'],array('cast'=>"Cast", 'prod'=>"Production Team", 'band'=>"Band/Orchestra")); ?>
																															       (you<strong> must </strong>enter production team and cast separately)
																																 </td></tr>
																																 <tr><td>&nbsp;</td><td>
																																		   <input type="submit" name="add" value="Add All">
																																		   </td></tr></Table>
																																		   </form>
																																		   <form action="<?=thisPage(); ?>" method="post" name="form" id="form">
																																		   <h4>Add Single Entry</h4>
																																		   <p>You can also enter each cast/crew member individually below, clicking add after each one.</p>
																																		   <table class="editor"><tr><td>
																																		   <input name="order" type="hidden" value="1000">
																																		   Role</td><td>
																																		   <input type="text" name="role"></td></tr><tr><td>
																																		   Role type</td><td>
																																		   <select name="type" id="type">
																																		   <option value="cast"<?php if($_POST['type']=="cast") echo("selected");?>>Cast</option>
																																											      <option value="prod"<?php if($_POST['type']=="prod") echo("selected");?>>Production
																																																					 Team</option>
																																																					 </select></td></tr><tr><td>
																																																					 Name</td><td>
																																																					 <input type="text" name="name">
																																																					 (can be a list separated by commas)</td></tr><tr><td>&nbsp;</td><td>
																																																													<input name="add" type="submit" id="add" value="Add">
																																																													</td></tr></table>
																																																													</form>
																																																													<?php } ?>
