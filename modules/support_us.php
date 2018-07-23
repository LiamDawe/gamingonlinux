<?php
$templating->set_previous('meta_description', 'There are ways you can help GamingOnLinux', 1);
$templating->set_previous('title', 'Support GamingOnLinux', 1);

$templating->load('support_us');
$templating->block('main');
$templating->set('config_support_us_text', $core->config('support_us_text'));

$templating->block('supporter_plus_top');
// supporter plus level
$res = $dbl->run("SELECT u.`username`, u.`user_id`, u.`supporter_link`, u.`avatar`, u.`avatar_uploaded`, u.`avatar_gallery` FROM `users` u INNER JOIN `user_group_membership` g ON u.user_id = g.user_id WHERE g.group_id = 9 ORDER BY u.`username` ASC")->fetch_all();

$total = count($res);
$templating->set('total', $total);

foreach ($res as $rowuser)
{
	$templating->block('supporter_plus_row');

	$templating->set('user_id', $rowuser['user_id']);
	$templating->set('username', $rowuser['username']);

	$avatar = $user->sort_avatar($rowuser);
	$templating->set('avatar', $avatar);

	$supporter_link = '';
	if (!empty($rowuser['supporter_link']))
	{
		$supporter_link = " | <a href=\"{$rowuser['supporter_link']}\">Website</a>";
	}
	$templating->set('supporter_link', $supporter_link);
}

$templating->block('supporter_top');
// get supporter list 
$res = $dbl->run("SELECT u.`username`, u.`user_id`, u.`supporter_link`, u.`avatar`, u.`avatar_uploaded`, u.`avatar_gallery` FROM `users` u INNER JOIN `user_group_membership` g6 ON u.user_id = g6.user_id and g6.group_id = 6 
LEFT  JOIN `user_group_membership` g9 ON u.user_id = g9.user_id and g9.group_id = 9 
WHERE g9.group_id is null
ORDER BY u.`username` ASC")->fetch_all();

$total = count($res);
$templating->set('total', $total);

foreach ($res as $rowuser)
{
	$templating->block('supporter_row');

	$templating->set('user_id', $rowuser['user_id']);
	$templating->set('username', $rowuser['username']);

	$avatar = $user->sort_avatar($rowuser);
	$templating->set('avatar', $avatar);

	$supporter_link = '';
	if (!empty($rowuser['supporter_link']))
	{
		$supporter_link = " | <a href=\"{$rowuser['supporter_link']}\">Website</a>";
	}
	$templating->set('supporter_link', $supporter_link);
}

$templating->block('bottom', 'support_us');