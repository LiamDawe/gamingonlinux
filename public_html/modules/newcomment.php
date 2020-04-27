<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
if (!isset($_GET['aid']) || isset($_GET['aid']) && !is_numeric($_GET['aid']))
{
	header("Location: /home/");
	die();
}
if (!isset($_SESSION['user_id']) || isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0)
{
	header("Location: /articles/" . $_GET['aid']);
	die();
}
$templating->set_previous('title', 'Commenting on an article', 1);
if ($_SESSION['user_id'] > 0 && !isset($_SESSION['activated']))
{
	$core->message('You do not have permission to comment! Your account isn\'t activated!');
}
else
{				
	if ($core->config('comments_open') == 1)
	{
		// get article details
		$article = $dbl->run("SELECT `article_id`, `title`, `comments_open` FROM `articles` WHERE `article_id` = ?", array((int) $_GET['aid']))->fetch();

		if ($article['comments_open'] == 1)
		{
			// check they don't already have a reply in the mod queue for this forum topic
			$check_queue = $dbl->run("SELECT COUNT(`comment_id`) FROM `articles_comments` WHERE `approved` = 0 AND `author_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['aid']))->fetchOne();
			if ($check_queue == 0)
			{
				$templating->load('newcomment');
				$templating->block('top');
				$templating->set('title', $article['title']);
				$templating->set('article_id', $_GET['aid']);

				// if they're quoting, get the details
				$comment = '';
				if (isset($_GET['qid']) && is_numeric($_GET['qid']))
				{
					$get_comment = $dbl->run("SELECT c.`comment_text`, u.`username` FROM `articles_comments` c LEFT JOIN `users` u ON u.user_id = c.author_id WHERE c.comment_id = ?", array($_GET['qid']))->fetch();
					$comment = '[quote=' . $get_comment['username'] . ']' . $get_comment['comment_text'] . '[/quote]';
				}

				$mod_queue = $user->user_details['in_mod_queue'];
				$forced_mod_queue = $user->can('forced_mod_queue');
	
				if ($forced_mod_queue == true || $mod_queue == 1)
				{
					$core->message('Some comments are held for moderation. Your post may not appear right away.', NULL, 2);
				}
				$subscribe_check = $user->check_subscription($_GET['aid'], 'article');

				$templating->load('articles_full');
				$templating->block('comments_box_top');										
				$templating->set('url', $core->config('website_url'));
				$templating->set('article_id', $_GET['aid']);

				$core->editor(['name' => 'text', 'content' => $comment, 'editor_id' => 'comment']);

				$templating->block('comment_buttons', 'articles_full');
				$templating->set('url', $core->config('website_url'));
				$templating->set('subscribe_check', $subscribe_check['auto_subscribe']);
				$templating->set('subscribe_email_check', $subscribe_check['emails']);
				$templating->set('aid', $_GET['aid']);

				$templating->block('preview', 'articles_full');
			}
			else
			{
				$core->message('You currently have a comment in the moderation queue for this article, you must wait for that to be approved/denied before you can post another reply here.', NULL, 2);
			}
		}
		else
		{
			$core->message('Comments on this article are closed.');
		}
	}
	else
	{
		$core->message('Posting is currently down for maintenance.');
	}
}
?>
