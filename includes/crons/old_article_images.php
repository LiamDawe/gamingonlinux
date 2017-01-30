<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

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
