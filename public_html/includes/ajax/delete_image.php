<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

// only logged in accounts can do this too, it's only for articles
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	die('You shouldn\'t be here. You need to be logged in.');
}

if (isset($_POST['image_id']) && is_numeric($_POST['image_id']))
{
	$qry1 = "SELECT `id`, `filename`, `filetype`,`youtube_cache`, `uploader_id` FROM `article_images` WHERE `id` = ?";
	$grabber = $dbl->run($qry1, array($_POST['image_id']))->fetch();

	$qry2 = "DELETE FROM `article_images` WHERE `id` = ?";
	$result = $dbl->run($qry2, array($_POST['image_id']));
	if(isset($result))
	{
		if ($grabber['youtube_cache'] == 0)
		{
			unlink(APP_ROOT . '/uploads/articles/article_media/' . $grabber['filename']);

			if ($grabber['filetype'] == 'gif')
			{
				$static_filename = str_replace('.gif', '_static.jpg', $grabber['filename']);
				unlink(APP_ROOT . '/uploads/articles/article_media/' . $static_filename);
			}
			if (file_exists(APP_ROOT . '/uploads/articles/article_media/thumbs/' . $grabber['filename']))
			{
				unlink(APP_ROOT . '/uploads/articles/article_media/thumbs/' . $grabber['filename']);
			}
		}
		else
		{
			if (file_exists(APP_ROOT . '/cache/youtube_thumbs/' . $grabber['filename']))
			{
				unlink(APP_ROOT . '/cache/youtube_thumbs/' . $grabber['filename']);
			}				
		}
		unset($_SESSION['uploads'][$grabber['id']]);
		echo "YES";
	}

	else
	{
		echo "NO";
	}
}
?>
