<?php
$templating->load('admin_blocks/admin_block_livestreams');
$templating->block('main');

$livestream_counter = $dbl->run("SELECT COUNT(`row_id`) as counter FROM `livestreams` WHERE `accepted` = 0")->fetchOne();

if ($livestream_counter == 0)
{
	$livestream_indicator = '(0)';
}
else if ($livestream_counter > 0)
{
	$livestream_indicator = '<span class="badge badge-important">'.$livestream_counter.'</span>';
}
$templating->set('livestream_indicator', $livestream_indicator);
