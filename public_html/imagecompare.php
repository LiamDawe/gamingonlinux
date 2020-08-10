<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);
include(APP_ROOT . '/includes/header.php');

$templating->set_previous('title', 'GamingOnLinux Image Comparison', 1);
$templating->set_previous('meta_description', 'GamingOnLinux Image Comparison', 1);


if (!isset($_GET['1']) || !isset($_GET['2']))
{
	$core->message('You must have a broken link, the images were not set properly!',1);
}
else
{
	$images = $dbl->run("SELECT `filename`,`location` FROM `article_images` WHERE `filename` IN ( ?,? ) ORDER BY FIELD(`filename`, ?,?)", array($_GET['1'], $_GET['2'],$_GET['1'], $_GET['2']))->fetch_all();
	$templating->load('image_compare');
	$templating->block('main');

	if ($images[0]['location'] == NULL)
	{
		$before_location = $core->config('website_url') . 'uploads/articles/article_media/';
	}
	else
	{
		$before_location = $images[0]['location'] . 'uploads/articles/article_media/';
	}

	if ($images[1]['location'] == NULL)
	{
		$after_location = $core->config('website_url') . 'uploads/articles/article_media/';
	}
	else
	{
		$after_location = $images[1]['location'] . 'uploads/articles/article_media/';
	}

	$templating->set('before', $before_location . $images[0]['filename']);
	$templating->set('after', $after_location . $images[1]['filename']);
}

include(APP_ROOT . '/includes/footer.php');
?>
