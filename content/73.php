<?php
global $site_support_email;
	session_start();
	logout();
	
	// don't carry any information over
	foreach($_POST as $key=>$value)
		unset($_POST[$key]);
		
	foreach($_GET as $key=>$value)
		unset($_GET[$key]);
	
	login();
?><p>If you get security warnings from your browser when using HTTPS, you need to <strong>install the CAcert root
certificate</strong>, available <a href="http://www.cacert.org" target="_blank">here</a>. </p>

<p><strong>Support available via</strong><br><a href="mailto:<?=$site_support_email?>"><?=$site_support_email?></a></p>
