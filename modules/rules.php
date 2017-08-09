<?php
$templating->set_previous('meta_description', 'Rules of posting on GamingOnLinux', 1);
$templating->set_previous('title', 'Posting rules', 1);

$templating->load('rules');
$templating->block('main');

$templating->set('rules',$core->config('rules'));
?>
