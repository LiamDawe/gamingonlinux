<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_template.php');
$templating = new template('default');

if(isset($_GET['post_id']))
{
	$post_id = (int) $_GET['post_id'];
	$templating->load('post_link');
	$templating->block('main');
	
	if (isset($_GET['type']))
	{
		if ($_GET['type'] == 'comment')
		{
			$db->sqlquery("SELECT c.`comment_id`, a.`article_id`, a.`slug` FROM `articles_comments` c LEFT JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array($post_id));
			$exists = $db->num_rows();
			if ($exists == 1)
			{
				$permalink_info = $db->fetch();
				
				include($file_dir . '/includes/class_article.php');
				$article_class = new article_class();
				
				$permalink = core::config('website_url') . article_class::get_link($permalink_info['article_id'], $permalink_info['slug'], 'comment_id=' . $permalink_info['comment_id']);
				$templating->set('permalink', $permalink);
			}
			else
			{
				$core->message('That comment does not exist!');
			}
		}
		
		if ($_GET['type'] == 'forum_topic')
		{
			$db->sqlquery("SELECT `topic_id` FROM `forum_topics` WHERE `topic_id` = ?", array($post_id));
			$exists = $db->num_rows();
			if ($exists == 1)
			{
				$permalink_info = $db->fetch();
				
				include($file_dir . '/includes/class_forum.php');
				$forum_class = new forum_class();
				
				$permalink = forum_class::get_link($permalink_info['topic_id']);
				$templating->set('permalink', $permalink);
			}
			else
			{
				$core->message('That forum topic does not exist!');
			}
		}
		
		if ($_GET['type'] == 'forum_reply')
		{
			$db->sqlquery("SELECT `topic_id`, `post_id` FROM `forum_replies` WHERE `post_id` = ?", array($post_id));
			$exists = $db->num_rows();
			if ($exists == 1)
			{
				$permalink_info = $db->fetch();
				
				include($file_dir . '/includes/class_forum.php');
				$forum_class = new forum_class();
				
				$permalink = forum_class::get_link($permalink_info['topic_id'], 'post_id=' . $permalink_info['post_id']);
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
