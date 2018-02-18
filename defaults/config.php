<?php 
$hostname_db = "127.0.0.1";
$database_db = "acts";
$username_db = "acts";
$password_db = "some_password";
ini_set('session.save_path','/tmp');
ini_set('arg_separator.input','&amp;');
ini_set('arg_separator.output','&amp;');
if(strpos($_SERVER['HTTP_USER_AGENT'],"google")!==false or strpos($_SERVER['HTTP_USER_AGENT'],"MSIECrawler")!==false)
{
	ini_set("url_rewriter.tags","");
	ini_set('session.use_trans_sid',false);
}
global $cookiedomain;
$cookiedomain='camdram.johndilley.me.uk';
ini_set('session.cookie_domain',$cookiedomain);
global $loginscript;
$loginscript = "https://www.johndilley.me.uk/camdram/dologin.php";

global $cvs_rsh;
$cvs_rsh="ssh";

global $base;
$base="http://camdram.johndilley.me.uk";

global $securebase;
$securebase="https://camdram.johndilley.me.uk";

global $mail_redirect;

$logpath = "../private";
$mail_redirect="websupport@camdram.net";
$mail_alsomail = "webteam@camdram.net"; 
$site_support_email = "websupport@camdram.net";
$site_db_prefix = "acts_"; 

$theme = "winter";
?>
