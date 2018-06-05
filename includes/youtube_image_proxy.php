<?php
/* 
need a cron to remove cache files this makes older than 3 months to save disk space?
Also need to add a random number to the time_to_cache when checking, to prevent cache hammering for loads of people accessing at the same time?
*/
session_cache_limiter('');
define('time_to_cache', 172800); // 48 hours

if (isset($_GET['id']))
{
	header('Cache-control: max-age='.(60*60*24*365));
	header('Expires: '.gmdate(DATE_RFC1123,time()+60*60*24*365));
	
	define("APP_ROOT", dirname(dirname(__FILE__)));
	require APP_ROOT . "/includes/bootstrap.php";

	$youtube_url = "https://img.youtube.com/vi/";

	$types = array('maxresdefault.jpg', 'hqdefault.jpg');
	$total_to_check = count($types);

	// first we loop over the cache to check against the types set above
	$download = 0;
	$counter = 0;
	foreach ($types as $type)
	{
		$counter++;

		$cache_file_check = APP_ROOT . '/cache/youtube_thumbs/' . md5($youtube_url.$_GET['id'].'/'.$type) . '.jpg';
		if (file_exists($cache_file_check))
		{
			// cache expired for this file, need to download
			if (filemtime($cache_file_check) + time_to_cache < time())
			{
				$download = 1;
				break;
			}
			// cache hasn't expired and we have it, set the local file to load to this one
			$local_file = $cache_file_check;
			break;
		}
		else if ($counter == $total_to_check)
		{
			$download = 1;
		}
	}

	// if no cache found, we will loop over the types and attempt to download one of them in the order they're set in $types (best first)
	// TODO: Add a default YouTube image if none found!
	$local_file = '';
	if ($download == 1)
	{
		foreach ($types as $type)
		{
			$image_raw = core::file_get_contents_curl($youtube_url . $_GET['id'] . '/' . $type);
			if ($image_raw)
			{
				$local_file = APP_ROOT.'/cache/youtube_thumbs/' . md5($youtube_url.$_GET['id'].'/'.$type) . '.jpg';
				$new_image = imagecreatefromstring($image_raw);
				imagejpeg($new_image, $local_file);	
				break;
			}
		}
	}

	if (!isset($local_file) || empty($local_file))
	{
		$local_file = APP_ROOT.'/cache/youtube_thumbs/default.png';
	}

	header('Content-Type: image/jpeg');
	readfile($local_file);
}
else
{
	echo 'No image supplied!';
}
?>