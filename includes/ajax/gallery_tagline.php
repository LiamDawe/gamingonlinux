<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_template.php');

$templating = new template(core::config('template'));

$templating->load('/admin_modules/gallery_tagline');

$templating->block('top');

$db->sqlquery("SELECT `id`, `filename`, `name` FROM `articles_tagline_gallery` ORDER BY `name` ASC");
while ($images = $db->fetch())
{
  $templating->block('image_row');
  $templating->set('name', $images['name']);
  $templating->set('filename', $images['filename']);
  $templating->set('id', $images['id']);
}
$templating->block('bottom');
echo $templating->output();
