<?php
$templating->load('admin_blocks/admin_block_forum');
$templating->block('main');

$admin_links = '';
if ($user->check_group(1) == true)
{
	$admin_links = '<li><a href="admin.php?module=forum&amp;view=category">Add Category</a></li>
	<li><a href="admin.php?module=forum&amp;view=forum">Add Forum</a></li>
	<li><a href="admin.php?module=forum&amp;view=manage">Manage Forums</a></li>';
}
$templating->set('admin_links', $admin_links);

// sort out the counters for topic and reply reports
$db->sqlquery("SELECT `reported` FROM `forum_topics` WHERE `reported` = 1");
$topic_count = $db->num_rows();

if ($topic_count > 0)
{
	$templating->set('topic_count', "<span class=\"badge badge-important\">$topic_count</span>");
}

else if ($topic_count == 0)
{
	$templating->set('topic_count', "($topic_count)");
}

$db->sqlquery("SELECT `reported` FROM `forum_replies` WHERE `reported` = 1");
$replies_count = $db->num_rows();

if ($replies_count > 0)
{
	$templating->set('replies_count', "<span class=\"badge badge-important\">$replies_count</span>");
}

else if ($replies_count == 0)
{
	$templating->set('replies_count', "($replies_count)");
}
