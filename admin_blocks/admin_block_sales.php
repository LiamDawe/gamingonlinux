<?php
$templating->merge('admin_blocks/admin_block_sales');
$templating->block('main');

// count any reported sales
$db->sqlquery("SELECT `id` FROM `game_sales` WHERE `reported` = 1");
$reported_count = $db->num_rows();

if ($reported_count > 0)
{
	$templating->set('reported_count', "<span class=\"badge badge-important\">$reported_count</span>");
}

else if ($reported_count == 0)
{
	$templating->set('reported_count', "");
}

// count any submitted sales
$db->sqlquery("SELECT `id` FROM `game_sales` WHERE `accepted` = 0");
$submitted_count = $db->num_rows();

if ($submitted_count > 0)
{
	$templating->set('submitted_count', "<span class=\"badge badge-important\">$submitted_count</span>");
}

else if ($submitted_count == 0)
{
	$templating->set('submitted_count', "");
}
