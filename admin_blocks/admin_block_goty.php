<?php
$templating->merge('admin_blocks/admin_block_goty');
$templating->block('main');

$db->sqlquery("SELECT `id` FROM `goty_games` WHERE `accepted` = 0");
$submitted = $db->num_rows();

$templating->set('submitted_count', '<span class="badge">' . $submitted . '</span>');
