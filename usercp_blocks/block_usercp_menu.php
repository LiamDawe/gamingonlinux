<?php
// main menu block
$templating->merge('usercp_blocks/block_usercp_menu');
$templating->block('menu');
$templating->set('module_links', $module_links);
