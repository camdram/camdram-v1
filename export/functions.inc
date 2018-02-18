<?php
chdir("../");
require_once("config.php");
require_once("library/adcdb.php");
require_once("library/lib.php");

function xmlgettechadvert($showuid)
{
	$techadvert=new CDtechadvert();
	if (preg_match("/SHOW([0-9]*)/i",$showuid,$ids))
	{
		global $adcdb;
		$showid=$ids[1];
		$query="SELECT positions, acts_techies.id AS adid, acts_techies.showid, techextra,acts_techies.contact, acts_techies.deadline, acts_techies.expiry,acts_techies.deadlinetime, acts_shows_refs.ref as uref FROM acts_shows_refs,acts_techies,acts_shows WHERE acts_techies.showid=acts_shows.id AND acts_techies.expiry>=NOW() AND acts_shows.authorizeid>0 AND acts_shows.entered=1 AND refid=primaryref AND acts_techies.showid='$showid' LIMIT 1;";
		$result=mysql_query($query,$adcdb);
		if ($row=mysql_fetch_assoc($result))
		{
			$techadvert->uid="tech".$row['adid'];
			$techadvert->showuid="show".$showid;
			if ($row['deadline']==1)
			{
				$techadvert->deadline=iso8601_encode(strtotime($row['expiry']),0);
			}
			$techadvert->positions=$row['positions'];
			$techadvert->contact=$row['contact'];
			$techadvert->info=$row['techextra'];
			$showref=$row['uref'];
			$techadvert->url=linkTo(104,array("techextra"=>$row['adid'],"CLEARALL"=>"CLEARALL"),"/index.php", false, $showref);
			$techadvert->microurl=linkTo(104,array("techextra"=>$row['adid'],"showid"=>$showid,"CLEARALL"=>"CLEARALL"),"/micro.php");
		}
	}
	return $techadvert;
}

function xmlgetAudition($showuid)
{
	$audition = new CDaudition();
	if (preg_match("/SHOW([0-9]*)/i",$showuid,$ids))
	{
		global $adcdb;
		$showid=$ids[1];
		$query="SELECT * FROM acts_auditions, acts_shows_refs, acts_shows LEFT JOIN acts_societies ON acts_shows.socid = acts_societies.id WHERE refid=primaryref AND acts_shows.id=acts_auditions.showid AND acts_auditions.showid='$showid' AND acts_auditions.date>=NOW()";
		$result=mysql_query($query, $adcdb);
		$audition->times=array();
		while ($row=mysql_fetch_assoc($result))
		{
			$showref=$row['ref'];
			$audition->showuid="show".$showid;
			$audition->info=$row['audextra'];
			$newtime=new CDtime();
			$newtime->start=iso8601_encode(strtotime($row['date'])+strtotime('1970-01-01 '.$row['starttime'].' GMT'));
			$newtime->end=iso8601_encode(strtotime($row['date'])+strtotime('1970-01-01 '.$row['endtime'].' GMT'));
			$newtime->venue=$row['location'];
			$newtime->unscheduled=$row['nonscheduled'];
			array_push($audition->times,$newtime);
			$audition->type="audition";
			$audition->uid="aud".$showid;
			$audition->url=linkTo(104,array("audextra"=>"true","CLEARALL"=>"CLEARALL"),"/index.php", false, $showref);
			$audition->microurl=linkTo(104,array("audextra"=>"true","showid"=>$showid,"CLEARALL"=>"CLEARALL"),"/micro.php");
			if ($row['socid']!=0)
			{
				$audition->societyname=$row['name'];
				$audition->societyuid='soc'.$row['socid'];
			}
			else {
				$audition->societyname=$row['society'];
			}
		}
		
	}
	return $audition;
}

function xmlgetcoreadvert($showuid)
{
	$coreadvert=new CDcoreadvert();
	if (preg_match("/SHOW([0-9]*)/i",$showuid,$ids))
	{
		global $adcdb;
		$showid=$ids[1];
		$query="SELECT acts_applications.*, ref FROM acts_shows_refs,acts_applications,acts_shows WHERE acts_applications.showid=acts_shows.id AND acts_applications.deadlinedate>=NOW() AND acts_shows.authorizeid>0 AND acts_shows.entered=1 AND refid=primaryref AND acts_applications.showid='$showid' LIMIT 1;";
		$result=mysql_query($query,$adcdb);
		if ($row=mysql_fetch_assoc($result))
		{
			$coreadvert->uid="core".$row['id'];
			$coreadvert->showuid="show".$showid;
			$coreadvert->deadline=iso8601_encode(strtotime($row['deadlinedate'])+strtotime('1970-01-01 '.$row['deadlinetime'].' GMT'),0);
			$coreadvert->info=$row['text'];
			$coreadvert->extrainfo=$row['furtherinfo'];
			$showref=$row['ref'];
			$coreadvert->url=linkTo(104,array("coreextra"=>$row['id'],"CLEARALL"=>"CLEARALL"),"/index.php", false, $showref);
			$coreadvert->microurl=linkTo(104,array("coreextra"=>$row['id'],"showid"=>$showid,"CLEARALL"=>"CLEARALL"),"/micro.php");
		}
	}
	return $coreadvert;
}

function xmlgetapplicationadvert($socuid)
{
	$application = new CDApplication();
	if (preg_match("/SOC([0-9]*)/i",$socuid,$ids))
	{
		global $adcdb;
		$socid=$ids[1];
		$query="SELECT acts_applications.*, acts_societies.name FROM acts_applications, acts_societies WHERE acts_applications.socid=acts_societies.id AND acts_applications.deadlinedate>=NOW() AND acts_applications.socid='$socid' LIMIT 1;";
		$result=mysql_query($query,$adcdb);
		if ($row=mysql_fetch_assoc($result))
		{
			$application->type="application";
			$application->uid="application".$row['id'];
			$application->societyuid="soc".$socid;
			$application->societyname=$row['name'];
			$application->deadline=iso8601_encode(strtotime($row['deadlinedate'])+strtotime('1970-01-01 '.$row['deadlinetime'].' GMT'),0);
			$application->info=$row['text'];
			$application->extrainfo=$row['furtherinfo'];
			$application->url=linkTo(205,array("CLEARALL"=>"CLEARALL"),"/index.php")."#".$row['deadlinedate'];
		}
	}
	return $application;
}

function xmlgetsocieties($socquery)
{
	$societyarray=new CDsociety_array();
	global $adcdb;
	$condition="type=0";
	if (preg_match("/SOC([0-9]*)/i",$socquery['uid'],$ids))
	{
		$socid=$ids[1];
		$condition.=" AND id='$socid'";
	}
	if (isset($socquery['name']) && $socquery['name'] !="")
	{
		$condition.=" AND (name LIKE '%".$socquery['name']."%' OR shortname LIKE '%".$socquery['name']."%')";
	}
	if ($socquery['include_full'])
	{
		$extrafields=", description";
	}
	
	
	
	$query="SELECT acts_societies.id, name, shortname, affiliate, college, logourl$extrafields, tmp.id AS appid FROM acts_societies LEFT JOIN (SELECT * FROM acts_applications WHERE deadlinedate>=NOW()) AS tmp ON socid=acts_societies.id WHERE $condition";
	
	$result=mysql_query($query, $adcdb) or die(mysql_error());
	
	$societyarray->total=mysql_num_rows($result);
	
	if (isset($socquery['maxreturn']) && $socquery['maxreturn']>=0)
	{
		$limit=" LIMIT ".$socquery['maxreturn'];
		if (isset($socquery['startreturn']) && $socquery['startreturn']!='')
		{
			$limit.=" OFFSET ".$socquery['startreturn'];
		}
		$query.=$limit;
		$result=mysql_query($query, $adcdb) or die(mysql_error());
	}

	$i=0;
	
	while($row=mysql_fetch_assoc($result))
	{
		$obj=new CDSociety();
		$obj->uid="soc".$row['id'];
		$obj->name=$row['name'];
		$obj->shortname=$row['shortname'];
		$obj->acts_affiliated=$row['affiliate'];
		$obj->has_application=false;
		if (isset($row['appid'])) $obj->has_application=true;
		$obj->college=$row['college'];
		$obj->info=$row['description'];
		if ($row['logourl']==1)
		{
			global $currentbase;
			$obj->image_url=$currentbase."/images/societies/".$row['id'].".jpg";
		}
		$obj->microurl=linkTo(116,array("socid"=>$row['id'],"CLEARALL"=>"CLEARALL"),"/micro.php");
		$obj->url=linkTo(116,array("CLEARALL"=>"CLEARALL"),"/index.php",false,string_to_url (noslashes($row['shortname'])));
		$societyarray->societies[$i]=$obj;
		$i++;
	}
	return $societyarray;
}

function xmlgetpeople($personquery)
{
	global $adcdb;
	$personarray=new CDperson_array();

	$condition="pid=acts_people_data.id";
	if (preg_match("/PERSON([0-9]*)/i",$personquery['uid'],$ids))
	{
		$personid=$ids[1];
		$condition.=" AND pid='$personid'";
	}

	if (preg_match("/SHOW([0-9]*)/i",$personquery['uid'],$ids))
	{
		$showid=$ids[1];
		$condition.=" AND sid='$showid'";
	}
	
	if (isset($personquery['name']))
	{
		$condition.=" AND name LIKE '%".$personquery['name']."%'";
	}

	$query="SELECT DISTINCT acts_shows_people_link.pid,acts_people_data.name FROM acts_shows_people_link,acts_people_data WHERE $condition";

	$result=mysql_query($query,$adcdb);
	
	$personarray->total=mysql_num_rows($result);	

	if (isset($personquery['maxreturn']) && $personquery['maxreturn']>=0)
	{
		$limit=" LIMIT ".$personquery['maxreturn'];
		if (isset($personquery['startreturn']) && $personquery['startreturn']!='')
		{
			$limit.=" OFFSET ".$personquery['startreturn'];
		}
		$query.=$limit;
		$result=mysql_query($query, $adcdb) or die(mysql_error());
	}
	$i=0;
	while ($row=mysql_fetch_assoc($result))
	{
		$obj=new CDperson();
		$obj->uid="person".$row['pid'];
		$obj->name=$row['name'];
		if ($personquery['include_full']) {
			$condition="pid='".$row['pid']."'";
			if (isset($showid))
			{
				$condition=" AND sid='$showid'";
			}
			$rolequery="SELECT * FROM acts_shows_people_link WHERE $condition";
			
			$roleresult=mysql_query($rolequery,$adcdb) or die(mysql_error());;
			
			$j=0;
			
			while ($rolerow=mysql_fetch_assoc($roleresult))
			{
				$role=new CDshow_role();
				$role->uid="role".$rolerow['id'];
				$role->showuid="show".$rolerow['sid'];
				$role->role=$rolerow['role'];
				$role->section=$rolerow['type'];
				$obj->shows_roles[$j]=$role;
				$j++;
			}
			
			
		}
		$personarray->people[$i]=$obj;
		$i++;
	}

	return $personarray;
}
?>
