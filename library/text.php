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

// INPUT PROCESSING
function maxChars($str,$n)
{
  $str = str_replace("\n","; ",$str);
  if(strlen($str)>$n+3)
    {
      $str=substr($str,0,$n);
      $str.="...";
    }
  return $str;
}

function removeEvilTags($source)
{
   /*  This regexp starts matching at any < and continuing to search until it finds a > or a <.
	We then look to see if the tag is one of those allowed above. If so, the tag is allowed through (but no attributes).
	
	Note - removing all attributes is a change from previous behaviour, which had a blacklist on attributes - but potentially allowed some through that
	could be abused for XSS. I'd prefer a whitelist of attributes, if anyone has any cases where attributes are appropriate. 
  */
   $source = preg_replace_callback("#<\s*(/?)\s*([^<>\s]+)(\s*/)?(\s+[^<>]*)?(>?)#",  create_function('$matches',
      '
      $allowedTags="<b><i><u><strong><em><p><ul><li><ol><br><green><red><pre><hr>";
      if(strpos($allowedTags,strToLower("<" . $matches[2] . ">")) === false  || ( !$matches[5] )){
	return htmlspecialchars($matches[0], ENT_COMPAT | ENT_HTML401, "ISO-8859-1", false);
      }else{
	return "<" . $matches[1] . strToLower($matches[2]) . $matches[3] . ">";
      }
      '), $source);
  /*
  We now have a string that only includes the allowed tags. However, it doesn't necessarily have matching tags. The following will ensure all
  tags that get opened are closed again, in the right order. 
  */
  $tags_open = array();
  $offset = 0;
  $matches = array();
  while(preg_match('#<(/?)(\w+)(\s*/)?>#', $source, $matches, PREG_OFFSET_CAPTURE,  $offset)  ){
    if($matches[1][0]){
      // Closing tag
      if(end($tags_open) == $matches[2][0]){
	// Valid closing tag.
	$offset = strlen($matches[0][0]) + $matches [0][1];
	array_pop($tags_open);
      }else{
	// Remove this tag from the output
	$source = substr($source, 0, $matches[0][1]) . substr($source, $matches[0][1] + strlen($matches[0][0]));
      }
    }else if(isset($matches[3])){
      // Self closing tag
      $offset = strlen($matches[0][0]) + $matches[0][1];
    }else{
      //Opening tag
      array_push($tags_open, $matches[2][0]);
      $offset = strlen($matches[0][0]) + $matches[0][1];
    }
  }
  //Close any remaining tags
  while($tag = array_pop($tags_open)){
    $source .= "</" . $tag . ">";
  }
  return $source;   
}

function lineToKb($line)
{
  $query = "SELECT * FROM acts_keywords WHERE LOCATE(LOWER(kw),LOWER('$line')) AND (LENGTH(kw)>3 OR kw='$line') ORDER BY LENGTH(kw) DESC";
  $r = sqlQuery($query);
  if($r>0)
    {
      if(mysql_num_rows($r)>0)
	{
	  $row = mysql_fetch_assoc($r);
	  mysql_free_result($r);
	  return startLink($row['pageid'],array(),true);
	}
      mysql_free_result($r);
    }
  return 0;
}

function preprocess($line,$tagfilter=true,$kblinks=false,$allowcall=false)
{
	global $mode;
	$build = "";	
	  
	if($tagfilter) $line=removeEvilTags($line);

	// search for metadirectives
       
	$n=strpos($line,"[KBLINKS:OFF]");
	if($n!==false) {
	  if($n>0) $build.=preprocess(substr($line,0,$n),false,$kblinks,$allowcall);
	  $build.=preprocess(substr($line,$n+13,strlen($line)),false,false,$allowcall);
	  return $build;
	}
	
	$n=strpos($line,"[KBLINKS:ON]");
	if($n!==false) {
	  if($n>0) $build.=preprocess(substr($line,0,$n),false,$kblinks,$allowcall);
	  $build.=preprocess(substr($line,$n+12,strlen($line)),false,true,$allowcall);
	  return $build;
	}
	
	// inclues detected first
	$n=strpos($line,"[INC:");
	if($n!=false)
	{
		$build.=preprocess(substr($line,0,$n),false,$kblinks,$allowcall);
		
		$n+=5;
		$m=strpos(substr($line,$n,strlen($line)-$n),"]");
		$file=substr($line,$n,$m);
		loadPage($file);
		
		$build.=preprocess(substr($line,$n+$m+1,strlen($line)),false,$kblinks,$allowcall);
		return $build;
		  }
	$n=strpos($line,"[CALL:");
	  
	if($n>-1 && $allowcall==true)
	{
		$build.=preprocess(substr($line,0,$n),false,$kblinks,$allowcall);

		
		$n+=6;
		$m=strpos(substr($line,$n,strlen($line)-$n),"]");
		$call=substr($line,$n,$m);
		  call_user_func($call);
		
		$build.=preprocess(substr($line,$n+$m+1,strlen($line)),false,$kblinks,$allowcall);
		return $build;
	}

	
	
	// warning: the order in which the regular expressions are
	// applied is important, e.g. matching [E:.*@.*] before [E:.*] which translates to a cam.ac.uk address
	$search = array(
					"/\r/"
					,"/^\\+\\+(.*?)\\+\\+\$/m"
					,"/^\\+(.*?)\\+\$/m"
					,"/<\\/h(.)>[\n]+/"
					,"/[\n]+<h(.)>/"
					,"/\\[L:(http:\/\/|)([^;\\]]*)\\]/" 
								// standard link, no text provided
					,"/\\[L:(http:\/\/|)([^;\\]]*);([^\\]]*)\\]/"					// standard link, text provided
					,"/\\[E:([^@;\\]]*)\\]/"										// email, without @ (->cam.ac.uk)
					,"/\\[E:([^@;\\]]*)@(([^;\\]]*)|)\\]/"							// email, with @
					,"/\\[E:([^@;\\]]*);([^\\]]*)\\]/"							// email, without @ but with text
					,"/\\[E:([^@;\\]]*)@(([^;\\]]*)|);([^\\]]*)\\]/"				// email, with @ and text provided
					,"/<red>/"
					,"/<green>/"
					,"/<\\/red>/"
					,"/<\\/green>/"

					);				
	
	$replace = array(
						"",
						"<h5>\\1</h5>",
						"<h4>\\1</h4>",
						"</h\\1>",
						"<h\\1>",
						"[<a href=\"http://\\2\" target=\"_blank\">\\2</a>]",
						"<a href=\"http://\\2\" target=\"_blank\">\\3</a>",
						"[<a href=\"mailto:\\1@cam.ac.uk\">\\1</a>]",
						"[<a href=\"mailto:\\1@\\2\">\\1@\\2</a>]",
						"<a href=\"mailto:\\1@cam.ac.uk\">\\2</a>",
						"<a href=\"mailto:\\1@\\2\">\\4</a>",
						"<span class=\"kbred\">",
						"<span class=\"kbgreen\">",
						"</span>",
						"</span>"
						);
	
	$line=preg_replace($search,$replace,$line);
	
	$line=nl2br_except_in_some_html($line);
	if($kblinks)
	  {
	    // INSERT KEYWORD LINKS

	    $search=array();
	    $replace=array();

	    global $currentid;
	    $query = "SELECT * FROM acts_keywords ORDER BY LENGTH(`kw`) DESC";
	    $r = sqlQuery($query);
	    $n=0;
	    if($r>0)
	      {
		while($row = mysql_fetch_assoc($r))
		  {
		    $search[$n]="/([^[:alnum:]<>\\/])(".$row['kw'].")([^[:alnum:]<>\\/])/";
		    $initreplace[$n]="\\1~REPLACE$n~\\3";
		    $initsearch[$n]="/~REPLACE$n~/";
		    if($row['pageid']!=$currentid) 
		      $replace[$n]="<span class=\"keyword\">".makeLinkText($row['pageid'],$row['kw'])."</span>";
		    else
		      $replace[$n]="<span class=\"keyword\">$row[kw]</span>";
		    $n++;
		  }
	      }

	    $line = preg_replace($search,$initreplace,$line);
	    $line = preg_replace($initsearch,$replace,$line);
	
	  }
	// callback replacements
	$line = preg_replace_callback("/\\[CAMDRAMNET:([^;:\\]]*)((;|)([^:\\]]*))((:|)([^:\\]]*))\\]/",create_function('$matches','return makeLinkText($matches[1],$matches[4],array("OVERRIDESTRING"=>$matches[7]),true);'),$line);
	$line = preg_replace_callback("/\\[SHOW:([0-9]*)((;|)([^\\]]*))\\]/",create_function('$matches','$row=getShowRow($matches[1]); return makeLinkText(104,($matches[4]=="")?$row["title"]:$matches[4],array("showid"=>$matches[1]),true);'),$line);
					
	
	return $build.$line;
}

function present($socname) {
	$socname =strtolower($socname);
	$ret="presents";
	if(strstr($socname,"and")) $ret="present";
	if(strstr($socname,"&")) $ret="present";
	if(strstr($socname,"in association with")) $ret="present";
	if(strstr($socname,"/")) $ret="present";
	return $ret;
}

function nl2br_except_in_some_html($string){
  $offset = 0;
  $matches = array();
  $output = "";
  // Regular expression below: before the | matches anything within a <pre> block. After the | matches any html tag that's
  // followed by whitespace and then another html tag. The (?=<) is a zero-width positive lookahead -
  // i.e. it demands that the next character is a < without including it in the pattern - this means that it can form the start of a HTML
  // tag the next time round the loop... 
  while(preg_match("#(?:<pre>.*?</pre>|</?\w+>\s+(?=<))#s", $string, $matches, PREG_OFFSET_CAPTURE, $offset)){
    $output .= nl2br(substr($string, $offset, $matches[0][1] - $offset));
    $output .= $matches[0][0];
    $offset = $matches[0][1] + strlen($matches[0][0]);
  }
  $output .= nl2br(substr($string, $offset));
  return $output;
}

function lcamp($string) {
	$string=str_replace("&AMP;","&amp;",$string);
	$string=str_replace("&LT;","&lt;",$string);
	$string=str_replace("&GT;","&gt;",$string);
	return $string;
}

function unhtmlentities($string) {
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	return strtr($string, $trans_tbl);
}

function linecount($text,$length=76) {
	return substr_count(wordwrap($text,$length),"\n")+1;
}

function linelimit($text,$maxlines,$length=76) {
	$newtext=wordwrap($text,$length);
	$lines=explode("\n",$newtext);
	if (count($lines)>$maxlines) {
		for ($i=0;$i<$maxlines;$i++) {
			$newlines[$i]=$lines[$i];
		}
		$text=implode("\n",$newlines);
	}
	return $text;
}
?>
