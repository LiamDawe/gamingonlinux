<?php
$templating->set_previous('meta_description', 'Rules of posting on ' . $core->config('site_title'), 1);
$templating->set_previous('title', 'Posting rules', 1);

$templating->merge('rules');
$templating->block('main');

$templating->set('rules',$core->config('rules'));
?>
