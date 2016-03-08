<?php
$templating->set_previous('meta_description', 'There are ways you can help GamingOnLinux', 1);
$templating->set_previous('title', 'Support GamingOnLinux!', 1);

$templating->merge('support_us');
$templating->block('main');

$templating->block('list_top');

// get supporter list
$db->sqlquery("SELECT `username`, `user_id`, `supporter_link` FROM `users` WHERE `secondary_user_group` IN (6,7) AND `user_group` != 1 AND `user_group` != 2");
while ($supporter_list = $db->fetch())
{
	$templating->block('list_row');
	$templating->set('user_id', $supporter_list['user_id']);
	$templating->set('username', $supporter_list['username']);

	$supporter_link = '';
	if (!empty($supporter_list['supporter_link']))
	{
		$supporter_link = "- <a href=\"{$supporter_list['supporter_link']}\">{$supporter_list['supporter_link']}</a>";
	}
	$templating->set('supporter_link', $supporter_link);
}
?>
