<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('admin_blocks/admin_block_games');
$templating->block('main');

// count any submitted tags for review
$review_count = $dbl->run("SELECT COUNT(*) FROM `game_genres_suggestions`")->fetchOne();
if ($review_count > 0)
{
	$templating->set('tag_count', "<span class=\"badge badge-important\">$review_count</span>");
}
else if ($review_count == 0)
{
	$templating->set('tag_count', "(0)");
}

// count any submitted items for review
$items_count = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `approved` = 0")->fetchOne();
$devs_count = $dbl->run("SELECT COUNT(*) FROM `developers` WHERE `approved` = 0")->fetchOne();
$total_count = $items_count + $devs_count;
if ($total_count > 0)
{
	$templating->set('items_count', "<span class=\"badge badge-important\">$total_count</span>");
}
else if ($total_count == 0)
{
	$templating->set('items_count', "(0)");
}