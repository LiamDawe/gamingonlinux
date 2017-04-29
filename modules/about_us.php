<?php
$templating->set_previous('title', 'About Us', 1);
$templating->set_previous('meta_description', 'About Us information for ' . core::config('site_title'), 1);

$templating->merge('about_us');
$templating->block('top');
$templating->set('about_text', core::config('about_text'));

$db->sqlquery("SELECT `user_id`, `username`, `article_bio` FROM `".$dbl->table_prefix."users` WHERE `user_group` IN (1,2) ORDER BY `user_id`");
while ($editors = $db->fetch())
{
	if (!empty($editors['article_bio']))
	{
		$templating->block('row');
		$templating->set('user_id', $editors['user_id']);
		$templating->set('username', $editors['username']);
		$templating->set('bio', $bbcode->parse_bbcode($editors['article_bio']));
	}
}
?>
