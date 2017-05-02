<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'],$db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_template.php');
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
