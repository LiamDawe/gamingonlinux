<?php
if (core::config('forum_posting_open') == 1)
{
	$forum_id = $_GET['forum_id'];
	$topic_id = $_GET['topic_id'];

	$core->forum_permissions($forum_id);

	$db->sqlquery("SELECT t.`topic_title`, t.`replys`, t.`is_locked`, t.`is_sticky`, f.`name`, f.`forum_id` FROM `forum_topics` t JOIN `forums` f ON t.`forum_id` = f.`forum_id` WHERE topic_id = ?", array($topic_id));

	$name = $db->fetch();

	// check topic exists
	if ($db->num_rows() != 1)
	{
		$core->message('That is not a valid forum topic!');
	}

	// permissions for forum
	else if($parray['reply'] == 0)
	{
		$core->message('You do not have permission to post in this forum!');
	}

	// if the user wants to make their reply
	else if (isset($_POST['act']))
	{
		if ($_POST['act'] == 'Add')
		{
			if ($name['is_locked'] == 1 && $user->check_group([1,2]) == false)
			{
				$core->message("This topic is locked! <a href=\"index.php?module=viewtopic&amp;topic_id={$topic_id}\">Click here to go back to the topic</a>.");
			}

			else
			{
				// make safe
				$message = core::make_safe($_POST['text']);
				$message = trim($message);
				$author = $_SESSION['user_id'];

				// check empty
				if (empty($message))
				{
					$core->message('You have to enter a message to post a new reply!');
				}

				else
				{
					$mod_sql = '';
					if (!empty($_POST['moderator_options']))
					{
						if ($_POST['moderator_options'] == 'sticky')
						{
							$mod_sql = '`is_sticky` = 1,';
						}

						if ($_POST['moderator_options'] == 'unsticky')
						{
							$mod_sql = '`is_sticky` = 0,';
						}

						if ($_POST['moderator_options'] == 'lock')
						{
							$mod_sql = '`is_locked` = 1,';
						}

						if ($_POST['moderator_options'] == 'unlock')
						{
							$mod_sql = '`is_locked` = 0,';
						}

						if ($_POST['moderator_options'] == 'bothunlock')
						{
							$mod_sql = '`is_locked` = 0,`is_sticky` = 1,';
						}

						if ($_POST['moderator_options'] == 'bothunsticky')
						{
							$mod_sql = '`is_locked` = 1,`is_sticky` = 0,';
						}

						if ($_POST['moderator_options'] == 'bothundo')
						{
							$mod_sql = '`is_locked` = 0,`is_sticky` = 0,';
						}

						if ($_POST['moderator_options'] == 'both')
						{
							$mod_sql = '`is_locked` = 1,`is_sticky` = 1,';
						}
					}

					// see if we need to add it into the mod queue
					$db->sqlquery("SELECT `in_mod_queue` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
					$check_queue = $db->fetch();

					$approved = 1;
					if ($check_queue['in_mod_queue'] == 1)
					{
						$approved = 0;
					}

					// add the reply
					$db->sqlquery("INSERT INTO `forum_replies` SET `topic_id` = ?, `author_id` = ?, `reply_text` = ?, `creation_date` = ?, `approved` = ?", array($topic_id, $author, $message, core::$date, $approved));
					$post_id = $db->grab_id();

					// update user post counter
					if ($approved == 1)
					{
						$db->sqlquery("UPDATE `users` SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($author));

						// update forums post counter and last post info
						$db->sqlquery("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($author, core::$date, $topic_id, $forum_id));

						// update topic reply count and last post info
						$db->sqlquery("UPDATE `forum_topics` SET `replys` = (replys + 1), `last_post_date` = ?, $mod_sql `last_post_id` = ? WHERE `topic_id` = ?", array(core::$date, $author, $topic_id));

						// get article name for the email and redirect
						$db->sqlquery("SELECT `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($topic_id));
						$title = $db->fetch();

						// see if they are subscribed right now, if they are and they untick the subscribe box, remove their subscription as they are unsubscribing
						$db->sqlquery("SELECT `topic_id`, `emails`, `send_email` FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $topic_id));
						if ($db->num_rows() == 1)
						{
							if (!isset($_POST['subscribe']))
							{
								$db->sqlquery("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $topic_id));
							}
						}

						// are we subscribing?
						if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
						{
							$emails = 0;
							if ($_POST['subscribe-type'] == 'sub-emails')
							{
								$emails = 1;
							}

							$forum_class->subscribe($topic_id, $emails);
						}

						// email anyone subscribed which isn't you
						$db->sqlquery("SELECT s.`user_id`, s.`emails`, s.`secret_key`, u.email, u.username FROM `forum_topics_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`topic_id` = ? AND s.send_email = 1 AND s.emails = 1", array($topic_id));
						$users_array = array();
						while ($users = $db->fetch())
						{
							if ($users['user_id'] != $_SESSION['user_id'] && $users['emails'] == 1)
							{
								// use existing key, or generate any missing keys
								if (empty($users['secret_key']))
								{
									$secret_key = core::random_id(15);
									$db->sqlquery("UPDATE `forum_topics_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `topic_id` = ?", array($secret_key, $users['user_id'], $topic_id));

								}
								else
								{
									$secret_key = $email_user['secret_key'];
								}
								$users_array[$users['user_id']]['user_id'] = $users['user_id'];
								$users_array[$users['user_id']]['email'] = $users['email'];
								$users_array[$users['user_id']]['username'] = $users['username'];
								$users_array[$users['user_id']]['secret_key'] = $secret_key;
							}
						}

						// send the emails
						foreach ($users_array as $email_user)
						{
							$email_message = email_bbcode($_POST['text']);

							$to  = $email_user['email'];

							// subject
							$subject = "New reply to forum post {$title['topic_title']} on GamingOnLinux.com";

							// message
							$message = "
							<html>
							<head>
							<title>New reply to a forum topic you follow on GamingOnLinux.com</title>
							</head>
							<body>
							<img src=\"" . core::config('website_url') . "templates/default/images/icon.png\" alt=\"Gaming On Linux\">
							<br />
							<p>Hello <strong>{$email_user['username']}</strong>,</p>
							<p><strong>{$_SESSION['username']}</strong> has replied to a forum topic you follow on titled \"<strong><a href=\"" . core::config('website_url') . "forum/topic/{$topic_id}/post_id={$post_id}\">{$title['topic_title']}</a></strong>\". There may be more replies after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
							<div>
							<hr>
					 		{$email_message}
					 		<hr>
							You can unsubscribe from this topic by <a href=\"" . core::config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&topic_id={$topic_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . core::config('website_url') . "usercp.php\">User Control Panel</a>.
							<hr>
					  		<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
					  		<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
					  		<p>-----------------------------------------------------------------------------------------------------------</p>
							</div>
							</body>
							</html>
							";

							// To send HTML mail, the Content-type header must be set
							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
							$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: ".core::genReplyAddress($topic_id,'forum')."\r\n";

							// Mail it
							if (core::config('send_emails') == 1)
							{
								mail($to, $subject, $message, $headers);
							}

							// remove anyones send_emails subscription setting if they have it set to email once
							$db->sqlquery("SELECT `email_options` FROM `users` WHERE `user_id` = ?", array($email_user['user_id']));
							$update_sub = $db->fetch();

							if ($update_sub['email_options'] == 2)
							{
								$db->sqlquery("UPDATE `forum_topics_subscriptions` SET `send_email` = 0 WHERE `topic_id` = ? AND `user_id` = ?", array($topic_id, $email_user['user_id']));
							}
						}

						// help stop double postings
						unset($message);

						if (core::config('pretty_urls') == 1)
						{
							header("Location: /forum/topic/{$topic_id}/post_id={$post_id}");
						}
						else
						{
							header("Location: " . core::config('website_url') . "index.php?module=viewtopic&topic_id={$topic_id}&post_id={$post_id}");
						}
					}

					if ($approved == 0)
					{
						// help stop double postings
						unset($message);

						$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `created_date` = ?, `data` = ?, `type` = 'mod_queue_reply'", array($_SESSION['user_id'], core::$date, $post_id));

						header("Location: " . core::config('website_url') . "index.php?module=viewtopic&topic_id={$topic_id}&message=queue");
					}
				}
			}
		}
	}
}
else if (core::config('forum_posting_open') == 0)
{
	$core->message('Posting is currently down for maintenance.');
}
?>
