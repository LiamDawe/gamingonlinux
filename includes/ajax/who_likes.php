<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_template.php');
$templating = new template($core, $core->config('template'));

include($file_dir . '/includes/class_user.php');
$user = new user($dbl, $core);

if(isset($_GET['comment_id']) || isset($_GET['article_id']))
{
	if (isset($_GET['comment_id']))
	{
		$table = 'likes';
		$field = 'data_id';
		$replacer = 'comment_id';
	}
	if (isset($_GET['article_id']))
	{
		$table = 'article_likes';
		$field = 'article_id';
		$replacer = $field;
	}
	$grab_users = $dbl->run("SELECT u.`username`, u.`user_id`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_gallery`, u.`avatar`, u.`avatar_gravatar`, u.`avatar_uploaded`, l.like_id FROM `users` u INNER JOIN `$table` l ON u.`user_id` = l.`user_id` WHERE l.`$field` = ? ORDER BY u.`username` ASC LIMIT 50", array($_GET[$replacer]))->fetch_all();
	if (!$grab_users)
	{
		$core->message('That does not exist!');
	}
	else
	{
		$templating->load('who_likes');

		$templating->block('top');

		foreach($grab_users as $user_who)
		{
			if (core::config('pretty_urls') == 1)
			{
				$profile_link = '/profiles/' . $user_who['user_id'];
			}
			else
			{
				$profile_link = '/index.php?module=profile&user_id=' . $user_who['user_id'];
			}

			$avatar = $user->sort_avatar($user_who['user_id']);

			$templating->block('user_row');
			$templating->set('username', $user_who['username']);
			$templating->set('profile_link', $profile_link);
			$templating->set('avatar', $avatar);
		}

		$templating->block('end');
		echo $templating->output();
	}
}
