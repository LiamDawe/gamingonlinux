<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('admin_blocks/admin_block_mod_queue');

$post_counter = $dbl->run("SELECT COUNT(`id`) as count FROM `admin_notifications` WHERE `type` IN('mod_queue','mod_queue_reply', 'mod_queue_comment') AND `completed` = 0")->fetchOne();

$templating->block('main');

if ($post_counter > 0)
{
	$templating->set('post_counter', "<span class=\"badge badge-important\">{$post_counter}</span>");
}

else if ($post_counter == 0)
{
	$templating->set('post_counter', '(0)');
}
