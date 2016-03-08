<?php
$templating->merge('admin_blocks/admin_block_mod_queue');

$db->sqlquery("SELECT COUNT(`topic_id`) as count FROM `admin_notifications` WHERE `mod_queue` = 1 AND `completed` = 0");
$topic_counter = $db->fetch();

$templating->block('main');

if ($topic_counter['count'] > 0)
{
	$templating->set('topic_counter', "<span class=\"badge badge-important\">{$topic_counter['count']}</span>");
}

else if ($topic_counter['count'] == 0)
{
	$templating->set('topic_counter', "({$topic_counter['count']})");
}
