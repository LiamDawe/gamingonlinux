<?php
$templating->set_previous('meta_description', 'Rules of posting on gamingonlinux', 1);
$templating->set_previous('title', 'Posting rules', 1);

$templating->merge('rules');
$templating->block('main');

$templating->set('rules',$config['rules']);
?>
