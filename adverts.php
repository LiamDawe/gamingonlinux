<?php
include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

if (!isset($_GET['id']))
{
	header("Location: http://www.gamingonlinux.com");
}

else
{
	$db->sqlquery("UPDATE `adverts` SET `total_clicks` = (total_clicks + 1) WHERE `advert_id` = ?", array($_GET['id']));
	
	// get the url to go to
	$db->sqlquery("SELECT `url` FROM `adverts` WHERE `advert_id` = ?", array($_GET['id']));
	$get_site = $db->fetch();
	
	header("Location: {$get_site['url']}");
}
?>
