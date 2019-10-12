<?php
session_cache_limiter('');
session_start();

header('Content-Type: text/plain');
header('Cache-control: max-age='.(60*60*24*365));
header('Expires: '.gmdate(DATE_RFC1123,time()+60*60*24*365));

define("APP_ROOT", dirname(dirname(__FILE__)));
require APP_ROOT . "/includes/bootstrap.php";
define("APP_URL", $core->config('website_url'));

$parse_url = parse_url($_SERVER['HTTP_REFERER']);
if ($parse_url['scheme'].'://'.$parse_url['host'].'/' == $core->config('website_url'))
{
	if (isset($_GET['id']))
	{
		// only logged in accounts can do this too, it's only for articles
		if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
		{
			die('You shouldn\'t be here. You need to be logged in.');
		}

		$article_id = NULL;
		if (isset($_GET['aid']) && is_numeric($_GET['aid']))
		{
			$article_id = $_GET['aid'];
		}

		$youtube_url = "https://img.youtube.com/vi/";

		$video_id = str_replace(array('?rel=0', '?rel=1'), '', $_GET['id']);

		$types = array('maxresdefault.jpg', 'hqdefault.jpg');
		$total_to_check = count($types);

		// first we loop over the cache to check against the types set above
		$download = 0;
		$counter = 0;
		foreach ($types as $type)
		{
			$counter++;
			$image_file_name = md5($youtube_url.$video_id.'/'.$type) . '.jpg';
			$cache_file_check = APP_ROOT . '/cache/youtube_thumbs/' . $image_file_name;
			if (file_exists($cache_file_check))
			{
				// cache file exists, so it should be in the database
				$media_db_id = $dbl->run("SELECT `id` FROM `article_images` WHERE `filename` = ? AND `youtube_cache` = 1", array($image_file_name))->fetchOne();
				if (!$media_db_id)
				{
					// add it to the database so we can keep track
					$new_image = $dbl->run("INSERT INTO `article_images` SET `filename` = ?, `uploader_id` = ?, `date_uploaded` = ?, `article_id` = ?, `filetype` = ?, `youtube_cache` = 1", [$image_file_name, $_SESSION['user_id'], core::$date, $article_id, 'jpg']);
					$media_db_id = $new_image->new_id();				
				}
				$local_file_url = APP_URL . 'cache/youtube_thumbs/' . $image_file_name;
				break;
			}
			else if ($counter == $total_to_check)
			{
				$download = 1;
			}
		}

		// if no cache found, we will loop over the types and attempt to download one of them in the order they're set in $types (best first)
		if ($download == 1)
		{
			$local_file_url = '';
			foreach ($types as $type)
			{
				$image_raw = core::file_get_contents_curl($youtube_url . $video_id . '/' . $type);
				if ($image_raw)
				{
					$filename = md5($youtube_url.$video_id.'/'.$type) . '.jpg';
					$local_file = APP_ROOT.'/cache/youtube_thumbs/' . $filename;
					$new_image = imagecreatefromstring($image_raw);
					imagejpeg($new_image, $local_file);
					$local_file_url = APP_URL . 'cache/youtube_thumbs/' . $filename;

					// add it to the database so we can keep track
					$new_image = $dbl->run("INSERT INTO `article_images` SET `filename` = ?, `uploader_id` = ?, `date_uploaded` = ?, `article_id` = ?, `filetype` = ?, `youtube_cache` = 1", [$filename, $_SESSION['user_id'], core::$date, $article_id, 'jpg']);
					$media_db_id = $new_image->new_id();

					break;
				}
			}
		}

		// if none found
		if (!isset($local_file_url) || empty($local_file_url))
		{
			$local_file_url = APP_URL . 'templates/default/images/youtube_cache_default.png';
		}

		echo json_encode(array("file_url" => $local_file_url, "db_id" => $media_db_id));
	}
	else
	{
		echo 'No image supplied!';
	}

}
else
{
	echo 'You shouldn\'t be here.';
	die();
}
?>