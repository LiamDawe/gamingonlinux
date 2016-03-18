<?php
// this page is used to check the time the sales expiry cron last ran, it runs every hour, so this check two hours ago and if it wasn't recent then it's not working
ini_set('display_errors',1);

include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

if (core::config('sales_expiry_lastrun') <= core::$date - 7200)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Expiry Error';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The sales expiry cron doesn't seem to have run for a while, needs checking for errors", $headers);

	echo "Mail sent!";
}
else {
	echo 'All fine';
}
