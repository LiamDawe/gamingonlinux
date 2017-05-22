<?php
$templating->load('admin_blocks/admin_block_mod_queue');

$db->sqlquery("SELECT COUNT(`id`) as count FROM `admin_notifications` WHERE `type` IN('mod_queue','mod_queue_reply', 'mod_queue_comment') AND `completed` = 0");
$post_counter = $db->fetch();

$templating->block('main');

if ($post_counter['count'] > 0)
{
	$templating->set('post_counter', "<span class=\"badge badge-important\">{$post_counter['count']}</span>");
}

else if ($post_counter['count'] == 0)
{
	$templating->set('post_counter', "({$post_counter['count']})");
}
