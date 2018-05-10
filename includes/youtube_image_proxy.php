<?php
/* 
need a cron to remove cache files this makes older than 3 months to save disk space?
Also need to add a random number to the time_to_cache when checking, to prevent cache hammering for loads of people accessing at the same time?
*/
session_cache_limiter('');
define('time_to_cache', 172800); // 48 hours

if (isset($_GET['url']))
{
	header('Cache-control: max-age='.(60*60*24*365));
	header('Expires: '.gmdate(DATE_RFC1123,time()+60*60*24*365));
	
	define("APP_ROOT", dirname(dirname(__FILE__)));
	require APP_ROOT . "/includes/bootstrap.php";

	$url_check = parse_url($_GET['url']);

	if (isset($url_check['scheme']) && ($url_check['scheme'] == 'http' || $url_check['scheme'] == 'https') && isset($url_check['host']) && $url_check['host'] == 'img.youtube.com')
	{
		// check if we have it cached first
		$local_file = APP_ROOT . '/cache/youtube_thumbs/' . md5($_GET['url']).'.jpg';

		$download = 0;
		// Determine whether the local file is too old
		if (file_exists($local_file))
		{
			if (filemtime($local_file) + time_to_cache < time()) 
			{
				// Download a fresh copy
				$download = 1;
			}
		}
		else
		{
			$download = 1;
		}

		if ($download == 1)
		{
			$image_raw = core::file_get_contents_curl($_GET['url']);
			$new_image = imagecreatefromstring($image_raw);
			imagejpeg($new_image, $local_file);
		}

		header('Content-Type: image/jpeg');
		readfile($local_file);
	}
	else
	{
		echo 'Not a valid image url!';
	}
}
else
{
	echo 'No image supplied!';
}
?>