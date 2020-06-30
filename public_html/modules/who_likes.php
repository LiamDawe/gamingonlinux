<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

$templating->set_previous('title', 'Who Likes This Content', 1);
$templating->set_previous('meta_description', 'Who likes this content on GamingOnLinux', 1);

if(isset($_GET['comment_id']) || isset($_GET['article_id']) || isset($_GET['topic_id']) || isset($_GET['reply_id']))
{
	if (isset($_GET['comment_id']))
	{
		$table = 'likes';
		$field = 'data_id';
		$replacer = 'comment_id';
		$type = " AND l.`type` = 'comment'";
		$title_type = "Comment";
		
		$title = $dbl->run("SELECT a.`title` FROM articles_comments c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array($_GET['comment_id']))->fetchOne();
	}
	if (isset($_GET['topic_id']))
	{
		$table = 'likes';
		$field = 'data_id';
		$replacer = 'topic_id';
		$type = " AND l.`type` = 'forum_topic'";
		$title_type = "Forum Topic";
		
		$title = $dbl->run("SELECT `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']))->fetchOne();
	}
	if (isset($_GET['reply_id']))
	{
		$table = 'likes';
		$field = 'data_id';
		$replacer = 'reply_id';
		$type = " AND l.`type` = 'forum_reply'";
		$title_type = "Forum Reply";

		$title = $dbl->run("SELECT t.`topic_title` FROM `forum_replies` r INNER JOIN `forum_topics` t ON r.topic_id = t.topic_id WHERE r.`post_id` = ?", array($_GET['reply_id']))->fetchOne();
	}
	if (isset($_GET['article_id']))
	{
		$table = 'article_likes';
		$field = 'article_id';
		$replacer = $field;
		$type = '';
		$title_type = "Article";

		$title = $dbl->run("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']))->fetchOne();
	}
	$grab_users = $dbl->run("SELECT u.`username`, u.`user_id`, u.`avatar_gallery`, u.`avatar`, u.`avatar_uploaded`, l.like_id FROM `users` u INNER JOIN `$table` l ON u.`user_id` = l.`user_id` WHERE l.`$field` = ? $type ORDER BY u.`username` ASC LIMIT 50", array($_GET[$replacer]))->fetch_all();
	if (!$grab_users)
	{
		$core->message('That does not exist!');
	}
	else
	{
		$templating->load('who_likes');

		$templating->block('standalone_top');
		$templating->set('type', $title_type);
		$templating->set('title', $title);

		$templating->block('top');
		$templating->set('modal-standalone', 'modal-standalone');

		foreach($grab_users as $user_who)
		{
			$avatar = $user->sort_avatar($user_who);

			$templating->block('user_row');
			$templating->set('username', $user_who['username']);
			$templating->set('profile_link', '/profiles/' . $user_who['user_id']);
			$templating->set('avatar', $avatar);
		}

		$templating->block('end');
	}
}
