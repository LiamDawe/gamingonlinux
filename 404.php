<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

$templating->set_previous('meta_description', '404 not found', 1);
$templating->set_previous('title', '404 not found', 1);

$templating->load('404');
$templating->block('main');

include(APP_ROOT . '/includes/footer.php');
?>
