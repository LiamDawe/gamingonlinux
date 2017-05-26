<?php
define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$forum_class = new forum($dbl, $core);
$templating = new template($core, $core->config('template'));

if(isset($_GET['post_id']))
{
	$post_id = (int) $_GET['post_id'];
	$templating->load('post_link');
	$templating->block('main');
	
	if (isset($_GET['type']))
	{
		if ($_GET['type'] == 'comment')
		{
			$permalink_info = $dbl->run("SELECT c.`comment_id`, a.`article_id`, a.`slug` FROM `articles_comments` c LEFT JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", [$post_id])->fetch();
			if ($permalink_info)
			{
				include($file_dir . '/includes/class_article.php');
				$article_class = new article($dbl, $core, $plugins);
				
				$permalink = $article_class->get_link($permalink_info['article_id'], $permalink_info['slug'], 'comment_id=' . $permalink_info['comment_id']);
				$templating->set('permalink', $permalink);
			}
			else
			{
				$core->message('That comment does not exist!');
			}
		}
		
		if ($_GET['type'] == 'forum_topic')
		{
			$permalink_info = $dbl->run("SELECT `topic_id` FROM `forum_topics` WHERE `topic_id` = ?", [$post_id])->fetch();
			if ($permalink_info)
			{
				$permalink = $forum_class->get_link($permalink_info['topic_id']);
				$templating->set('permalink', $permalink);
			}
			else
			{
				$core->message('That forum topic does not exist!');
			}
		}
		
		if ($_GET['type'] == 'forum_reply')
		{
			$permalink_info = $dbl->run("SELECT `topic_id`, `post_id` FROM `forum_replies` WHERE `post_id` = ?", [$post_id])->fetch();
			if ($permalink_info)
			{
				$permalink = $forum_class->get_link($permalink_info['topic_id'], 'post_id=' . $permalink_info['post_id']);
				$templating->set('permalink', $permalink);
			}
			else
			{
				$core->message('That forum topic does not exist!');
			}
		}
	}
	else
	{
		$core->message('The type of message was not set, if you got here this is likely a bug please let us know!');
	}

	echo $templating->output();
}
