<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

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
		if(unlink($file_dir . '/uploads/articles/article_images/' . $grabber['filename']))
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
