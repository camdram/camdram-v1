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

require_once("config.php");
require_once("library/adcdb.php");
require_once('library/lib.php');
require_once('library/emailGen.php');
require_once('library/showfuns.php');

/** Converts special characters in XML 1.0 into 'predefined entities'.
 * Analogous to htmlentities(). N.b. in PHP v5.4 this function could be replaced
 * by a call to htmlentities() using the ENT_XML1 flag.
 * @param $string Input string, encoded as UTF-8.
 * @return Returns the encoded string.
 */
function xmlentities($string)
{
    // Map the special characters to their predefined entities. 
    $trans = array("\"" => "&quot;", "&" => "&amp;", "'" => "&apos;", "<" => "&lt;", ">" => "&gt;");
    return strtr($string, $trans);
}

/** Recursively walk through the DOM and remove any attributes that are not
 * whitelisted.
 * @param $node A DOMNode object to be processed.
 */
function walk_dom($node)
{
    // Whitelist taken from http://feed1.w3.org/docs/warning/SecurityRiskAttr.html
    $whitelist = array("abbr","accept","accept-charset","accesskey","action",
        "align","alt","axis","border","cellpadding","cellspacing","char",
        "charoff","charset","checked","cite","class","clear","cols","colspan",
        "color","compact","coords","datetime","dir","disabled","enctype","for",
        "frame","headers","height","href","hreflang","hspace","id","ismap",
        "label","lang","longdesc","maxlength","media","method","multiple",
        "name","nohref","noshade","nowrap","prompt","readonly","rel","rev",
        "rows","rowspan","rules","scope","selected","shape","size","span",
        "src","start","summary","tabindex","target","title","type","usemap",
        "valign","value","vspace","width");

    foreach ($node->childNodes as $child)
    {
        if ($child->hasAttributes())
        {
            $blacklist = array();
            foreach ($child->attributes as $attr)
            {
                /* We can't remove attributes straight away, instead build up
                 * a blacklist for removing later.
                 */
                if (!in_array($attr->name, $whitelist))
                {
                    array_push($blacklist, $attr->name);
                }
            }
            // Remove all the blacklisted attributes.
            foreach ($blacklist as $black)
            {
                $child->removeAttribute($black);
            }
        }
        if ($child->hasChildNodes())
        {
            // Recurse
            walk_dom($child);
        }
    }
}

/** Ensure that only whitelisted html attributes appear in the element
 *
 */
function whitelist_attributes($element)
{
    /* Create DOMDocument with correct encoding. N.b. calling html_entity_decode()
     * results in the html being incorrectly parsed and producing incorrect output.
     * Hence we use mb-convert_encoding.
     */
    $html = mb_convert_encoding($element, "HTML-ENTITIES", "UTF-8");
    $dom = new DOMDocument("1.0", "utf-8");
    
    // Make sure errors in the input don't affect us.
    @$dom->loadHTML($html);
    // Walk through the document, removing non-whitelisted attributes.
    walk_dom($dom);
    /* saveHTML() seems to put all kinds of crap around our text. Remove
     * it here. Later versions of PHP (>=5.3.6) would allow us to choose a specific node.
     * For now we kludge this with a RegEx. Ew.
     */
    $white_html = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $dom->saveHTML())); 
    
    // Returned text should be converted back.
    return html_entity_decode($white_html, ENT_COMPAT, "UTF-8");
}

header("Content-type:application/rss+xml; charset=utf-8");
echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n")
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
<?php
echo "\t\t<atom:link href=\"http://",$_SERVER['SERVER_NAME'],$_SERVER['REQUEST_URI'], "\" rel=\"self\" type=\"application/rss+xml\" />\n";
ob_start();
if ($_GET['type']=="techies") {
	?>
		<title>camdram.net - Production Team Vacancies</title>
		<description>Production Team Vacancies advertised for shows in Cambridge</description>
		<link>http://www.camdram.net/techies</link>
	<?
	$ad_query="SELECT positions, acts_techies.id AS adid, showid, techextra,acts_techies.contact, acts_techies.deadline, acts_techies.expiry,acts_techies.deadlinetime,MAX(IF(acts_performances.id IS NULL, 2034-01-01, acts_performances.enddate)) AS enddate,MIN(IF(acts_performances.id IS NULL, 2034-01-01,acts_performances.startdate)) AS startdate, lastupdated FROM acts_techies,acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_techies.showid=acts_shows.id AND acts_techies.expiry>=NOW() AND acts_shows.authorizeid>0 AND acts_shows.entered=1 GROUP BY acts_shows.id ORDER BY startdate,enddate,title,society;";
	$ads=sqlquery($ad_query,$adcdb) or die(mysql_error());
	while ($ad=mysql_fetch_assoc($ads)) {
		$showfields=getShowRow($ad['showid']);
		$adid=$ad['adid'];
		$showid=$ad['showid'];
		echo"<item><title>",xmlentities($showfields['title'])," - last updated ",gmdate('D, d M Y H:i \G\M\T',strtotime($ad['lastupdated'])),"</title><description>";
		echo xmlentities(nl2br(whitelist_attributes(generateTechItem($showid))));
		echo "</description><link>http://www.camdram.net/techies?fulldetails=on&amp;lastupdate=",gmdate("dmYHi",strtotime($ad['lastupdated'])),"#$adid</link><pubDate>";
		echo gmdate('D, d M Y H:i:s \G\M\T',strtotime($ad['lastupdated']));
		echo "</pubDate><guid isPermaLink=\"false\">",$adid," : ",$ad['lastupdated'],"</guid></item>\n";
	}
}
else if ($_GET['type']=="shows") {
	?>
		<title>camdram.net - Shows</title>
		<description>Shows produced by students in Cambridge</description>
		<link>http://www.camdram.net/diary</link>
	<?
	$endnextweek=date('Y-m-d',strtotime("+10 days"));
	$query_closeshows = "SELECT acts_shows.*,acts_shows_refs.ref,MAX(acts_performances.enddate),MIN(acts_performances.startdate) FROM acts_shows_refs,acts_shows LEFT JOIN acts_performances ON acts_performances.sid=acts_shows.id WHERE acts_performances.enddate>=NOW() AND acts_performances.startdate<='$endnextweek' AND acts_shows.authorizeid>0 AND acts_shows.entered=1 AND acts_shows_refs.refid=primaryref GROUP BY acts_shows.id ORDER BY acts_performances.enddate,acts_performances.startdate";
	$shows=sqlquery($query_closeshows,$adcdb) or die(mysql_error());
	while ($show=mysql_fetch_assoc($shows)) {
		$showid=$show['id'];
		$ref=$show['ref'];
		echo"<item><title>",xmlentities($show['title']),"</title><description>";
		echo xmlentities(nl2br(whitelist_attributes(generateInfoItem($showid))));
		echo "</description><link>http://www.camdram.net/shows/$ref</link>";
		echo "<guid>http://www.camdram.net/shows/$ref</guid></item>\n";
		
	}
}
else if ($_GET['type']=="events") {
	?>
		<title>camdram.net - Events</title>
		<description>Events put on by Cambridge drama societies</description>
		<link>http://www.camdram.net/diary</link>
	<?
	$endnextweek=date('Y-m-d',strtotime("+10 days"));
	$query_events = "SELECT * FROM acts_events WHERE (acts_events.date>=NOW() AND acts_events.date<='$endnextweek') ORDER BY acts_events.date";
	$events=sqlquery($query_events,$adcdb) or die(mysql_error());
	while ($event=mysql_fetch_assoc($events)) {
		$eventid=$event['id'];
		echo"<item><title>",xmlentities($event['text']),"</title><description>";
		echo xmlentities(nl2br(whitelist_attributes(generateEventItem($eventid))));
		echo "</description><link>http://www.camdram.net/events?eventid=$eventid</link>";
		echo "<guid>http://www.camdram.net/?id=112&amp;eventid=$eventid</guid></item>\n";
		
	}

}
?>

	</channel>
</rss>
<?
$output=ob_get_contents();
ob_end_clean();
echo $output;
?>
