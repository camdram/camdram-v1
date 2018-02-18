<?php

require_once('config.php');
require_once("config.php");
require_once("library/adcdb.php");
require_once('library/lib.php');
require_once('library/showfuns.php');
require_once('library/editors.php');

function FetchAdcOnlineBookingUrls(){
  $domains = array('https://www.adctheatre.com', 'https://www.adcticketing.com', 'https://www.corpusplayroom.com');
  $whats_on = "/whats-on.aspx";

  libxml_use_internal_errors(true); // So we ignore errors from parsing the HTML

  foreach($domains as $domain){
    $page = file_get_contents($domain . $whats_on);
    $dom = new DOMDocument;
    $dom->loadHTML('<?xml encoding="UTF-8">'.$page); // Bug where it can't pick up UTF8 from the meta tag in the head

    $xpath = new DOMXpath($dom);

    $links = $xpath->query("//section[@id='whatsOnWrapper']//a");

    if(! is_null($links) ){
      $show_pages = array();
      foreach($links as $a){
	array_push($show_pages, $a->getAttribute('href'));
      }

      foreach ($show_pages as $show_link){
	$show_url = $domain . preg_replace_callback("#[^a-zA-Z/]#",create_function('$matches','return urlencode($matches[0]);'),$show_link);
	$show_url = $domain . implode('/', array_map('urlencode', explode('/', $show_link)));
	$page = file_get_contents($show_url);
	$dom = new DOMDocument;
	$dom->loadHTML($page);
	$xpath = new DOMXpath($dom);

	$links = $xpath->query("//section[@id='cast']//a");
	if(! is_null($links) && !is_null($links->item(0)) ){
	  $camdramurl = $links->item(0)->getAttribute('href');
	  $matches = array();
	  if(preg_match("/from_show=(\d+)/",$camdramurl,$matches)){
	    $show_id = $matches[1];
	    print "$show_id => $show_url\n";
	    $show_url = mysql_real_escape_string($show_url);
	    $sql = "UPDATE acts_shows set onlinebookingurl = '$show_url' where id = $show_id;\n";
	    global $adcdb;
	    $q = sqlQuery($sql, $adcdb) or die(sqlEr());
	  }else{
	    print "$show_url - no backlinks found\n";
	  }
	}else{
	  print "$show_url - could not find cast section\n";
	}
      }
    }
  }
}

?>