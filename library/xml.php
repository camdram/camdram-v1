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

/**

Takes an array ($data) and outputs it as an XML string, with a root node of $root_node. 

Designed to mirror json_encode

Based on http://stackoverflow.com/a/5965940

 */


function xml_encode($data,$root_node = "root"){
	$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><$root_node></$root_node>");
	_xml_encode_subnode($data, $xml);
	return $xml->asXML();
}

function _xml_encode_subnode($data, &$xml){
	foreach ($data as $key => $value){
		if(is_array($value)){
			if(!is_numeric($key)){
				$subnode = $xml->addChild("$key");
				_xml_encode_subnode($value, $subnode);
			}else{
				$subnode = $xml->addChild("item");
				$subnode->addAttribute('index',$key);
				_xml_encode_subnode($value, $subnode);
			}
		}else{
			if(is_numeric($key)){
				// if you're confused that we have to use htmlspecialchars below... so am I! Unfortunately it won't escape &s for you, although it will escape < and > - ... see http://stackoverflow.com/q/552957
				$node = $xml->addChild('item', htmlspecialchars("$value")); 
				$node->addAtrribute('index',$key);
			}else{
				$xml->addChild("$key", htmlspecialchars("$value"));
			}
		}
	}
}


?>