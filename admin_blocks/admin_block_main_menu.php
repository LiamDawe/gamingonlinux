<?php
// main menu block
$templating->merge('admin_blocks/admin_block_main_menu');
$templating->block('menu');
$templating->set('module_links', $module_links);
