<?php
include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingolinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

$timeout = 86400; // 1 day

$stamp = time() - $timeout;

// grab all old article_images
$db->sqlquery("SELECT `filename` FROM `article_images` WHERE `date_uploaded` < ? AND `article_id` = 0", array($stamp));
while ($grabber = $db->fetch())
{
	if(unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/' . $grabber['filename']))
	{
		echo 'Deleted: ' . $grabber['filename'];
	}
}

$db->sqlquery("DELETE FROM `article_images` WHERE `date_uploaded` < ? AND `article_id` = 0", array($stamp));
