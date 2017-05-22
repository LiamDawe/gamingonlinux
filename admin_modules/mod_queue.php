<?php
$templating->set_previous('title', 'Comments & Forum moderation queue', 1);

$templating->load('admin_modules/mod_queue');

$templating->block('top', 'admin_modules/mod_queue');

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'manage')
	{
		$topics = $db->sqlquery("SELECT t.`topic_id`, t.`topic_title`, t.`topic_text`, t.`author_id`, t.`forum_id`, t.`creation_date`, u.`username` FROM `forum_topics` t INNER JOIN ".$core->db_tables['users']." u ON t.`author_id` = u.`user_id` WHERE t.`approved` = 0");
		$topic_counter = $db->num_rows();
		if ($topic_counter > 0)
		{
			while ($results = $topics->fetch())
			{
				$templating->block('approve_forum', 'admin_modules/mod_queue');
				$templating->set('mod_type', 'Forum Topic');
				$templating->set('username', $results['username']);
				$templating->set('topic_id', $results['topic_id']);
				$templating->set('post_id', '');
				$templating->set('type', 'forum_topic');
				$templating->set('title', $results['topic_title']);
				$templating->set('text', $bbcode->parse_bbcode($results['topic_text']));
				$templating->set('author_id', $results['author_id']);
				$templating->set('forum_id', $results['forum_id']);
				$templating->set('creation_date', $results['creation_date']);
			}
		}

		$replies = $db->sqlquery("SELECT t.`topic_id`, t.`topic_title`, p.`post_id`, p.`reply_text`, p.`author_id`, t.`forum_id`, p.`creation_date`, u.`username` FROM `forum_replies` p INNER JOIN `forum_topics` t ON t.`topic_id` = p.`topic_id` INNER JOIN ".$core->db_tables['users']." u ON p.`author_id` = u.`user_id` WHERE p.`approved` = 0");
		$reply_counter = $db->num_rows();

		if ($reply_counter > 0)
		{
			while ($results = $replies->fetch())
			{
				$templating->block('approve_forum', 'admin_modules/mod_queue');
				$templating->set('mod_type', 'Forum Reply');
				$templating->set('username', $results['username']);
				$templating->set('topic_id', $results['topic_id']);
				$templating->set('post_id', $results['post_id']);
				$templating->set('type', 'forum_reply');
				$templating->set('title', '<a href="/index.php?module=viewtopic&topic_id='.$results['topic_id'].'">'.$results['topic_title'].'</a>');
				$templating->set('text', $bbcode->parse_bbcode($results['reply_text']));
				$templating->set('author_id', $results['author_id']);
				$templating->set('forum_id', $results['forum_id']);
				$templating->set('creation_date', $results['creation_date']);
			}
		}
		
		$comments = $dbl->run("SELECT a.`article_id`, a.`title`, a.`slug`, c.`comment_id`, c.`comment_text`, c.`author_id`, c.`time_posted`, u.`username` FROM `articles_comments` c INNER JOIN `articles` a ON a.`article_id` = c.`article_id` INNER JOIN ".$core->db_tables['users']." u ON c.`author_id` = u.`user_id` WHERE c.`approved` = 0")->fetch_all();

		if ($comments)
		{
			foreach ($comments as $comment)
			{
				$article_link = $article_class->get_link($comment['article_id'], $comment['slug']);
				
				$templating->block('approve_forum', 'admin_modules/mod_queue');
				$templating->set('mod_type', 'Article Comment');
				$templating->set('username', $comment['username']);
				$templating->set('topic_id', '');
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

	if ($reply_counter == 0 && $topic_counter == 0 && !$comments)
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
			$db->sqlquery("SELECT `approved` FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));
			$find_approval = $db->fetch();
			if ($find_approval['approved'] == 0)
			{
				$db->sqlquery("UPDATE `forum_topics` SET `approved` = 1, `creation_date` = ? WHERE `topic_id` = ?", array(core::$date, $_POST['topic_id']));

				$db->sqlquery("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ?, `posts` = (posts + 1) WHERE `forum_id` = ?", array(core::$date, $_POST['author_id'], $_POST['topic_id'], $_POST['forum_id']));

				$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue'", array(core::$date, $_POST['topic_id']));
				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_approved', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['topic_id']));

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
			$db->sqlquery("SELECT `approved`, `reply_text` FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']));
			$find_post = $db->fetch();
			if ($find_post['approved'] == 0)
			{
				$db->sqlquery("UPDATE `forum_replies` SET `approved` = 1, `creation_date` = ? WHERE `post_id` = ?", array(core::$date, $_POST['post_id']));

				$db->sqlquery("UPDATE `forum_topics` SET `last_post_date` = ?, `last_post_id` = ?, `replys` = (replys + 1) WHERE `topic_id` = ?", array(core::$date, $_POST['author_id'], $_POST['topic_id']));

				$db->sqlquery("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ?, `posts` = (posts + 1) WHERE `forum_id` = ?", array(core::$date, $_POST['author_id'], $_POST['topic_id'], $_POST['forum_id']));

				$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_reply'", array(core::$date, $_POST['post_id']));
				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_reply_approved', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['post_id']));

				// get article name for the email and redirect
				$db->sqlquery("SELECT `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));
				$topic_info = $db->fetch();

				// email anyone subscribed which isn't you
				$db->sqlquery("SELECT s.`user_id`, s.`emails`, u.`email`, u.`username` FROM `forum_topics_subscriptions` s INNER JOIN ".$core->db_tables['users']." u ON s.`user_id` = u.`user_id` WHERE s.`topic_id` = ? AND s.`send_email` = 1 AND s.`emails` = 1", array($_POST['topic_id']));
				$users_array = array();
				while ($users = $db->fetch())
				{
					if ($users['user_id'] != $_POST['author_id'] && $users['emails'] == 1)
					{
						$users_array[$users['user_id']]['user_id'] = $users['user_id'];
						$users_array[$users['user_id']]['email'] = $users['email'];
						$users_array[$users['user_id']]['username'] = $users['username'];
					}
				}

				$db->sqlquery("SELECT `username` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($_POST['author_id']));
				$author_username = $db->fetch();

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
						$mail = new mail($email_user['email'], $subject, $html_message, $plain_message);
						$mail->send();
					}

					// remove anyones send_emails subscription setting if they have it set to email once
					$db->sqlquery("SELECT `email_options` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($email_user['user_id']));
					$update_sub = $db->fetch();

					if ($update_sub['email_options'] == 2)
					{
						$db->sqlquery("UPDATE `forum_topics_subscriptions` SET `send_email` = 0 WHERE `topic_id` = ? AND `user_id` = ?", array($_POST['topic_id'], $email_user['user_id']));
					}
				}

				// update their post counter
				$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($_POST['author_id']));

				// add 1 to their approval rating
				$db->sqlquery("SELECT `mod_approved` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($_POST['author_id']));
				$user_get = $db->fetch();

				// remove them from the mod queue if we need to
				if ($user_get['mod_approved'] >= 2)
				{
					$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `in_mod_queue` = 0 WHERE `user_id` = ?", array($_POST['author_id']));
				}
				$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `mod_approved` = (mod_approved + 1), `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($_POST['author_id']));

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
				$user_get = $dbl->run("SELECT `mod_approved` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", [$_POST['author_id']])->fetch();
				
				// remove them from the mod queue if we need to
				if ($user_get['mod_approved'] >= 2)
				{
					$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `in_mod_queue` = 0 WHERE `user_id` = ?", [$_POST['author_id']]);
				}
				
				// update their approved count and comment count
				$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `mod_approved` = (mod_approved + 1), `comment_count` = (comment_count + 1) WHERE `user_id` = ?", [$_POST['author_id']]);

				$new_notification_id = $article_class->quote_notification($approved['comment_text'], $approved['username'], $_POST['author_id'], $approved['article_id'], $_POST['post_id']);
				
				/* gather a list of subscriptions for this article (not including yourself!)
				- Make an array of anyone who needs an email now
				- Additionally, send a notification to anyone subscribed
				*/
				$db->sqlquery("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`email_options` FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.user_id != ?", array($approved['article_id'], $_POST['author_id']));
				$users_array = array();
				$users_to_email = $db->fetch_all_rows();
				foreach ($users_to_email as $email_user)
				{
					// gather list
					if ($email_user['emails'] == 1 && $email_user['send_email'] == 1)
					{
						// use existing key, or generate any missing keys
						if (empty($email_user['secret_key']))
						{
							$secret_key = core::random_id(15);
							$db->sqlquery("UPDATE `articles_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $email_user['user_id'], $approved['article_id']));
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
						$db->sqlquery("SELECT `id`, `article_id`, `seen` FROM `user_notifications` WHERE `article_id` = ? AND `owner_id` = ? AND `is_like` = 0 AND `is_quote` = 0", array($approved['article_id'], $email_user['user_id']));
						$check_exists = $db->num_rows();
						$get_note_info = $db->fetch();
						if ($check_exists == 0)
						{
							$db->sqlquery("INSERT INTO `user_notifications` SET `date` = ?, `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `total` = 1", array(core::$date, $email_user['user_id'],  $_POST['author_id'], $approved['article_id'], $_POST['post_id']));
							$new_notification_id[$email_user['user_id']] = $db->grab_id();
						}
						else if ($check_exists == 1)
						{
							// they have seen this one before, but kept it, so refresh it as if it's literally brand new (don't waste the row id)
							if ($get_note_info['seen'] == 1)
							{
								$db->sqlquery("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `date` = ?, `total` = 1, `seen_date` = NULL, `comment_id` = ? WHERE `id` = ?", array($_POST['author_id'], core::$date, $_POST['post_id'], $get_note_info['id']));
							}
							// they haven't seen this note before, so add one to the counter and update the date
							else if ($get_note_info['seen'] == 0)
							{
								$db->sqlquery("UPDATE `user_notifications` SET `date` = ?, `total` = (total + 1) WHERE `id` = ?", array(core::$date, $get_note_info['id']));
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

					// message
					$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
					<p><strong>{$approved['username']}</strong> has replied to an article you follow on titled \"<strong><a href=\"" . $core->config('website_url') . "index.php?module=articles_full&aid={$approved['article_id']}&comment_id={$_POST['post_id']}&clear_note={$new_notification_id[$email_user['user_id']]}\">{$approved['title']}</a></strong>\". There may be more comments after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
					<div>
					<hr>
					{$comment_email}
					<hr>
					<p>You can unsubscribe from this article by <a href=\"" . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$approved['article_id']}&email={$email_user['email']}&secret_key={$email_user['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $core->config('website_url') . "usercp.php\">User Control Panel</a>.</p>";

					$plain_message = PHP_EOL."Hello {$email_user['username']}, {$_SESSION['username']} replied to an article on " . $core->config('website_url') . "index.php?module=articles_full&aid=$article_id&comment_id={$_POST['post_id']}&clear_note={$new_notification_id[$email_user['user_id']]}\r\n\r\n{$approved['comment_text']}\r\n\r\nIf you wish to unsubscribe you can go here: " . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$approved['article_id']}&email={$email_user['email']}&secret_key={$email_user['secret_key']}";

					// Mail it
					if ($core->config('send_emails') == 1)
					{
						$mail = new mail($email_user['email'], $subject, $html_message, $plain_message);
						$mail->send();
					}

					// remove anyones send_emails subscription setting if they have it set to email once
					if ($email_user['email_options'] == 2)
					{
						$db->sqlquery("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($approved['article_id'], $email_user['user_id']));
					}
				}
				
				$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_comment'", array(core::$date, $_POST['post_id']));
				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_comment_approved', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['post_id']));
				
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
			$db->sqlquery("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));
			$db->sqlquery("DELETE FROM `forum_topics_subscriptions` WHERE `topic_id` = ?", array($_POST['topic_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue'", array(core::$date, $_POST['topic_id']));
			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_removed', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['topic_id']));
		}
		else if ($_POST['type'] == 'forum_reply')
		{
			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_reply'", array(core::$date, $_POST['post_id']));
			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_removed', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['topic_id']));
		}
		else if ($_POST['type'] == 'comment')
		{
			$db->sqlquery("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array($_POST['post_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_comment'", array(core::$date, $_POST['post_id']));
			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_comment_removed', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['post_id']));
		}

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'post';
		header("Location: /admin.php?module=mod_queue&view=manage");
		die();
	}

	// ban them and remove the topic
	if ($_POST['action'] == 'remove_ban')
	{
		if ($_POST['type'] == 'forum_topic')
		{
			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue'", array(core::$date, $_POST['topic_id']));
		}
		else if ($_POST['is_topic'] == 'forum_reply')
		{
			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_reply'", array(core::$date, $_POST['post_id']));
		}
		else if ($_POST['type'] == 'comment')
		{
			$db->sqlquery("DELETE FROM `articles_comments` WHERE `comment_id` = ?", array($_POST['post_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_comment'", array(core::$date, $_POST['post_id']));
		}

		// do the ban as well
		$db->sqlquery("SELECT `ip` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($_POST['author_id']));
		$get_ip = $db->fetch();

		$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `banned` = 1 WHERE `user_id` = ?", array($_POST['author_id']));

		$db->sqlquery("INSERT INTO `ipbans` SET `ip` = ?", array($get_ip['ip']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_removed_ban', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['topic_id']));

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'post';
		header("Location: /admin.php?module=mod_queue&view=manage");
		die();
	}
}
?>
