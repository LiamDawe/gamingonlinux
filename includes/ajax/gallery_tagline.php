<?php
session_start();

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

include('../class_template.php');

$templating = new template('default');

$templating->load('/admin_modules/gallery_tagline');

$db->sqlquery("SELECT id, filename FROM `articles_tagline_gallery` ORDER BY `filename` ASC");
while ($images = $db->fetch())
{
  $templating->block('image_row');
  $templating->set('filename', $images['filename']);
  $templating->set('id', $images['id']);
}
echo $templating->output();
