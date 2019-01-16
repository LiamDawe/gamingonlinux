<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'About Us', 1);
$templating->set_previous('meta_description', 'About Us information for GamingOnLinux', 1);

if ($filecache->check_cache('about_us', 604800)) // 1 week cache, almost never needed to update
{
	$templating->get_cache('about_us');
}
else
{
	echo $templating->output();
	$filecache->init();

	$templating->load('about_us');
	$templating->block('top');
	$templating->set('about_text', $core->config('about_text'));

	$get_editors = $dbl->run("SELECT u.`username`, u.`user_id`, u.`supporter_link`, u.`avatar`, u.`avatar_uploaded`, u.`avatar_gallery`, u.`article_bio` FROM `users` u INNER JOIN `user_group_membership` g ON u.user_id = g.user_id WHERE u.`user_id` != 1844 AND g.group_id = 1 OR g.group_id = 2 ORDER BY u.`user_id` = 1 DESC, u.`username` ASC")->fetch_all();

	foreach ($get_editors as $editors)
	{
		if (!empty($editors['article_bio']))
		{
			$templating->block('row');
			$templating->set('user_id', $editors['user_id']);
			$templating->set('username', $editors['username']);
			$templating->set('bio', $bbcode->parse_bbcode($editors['article_bio']));
		}
	}

	$templating->block('contributors_top');

	$get_editors = $dbl->run("SELECT u.`username`, u.`user_id`, u.`supporter_link`, u.`avatar`, u.`avatar_uploaded`, u.`avatar_gallery`, u.`article_bio` FROM `users` u INNER JOIN `user_group_membership` g ON u.user_id = g.user_id WHERE g.group_id = 5 ORDER BY u.`username` ASC")->fetch_all();
	
	foreach ($get_editors as $editors)
	{
		if (!empty($editors['article_bio']))
		{
			$templating->block('row');
			$templating->set('user_id', $editors['user_id']);
			$templating->set('username', $editors['username']);
			$templating->set('bio', $bbcode->parse_bbcode($editors['article_bio']));
		}
	}

	echo $templating->output();

	$filecache->write('about_us');
}
?>
