<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: mod queue');
}

$templating->set_previous('title', 'Comments & Forum moderation queue', 1);

$templating->load('admin_modules/mod_queue');

$templating->block('top', 'admin_modules/mod_queue');

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'manage')
	{
		/* THIS NEEDS ADJUSTING FOR THE REPLY_TEXT FOR FORUM TOPICS ALONG WITH IS_TOPIC = 1
		New sql to cover all of them, in progress:
		SELECT n.id, ids.user_id, n.created_date, u.username as 'author_username', ids.type, ids.item_id, ids.post_title, ids.post_text, ids.reported_by_id, r.username as 'reporter_username'
		FROM admin_notifications n 
		JOIN (
			(SELECT t.author_id as 'user_id', t.topic_title AS post_title, t.topic_text as post_text, t.topic_id AS item_id, 'mod_queue' AS type, NULL as 'reported_by_id' FROM `forum_topics` t WHERE t.approved = 0)
		UNION
			(SELECT p.author_id as 'user_id', t.topic_title AS post_title, p.reply_text as post_text, p.post_id AS item_id, 'mod_queue_reply' AS type, NULL as 'reported_by_id' FROM `forum_replies` p LEFT JOIN `forum_topics` t ON p.topic_id = t.topic_id WHERE p.approved = 0)
		UNION
			(SELECT c.author_id as 'user_id', a.title AS post_title, c.comment_text as post_text, a.article_id AS item_id, 'mod_queue_comment' AS type, NULL as 'reported_by_id' FROM `articles_comments` c INNER JOIN `articles` a ON a.article_id = c.article_id WHERE c.approved = 0)
		UNION
			(SELECT c.author_id as 'user_id', a.title AS post_title, c.comment_text as post_text, c.comment_id AS item_id, 'reported_comment' AS type, c.spam_report_by as 'reported_by_id' FROM `articles_comments` c INNER JOIN `articles` a ON a.article_id = c.article_id WHERE c.spam = 1)
		) ids ON n.data = ids.item_id AND n.type = ids.type
		LEFT JOIN `users` u ON u.user_id = ids.user_id
		LEFT JOIN `users` r ON r.user_id = ids.reported_by_id
		WHERE n.completed = 0
		ORDER BY n.created_date
		*/
		$topics = $dbl->run("SELECT t.`topic_id`, t.`topic_title`, r.`reply_text`, t.`author_id`, t.`forum_id`, t.`creation_date`, u.`username`, u.`mod_approved` FROM `forum_topics` t JOIN `forum_replies` r ON r.topic_id = t.topic_id AND r.is_topic = 1 INNER JOIN `users` u ON t.`author_id` = u.`user_id` WHERE t.`approved` = 0")->fetch_all();

		if ($topics)
		{
			foreach ($topics as $results)
			{
				$templating->block('approve_forum', 'admin_modules/mod_queue');
				$templating->set('mod_type', 'Forum Topic');
				$templating->set('username', $results['username']);
				$templating->set('topic_id', $results['topic_id']);
				$templating->set('total', $results['mod_approved']);
				$templating->set('post_id', '');
				$templating->set('type', 'forum_topic');
				$templating->set('title', $results['topic_title']);
				$templating->set('text', $bbcode->parse_bbcode($results['reply_text']));
				$templating->set('author_id', $results['author_id']);
				$templating->set('forum_id', $results['forum_id']);
				$templating->set('creation_date', $results['creation_date']);
			}
		}

		$replies = $dbl->run("SELECT t.`topic_id`, t.`topic_title`, p.`post_id`, p.`reply_text`, p.`author_id`, t.`forum_id`, p.`creation_date`, u.`username`, u.`mod_approved` FROM `forum_replies` p INNER JOIN `forum_topics` t ON t.`topic_id` = p.`topic_id` INNER JOIN `users` u ON p.`author_id` = u.`user_id` WHERE p.`approved` = 0 AND p.is_topic = 0")->fetch_all();

		if ($replies)
		{
			foreach ($replies as $results)
			{
				$templating->block('approve_forum', 'admin_modules/mod_queue');
				$templating->set('mod_type', 'Forum Reply');
				$templating->set('username', $results['username']);
				$templating->set('topic_id', $results['topic_id']);
				$templating->set('total', $results['mod_approved']);
				$templating->set('post_id', $results['post_id']);
				$templating->set('type', 'forum_reply');
				$templating->set('title', '<a href="/index.php?module=viewtopic&topic_id='.$results['topic_id'].'">'.$results['topic_title'].'</a>');
				$templating->set('text', $bbcode->parse_bbcode($results['reply_text']));
				$templating->set('author_id', $results['author_id']);
				$templating->set('forum_id', $results['forum_id']);
				$templating->set('creation_date', $results['creation_date']);
			}
		}
		
		$comments = $dbl->run("SELECT a.`article_id`, a.`title`, a.`slug`, c.`comment_id`, c.`comment_text`, c.`author_id`, c.`time_posted`, u.`username`, u.`mod_approved` FROM `articles_comments` c INNER JOIN `articles` a ON a.`article_id` = c.`article_id` INNER JOIN `users` u ON c.`author_id` = u.`user_id` WHERE c.`approved` = 0")->fetch_all();

		if ($comments)
		{
			foreach ($comments as $comment)
			{
				$article_link = $article_class->get_link($comment['article_id'], $comment['slug']);
				
				$templating->block('approve_forum', 'admin_modules/mod_queue');
				$templating->set('mod_type', 'Article Comment');
				$templating->set('username', $comment['username']);
				$templating->set('topic_id', '');
				$templating->set('total', $comment['mod_approved']);
				$templating->set('post_id', $comment['comment_id']);
				$templating->set('type', 'comment');
				$templating->set('title', '<a href="'.$article_link.'">'.$comment['title'].'</a>');
				$templating->set('text', $bbcode->parse_bbcode($comment['comment_text']));
				$templating->set('author_id', $comment['author_id']);
				$templating->set('forum_id', 0);
				$templating->set('creation_date', $comment['time_posted']);
			}
		}
	}

	if (!$topics && !$replies && !$comments)
	{
		$core->message("Nothing to approve!");
	}
}

$templating->block('bottom', 'admin_modules/mod_queue');

if (isset($_POST['action']))
{
	// approve it
	if ($_POST['action'] == 'approve')
	{
		if ($_POST['type'] == 'forum_topic')
		{
			$find_approval = $dbl->run("SELECT `approved`, `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']))->fetch();

			if ($find_approval['approved'] == 0)
			{
				$dbl->run("UPDATE `forum_topics` SET `approved` = 1, `creation_date` = ? WHERE `topic_id` = ?", array(core::$date, $_POST['topic_id']));

				$dbl->run("UPDATE `forum_replies` SET `approved` = 1 WHERE `topic_id` = ? AND `is_topic` = 1", array($_POST['topic_id']));

				$dbl->run("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ?, `posts` = (posts + 1) WHERE `forum_id` = ?", array(core::$date, $_POST['author_id'], $_POST['topic_id'], $_POST['forum_id']));

				$core->update_admin_note(array('type' => 'mod_queue', 'data' => $_POST['topic_id']));

				$core->new_admin_note(array('completed' => 1, 'content' => ' approved a forum topic: <a href="/forum/topic/'.$_POST['topic_id'].'">'.$find_approval['topic_title'].'</a>.'));

				$_SESSION['message'] = 'accepted';
				$_SESSION['message_extra'] = 'post';
				header("Location: /admin.php?module=mod_queue&view=manage");
				die();
			}
			else if ($find_approval['approved'] == 1)
			{
				$_SESSION['message'] = 'already_approved';
				$_SESSION['message_extra'] = 'post';
				header("Location: /admin.php?module=mod_queue&view=manage");
				die();
			}
		}

		else if ($_POST['type'] == 'forum_reply')
		{
			$find_post = $dbl->run("SELECT `approved`, `reply_text` FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']))->fetch();
			if ($find_post['approved'] == 0)
			{
				$dbl->run("UPDATE `forum_replies` SET `approved` = 1, `creation_date` = ? WHERE `post_id` = ?", array(core::$date, $_POST['post_id']));

				$dbl->run("UPDATE `forum_topics` SET `last_post_date` = ?, `last_post_user_id` = ?, `replys` = (replys + 1), `last_post_id` = ? WHERE `topic_id` = ?", array(core::$date, $_POST['author_id'], $_POST['post_id'], $_POST['topic_id']));

				$dbl->run("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ?, `posts` = (posts + 1) WHERE `forum_id` = ?", array(core::$date, $_POST['author_id'], $_POST['topic_id'], $_POST['forum_id']));

				// get article name for the email and redirect
				$topic_info = $dbl->run("SELECT `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']))->fetch();

				// notify editors this was done
				$core->update_admin_note(array('type' => 'mod_queue_reply', 'data' => $_POST['post_id']));

				$core->new_admin_note(array('completed' => 1, 'content' => ' approved a forum reply in: <a href="/forum/topic/'.$_POST['topic_id'].'/post_id='.$_POST['post_id'].'">'.$topic_info['topic_title'].'</a>.'));

				// email anyone subscribed which isn't you
				$email_res = $dbl->run("SELECT s.`user_id`, s.`emails`, u.`email`, u.`username` FROM `forum_topics_subscriptions` s INNER JOIN `users` u ON s.`user_id` = u.`user_id` WHERE s.`topic_id` = ? AND s.`send_email` = 1 AND s.`emails` = 1", array($_POST['topic_id']))->fetch_all();
				$users_array = array();
				foreach ($email_res as $users)
				{
					if ($users['user_id'] != $_POST['author_id'] && $users['emails'] == 1)
					{
						$users_array[$users['user_id']]['user_id'] = $users['user_id'];
						$users_array[$users['user_id']]['email'] = $users['email'];
						$users_array[$users['user_id']]['username'] = $users['username'];
					}
				}

				$author_username = $dbl->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']))->fetch();

				// send the emails
				foreach ($users_array as $email_user)
				{
					$email_message = $bbcode->email_bbcode($find_post['reply_text']);

					// subject
					$subject = "New reply to forum post {$topic_info['topic_title']} on GamingOnLinux.com";

					// message
					$html_message = "
					<p>Hello <strong>{$email_user['username']}</strong>,</p>
					<p><strong>{$author_username['username']}</strong> has replied to a forum topic you follow on titled \"<strong><a href=\"" . $core->config('website_url') . "forum/topic/{$_POST['topic_id']}/post_id={$_POST['post_id']}\">{$topic_info['topic_title']}</a></strong>\". There may be more replies after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
					<div>
					<hr>
					{$email_message}
					<hr>
					You can unsubscribe from this topic by <a href=\"" . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&topic_id={$_POST['topic_id']}&email={$email_user['email']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $core->config('website_url') . "usercp.php\">User Control Panel</a>.";

					$plain_message = "Hello {$email_user['username']}, {$author_username['username']} has replied to a forum topic you follow on titled \"{$topic_info['topic_title']}\". There may be more replies after this one, and you may not get any more emails depending on your email settings in your UserCP. See this new message here: " . $core->config('website_url') . "forum/topic/{$_POST['topic_id']}/post_id={$_POST['post_id']}";

					// Mail it
					if ($core->config('send_emails') == 1)
					{
						$mail = new mailer($core);
						$mail->sendMail($email_user['email'], $subject, $html_message, $plain_message);
					}

					// remove anyones send_emails subscription setting if they have it set to email once
					$update_sub = $dbl->run("SELECT `email_options` FROM `users` WHERE `user_id` = ?", array($email_user['user_id']))->fetch();

					if ($update_sub['email_options'] == 2)
					{
						$dbl->run("UPDATE `forum_topics_subscriptions` SET `send_email` = 0 WHERE `topic_id` = ? AND `user_id` = ?", array($_POST['topic_id'], $email_user['user_id']));
					}
				}

				// update their post counter
				$dbl->run("UPDATE `users` SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($_POST['author_id']));

				// add 1 to their approval rating
				$user_get = $dbl->run("SELECT `mod_approved` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']))->fetch();

				// remove them from the mod queue if we need to
				if ($user_get['mod_approved'] >= 2)
				{
					$dbl->run("UPDATE `users` SET `in_mod_queue` = 0 WHERE `user_id` = ?", array($_POST['author_id']));
				}
				$dbl->run("UPDATE `users` SET `mod_approved` = (mod_approved + 1), `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($_POST['author_id']));

				$_SESSION['message'] = 'accepted';
				$_SESSION['message_extra'] = 'post';
				header("Location: /admin.php?module=mod_queue&view=manage");
				die();
			}
			else if ($find_post['approved'] == 1)
			{
				$_SESSION['message'] = 'already_approved';
				$_SESSION['message_extra'] = 'post';
				header("Location: /admin.php?module=mod_queue&view=manage");
				die();
			}
		}
		if ($_POST['type'] == 'comment')
		{
			$approved = $dbl->run("SELECT c.`approved`, c.`comment_text`, a.`article_id`, a.`title`, u.`username` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id INNER JOIN `users` u ON u.user_id = c.author_id WHERE c.`comment_id` = ?", [$_POST['post_id']])->fetch();
			if ($approved['approved'] == 0)
			{
				// update the news items comment count
				$dbl->run("UPDATE `articles` SET `comment_count` = (comment_count + 1) WHERE `article_id` = ?", [$approved['article_id']]);
				
				$dbl->run("UPDATE `articles_comments` SET `approved` = 1 WHERE `comment_id` = ?", [$_POST['post_id']]);
				
				// add 1 to their approval rating
				$user_get = $dbl->run("SELECT `mod_approved` FROM `users` WHERE `user_id` = ?", [$_POST['author_id']])->fetch();
				
				// remove them from the mod queue if we need to
				if ($user_get['mod_approved'] >= 2)
				{
					$dbl->run("UPDATE `users` SET `in_mod_queue` = 0 WHERE `user_id` = ?", [$_POST['author_id']]);
				}
				
				// update their approved count and comment count
				$dbl->run("UPDATE `users` SET `mod_approved` = (mod_approved + 1), `comment_count` = (comment_count + 1) WHERE `user_id` = ?", [$_POST['author_id']]);

				$new_notification_id = $article_class->quote_notification($approved['comment_text'], $approved['username'], $_POST['author_id'], $approved['article_id'], $_POST['post_id']);
				
				/* gather a list of subscriptions for this article (not including yourself!)
				- Make an array of anyone who needs an email now
				- Additionally, send a notification to anyone subscribed
				*/
				$users_to_email = $dbl->run("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`email_options` FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.user_id != ?", array($approved['article_id'], $_POST['author_id']))->fetch_all();
				$users_array = array();
				foreach ($users_to_email as $email_user)
				{
					// gather list
					if ($email_user['emails'] == 1 && $email_user['send_email'] == 1)
					{
						// use existing key, or generate any missing keys
						if (empty($email_user['secret_key']))
						{
							$secret_key = core::random_id(15);
							$dbl->run("UPDATE `articles_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $email_user['user_id'], $approved['article_id']));
						}
						else
						{
							$secret_key = $email_user['secret_key'];
						}
										
						$users_array[$email_user['user_id']]['user_id'] = $email_user['user_id'];
						$users_array[$email_user['user_id']]['email'] = $email_user['email'];
						$users_array[$email_user['user_id']]['username'] = $email_user['username'];
						$users_array[$email_user['user_id']]['email_options'] = $email_user['email_options'];
						$users_array[$email_user['user_id']]['secret_key'] = $secret_key;
					}

					// notify them, if they haven't been quoted and already given one
					if (!in_array($email_user['username'], $new_notification_id['quoted_usernames']))
					{
						$get_note_info = $dbl->run("SELECT `id`, `article_id`, `seen` FROM `user_notifications` WHERE `article_id` = ? AND `owner_id` = ? AND `type` != 'liked' AND `type` != 'quoted'", array($approved['article_id'], $email_user['user_id']))->fetch();

						if (!$get_note_info)
						{
							$dbl->run("INSERT INTO `user_notifications` SET `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `total` = 1, `type` = 'article_comment'", array($email_user['user_id'],  $_POST['author_id'], $approved['article_id'], $_POST['post_id']));
							$new_notification_id[$email_user['user_id']] = $dbl->new_id();
						}
						else if ($get_note_info)
						{
							if ($get_note_info['seen'] == 1)
							{
								// they already have one, refresh it as if it's literally brand new (don't waste the row id)
								$dbl->run("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `last_date` = ?, `total` = 1, `seen_date` = NULL, `comment_id` = ? WHERE `id` = ?", array($_POST['author_id'], core::$sql_date_now, $_POST['post_id'], $get_note_info['id']));
							}
							else if ($get_note_info['seen'] == 0)
							{
								// they haven't seen the last one yet, so only update the time and date
								$dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `total` = (total + 1) WHERE `id` = ?", array(core::$sql_date_now, $get_note_info['id']));
							}
							
							$new_notification_id[$email_user['user_id']] = $get_note_info['id'];
						}
					}
				}
				
				// send the emails
				foreach ($users_array as $email_user)
				{
					// subject
					$subject = "New reply to article {$approved['title']} on GamingOnLinux.com";

					$comment_email = $bbcode->email_bbcode($approved['comment_text']);
					
					$article_link = $core->config('website_url') . 'index.php?module=articles_full&aid=' . $approved['article_id'] . '&comment_id=' . $_POST['post_id'] . '&clear_note=' . $new_notification_id[$email_user['user_id']];

					// message
					$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
					<p><strong>{$approved['username']}</strong> has replied to an article you follow on titled \"<strong><a href=\"" . $article_link .  "\">{$approved['title']}</a></strong>\". There may be more comments after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
					<div>
					<hr>
					{$comment_email}
					<hr>
					<p>You can unsubscribe from this article by <a href=\"" . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$approved['article_id']}&email={$email_user['email']}&secret_key={$email_user['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $core->config('website_url') . "usercp.php\">User Control Panel</a>.</p>";

					$plain_message = PHP_EOL."Hello {$email_user['username']}, {$approved['username']} replied to an article on GamingOnLinux: " . $article_link . "\r\n\r\n{$approved['comment_text']}\r\n\r\nIf you wish to unsubscribe you can go here: " . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$approved['article_id']}&email={$email_user['email']}&secret_key={$email_user['secret_key']}";

					// Mail it
					if ($core->config('send_emails') == 1)
					{
						$mail = new mailer($core);
						$mail->sendMail($email_user['email'], $subject, $html_message, $plain_message);
					}

					// remove anyones send_emails subscription setting if they have it set to email once
					if ($email_user['email_options'] == 2)
					{
						$dbl->run("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($approved['article_id'], $email_user['user_id']));
					}
				}

				// notify editors this was done
				$core->update_admin_note(array('type' => 'mod_queue_comment', 'data' => $_POST['post_id']));

				$core->new_admin_note(array('completed' => 1, 'content' => ' approved an article comment in: <a href="/articles/'.$approved['article_id'].'/comment_id='.$_POST['post_id'].'">'.$approved['title'].'</a>.'));
				
				$_SESSION['message'] = 'accepted';
				$_SESSION['message_extra'] = 'comment';
				header("Location: /admin.php?module=mod_queue&view=manage");
				die();
			}
			else
			{
				$_SESSION['message'] = 'already_approved';
				$_SESSION['message_extra'] = 'comment';
				header("Location: /admin.php?module=mod_queue&view=manage");
				die();				
			}
		}
	}

	// remove a plain topic, if in mod queue but not spam (eg a message attacking someone, not actually spam, but we don't want it either way)
	if ($_POST['action'] == 'remove')
	{
		if ($_POST['type'] == 'forum_topic')
		{
			// now we can remove the topic
			$dbl->run("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));
			$dbl->run("DELETE FROM `forum_replies` WHERE `topic_id` = ? AND `is_topic` = 1", array($_POST['topic_id']));
			$dbl->run("DELETE FROM `forum_topics_subscriptions` WHERE `topic_id` = ?", array($_POST['topic_id']));

			// notify editors this was done
			$core->update_admin_note(array('type' => 'mod_queue', 'data' => $_POST['topic_id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' removed a forum topic from the mod queue.'));
		}
		else if ($_POST['type'] == 'forum_reply')
		{
			// now we can remove the reply
			$dbl->run("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']));

			// notify editors this was done
			$core->update_admin_note(array('type' => 'mod_queue_reply', 'data' => $_POST['post_id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' removed a forum reply from the mod queue.'));
		}
		else if ($_POST['type'] == 'comment')
		{
			$dbl->run("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array($_POST['post_id']));

			// notify editors this was done
			$core->update_admin_note(array('type' => 'mod_queue_comment', 'data' => $_POST['post_id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' removed an article comment from the mod queue.'));
		}

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'post';
		header("Location: /admin.php?module=mod_queue&view=manage");
		die();
	}

	// ban them and remove the topic
	if ($_POST['action'] == 'remove_ban')
	{
		// get the users information
		$get_details = $dbl->run("SELECT `ip`,`username` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']))->fetch();

		if ($_POST['type'] == 'forum_topic')
		{
			// now we can remove the topic
			$dbl->run("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));

			// notify editors this was done
			$core->update_admin_note(array('type' => 'mod_queue', 'data' => $_POST['topic_id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' removed a forum topic from the mod queue and banned the user <a href="/profiles/'.$_POST['author_id'].'">'.$get_details['username'].'</a>.'));
		}
		else if ($_POST['type'] == 'forum_reply')
		{
			// now we can remove the topic
			$dbl->run("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']));
			// notify editors this was done
			$core->update_admin_note(array('type' => 'mod_queue_reply', 'data' => $_POST['post_id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' removed a forum reply from the mod queue and banned the user <a href="/profiles/'.$_POST['author_id'].'">'.$get_details['username'].'</a>.'));
		}
		else if ($_POST['type'] == 'comment')
		{
			$dbl->run("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array($_POST['post_id']));
			// notify editors this was done
			$core->update_admin_note(array('type' => 'mod_queue_comment', 'data' => $_POST['post_id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' removed an article comment from the mod queue and banned the user <a href="/profiles/'.$_POST['author_id'].'">'.$get_details['username'].'</a>.'));
		}

		// remove any other pending items from this user, as we clearly don't want them
		$dbl->run("DELETE FROM `articles_comments` WHERE `approved` = 0 AND `author_id` = ?", array($_POST['author_id']));
		$dbl->run("DELETE FROM `forum_topics` WHERE `approved` = 0 AND `author_id` = ?", array($_POST['author_id']));
		$dbl->run("DELETE FROM `forum_replies` WHERE `approved` = 0 AND `author_id` = ?", array($_POST['author_id']));

		// remove their other pending admin notifications for the above removals
		$dbl->run("DELETE FROM `admin_notifications` WHERE `user_id` = ? AND `type` IN ('mod_queue', 'mod_queue_reply', 'mod_queue_comment')", array($_POST['author_id']));

		// do the ban as well
		$dbl->run("UPDATE `users` SET `banned` = 1 WHERE `user_id` = ?", array($_POST['author_id']));
		$dbl->run("INSERT INTO `ipbans` SET `ip` = ?", array($get_details['ip']));

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'post';
		header("Location: /admin.php?module=mod_queue&view=manage");
		die();
	}
}
?>
