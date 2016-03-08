<?php
$templating->merge('admin_blocks/admin_block_videos');
$templating->block('main');

// count any submitted videos for review
$db->sqlquery("SELECT `id` FROM `videos` WHERE `approved` = 0");
$submitted_count = $db->num_rows();

if ($submitted_count > 0)
{
	$templating->set('submitted_count', "<span class=\"badge badge-important\">$submitted_count</span>");
}

else if ($submitted_count == 0)
{
	$templating->set('submitted_count', "($submitted_count)");
}
