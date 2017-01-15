<?php
session_start();

include('config.php');

include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('class_core.php');
$core = new core();

if (isset($_POST['image_id']) && is_numeric($_POST['image_id']))
{
	// get the image 
	$qry1 = "SELECT `id`, `filename` FROM `article_images` WHERE `id` = ?";
	$db->sqlquery($qry1, array($_POST['image_id']));
	$grabber = $db->fetch();

	$qry2 = "DELETE FROM `article_images` WHERE `id` = ?";
	$result=$db->sqlquery($qry2, array($_POST['image_id']));
	if(isset($result))
	{
		if(unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/' . $grabber['filename']))
		{
			unset($_SESSION['uploads'][$grabber['id']]);
			echo "YES";
		}
	}

	else
	{
		echo "NO";
	}
}
?>
