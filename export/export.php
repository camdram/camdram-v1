<?php
require_once("lib/xmlrpc.inc");
require_once("lib/xmlrpcs.inc");
require_once("lib/docxmlrpcs.inc");
require_once("user/objects.inc");
require_once("functions.inc");

ini_set('display_errors', 0);

function plusone($num) {
	return $num+1;
}

$s = new documenting_xmlrpc_server(
	array(
		"camdram.occasions.get"=>array(
			"function"=>"xmlgetoccasions",
			"signature"=> array(
				array($xmlrpcStruct,$xmlrpcStruct)
				),
			"sigdoc"=> array(
				array("Array of occasions - type occasion_array","Query - type occasion_query")
				),
			"docstring"=>"Get occasions specified by query"
			),
		"camdram.occasions.show.getTechAdvert"=>array(
			"function"=>"xmlgettechadvert",
			"signature"=> array(
				array($xmlrpcStruct,$xmlrpcString)
				),
			"sigdoc"=> array(
				array("Tech advert - type techadvert","UID of show")
				),
			"docstring"=>"Get tech advert specified by show ID"
			),
		"camdram.occasions.show.getAudition"=>array(
			"function"=>"xmlgetaudition",
			"signature"=> array(
				array($xmlrpcStruct,$xmlrpcString)
				),
			"sigdoc"=> array(
				array("Audition details - type audition","UID of show")
				),
			"docstring"=>"Get audition specified by show ID"
			),
		"camdram.occasions.show.getCoreAdvert"=>array(
			"function"=>"xmlgetcoreadvert",
			"signature"=> array(
				array($xmlrpcStruct,$xmlrpcString)
				),
			"sigdoc"=> array(
				array("Core advert - type coreadvert","UID of show")
				),
			"docstring"=>"Get core advert specified by show ID"
			),
		"camdram.societies.get"=>array(
			"function"=>"xmlgetsocieties",
			"signature"=> array(
				array($xmlrpcStruct,$xmlrpcStruct)
				),
			"sigdoc"=> array(
				array("Array of socieities - type society_array","Query - type society_query")
				),
			"docstring"=>"Get societies specified by input parameters"
			),
		"camdram.societies.society.getApplicationAdvert"=>array(
			"function"=>"xmlgetapplicationadvert",
			"signature"=> array(
				array($xmlrpcStruct,$xmlrpcString)
				),
			"sigdoc" => array(
				array("Application advert - type application","Society UID")
				),
			"docstring"=>"Get application advert specified by society ID"
			),
		"camdram.people.get"=>array(
			"function"=>"xmlgetpeople",
			"signature"=> array(
				array($xmlrpcStruct,$xmlrpcStruct)
				),
			"sigdoc"=> array(
				array("Array of people - type person_array","Query - type person_query")
				),
			"docstring"=>"Get details on people specified by input parameters"
			)
	
	     ),
	     false
);
$s->functions_parameters_type = 'phpvals';
$s->service();
