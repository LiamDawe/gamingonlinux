<?php
session_cache_limiter('public');

if (isset($_GET['url']))
{
	header('Cache-control: max-age='.(60*60*24*365));
	header('Expires: '.gmdate(DATE_RFC1123,time()+60*60*24*365));
	header('Last-Modified: '.gmdate(DATE_RFC1123,filemtime($_GET['url'])));
	
	define("APP_ROOT", dirname(dirname(__FILE__)));
	require APP_ROOT . "/includes/bootstrap.php";

	$url_check = parse_url($_GET['url']);

	if (isset($url_check['scheme']) && ($url_check['scheme'] == 'http' || $url_check['scheme'] == 'https'))
	{
		$image_raw = core::file_get_contents_curl($_GET['url']);
		
		$new_image = imagecreatefromstring($image_raw);

		if ($new_image !== false) 
		{
			$image_info = getimagesizefromstring($image_raw);
			$image_type = $image_info['mime'];

			if (stripos($image_type, 'image/') !== false)
			{
				header('Content-Type: '.$image_type);
				imagepng($new_image);
				imagedestroy($new_image);
			}
		}
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