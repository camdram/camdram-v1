<?php
chdir ("public_html");

$hostname_db = "localhost";
$database_db = "acts";
$username_db = "acts";
$password_db = "password";

include ("library/adcdb.php");
include ("library/lib.php");
if ($argc != 8) {
  echo "Wrong argument list (".$argc." arguments received)\r\n";
  exit (-1); # borked.
}

$debugData = print_r($argv, true);
$handle = fopen("/home/camdram/mail-helper.log", "a");
fwrite($handle, $debugData);
fclose($handle);
chmod("/home/camdram/mail-helper.log", 0640);

$body = "";
while (!feof(STDIN)) {
  $body .= fgets(STDIN);
}

preg_match ("/support-(.*?)\@/", $argv[1], $matches);
$issue = "$matches[1]";

# Stuart addition - solve base64 problem
$encoding = $argv[6];
if (($encoding == "base64") or ($encoding == "quoted-printable")) {
  # Following patterns identify actual character set, e.g. 'utf-8' within 'charset="utf-8"'.
  # There are various allowable formats for this as per RFC 2045.
  if ( preg_match ("/charset=\"(.+?)\"/", $argv[7], $matches) ) {
    # Pattern 'charset="utf-8"'
    $content_type = "$matches[1]";
  }
  else if ( preg_match ("/charset=(.+?) \(.*?\)/", $argv[7], $matches) ) {
    # Pattern 'charset=utf-8 (Comment)'
    $content_type = "$matches[1]";
  }
  else if ( preg_match ("/charset=(.+?)$/", $argv[7], $matches) ) {
    # Pattern 'charset=utf-8' at end-of-line
    $content_type = "$matches[1]";
  }
  else {
    $content_type = "us-ascii"; # RFC 2045 default
  }

  $body = mb_convert_encoding ($body, $content_type, $encoding);
}
echo "Hello\n";
addSupport ($argv[2], $argv[3], $argv[4], $argv[5], $body, $issue);
?>

