<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

$timeout = 31536000; // 1 year

$stamp = time() - $timeout;

$closed = '';
$closing = $dbl->run("SELECT `title` FROM `articles` WHERE `date` < ? AND `comments_open` = 1", array($stamp))->fetch_all();
foreach ($closing as $to_close)
{
	$closed .= $to_close['title'] . '<br />';
}

$dbl->run("UPDATE `articles` SET `comments_open` = 0 WHERE `date` < ?", array($stamp));

$subject = $core->config('site_title') . ' CRON - Comments Closed';

// To send HTML mail, the Content-type header must be set
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

mail($core->config('contact_email'), $subject, "Comments closed on these articles: " . $closed, $headers);
