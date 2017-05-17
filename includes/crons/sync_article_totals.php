<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

$dbl->run("TRUNCATE TABLE `article_totals`");

$start = 2010 . '-01-01 00:00:00'; // when the current GOL began
// articles total for each month
$monthly_list = $dbl->run("SELECT Year(FROM_UNIXTIME(date)) as year, Month(FROM_UNIXTIME(date)) as month, Count(*) as `total` FROM `articles` WHERE `date` BETWEEN UNIX_TIMESTAMP( ? ) AND UNIX_TIMESTAMP( ? ) GROUP BY Year(FROM_UNIXTIME(date)), Month(FROM_UNIXTIME(date))", array($start, core::$sql_date_now))->fetch_all();

foreach ($monthly_list as $list)
{
	$dbl->run("INSERT INTO `article_totals` SET `year` = ?, `month` = ?, `total` = ?", [$list['year'], $list['month'], $list['total']]);
}
