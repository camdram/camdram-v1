<?php

require_once("library/editors.php");

if(hasEquivalentToken('security',-3))
{
  if(isset($_POST[Submit]))
    {
      genericSubmission("acts_reviews",array("name","friendlyname","startdate","enddate"));
    }

  if(isset($_GET[editid])) 
    {
      $editid=$_GET[editid];
      unset($_GET[editid]);
      genericEditor("acts_reviews",$editid);
    }
  
  genericEditorTable("acts_reviews","",order());
} else inputFeedback(); ?>