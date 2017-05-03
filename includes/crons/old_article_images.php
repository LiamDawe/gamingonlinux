<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

$timeout = 86400; // 1 day

$stamp = time() - $timeout;

// grab all old article_images
$grab_all = $dbl->run("SELECT `filename` FROM `article_images` WHERE `date_uploaded` < ? AND `article_id` = 0", array($stamp))->fetch_all();
foreach ($grab_all as $grabber)
{
	unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/' . $grabber['filename']);
	unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/thumbs/' . $grabber['filename']);
}

$dbl->run("DELETE FROM `article_images` WHERE `date_uploaded` < ? AND `article_id` = 0", array($stamp));
