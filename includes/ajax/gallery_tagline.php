<?php
define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$templating = new template($core, $core->config('template'));

$templating->load('/admin_modules/gallery_tagline');

$templating->block('top');

$images = $dbl->run("SELECT `id`, `filename`, `name` FROM `articles_tagline_gallery` ORDER BY `name` ASC")->fetch_all();
foreach ($images as $image)
{
	$templating->block('image_row');
	$templating->set('name', $image['name']);
	$templating->set('filename', $image['filename']);
	$templating->set('id', $image['id']);
}
$templating->block('bottom');
echo $templating->output();
