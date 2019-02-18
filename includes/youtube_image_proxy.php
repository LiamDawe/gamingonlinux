<?php
session_cache_limiter('');
define('time_to_cache', 172800); // 48 hours

header('Content-Type: text/plain');

if (isset($_GET['id']))
{
	header('Cache-control: max-age='.(60*60*24*365));
	header('Expires: '.gmdate(DATE_RFC1123,time()+60*60*24*365));
	
	define("APP_ROOT", dirname(dirname(__FILE__)));
	require APP_ROOT . "/includes/bootstrap.php";
	define("APP_URL", $core->config('website_url'));

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

		$cache_file_check = APP_ROOT . '/cache/youtube_thumbs/' . md5($youtube_url.$video_id.'/'.$type) . '.jpg';
		if (file_exists($cache_file_check))
		{
			$local_file_url = APP_URL . 'cache/youtube_thumbs/' . md5($youtube_url.$video_id.'/'.$type) . '.jpg';
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
				$local_file = APP_ROOT.'/cache/youtube_thumbs/' . md5($youtube_url.$video_id.'/'.$type) . '.jpg';
				$new_image = imagecreatefromstring($image_raw);
				imagejpeg($new_image, $local_file);
				$local_file_url = APP_URL . 'cache/youtube_thumbs/' . md5($youtube_url.$video_id.'/'.$type) . '.jpg';
				break;
			}
		}
	}

	// if none found
	if (!isset($local_file_url) || empty($local_file_url))
	{
		$local_file_url = APP_URL . 'templates/default/images/youtube_cache_default.png';
	}

	echo $local_file_url;
}
else
{
	echo 'No image supplied!';
}
?>