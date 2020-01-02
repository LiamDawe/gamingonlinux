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
	$templating->load('image_compare');
	$templating->block('main');
	$templating->set('before', $_GET['1']);
	$templating->set('after', $_GET['2']);
}

include(APP_ROOT . '/includes/footer.php');
?>
