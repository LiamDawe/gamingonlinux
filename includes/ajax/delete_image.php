<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'],$db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

if (isset($_POST['image_id']) && is_numeric($_POST['image_id']))
{
	// get the image 
	$qry1 = "SELECT `id`, `filename` FROM `article_images` WHERE `id` = ?";
	$grabber = $dbl->run($qry1, array($_POST['image_id']))->fetch();

	$qry2 = "DELETE FROM `article_images` WHERE `id` = ?";
	$result = $dbl->run($qry2, array($_POST['image_id']));
	if(isset($result))
	{
		if(unlink($file_dir . '/uploads/articles/article_images/' . $grabber['filename']) && unlink($file_dir . '/uploads/articles/article_images/thumbs/' . $grabber['filename']))
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
