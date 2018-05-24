<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

/*
REMOVE LIVESTREAMS THAT HAVE FINISHED
*/
$livestream_timeout = 86400; // 1 day

$stamp = time() - $livestream_timeout;

$sql_date = date('Y/m/d H:i:s', $stamp);

$dbl->run("DELETE FROM `livestreams` WHERE `end_date` <= ?", array($sql_date));

/* 
REMOVE OLD BUNDLES FROM SALES PAGE
*/
$dbl->run("DELETE FROM `sales_bundles` WHERE `end_date` < now()");

/*
REMOVE OLD IP BANS
*/
$ip_timeout = $core->config('ip_ban_length');

$dbl->run("DELETE FROM `ipbans` WHERE `ban_date` < NOW() - INTERVAL $ip_timeout DAY");

/*
Remove IP address from users who haven't logged into the site in 3 months
*/
$dbl->run("UPDATE `users` SET `ip` = NULL, `private_profile` = 1 WHERE `last_login` <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY))");

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
	unlink(APP_ROOT . '/uploads/articles/article_media/' . $grabber['filename']);
	unlink(APP_ROOT . '/uploads/articles/article_media/thumbs/' . $grabber['filename']);
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
	// update cache
	$new_featured_total = $core->config('total_featured') - $total_to_remove;
	core::$redis->set('CONFIG_total_featured', $new_featured_total); // no expiry as config hardly ever changes
}

/* REMOVE COMPLETED ADMIN NOTES OLDER THAN ONE YEAR */
$year = 365*24*60*60;
$dbl->run("DELETE FROM `admin_notifications` WHERE `completed` = 1 AND created_date <= (created_date - $year)");

/* REMOVE SEEN USER NOTIFICATIONS OLDER THAN SIX MONTH */
$dbl->run("DELETE FROM `user_notifications` WHERE last_date <= (now() - interval 6 month)");

// remove pc info where the user hasn't updated the info for 2 years

$dbl->run("DELETE FROM `user_profile_info` WHERE `date_updated` < DATE_SUB(NOW(),INTERVAL 2 YEAR)");

// remove users who aren't activated after 10 days

$say_bye = $dbl->run("SELECT `user_id` FROM `users` WHERE `register_date` <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 10 DAY)) AND `activated` = 0")->fetch_all();
$total_users = count($say_bye);

foreach ($say_bye as $remove)
{
	$dbl->run("DELETE FROM `users` WHERE `user_id` = ?", array($remove['user_id']));
	$dbl->run("DELETE FROM `user_profile_info` WHERE `user_id` = ?", array($remove['user_id']));
	$dbl->run("DELETE FROM `user_group_membership` WHERE `user_id` = ?", array($remove['user_id']));
}
$dbl->run("UPDATE `config` SET `data_value` = (data_value - ?) WHERE `data_key` = 'total_users'", array($total_users));
core::$redis->delete('CONFIG_total_users'); // force new cache

// remove guests from the mailing list if they haven't activated after 7 days
$dbl->run("DELETE FROM `mailing_list` WHERE `date_added` <= (now() - INTERVAL 7 DAY) AND `activated` = 0")->fetch_all();

// deleted expired sessions
$dbl->run("DELETE FROM `saved_sessions` WHERE `expires` < NOW() OR `expires` IS NULL");

// update last ran datetime
$dbl->run("UPDATE `crons` SET `last_ran` = ? WHERE `name` = 'housekeeping'", [core::$sql_date_now]);