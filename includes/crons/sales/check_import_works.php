<?php
// this page is used to check the time the sales humble store import cron last ran
// we can adjust this to check other feeds too
ini_set('display_errors',1);

define('path', '/home/gamingonlinux/public_html/includes/');

include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'class_core.php');
$core = new core();

// check HUMBLE STORE every 48 hours
if (core::config('humble_import_lastrun') <= core::$date - 172800)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Humble Import Error';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The sales Humble Store Import cron doesn't seem to have run for a while, needs checking for errors", $headers);

	echo "Mail sent!";
}
else {
	echo 'All fine';
}
