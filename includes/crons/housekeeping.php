<?php
// remove livestreams that have been finished
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_mail.php');

/*
REMOVE LIVESTREAMS THAT HAVE FINISHED
*/
$livestream_timeout = 86400; // 1 day

$stamp = time() - $livestream_timeout;

$sql_date = date('Y/m/d H:i:s', $stamp);

$dbl->run("DELETE FROM `livestreams` WHERE `end_date` <= ?", array($sql_date));

/*
REMOVE OLD IP BANS
*/
$ip_timeout = $core->config('ip_ban_length');

$dbl->run("DELETE FROM `ipbans` WHERE `ban_date` < NOW() - INTERVAL $ip_timeout DAY");

/*
REMOVE A LOCK ON ARTICLES, WHEN PEOPLE FORGET TO UNLOCK THEM
*/
$lock_timeout = 1800; // 30 minutes

$lock_stamp = time() - $lock_timeout;

$locked = $dbl->run("SELECT `article_id`, `locked_date` FROM `articles` WHERE `locked_date` <= ? AND `locked_date` != 0", array($lock_stamp))->fetch_all();

foreach($locked as $row)
{
	$dbl->run("UPDATE `articles` SET `locked` = 0, `locked_date` = 0, `locked_by` = 0 WHERE `article_id` = ?", array($row['article_id']));
}

/*
REMOVE OLD ARTICLE IMAGE UPLOADS THAT AREN'T ATTACHED TO AN ARTICLE
*/
$upload_timeout = 86400; // 1 day

$upload_stamp = time() - $upload_timeout;

// grab all old article_images
$grab_all = $dbl->run("SELECT `filename` FROM `article_images` WHERE `date_uploaded` < ? AND `article_id` = 0", array($upload_stamp))->fetch_all();
foreach ($grab_all as $grabber)
{
	unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/' . $grabber['filename']);
	unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/thumbs/' . $grabber['filename']);
}

$dbl->run("DELETE FROM `article_images` WHERE `date_uploaded` < ? AND `article_id` = 0", array($stamp));

/*
REMOVE EXPIRED EDITOR PICKS
*/
$pick_timeout = 1209600; // 14 days

$pick_stamp = time() - $pick_timeout;

$featured = $dbl->run("SELECT p.`article_id`, p.`featured_image`, p.hits, a.date, a.title FROM `editor_picks` p INNER JOIN `articles` a ON p.article_id = a.article_id WHERE a.`date` < ?", array($pick_stamp))->fetch_all();

$games = '';

$total_to_remove = 0;
foreach($featured as $row)
{
	$games = $row['title'] . ' Hits: ' . $row['hits'] . '<br />';

	$dbl->run("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($row['article_id']));
	$dbl->run("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($row['article_id']));

	if (!empty($row['featured_image']))
	{
		$image = $core->config('path') . 'uploads/carousel/' . $row['featured_image'];

		if (file_exists($image))
		{
			unlink($image);
		}
	}
	$total_to_remove++;
}

if ($total_to_remove > 0)
{
	$dbl->run("UPDATE `config` SET `data_value` = (data_value - $total_to_remove) WHERE `data_key` = 'total_featured'");
}