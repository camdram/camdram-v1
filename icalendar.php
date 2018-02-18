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
require_once('library/showfuns.php');

header("Content-type:text/calendar; charset=utf-8");
//header("Content-type:text/plain; charset=utf-8");
header("Content-Disposition: inline; filename=camdram.ics");
/** 
 * Output a string into one or more content lines.
 *
 * iCalendars content lines always in CRLF and are limited to 75 octets in 
 * length. Strings we can't control the length of should go through this 
 * function. See section 3.1 of RFC 5545 
 * @link http://tools.ietf.org/html/rfc5545#section-3.1
 * @param $string Output string.
 */
function output_content_line($string)
{
    /* This is not multibyte safe, but readers should handle it.
     * AJF58 TODO is improve this to be safe.
     */
    /* Replace UNIX line endings with CRLF */
    $string = str_replace("\r\n", "\n", $string);
    $string = str_replace("\n", '\n', $string);
    // Escape special characters as per 3.3.11 of RFC 5545
    $string = addcslashes($string, "\;,");
    $output = wordwrap($string, 74, " \r\n ", TRUE);
    // Make sure we end with a single CRLF and no other whitespace
    $output = rtrim($output);
    echo $output."\r\n";
}

/**
 * Add a VEVENT to the calendar.
 * @param $dtstamp Timestamp.
 * @param $uid A unique id.
 * @param $start The start datetime for the event.
 * @param $end The end datetime for the event.
 * @param $summary A summary of the event.
 * @param $description A more detailed summary of the event.
 * @param $location Typically the venue of the event.
 * @param $exdate Either a performance date excluded from the range or NULL.
 * @param $duration Length of the performance, in seconds. 2 hours by default.
 */
function add_vevent($dtstamp, $uid, $start, $end, $summary, $description,
    $location, $duration = 7200, $exdate = NULL)
{
    // Date-Times are _almost_ like those in ISO 8601, but not quite.
    $datetime_format = "Ymd\THis\Z";
    echo("BEGIN:VEVENT\r\n");
    echo "DTSTAMP:", gmdate($datetime_format, $dtstamp), "\r\n";
    output_content_line("UID:".$uid);
    echo "DTSTART:", gmdate($datetime_format, $start), "\r\n";
    // See 3.3.6 of RFC 5545.
    $duration_format = "\P\TG\Hi\Ms\S";
    echo "DURATION:", date($duration_format, $duration), "\r\n";
    echo "RRULE:FREQ=DAILY;UNTIL=", gmdate($datetime_format, $end), "\r\n"; 
    // Add an exclusion rule, if required.
    if (!is_null($exdate))
    {
        echo "EXDATE:", gmdate($datetime_format, $exdate), "\r\n";  
    }
    output_content_line("SUMMARY:".$summary);
    output_content_line("DESCRIPTION:".$description);
    output_content_line("LOCATION:".$location);
    echo("END:VEVENT\r\n");
}
// The calendar header.
echo("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n");
echo("PRODID:-//Camdram//NONSGML Show Diary//EN\r\n");

if (isset($_GET['start_year']))
{
    // All shows in Camdram are post-2000. Make sure e.g. 08 is interpreted as 2008.
    $start_year = ($_GET['start_year'] < 100) ? 
        (2000 + $_GET['start_year']) : (0 + $_GET['start_year']);
}
else
{
    // Generate list of shows from the current academic year.
    $start_year = date('n')<10 ? (date('Y')-1) : date('Y');
}
// Academic years always start on 1st October.
$start_date = $start_year . "-10-01";

if (isset($_GET['society']))
{
    /* Restrict to a particular 'society' in the acts_societies sense of the word
     * i.e. both performing societies and venues.
     */
    $safe_soc_name = str_replace('-', ' ', mysql_real_escape_string($_GET['society'])); 
    $query_shows = "SELECT acts_performances.*, acts_shows.title, acts_shows.description, acts_shows.timestamp
        FROM (acts_performances LEFT JOIN acts_societies venue ON acts_performances.venid=venue.id) 
        LEFT JOIN (acts_shows INNER JOIN acts_societies soc ON acts_shows.socid=soc.id)
        ON acts_performances.sid=acts_shows.id 
        WHERE (venue.name LIKE '$safe_soc_name' OR venue.shortname LIKE '$safe_soc_name'
        OR soc.name LIKE '$safe_soc_name' OR soc.shortname LIKE '$safe_soc_name')
        AND acts_performances.enddate>='$start_date' AND acts_performances.enddate<DATE_ADD('$start_date', INTERVAL 1 YEAR) 
        AND acts_shows.authorizeid>0 ORDER BY acts_performances.startdate, acts_shows.id, acts_performances.enddate";
}
else
{
    $query_shows = "SELECT acts_performances.*, acts_shows.title, acts_shows.description, acts_shows.timestamp 
        FROM acts_performances LEFT JOIN acts_shows ON acts_performances.sid=acts_shows.id 
        WHERE acts_performances.enddate>='$start_date' AND acts_performances.enddate<DATE_ADD('$start_date', INTERVAL 1 YEAR) 
        AND acts_shows.authorizeid>0 ORDER BY acts_performances.startdate, acts_shows.id, acts_performances.enddate";
}

$shows=sqlquery($query_shows, $adcdb) or die(mysql_error());

// Add a VEVENT for each 'performance' from the last year.
while ($show=mysql_fetch_assoc($shows))
{
    $showid=$show['id'];

    // Crude assumption that all shows are in Western Europe.
    date_default_timezone_set('Europe/London');
    $dtstamp = strtotime($show['timestamp']);
    $start = strtotime($show['time'], strtotime($show['startdate']));
    $end = strtotime($show['time'], strtotime($show['enddate']));
    $exdate = strtotime($show['time'], strtotime($show['excludedate']));
    
    $summary = $show['title'];
    $description = $show['description'];
    $location = venueName($show);

    // A performance's duration is unknown. Make an educated guess.
    $start_details = getdate($start);
    if ($start_details["hours"] < 22)
    {
        $duration = 7200;
    }
    else
    {
        $duration = 3600;
    }
    // Check the exlusion date is valid & meaningful.
    if (($start < $exdate) &&  ($exdate < $end))
    {
        add_vevent($dtstamp, $show['id']."@camdram.net", $start, $end, $summary,
            $description, $location, $duration, $exdate);
    }
    else
    {
        add_vevent($dtstamp, $show['id']."@camdram.net", $start, $end, $summary,
            $description, $location, $duration);
    }
}

// Add the calendar's footer
echo("END:VCALENDAR\r\n");
?>
