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

require_once("library/editors.php");

if(hasEquivalentToken('security',-3))
{

  if(isset($_POST[Submit]) && checkSubmission())
    {
      genericSubmission("acts_termdates",array("name","friendlyname","startdate","enddate","firstweek","lastweek","displayweek","vacation"));
    }
  
  if(isset($_GET[deleteid]) && isset($_GET[confirmed])) {
    unset($_GET[confirmed]);
    if(checkSubmission()) genericDelete("acts_termdates",$_GET[deleteid]);
    unset($_GET[deleteid]);
  }

  allowSubmission();
  if(isset($_GET[editid])) 
    {
      $editid=$_GET[editid];
      unset($_GET[editid]);
      genericEditor("acts_termdates",$editid,array("name"=>"Name","friendlyname"=>"Alternative Name","startdate"=>"Start Date<br/><small>Monday of Week 0</small>","enddate"=>"End Date","firstweek"=>"First Week Displayed in Diary","lastweek"=>"Last Week Displayed in Diary","displayweek"=>"Display week numbers?<br /><small>0->No, 1->Yes</small>","vacation"=>"Vacation<br/><small>Name of vacation following term</small>"));
    }
  
  allowSubmission();
  genericEditorTable("acts_termdates","",order());
} else inputFeedback(); ?>
