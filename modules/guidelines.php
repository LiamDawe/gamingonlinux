<?php
$templating->set_previous('meta_description', 'Article Guidelines of posting on ' . $core->config('site_title'), 1);
$templating->set_previous('title', 'Article Writing Guide', 1);

$templating->merge('guidelines');
$templating->block('main');
?>
