
<?php

require_once("facebook/fcam.php");


if(logindetails(false,false,false))
	add_page_link ('admin_menu.php',"Edit content");
else
	add_page_link('admin_menu.php',"Registered users can edit our content");


function weekAhead()
{
  global $currentbase;
/*
  $row_term=whatTerm(time());
 
  $week0=strtotime($row_term['startdate']);

  $time = time();

  if ($time > $week0 + 7 * ($row_term['firstweek']) * 24 * 60 * 60) {
    $week0=strtotime($row_term['startdate']);
    $dayofweek=date('w',mktime(0,0,0,date("m",$time),date("d",$time),date("Y",$time)));
    if($dayofweek==0) $dayofweek=7;// display sundays correctly!
    $startofweek = strtotime("-". $dayofweek + 1 ." days",mktime(0,0,0,date("m",$time),date("d",$time),date("Y",$time)));
    $endofweek = mktime (0,0,0,date("m",$startofweek) ,date("d",$startofweek)+6,date("Y",$startofweek));
	
    $events = getAllEvents ($startofweek, $endofweek, array(), true);
    $times = getShowTimesArray ($events, $time, $endofweek);
    $n = 0;
    foreach ($times as $time)
    {
        $n++;
        if (rand ($n, sizeof ($times)) == $n)
        {
            $found_show = 1;
            break;
        }
    }
    $n = 0;
    if ($found_show)
    {
        if ($time['show']['description'] !="") {
            $description=explode("\n\n",str_replace("\r","",trim($time['show']['description'])));
            foreach ($description as $line) {
                if ($n == 1)
                    $build=$build.$line."<br /><br />";
                else
                    $build=$line."<br /><br />";
                if (strlen($line) > 150) break;
                $n = 1;
            }
        }

        print "<div class=\"boxed\" style=\"float: right; width: 50%; clear: left;\"><h3 class=\"noborder\">";
    	if ($time['show']['photourl'] != "")
	        print "<img src=\"$currentbase/images/shows/" . $time['show']['photourl'] . "\" align=\"left\">";
        print "<div class=\"byline\">This week...</div>";
        makeLink(104,$time['show']['title'],array(), true, 0, false, $time['show']['ref'], array("showid"=>$time['show']['id']));
        if ($time['show']['author'] != "")
    	    print "<div class=\"byline\"> by " . $time['show']['author'] . "</div>";
       	print "<div class=\"byline\"><b>" . showPerfs (getShowRow ($time['show']['id']),false,false,$startofweek,$endofweek) . "</b></div></h3>";
       	if ($build != "")
            print "<div class=\"byline\">$build</div>";
       	print "<div class=\"byline\" style=\"text-align:right;\">View more shows in the " . makeLinkText('diary') . "...</div>";
        print "</div>";
    }
  }
*/
?>


<p><form name="searchform" id="searchform" method="get" action="<?= thisPage(array("page"=>1)) ?>" onsubmit="gothere(); return false;">
<font size="+1"> Look for:<br/>
<input name="searchtext" type="text" id="searchtext" width="50" style="font-size: 20px;" onkeyup="search(event)"></font>
<div id="autocomplete_choices"></div>
<script type="text/javascript">
//<!--

$('searchtext').select();
$('searchtext').focus();

// new Ajax.Autocompleter("searchtext", "autocomplete_choices", "<?=$currentbase?>/postback.php?type=generic_search&search=frontpage", {afterUpdateElement: getSelectionId});

var firstUpdate = true;
var ptOrig = ['','',''];
var ptVisible = [true,true,true];

var updateIds = ['show','people','pages'];

function search(e)
{
	if(firstUpdate) {
		firstUpdate=false;
		for(i=0; i<updateIds.length; i++) {
			ptOrig[i] = $(updateIds[i]+'text').innerHTML;
		}
	}
	if($('searchtext').value=="") {
		for(i=0; i<updateIds.length; i++) {
			$(updateIds[i]+'text').innerHTML = (ptOrig[i]);
			if(!ptVisible[i])
				new Effect.Fade(updateIds[i], { duration: 0.5, to: 1} );
		}

	} else
		new Ajax.Request("<?=$currentbase?>/postback.php?type=generic_search&search=frontpage&searchtext="+$('searchtext').value, {onSuccess: updateText});
}

function gothere()
{
	for(i=0; i<updateIds.length; i++) {
		 var elements = $(updateIds[i]+'text').descendants();
		 for(j=0; j<elements.length; j++) {
		      if(elements[j].tagName=='A') {
		      	if(elements[j].onclick==null) {
			   window.location = elements[j].href;
                        } else {		        
 			   elements[j].onclick();
 			}
			return;
		      }
		 }
	}
}


function updateText(transp) 
{
	var respArray = transp.responseText.split("<!--SPLIT-->");
	for(i=0; i<updateIds.length; i++) {
		 if(respArray[i]) {
		 	
			$(updateIds[i]+'text').innerHTML = respArray[i];

			if(!ptVisible[i])
				new Effect.Fade(updateIds[i], { duration: 0.5, to: 1} );
			
			
			ptVisible[i]=true;
			
		}
		else {
		     $(updateIds[i]+'text').innerHTML = ("<span class=\"informal\">(No results found)</span>");
		     if(ptVisible[i])
			new Effect.Fade(updateIds[i],{ duration: 0.5, to: 0.5} );
		     ptVisible[i]=false;
		}
	}

}


function getSelectionId(text, li) {
	window.location.href = "<?=$currentbase?>/searchredirect.php?id=" + li.id;
}
//-->
</script>


<table width="100%" style="clear: right;">
<tr><td width="33%" valign="top">
<div class="frontpageboxed" id="people">
<p class="frontpageheading">People</p>
<div id="peopletext"><?=facebook(); ?></div>
<p align="right"><small>get involved yourself: <?php makeLink('production','techie') ?> | <?php makeLink('actors','actor') ?> | <?php makeLink('directors producers','producer/director') ?> </small></p>
</div>
</td>
<td width="33%" valign="top">
<div class="frontpageboxed" id="show">
<p class="frontpageheading"><?php makeLink('diary', 'Shows'); ?></p>
<div id="showtext">Our comprehensive <?php makeLink('diary'); ?> tells you what shows are on this week. Alternatively, you can search our <?php makeLink('archives'); ?> of shows gone-by, many of which include cast and crew lists.</div>
<p align="right"><small>is your show missing? <?php makeLink('administration','add it here...') ?></small></p>
</div>
</td>
<td width="33%" valign="top">
<div class="frontpageboxed" id="pages">
<p class="frontpageheading"><?php makeLink(119, 'Pages'); ?></p>
<div id="pagestext">Want to know how to get involved with the backstage side, or wondering where to look for that elusive prop? Our <?php makeLink(119); ?> contains answers to a wealth of questions about Cambridge drama. And if you know better, you can edit it and help others.</div>
</div>
</td>
</tr></table>

<!--<p>Camdram.net centralises information about theatre in Cambridge into one common resource. We have information about all aspects of theatre in Cambridge, including information about venues, production team roles, acting, and the technical side of theatre.</p>
<p>Camdram.net is the website of the Association of Cambridge Theatre Societies (ACTS). Formed in April 2004 when the presidents of 22 different drama groups met to discuss a range of schemes to be run under the new banner, the group has now expanded to represent more than 30 societies.</p>
-->

<?php
}

function forumSynopsis()
{
 
}
function validatorIcons()

{ 

return; // TEMPORARY FIX since we don't pass - AP 19/10/07

?>
  <p align="right">
     <a href="http://validator.w3.org/check?uri=referer"><img border="0"
          src="http://www.w3.org/Icons/valid-html401"
          alt="Valid HTML 4.01!" height="31" width="88"></a>
 <a href="http://jigsaw.w3.org/css-validator/">
  <img style="border:0;width:88px;height:31px"
       src="http://jigsaw.w3.org/css-validator/images/vcss" 
       alt="Valid CSS!">
</a> 
</p>


     <?php }

function quickLogin()
{

  logindetails(true,false,false); 

}?>
