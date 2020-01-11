<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/cron_bootstrap.php";

// update the total user count
$total_users = $dbl->run("SELECT COUNT(*) FROM `users` ORDER BY `user_id` ASC")->fetchOne();
$dbl->run("INSERT INTO `stats_registered_users` SET `total` = ?, `date` = ?", array($total_users, core::$sql_date_now));