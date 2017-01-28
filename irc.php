<?php
$file_dir = dirname(__FILE__);

include($file_dir . '/includes/header.php');

$templating->set_previous('title', 'GamingOnLinux IRC Chat', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com IRC Chat', 1);

$templating->merge('irc');

$templating->block('main');

$templating->block('irc_main');

include($file_dir . '/includes/footer.php');
