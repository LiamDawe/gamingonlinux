<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if (isset($_POST['image_id']) && is_numeric($_POST['image_id']))
{
	// get the image 
	$qry1 = "SELECT `id`, `filename`, `filetype` FROM `article_images` WHERE `id` = ?";
	$grabber = $dbl->run($qry1, array($_POST['image_id']))->fetch();

	$qry2 = "DELETE FROM `article_images` WHERE `id` = ?";
	$result = $dbl->run($qry2, array($_POST['image_id']));
	if(isset($result))
	{
		if(unlink(APP_ROOT . '/uploads/articles/article_media/' . $grabber['filename']) && unlink(APP_ROOT . '/uploads/articles/article_media/thumbs/' . $grabber['filename']))
		{
			if ($grabber['filetype'] == 'gif')
			{
				$static_filename = str_replace('.gif', '_static.jpg', $grabber['filename']);
				unlink(APP_ROOT . '/uploads/articles/article_media/' . $static_filename);
			}
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
