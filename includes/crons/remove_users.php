<?php
// This cron is for when a user deletes their account, it will search for where they're quoted by another user and re-name them to "Guest"
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$get_users = $dbl->run("SELECT `user_id`, `username`, `remove_comments`, `remove_forum_posts` FROM `remove_users`")->fetch_all();
foreach ($get_users as $user)
{
	if ($user['remove_comments'] == 1)
	{
		$article_ids = [];
		$remove_comments = $dbl->run("SELECT `article_id`, `comment_id` FROM `articles_comments` WHERE `author_id` = ?", array($user['user_id']))->fetch_all();
		foreach ($remove_comments as $removal)
		{
			$dbl->run("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array($removal['comment_id']));

			// update how many comments to remove from the total comments counter for that article
			if (isset($article_ids[$removal['article_id']]))
			{
				$article_ids[$removal['article_id']] = $article_ids[$removal['article_id']] + 1;
			}
			else
			{
				$article_ids[$removal['article_id']] = 1;
			}

			$dbl->run("DELETE FROM `admin_notifications` WHERE `completed` = 0 AND `user_id` = ? AND `type` = 'mod_queue_comment'", array($user['user_id']));
		}

		// loop over article ids, adjust total comment counter now
		foreach ($article_ids as $key => $total)
		{
			$dbl->run("UPDATE `articles` SET `comment_count` = (comment_count - ?) WHERE `article_id` = ?", array($total, $key));
		}
	}
	else
	{
		$dbl->run("UPDATE `articles_comments` SET `author_id` = 0 WHERE `author_id` = ?", array($user_id));
	}

	$topic_ids = [];
	$post_ids = [];
	if ($user['remove_forum_posts'] == 1)
	{
		// remove topics
		$remove_topics = $dbl->run("SELECT `topic_id`, `forum_id` FROM `forum_topics` WHERE `author_id` = ?", array($user['user_id']))->fetch_all();
		foreach ($remove_topics as $topic)
		{
			// count all posts including the topic
			$total_count = 0;
			$current_count = $dbl->run("SELECT COUNT(`post_id`) FROM `forum_replies` WHERE `topic_id` = ?", array($topic['topic_id']))->fetchOne();
			$total_count = $current_count + 1;

			// Here we get each person who has posted along with their post count for the topic ready to remove it from their post count sql
			$posts = $dbl->run("SELECT `author_id` FROM `forum_replies` WHERE `topic_id` = ?", array($topic['topic_id']))->fetch_all();

			$users_posts = array();
			foreach ($posts as $post)
			{
				$user_post_count = $dbl->run("SELECT COUNT(`post_id`) FROM `forum_replies` WHERE `author_id` = ? AND `topic_id` = ?", array($post['author_id'], $topic['topic_id']))->fetchOne();

				if ($post['author_id'] != $user['user_id']) // don't include the deleted user, they don't exist now
				{
					$users_posts[$post['author_id']]['author_id'] = $post['author_id'];
					$users_posts[$post['author_id']]['posts'] = $user_post_count;
				}
			}

			// now we can remove the topic
			$dbl->run("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($topic['topic_id']));

			// now we can remove all replys
			$dbl->run("DELETE FROM `forum_replies` WHERE `topic_id` = ?", array($topic['topic_id']));

			// now update each users post count
			foreach($users_posts as $post)
			{
				$dbl->run("UPDATE `users` SET `forum_posts` = (forum_posts - ?) WHERE `user_id` = ?", array($post['posts'], $post['author_id']));
			}

			// now update the forums post count
			$dbl->run("UPDATE `forums` SET `posts` = (posts - ?) WHERE `forum_id` = ?", array($total_count, $topic['forum_id']));

			// finally check if this is the latest topic we are deleting to update the latest topic info for the forum
			$last_post = $dbl->run("SELECT `last_post_topic_id` FROM `forums` WHERE `forum_id` = ?", array($topic['forum_id']))->fetchOne();

			// if it is then we need to get the *now* newest topic and update the forums info
			if ($last_post == $topic['topic_id'])
			{
				$new_info = $dbl->run("SELECT `topic_id`, `last_post_date`, `last_post_user_id` FROM `forum_topics` WHERE `forum_id` = ? ORDER BY `last_post_date` DESC LIMIT 1", array($topic['forum_id']))->fetch();

				$dbl->run("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($new_info['last_post_date'], $new_info['last_post_user_id'], $new_info['topic_id'], $topic['forum_id']));
			}
		}

		// remove their normal replies now to others topics
		$remove_posts = $dbl->run("SELECT `post_id`, `topic_id` FROM `forum_replies` WHERE `author_id` = ?", array($user['user_id']))->fetch_all();
		foreach ($remove_posts as $removal)
		{
			$dbl->run("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($removal['post_id']));

			// update how many posts to remove from the total replys counter for that forum topic
			if (isset($topic_ids[$removal['topic_id']]))
			{
				$topic_ids[$removal['topic_id']] = $topic_ids[$removal['topic_id']] + 1;
			}
			else
			{
				$topic_ids[$removal['topic_id']] = 1;
			}
		}

		// loop over topic ids, adjust total reply counter now
		foreach ($topic_ids as $key => $total)
		{
			$dbl->run("UPDATE `forum_topics` SET `replys` = (replys - ?) WHERE `topic_id` = ?", array($total, $key));
		}
	}

	else
	{
		$dbl->run("UPDATE `forum_topics` SET `author_id` = 0 WHERE `author_id` = ?", array($user_id));
		$dbl->run("UPDATE `forum_replies` SET `author_id` = 0 WHERE `author_id` = ?", array($user_id));
	}

	// okay, now we need to remove their username from all direct quotes in comments
	$comments = $dbl->run("SELECT `comment_id`, `comment_text` FROM `articles_comments` WHERE `comment_text` LIKE ?", array('%[quote='.$user['username'].']%'))->fetch_all();

	foreach ($comments as $comment)
	{
		$pattern = '/\[quote='.$user['username'].'\]/';
		$replace = '[quote=Guest]';
		while(preg_match($pattern, $comment['comment_text']))
		{
			$comment['comment_text'] = preg_replace($pattern, $replace, $comment['comment_text']);
		}

		$dbl->run("UPDATE `articles_comments` SET `comment_text` = ? WHERE `comment_id` = ?", array($comment['comment_text'], $comment['comment_id']));
	}

	// okay, now we need to remove their username from all direct quotes in forum replies and forum topics (they both now have a row in the same table for the text)
	$forum_posts = $dbl->run("SELECT `post_id`, `reply_text` FROM `forum_replies` WHERE `reply_text` LIKE ?", array('%[quote='.$user['username'].']%'))->fetch_all();

	foreach ($forum_posts as $post)
	{
		$pattern = '/\[quote='.$user['username'].'\]/';
		$replace = '[quote=Guest]';
		while(preg_match($pattern, $post['reply_text']))
		{
			$post['reply_text'] = preg_replace($pattern, $replace, $post['reply_text']);
		}

		$dbl->run("UPDATE `forum_replies` SET `reply_text` = ? WHERE `post_id` = ?", array($post['reply_text'], $post['post_id']));
	}

	// deal with article likes
	$article_likes = $dbl->run("SELECT `article_id` FROM `article_likes` WHERE `user_id` = ?", array($user['user_id']))->fetch_all();
	foreach ($article_likes as $like) // loop over each article, remove a like
	{
		$dbl->run("UPDATE `articles` SET `total_likes` = (total_likes - 1) WHERE `article_id` = ?", array($like['article_id']));
	}
	$dbl->run("DELETE FROM `article_likes` WHERE `user_id` = ?", array($user['user_id'])); // now remove all their likes

	$dbl->run("DELETE FROM `remove_users` WHERE `user_id` = ?", array($user['user_id']));
}

echo 'Done';
?>