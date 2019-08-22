<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('admin_blocks/admin_block_goty');
$templating->block('main');

$submitted = $dbl->run("SELECT COUNT(`id`) FROM `goty_games` WHERE `accepted` = 0")->fetchOne();

$templating->set('submitted_count', '<span class="badge">' . $submitted . '</span>');
