<?php
$templating->set_previous('meta_description', 'There are ways you can help ' . $core->config('site_title'), 1);
$templating->set_previous('title', 'Support ' . $core->config('site_title'), 1);

$templating->load('support_us');
$templating->block('main');
$templating->set('config_support_us_text', $core->config('support_us_text'));

$templating->block('list_top');

// get supporter list Sorted by last login
$res = $db->sqlquery("SELECT u.`username`, u.`user_id`, u.`supporter_link` FROM `users` u INNER JOIN ".$core->db_tables['user_group_membership']." g ON u.user_id = g.user_id WHERE g.group_id = 6 ORDER BY RAND() DESC LIMIT 9");
$templating->block('list_row_start');
while ($rowuser = $res->fetch())
{
	$templating->block('person');

	$templating->set('user_id', $rowuser['user_id']);
	$templating->set('username', $rowuser['username']);

	$avatar = $user->sort_avatar($rowuser['user_id']);
	$templating->set('avatarurl', $avatar);

	$supporter_link = '';
	if (!empty($rowuser['supporter_link']))
	{
		$supporter_link = "<a href=\"{$rowuser['supporter_link']}\">{$rowuser['supporter_link']}</a>";
	}
	$templating->set('supporter_link', $supporter_link);
}
$templating->block('list_row_end');
