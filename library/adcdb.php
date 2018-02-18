<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$adcdb = mysql_pconnect($hostname_db, $username_db, $password_db) or die (mysql_error());
mysql_select_db($database_db, $adcdb) or die (mysql_error());
mysql_query('SET NAMES utf8', $adcdb);
?>
