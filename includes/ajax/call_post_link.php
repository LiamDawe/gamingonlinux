<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

// setup the templating, if not logged in default theme, if logged in use selected theme
include($file_dir . '/includes/class_template.php');
$templating = new template($core, $core->config('template'));

include($file_dir . '/includes/class_forum.php');
$forum_class = new forum_class($dbl, $core);

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
				$article_class = new article_class($dbl);
				
				$permalink = article_class::get_link($permalink_info['article_id'], $permalink_info['slug'], 'comment_id=' . $permalink_info['comment_id']);
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
			$permalink_info = $db->sqlquery("SELECT `topic_id`, `post_id` FROM `forum_replies` WHERE `post_id` = ?", [$post_id])->fetch();
			if ($permalink_info)
			{
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
