<?php
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