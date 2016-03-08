<?php
include('../includes/config.php');

include('../includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../includes/class_core.php');
$core = new core();

$query = $db->sqlquery("SELECT `article_id`, `text`, `page2`, `page3` FROM `articles` WHERE `page2` != ''");
foreach ($query as $get)
{
	$new_text = '';

	if (!empty($get['page2']) && empty($get['page3']))
	{
		$new_text = $get['text'] . '<*PAGE*>' . $get['page2'];
	}
	if (!empty($get['page2']) && !empty($get['page3']))
	{
		$new_text = $get['text'] . '<*PAGE*>' . $get['page2'] . '<*PAGE*>' . $get['page3'];
	}

	$db->sqlquery("UPDATE `articles` SET `text` = ?, `page2` = '', `page3` = '' WHERE `article_id` = {$get['article_id']}", array($new_text));
}

echo 'done';
?>
