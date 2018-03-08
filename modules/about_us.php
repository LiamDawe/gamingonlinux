<?php
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
	
	$get_editors = $dbl->run("SELECT u.`user_id`, u.`username`, u.`article_bio` FROM `users` u INNER JOIN `user_group_membership` g ON u.user_id = g.user_id WHERE g.group_id IN (1,2) ORDER BY u.`user_id`")->fetch_all();
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
