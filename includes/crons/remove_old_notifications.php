<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$year = 365*24*60*60;

// remove completed admin notifications older than 1 year, clean up the cruft
$dbl->run("DELETE FROM `admin_notifications` WHERE `completed` = 1 AND created_date <= (created_date - $year)");
?>
