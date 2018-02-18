<?php
require_once("xmlrpc.inc");
require_once("objects.inc");

class camdramimport extends xmlrpc_client
{
	
	function camdramimport($camdramserver)
	{
		$this->xmlrpc_client($camdramserver);
		$this->return_type = 'phpvals';
	}
	
	function gettechadvert($showuid)
	{
		$message=new xmlrpcmsg("camdram.occasions.show.getTechAdvert",array(php_xmlrpc_encode($showuid)));
		$resp=$this->send($message);
		return $this->arraytotechadvert($resp->val);
	}
	
	function getaudition($showuid)
	{
		$message=new xmlrpcmsg("camdram.occasions.show.getAudition",array(php_xmlrpc_encode($showuid)));
		$resp=$this->send($message);
		return $this->arraytoaudition($resp->val);
	}
	
	function getcoreadvert($showuid)
	{
		$message=new xmlrpcmsg("camdram.occasions.show.getCoreAdvert",array(php_xmlrpc_encode($showuid)));
		$resp=$this->send($message);
		return $this->arraytocoreadvert($resp->val);
	}
	
	function getapplicationadvert($socuid)
	{
		$message=new xmlrpcmsg("camdram.societies.society.getApplicationAdvert",array(php_xmlrpc_encode($socuid)));
		$resp=$this->send($message);
		return $this->arraytoapplication($resp->val);
	}
	
	function getsocieties($societyquery)
	{
		$message=new xmlrpcmsg("camdram.societies.get",array(php_xmlrpc_encode($societyquery)));
		$resp=$this->send($message);
		return $this->arraytosocietyarray($resp->val);
	}
	
	function getpeople($personquery)
	{
		$message=new xmlrpcmsg("camdram.people.get",array(php_xmlrpc_encode($personquery)));
		$resp=$this->send($message);
		return $this->arraytopersonarray($resp->val);
	}
	

	function arraytoobject($array,$type)
	{
		if (!isset($array)) return;
		$obj= (object) $array;
		$tmp = explode(':', serialize($obj));
		$tmp[1] = strlen($type);
		$tmp[2] = '"' . $type . '"';
		$obj = unserialize(implode(':', $tmp));
		return $obj;
	
	}

	function arraytotechadvert($array)
	{
		return $this->arraytoobject($array,"CDTechAdvert");
	}

	function arraytocoreadvert($array)
	{
		return $this->arraytoobject($array,"CDCoreAdvert");
	}

	function arraytoaudition($array)
	{
		return $this->arraytoobject($array,"CDAudition");
	}
	
	function arraytoapplication($array)
	{
		return $this->arraytoobject($array,"CDApplication");
	}
		
	function arraytosociety($array)
	{
		return $this->arraytoobject($array,"CDsociety");
	}	
		
	function arraytosocietyarray($array)
	{
		$obj=$this->arraytoobject($array,"CDsociety_array");
		if (!is_array($obj->societies)) {
			$obj->societies=array();
		}
		foreach ($obj->societies as $key=>$soc)
		{
			$obj->societies[$key]=$this->arraytosociety($soc);
		}
		return $obj;
	}
	
	function arraytoshowrole($array)
	{
		return $this->arraytoobject($array,"CDshow_role");
	}
	
	function arraytoperson($array)
	{
		$obj=$this->arraytoobject($array,"CDperson");
		if (!is_array($obj->shows_roles))
		{
			$obj->shows_roles=array();
		}
		foreach ($obj->shows_roles as $key=>$show_role)
		{
			$obj->shows_roles[$key]=$this->arraytoshowrole($show_role);
		}
		return $obj;
	}
	
	function arraytopersonarray($array)
	{
		$obj=$this->arraytoobject($array,"CDperson_array");
		if (!is_array($obj->people))
		{
			$obj->people=array();
		}
		foreach ($obj->people as $key=>$person)
		{
			$obj->people[$key]=$this->arraytoperson($person);
		}
		return $obj;
	}
		
	

}

?>
