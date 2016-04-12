<?php
$templating->set_previous('title', 'About Us', 1);
$templating->set_previous('meta_description', 'About Us information for GamingOnLinux.com', 1);

$templating->merge('about_us');
$templating->block('top');

$db->sqlquery("SELECT `user_id`, `username`, `article_bio` FROM `users` WHERE `user_group` IN (1,2) ORDER BY `user_id`");
while ($editors = $db->fetch())
{
	if (empty($editors['article_bio'])) continue; //Skip user if they have nothing to say
	$templating->block('row');
	$templating->set('user_id', $editors['user_id']);
	$templating->set('username', $editors['username']);
	$templating->set('bio', bbcode($editors['article_bio']));
}
?>
