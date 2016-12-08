<?php
// main menu block
$templating->merge('admin_blocks/admin_block_main_menu');
$templating->block('menu');
$templating->set('module_links', $module_links);

$db->sqlquery("SELECT COUNT(`row_id`) as counter FROM `livestreams` WHERE `accepted` = 0");
$livestream_counter = $db->fetch();

if ($livestream_counter['counter'] == 0)
{
  $livestream_indicator = '(0)';
}
else if ($livestream_counter['counter'] > 0)
{
  $livestream_indicator = '<span class="badge badge-important">'.$livestream_counter['counter'].'</span>';
}

$templating->set('livestream_indicator', $livestream_indicator);
