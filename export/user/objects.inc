<?php
class CDoccasion_array
{
	var $occasions;
	var $total=0;
}

class CDoccasion
{
	var $type;
	var $uid;
	var $societyuid;
	var $societyname;
	var $url;
	var $microurl;
}

class CDshow extends CDoccasion
{
	var $title;
	var $is_authorized=true;
	var $category;
	var $times;
	var $has_audition;
	var $has_tech_advert;
	var $has_core_advert;

	var $author;
	var $prices;
	var $image_url;
	var $venueuid;
	var $venuename;
	var $bookingurl;
	var $info;
	var $people;
}

class CDevent extends CDoccasion
{
	var $title;
	var $time;

	var $info;
}

class CDaudition extends CDoccasion
{
	var $showuid;
	
	var $times;
	var $info;
}

class CDapplication extends CDoccasion
{
	var $info;
	var $deadline;

	var $extrainfo;
}

class CDshowadvert
{
	var $uid;
	var $showuid;
	var $deadline;
	var $url;
	var $microurl;
}

class CDtechadvert extends CDshowadvert
{
	var $positions;
	var $contact;

	var $info;
}

class CDcoreadvert extends CDshowadvert
{
	var $info;
	
	var $extrainfo;
}

class CDsociety_array
{
	var $societies;
	var $total=0;
}

class CDsociety
{
	var $uid;
	var $name;
	var $shortname;
	var $url;
	var $microurl;
	var $has_application;
	var $acts_affiliated;
	var $college;
	var $image_url;

	var $info;
}

class CDshow_role
{
	var $uid;
	var $showuid;
	var $role;
	var $section;
}

class CDperson_array
{
	var $people;
	var $total=0;
}

class CDperson
{
	var $uid;
	var $name;

	var $shows_roles;
}

class CDtime
{
	var $start;

	var $excludedate;
	var $end;
	var $venue;
	var $unscheduled;
}

class CDquery
{
	var $uid;
	var $maxreturn;
	var $startreturn;
	var $include_full;
	
	function CDQuery($uid="",$include_full=false, $maxreturn=-1,$startreturn=0) 
	{
		$this->uid=$uid;
		$this->include_full=$include_full;
		$this->maxreturn=$maxreturn;
		$this->startreturn=$startreturn;
	}
}

class CDoccasion_query extends CDquery
{
	var $soc;
	var $types;
	var $date1;
	var $date2; // Second date for finding range, "BEFORE" to find occasion before date1, "AFTER" to find occasion after date1, not set or "MATCH" to find occasions that match date1
	var $title;
	var $author;
	var $category;
	var $venue;
	var $has_audition;
	var $has_coreadvert;
	var $has_techadvert;
	var $person;
	var $personuid;
}

class CDsociety_query extends CDquery
{
	var $name;
	
	function CDsociety_query($name="",$uid="",$include_full=false, $maxreturn=-1,$startreturn=0) 
	{
		$this->name=$name;
		$this->CDquery($uid,$include_full, $maxreturn,$startreturn);
	}
}

class CDperson_query extends CDquery
{
	var $name;
	
	function CDperson_query($name="",$uid="",$include_full=false, $maxreturn=-1,$startreturn=0) 
	{
		$this->name=$name;
		$this->CDquery($uid,$include_full, $maxreturn,$startreturn);
	}
	
}

?>
