<?php
$templating->load('admin_blocks/admin_block_calendar');
$templating->block('main');

// count any submitted games
$db->sqlquery("SELECT `id` FROM `calendar` WHERE `approved` = 0");
$review_count = $db->num_rows();

if ($review_count > 0)
{
	$templating->set('counter', "<span class=\"badge badge-important\">$review_count</span>");
}

else if ($review_count == 0)
{
	$templating->set('counter', "($review_count)");
}
