<?php
define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_GET['comment_id']) || isset($_GET['article_id']) || isset($_GET['topic_id']))
{
	if (isset($_GET['comment_id']))
	{
		$table = 'likes';
		$field = 'data_id';
		$replacer = 'comment_id';
		$type = " AND l.`type` = 'comment'";
	}
	if (isset($_GET['topic_id']))
	{
		$table = 'likes';
		$field = 'data_id';
		$replacer = 'topic_id';
		$type = " AND l.`type` = 'forum_topic'";
	}
	if (isset($_GET['article_id']))
	{
		$table = 'article_likes';
		$field = 'article_id';
		$replacer = $field;
		$type = '';
	}
	$grab_users = $dbl->run("SELECT u.`username`, u.`user_id`, u.`avatar_gallery`, u.`avatar`, u.`avatar_uploaded`, l.like_id FROM `users` u INNER JOIN `$table` l ON u.`user_id` = l.`user_id` WHERE l.`$field` = ? $type ORDER BY u.`username` ASC LIMIT 50", array($_GET[$replacer]))->fetch_all();
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
			$avatar = $user->sort_avatar($user_who);

			$templating->block('user_row');
			$templating->set('username', $user_who['username']);
			$templating->set('profile_link', '/profiles/' . $user_who['user_id']);
			$templating->set('avatar', $avatar);
		}

		$templating->block('end');
		echo '<div class="who_likes_div">' . $templating->output() . '</div>';
	}
}
