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

if (!hasEquivalentToken("society",0)) die('You are not permitted to access this page');
?>

<?php
global $adcdb;
$uid=$_SESSION['userid'];
$emailid=$_GET['emailid'];
if (!is_numeric($emailid))
  die();
require_once('./library/editors.php');
require_once('./library/showfuns.php');
require_once('./library/emailGen.php');
require_once('./library/mailinglists.php');

if(isset($_GET["hidetext"]) || isset($_GET["pick"]))
	$displayFullText=false;
else
	$displayFullText=true;

if(isset($_POST["from"])) {
  if(isset($_POST['deleteonsend'])) $dos = 1; else $dos = 0;
  $query = "UPDATE acts_email SET `from`='".mysql_real_escape_string($_POST['from'])."', `listid`='".mysql_real_escape_string($_POST['listid'])."', `deleteonsend`='".$dos."' WHERE emailid='".mysql_real_escape_string($_GET['emailid'])."' LIMIT 1";
  
  $r = sqlQuery($query) or die(sqlEr($query));
  // $q = sqlQuery($query) or die(mysql_error());
}
if(canEditBuilderEmail($emailid)) {
  $query_emailinfo="SELECT * FROM acts_email WHERE emailid=".$emailid;
  $q=sqlQuery($query_emailinfo,$adcdb) or die(mysql_error());
  if (mysql_num_rows($q) ==0) die("Couldn't load email");
  $row_email=mysql_fetch_assoc($q);
  echo "<h3>Editing email \"".$row_email['title']."\"</h3><p>";
  makeLink(80, "Back to email selector", array("CLEARALL"=>"CLEARALL"));
  echo "</p>";


// DO INSERTIONS

if (isset($_POST['techid']) or isset($_POST['audid']) or isset($_POST['infoid']) or isset($_POST['eventid']) or (isset($_POST['appid'])) or isset($_POST['insertblank']) or isset($_POST['inserttoc'])) {
  
  if (checkSubmission()) {
		if ($_POST['submit']=="insert") {
			if (isset($_POST['infoid'])) {
				$ids[0]=$_POST['infoid'];
			}
			elseif (isset($_POST['techid'])) {
				$ids[0]=$_POST['techid'];
			}
			elseif (isset($_POST['audid'])) {
				$ids[0]=$_POST['audid'];
			}
			elseif (isset($_POST['eventid'])) {
				$ids[0]=$_POST['eventid'];
			}
			elseif (isset($_POST['appid'])) {
				$ids[0]=$_POST['appid'];
			}
			else {
				$ids[0]=0;
			}
		}
		else {  // Insert All button was pressed
			if (isset($_POST['infoid'])) {
				$query="SELECT acts_shows.id,acts_shows.title,acts_shows.venue,acts_shows.venid,MAX(IF(acts_performances.id IS NULL, '2034-01-01',acts_performances.enddate)),MIN(IF(acts_performances.id IS NULL, '2034-01-01', acts_performances.startdate)) AS startdate FROM acts_shows LEFT JOIN acts_performances ON acts_shows.id=acts_performances.sid WHERE acts_performances.enddate>=NOW() AND acts_shows.authorizeid>0 AND acts_shows.entered=1 GROUP BY acts_shows.id ORDER BY acts_performances.startdate,acts_performances.enddate,venue,society";
			}
			elseif (isset($_POST['techid'])) {
				$query="SELECT DISTINCT acts_shows.id AS id, title, MIN(IF(acts_performances.id IS NULL,'2034-01-01',acts_performances.enddate)) AS enddate,acts_shows.venue,acts_performances.time,acts_shows.venid,dates FROM acts_techies, acts_shows LEFT JOIN acts_performances ON (acts_performances.sid=acts_shows.id) WHERE acts_shows.id=acts_techies.showid AND acts_techies.expiry>=NOW() AND (acts_performances.enddate>=NOW() OR acts_performances.enddate IS NULL) AND acts_shows.authorizeid>0 GROUP BY acts_shows.id ORDER BY enddate,acts_shows.venue,society";
			}
			elseif (isset($_POST['audid'])) {
				$query="SELECT DISTINCT acts_shows.id AS id, title, MIN(IF(acts_performances.id IS NULL, '2034-01-01', acts_performances.startdate)) AS startdate,MIN(IF(acts_performances.id IS NULL, '2034-01-01', acts_performances.enddate)) AS enddate,acts_shows.venue,acts_performances.time,acts_shows.venid FROM acts_auditions,acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_shows.id=acts_auditions.showid AND acts_auditions.date>=NOW() AND (acts_performances.enddate>=NOW() OR acts_performances.enddate IS NULL) AND acts_shows.authorizeid>0 GROUP BY acts_shows.id ORDER BY startdate,enddate,venue,society";
			}
			elseif (isset($_POST['eventid'])) {
				$query="SELECT * FROM acts_events WHERE linkid=0 AND date>=NOW() ORDER BY date,starttime,endtime";
			}
			elseif (isset($_POST['appid'])) {
				$query="SELECT * FROM acts_applications WHERE deadlinedate>=CURDATE()";
			}
			$q=sqlQuery($query,$adcdb) or die(mysql_error());
			$i=0;
			while ($row=mysql_fetch_assoc($q)) {
				$ids[$i]=$row['id'];
				$i++;
			}
		}
		foreach ($ids as $id) {
			$query_max="SELECT MAX(orderid) AS orderid FROM acts_email_items,acts_email WHERE acts_email.emailid=acts_email_items.emailid AND acts_email_items.emailid=$emailid";
			$q=sqlQuery($query_max,$adcdb) or die(mysql_error());
			if ($row_max=mysql_fetch_assoc($q)) {
				$newmax=$row_max['orderid']+1;
			}
			else {
				$newmax=0;
			}
			$protect=0;
			$showstuff=getshowrow($id);
		        $startDate=getEarliestDate($showstuff);
   	                $endDate=getLatestDate($showstuff);
		        $termweek=datesByTerm($startDate,$endDate);

			if (isset($_POST['infoid'])) {
				$title=$showstuff['title'] . " (" . $termweek . ")";
				$text=generateInfoItem($id);
			}
			elseif (isset($_POST['techid'])) {
				$title=$showstuff['title'] . " (" . $termweek . ")";	
				$text=generateTechItem($id);
			}
			elseif (isset($_POST['audid'])) {
				$title=$showstuff['title'] . " (" . $termweek . ")";	
				$text=generateActorItem($id);
			}
			elseif (isset($_POST['eventid'])) {
				$stuff=getEventRow($id);
				$title=$stuff['text'] . " (" . date("D jS M", strtotime($stuff['date'])) . ")";
				$text=generateEventItem($id);
			}
			elseif (isset($_POST['appid'])) {
				$stuff=getAppRow($id);
				if ($stuff['showid']>0) {
					$stuffa=getShowRow($stuff['showid']);
					$other=$stuffa['title'];
				}
				if ($stuff['socid']>0) {
					$stuffa=getSocRow($stuff['socid']);
					$other=$stuffa['name'];
				}
				$title=$other." - ".$stuff['text'];
				$text=generateApplicationItem($id);
			}
			elseif (isset($_POST['inserttoc'])) {
				$text="~~~TABLE_OF_CONTENTS~~~";
				$title="";
				$protect=1;
			}
			else {
				$title="";
				$text="";
			}
			$text=addslashes($text);
			$title=addslashes($title);
			
			$query_insert="INSERT INTO acts_email_items(emailid,text,orderid,title,protect) VALUES ($emailid,'".mysql_real_escape_string($text)."',$newmax,'".mysql_real_escape_string($title)."',$protect)";
			$q=sqlQuery($query_insert) or die(mysql_error());
			if (isset($_POST['insertblank'])) $_GET['dispedit']= mysql_insert_id();
			actionlog("inserted article into email $emailid");
		}
	}
}

// DO DELETES

if (isset($_GET['delete'])) {
	if (checkSubmission()) {
		$delid=$_GET['delete'];
		$query_items="SELECT id,text FROM acts_email_items,acts_email WHERE acts_email.emailid=acts_email_items.emailid AND acts_email_items.emailid=$emailid AND acts_email_items.id=$delid";
		$query_delete="DELETE FROM acts_email_items WHERE id=$delid AND emailid=$emailid";
		$q=sqlQuery($query_items,$adcdb) or die(mysql_error());
		if (mysql_num_rows($q)>0) {
			sqlQuery($query_delete,$adcdb) or die(mysql_error());
			actionlog ("deleted article from email $emailid");
		}
	}
}

if( isset($_POST['deleteunprotected'])) {
	if(checkSubmission()) {
		$query_delete="DELETE FROM acts_email_items WHERE emailid=$emailid AND protect = 0";
		sqlQuery($query_delete,$adcdb) or die(mysql_error());
		actionlog ("deleted all unprotected articles frome email $emailid");
	}
}

// DO UPDATES

if (isset($_GET['editid'])) {
	if (checkSubmission()) {
		$editid=$_GET['editid'];
		$text=$_POST['text'];
		$title=$_POST['title'];
		if(isset($_POST['protect'])) $prot=1; else $prot=0;
		$query_items="SELECT id,text FROM acts_email_items,acts_email WHERE acts_email.emailid=acts_email_items.emailid AND acts_email_items.emailid=$emailid AND acts_email_items.id=$editid";
		$query_update="UPDATE acts_email_items SET text='".mysql_real_escape_string($text)."', title='".mysql_real_escape_string($title)."', protect=$prot WHERE id=$editid AND emailid=$emailid";
		
		$q=sqlQuery($query_items,$adcdb) or die(mysql_error());
		if (mysql_num_rows($q)>0) {
			sqlQuery($query_update,$adcdb) or die(mysql_error());
			actionlog ("updated article in email $emailid");
		}
	}
}
// DO MOVES

if (isset($_GET['drop']) && checkSubmission()) {
	$dropid=$_GET['drop'];
	$belowid=$_GET['below'];
	if ($belowid==-1) {
		$query="SELECT orderid FROM acts_email e, acts_email_items i WHERE i.emailid=$emailid AND i.emailid=e.emailid ORDER BY i.orderid LIMIT 1";
		$result=sqlQuery($query,$adcdb) or die(mysql_error());
		$row=mysql_fetch_assoc($result) or die ("An error occured");
		$neworder=$row['orderid']-1;
	}
	else {
		$query="SELECT orderid FROM acts_email e, acts_email_items i WHERE i.emailid=$emailid AND i.emailid=e.emailid AND i.id=$belowid";
		$result=sqlQuery($query,$adcdb) or die(mysql_error());
		$row=mysql_fetch_assoc($result) or die ("An error occured");
		$beloworder=$row['orderid'];
		$query="SELECT orderid FROM acts_email e, acts_email_items i WHERE i.emailid=$emailid AND i.emailid=e.emailid AND i.orderid>=$beloworder ORDER BY i.orderid LIMIT 2";
		$result=sqlQuery($query,$adcdb) or die(mysql_error());
		$row=mysql_fetch_assoc($result) or die ("An error occured");
		if ($row=mysql_fetch_assoc($result)) {
			$aboveorder=$row['orderid'];
			$neworder=($aboveorder+$beloworder)/2;
		}
		else {
			$neworder=$beloworder+1;
		}
	}
	$query="UPDATE acts_email_items SET orderid=$neworder WHERE id=$dropid"; 
	sqlQuery($query,$adcdb) or die(mysql_error());
}



// TIDY UP ORDERING

$query="SELECT * FROM acts_email_items WHERE emailid=$emailid ORDER BY orderid";
$i=0;
$result=sqlQuery($query,$adcdb) or die(mysql_error());
while($row=mysql_fetch_assoc($result)){
	$query="UPDATE acts_email_items SET orderid=$i WHERE id=".$row['id'];
	sqlQuery($query,$adcdb) or die(mysql_error());
	$i++;
}

if (isset($_GET['dispedit'])) {
	echo "<p>";
	makeLink(153,"Back to email Editor",array("CLEARALL"=>"CLEARALL","emailid"=>$emailid));
	echo "</p><form method=post action=\"".linkTo(153,array("CLEARALL"=>"CLEARALL","emailid"=>$_GET['emailid'],"editid"=>$_GET['dispedit']))."\">\n";
	echo "<input type=hidden name=\"submitid\" value=\"".allowSubmission()."\">";
	$artid=$_GET['dispedit'];
	$query_item="SELECT text,protect,acts_email_items.title FROM acts_email_items,acts_email WHERE  acts_email.emailid=acts_email_items.emailid AND acts_email_items.emailid=$emailid AND acts_email_items.id=$artid";
	$q=sqlQuery($query_item) or die (mysql_error());
	$row=mysql_fetch_assoc($q);
	$text=$row['text'];
	echo "<p>Title: <input type=\"text\" size=50 name=\"title\" value=\"".$row['title']."\" /><br />(for contents - leave blank to exclude from contents list)</p>";
	echo "<textarea name=\"text\" rows=8 cols=80 wrap=virtual>$text</textarea>";
	echo '<br/><input type="checkbox" name="protect"';
	if ($row['protect']>0) echo " checked";
	echo '> Protect (do not delete automatically after email is sent)';
	echo "<br /><input type=submit value=\"Edit\">";
	echo "</form>";
}
else { 
?><h4>Defaults for this Email</h4>
<form method="post" action="<?=linkTo(153,array("CLEARALL"=>"CLEARALL","emailid"=>$_GET['emailid']))?>">
<table class="editor">
<tr><th>Default Sender</th><td><?php fromField($row_email[from],$_SESSION[userid]); ?></td></tr>
<tr><th>Default Email List</th><td>
<?php
$lists=getlists();
if (count($lists)>0) { ?>
<select name="listid">
	<?php
	echo "<option value=\"-1\"";
	if ($row_email[listid] == -1 || $row_email[listid] =="") echo " selected";
	echo ">(None)</option>";
	foreach ($lists as $id=>$list) {
		echo "<option value=\"$id\"";
		if ($row_email[listid] == $id) echo " selected";
		echo ">$list</option>";
	} 
	?></select><?php
} else echo "not available";	
?></td></tr>
<tr><th>After Sending</th><td><input type="checkbox" name="deleteonsend"<?php if ($row_email[deleteonsend]>0) echo " checked"; ?>> Delete unprotected items</td></tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Update"></td></tr>
</table></form><?php
echo "<h4>Insert articles from camdram.net database</h4>";
// BUILD INSERT TABLE
// INFO
echo "<table class=\"editor\">";
$query_info="SELECT acts_shows.id,acts_shows.title,acts_shows.venue,acts_shows.venid,MAX(acts_performances.enddate),MIN(acts_performances.startdate) AS startdate FROM acts_shows LEFT JOIN acts_performances ON acts_shows.id=acts_performances.sid WHERE acts_performances.enddate>=NOW() AND acts_shows.authorizeid>0 GROUP BY acts_shows.id ORDER BY acts_performances.startdate,acts_performances.enddate,venue,society";
$q=sqlQuery($query_info,$adcdb) or die(mysql_error());
if (mysql_num_rows($q)>0) {
	echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
	echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
	echo "<th>Insert show information:</th><td><select name=\"infoid\">";
	while($row_info=mysql_fetch_assoc($q)) {
		$disp=maxChars($row_info['title'],30).", ".maxChars(showPerfs($row_info,false,false),30);
		if (venueName($row_info)!="") $disp=$disp.", ".maxChars(venueName($row_info),20);
		echo "<option value=\"".$row_info['id']."\">$disp</option>";
	}
	echo "</select></td><td><input type=submit name=submit value=\"insert\"> <input type=submit name=submit value=\"insert all\"></td></form>";
}

// TECHIES
$query_techies="SELECT DISTINCT acts_shows.id AS id, title, MIN(IF(acts_performances.id IS NULL, '2034-01-01', acts_performances.enddate)) AS enddate,acts_shows.venue,acts_performances.time,acts_shows.venid,dates FROM acts_techies,acts_shows LEFT JOIN acts_performances ON (acts_performances.sid=acts_shows.id) WHERE acts_shows.id=acts_techies.showid AND acts_techies.expiry>=NOW() AND (acts_performances.enddate>=NOW() OR acts_performances.enddate IS NULL) AND acts_shows.authorizeid>0 GROUP BY acts_shows.id ORDER BY enddate,acts_shows.venue,society";
$q=sqlQuery($query_techies) or die(mysql_error());
if (mysql_num_rows($q)>0) {
	echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
	echo "<input type=\"hidden\" name=\"submitid\" name=submit value=\"".allowSubmission()."\">";
	echo "<th>Insert production team advert:</th><td><select name=\"techid\">";
	while($row_techies=mysql_fetch_assoc($q)) {
		$disp=maxChars($row_techies['title'],30).", ".maxChars(showPerfs($row_techies),30);
		if (venueName($row_techies)!="") $disp=$disp.", ".maxChars(venueName($row_techies),20);
		echo "<option value=\"".$row_techies['id']."\">$disp</option>";
	}
	echo "</select></td><td><input type=submit name=submit value=\"insert\"> <input type=submit name=submit value=\"insert all\"></td></form></tr>";
}

// AUDITIONS
$query_auditions="SELECT DISTINCT acts_shows.id AS id, title, MIN(IF(acts_performances.id IS NULL, '2034-01-01', acts_performances.startdate)) AS startdate,MIN(IF(acts_performances.id IS NULL, '2034-01-01', acts_performances.enddate)) AS enddate,acts_shows.venue,acts_performances.time,acts_shows.venid FROM acts_auditions, acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_shows.id=acts_auditions.showid AND acts_auditions.date>=NOW() AND (acts_performances.enddate>=NOW() OR acts_performances.enddate IS NULL) AND acts_shows.authorizeid>0 GROUP BY acts_shows.id ORDER BY startdate,enddate,venue,society";
$q=sqlQuery($query_auditions,$adcdb) or die(mysql_error());
if (mysql_num_rows($q)>0) {
	echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
	echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
	echo "<th>Insert audition information:</th><td><select name=\"audid\">";
	while($row_auditions=mysql_fetch_assoc($q)) {
		$disp=maxChars($row_auditions['title'],30).", ".maxChars(showPerfs($row_auditions,false,true),30);
		if (venueName($row_auditions)!="") $disp=$disp.", ".maxChars(venueName($row_auditions),20);
		echo "<option value=\"".$row_auditions['id']."\">$disp</option>";
	}
	echo "</select></td><td><input type=submit name=submit value=\"insert\"> <input type=submit name=submit value=\"insert all\"></td></form>";
}

// EVENTS
$query_events="SELECT * FROM acts_events WHERE  date>=NOW() ORDER BY date,starttime,endtime";
$q=sqlQuery($query_events,$adcdb) or die(mysql_error());
if (mysql_num_rows($q)>0) {
	echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
	echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
	echo "<th>Insert event information:</th><td><select name=\"eventid\">";
	while($row_event=mysql_fetch_assoc($q)) {
		$disp=maxChars($row_event['text'],30).", ".maxChars(date("D jS M",strtotime($row_event['date'])),30);
		echo "<option value=\"".$row_event['id']."\">$disp</option>";
	}
	echo "</select></td><td><input type=submit name=submit value=\"insert\"> <input type=submit name=submit value=\"insert all\"></td></form>";
}

// APPLICATIONS
$query_applications="SELECT * FROM acts_applications WHERE deadlinedate>=CURDATE() ORDER BY deadlinedate,showid,socid";
$q=sqlQuery($query_applications,$adcdb) or die(mysql_error());
if (mysql_num_rows($q)>0) {
	echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
	echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
	echo "<th>Insert application information:</th><td><select name=\"appid\">";
	while($row_app=mysql_fetch_assoc($q)) {
		if ($row_app['socid']>0) {
			$row_soc=getsocrow($row_app['socid']);
			$name=$row_soc['name'];
		}
		if ($row_app['showid']>0) {
			$row_show=getshowrow($row_app['showid']);
			$name=maxchars($row_show['title'],40);
		}
		$disp=$name." - ".maxChars($row_app['text'],40);
		echo "<option value=\"".$row_app['id']."\">$disp</option>";
	}
	echo "</select></td><td><input type=submit name=submit value=\"insert\"> <input type=submit name=submit value=\"insert all\"></td></form>";
}
// BLANK
echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
echo "<th>Insert blank article</th><td><input type=hidden name=insertblank value=\"true\">";
echo "</select></td><td><input type=submit name=submit value=\"insert\"></td></form></tr>";

// TABLE OF CONTENTS
echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\">";
echo "<th>Insert table of contents</th><td><input type=hidden name=\"inserttoc\" value=\"true\">";
echo "</select></td><td><input type=submit name=submit value=\"insert\"></td></form></tr>";

// DELETE UNPROTECTED
echo "<tr><form method=post action=\"".thisPage(array("CLEARALL"=>"CLEARALL","emailid"=>$emailid))."\">\n";
echo "<input type=\"hidden\" name=\"submitid\" value=\"".allowSubmission()."\" \>";
echo "<th>Delete unprotected items</th><td><input type=hidden name=\"deleteunprotected\" value=\"true\" \>";
echo "</select></td><td><input type=submit name=submit value=\"Delete\" \></td></form></tr>";


echo "<tr><td colspan=3><div align=\"right\">";
makeLink(161,"Send this Email &gt; &gt; &gt;",array("CLEARALL"=>"CLEARALL","emailid"=>$emailid));
echo "</div></td></tr>";

echo "</table>";

echo "<br />\n";
// DISPLAY EMAIL
$query_items="SELECT id,text,creatorid,created,protect,acts_email_items.title FROM acts_email_items,acts_email WHERE acts_email.emailid=acts_email_items.emailid AND acts_email_items.emailid=$emailid ORDER BY orderid";
$q=sqlQuery($query_items,$adcdb) or die(sqlEr());

addJSLoad("Sortable.create('emaillist',{tag:'div', onUpdate: function() { new Ajax.Request('$base/postback.php?type=email_editor_reorder&emailid=$emailid&order=' + Sortable.sequence('emaillist').join('.'), {onSuccess: updatetoc}); } } );");
?>

<script type="text/javascript">
//<!--

function initupdatetoc() {
	 new Ajax.Request('<?=$base?>/postback.php?type=email_editor_toc&emailid=<?=$emailid?>', {onSuccess: updatetoc} );
}

//-->
</script>

<div onclick="ToggleEmailContents();" class="vbutton">Click to collapse/expand item contents</div>

<div id="emaillist"><?php
$jstoggle = ""; // this will eventually hold the JS code to toggle text visibility
$numtocs=0; // need to know how many TOCS there are in this document for generating JS later

if (mysql_num_rows($q) >0) {
	$submitid=allowSubmission();
	$buildtext="";

	$i=-1;
	$count=mysql_num_rows($q);
	$firstitem=true;
	
	$xartid=-1;
	while ($row_item=mysql_fetch_assoc($q)) {

		$i++;
		$xartid=$artid;
		$artid=$row_item['id'];


		if(isset($_GET['pick'])) {
		  if ($_GET['pick'] != $xartid) {
		    putDropPosition($artid,$droptitle);
		    //makelink(153,"Move to here",array("CLEARALL"=>"CLEARALL","submitid"=>$submitid,"drop"=>$_GET['pick'],"below"=>$artid,"emailid"=>$emailid));
		  } else {
		    putDropPosition($artid,$droptitle,true);
		  }
		}

		//Stumo: Myself and Alex are somewhat surprised at the code below.  We don't think this ever gets used(!) 

		if($row_item[creatorid]>0) {
		  echo "<p class=\"attention\">Public submission: ".getUserString($row_item[creatorid]);
		  echo " on ".date("d/m/Y",strtotime($row_item[created]))." at ".date("H:i",strtotime($row_item[created]))."</p>";
		}

		  echo "<div id='item_$row_item[id]'><h3 style=\"cursor: move;\"><span id=\"titleedit$i\">";
		  if($row_item[title]!="") echo $row_item[title]; else echo "[Click to title]";
		  echo "</span>";

		  echo "<div class=\"headerbuttons\">";
		  ajaxbutton("delete", "email_editor_deleteitem", array("emailid"=>$emailid, "delete"=>$artid), "function(transp) { new Effect.BlindUp('item_$row_item[id]', {duration: 0.1}); updatetoc(transp); }" );
		  echo "</div>";
		  echo "</h3>";

				
		if($row_item[protect]>0) echo "<strong>Protected</strong><br/><font color=\"#0000ff\">";
		if($displayFullText) {
		  if($row_item['text']=="~~~TABLE_OF_CONTENTS~~~") 
		    {
		      echo "<pre id=\"emailtoc$numtocs\">",wordwrap(htmlspecialchars(toc($emailid))),"</pre>";
		      $numtocs++;
		    }
		  else {
		    echo "<pre id=\"contentedit$i\">";
		    if($row_item['text']!="") echo wordwrap(htmlspecialchars($row_item['text'])); else echo "[Click to enter content]\n";
		    echo "</pre>";
		    $jstoggle.="new Effect.toggle('contentedit$i','Blind',{duration: 0.25});\n";	
		  }
		}
		if($row_item[protect]>0) echo "</font>";
		
		

		if($firstitem==false)
			$buildtext.="***************************************************\n";
		if($row_item[creatorid]!=0) {
		  $creator_info = getUserRow($row_item[creatorid]);
		  $buildtext.=strtoupper("Submitted by ".$creator_info[name]." (".$creator_info[email]."):\n\n");
		}
		if($row_item['text']=="~~~TABLE_OF_CONTENTS~~~") {
			$buildtext.=htmlspecialchars(toc($emailid))."\n\n";
		}
		else {
			$buildtext.=htmlspecialchars($row_item['text'])."\n\n";
		}
		$firstitem=false;
		echo "</div>";
		enableedit('titleedit'.$i,'email_editor_title',array('emailid'=>$emailid,'itemid'=>$artid),'title-edit-inplace','#dddddd',"initupdatetoc");
		enableedit('contentedit'.$i,'email_editor_content',array('emailid'=>$emailid,'itemid'=>$artid),'content-edit-inplace');
	}
	echo "<hr><a name=\"email\"></a>";
	echo "<p><b>Found the following email addresses (addresses in bold are guessed from CRSIDs)</b></p>";
	$pattern= "/([a-z0-9\-\_\.\+]+@[a-z0-9\.\-]+[a-z0-9])|([a-z][a-z]+[0-9]+)/i";
	preg_match_all($pattern,$buildtext,$matches);
	$first=1;
	foreach($matches as $key1=>$matcharr) {
	  foreach($matcharr as $key2=>$match)
	    $matches[$key1][$key2]=strtolower($match);
	}
	$matches[0]=array_unique($matches[0]);
	foreach ($matches[0] as $match) {
		if ($first==1) $first=0;
		else echo ", ";
		global $site_support_email;
		if (strrpos($match,"@")==false) {
			echo "<b>$match@cam.ac.uk</b>";
		}
		else if ($match!=$site_support_email) {
			echo "$match";
		}
	}
	//echo "<hr>";
	
	//echo "<h3>Email Preview</h3>\n";
	//echo "<pre>";
	//echo wordwrap($buildtext,76);
	//echo "</pre>";
}
?></div>

<script type="text/javascript">
//<!--
function ToggleEmailContents() { <?=$jstoggle?> }

function updatetoc(transp) {
<?php for($i=0;$i<$numtocs;$i++) { ?>
   $('emailtoc<?=$i?>').update(transp.responseText);
<?php } ?>
}

//-->
</script>
<?php
}
}
?>
