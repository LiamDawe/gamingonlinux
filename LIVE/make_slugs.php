<?php
include('../includes/config.php');

include('../includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../includes/class_core.php');
$core = new core();

$query = $db->sqlquery("SELECT `title`, `article_id` FROM `articles`");
foreach ($query as $get)
{
	$slug = core::nice_title($get['title']);
	$db->sqlquery("UPDATE `articles` SET `slug` = '$slug' WHERE `article_id` = {$get['article_id']}");
}
?>
