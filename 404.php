<?php
$file_dir = dirname(__FILE__);

error_reporting(E_ALL);

include($file_dir . '/includes/header.php');

$templating->set_previous('meta_description', '404 not found', 1);
$templating->set_previous('title', '404 not found', 1);

$templating->merge('404');
$templating->block('main');

include($file_dir . '/includes/footer.php');
?>
