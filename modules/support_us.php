<?php
$templating->set_previous('meta_description', 'There are ways you can help GamingOnLinux', 1);
$templating->set_previous('title', 'Support GamingOnLinux', 1);

$templating->load('support_us');
$templating->block('main');
$templating->set('config_support_us_text', $core->config('support_us_text'));

$templating->block('list_top');

// get supporter list Sorted by last login
$res = $dbl->run("SELECT u.`username`, u.`user_id`, u.`supporter_link`, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u.`avatar_gallery` FROM `users` u INNER JOIN `user_group_membership` g ON u.user_id = g.user_id WHERE g.group_id = 6 ORDER BY RAND() DESC LIMIT 9")->fetch_all();
$templating->block('list_row_start');
foreach ($res as $rowuser)
{
	$templating->block('person');

	$templating->set('user_id', $rowuser['user_id']);
	$templating->set('username', $rowuser['username']);

	$avatar = $user->sort_avatar($rowuser);
	$templating->set('avatarurl', $avatar);

	$supporter_link = '';
	if (!empty($rowuser['supporter_link']))
	{
		$supporter_link = "<a href=\"{$rowuser['supporter_link']}\">{$rowuser['supporter_link']}</a>";
	}
	$templating->set('supporter_link', $supporter_link);
}
$templating->block('list_row_end');
