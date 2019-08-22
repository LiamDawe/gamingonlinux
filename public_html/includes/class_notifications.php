<?php
class notifications
{
	// the required database connection
	private $dbl;
	// the requred core class
	private $core;
	
	function __construct($dbl, $core)
	{
		$this->dbl = $dbl;
		$this->core = $core;
	}
	
	// give a user a notification if their name was quoted in a post somewhere
	function quote_notification($text, $username, $author_id, $extra_data)
	{
		// $author_id, $article_id, $comment_id
		$new_notification_id = array();
		
		preg_match_all("~\[(quote)(?:=*[^]]*)\](?>(?R)|.)*?\[/quote]~si", $text, $matches);

		if (!empty($matches[0]))
		{
			$quoted_users = array();
			foreach ($matches[0] as $match)
			{
				//echo $match;
				preg_match("~\[quote=(.*?)]~si", $match, $username);
				if (!in_array($username[1], $quoted_users))
				{
					$quoted_users[] = $username[1];
				}
			}

			if (!empty($quoted_users))
			{
				foreach ($quoted_users as $username)
				{
					if ($username != $_SESSION['username'])
					{
						$quoted_user_id = $this->dbl->run("SELECT `user_id` FROM `users` WHERE `username` = ?", array($username))->fetchOne();
						if ($extra_data['type'] == 'article_comment')
						{
							$field1 = 'article_id';
							$field2 = 'comment_id';
						}
						if ($extra_data['type'] == 'forum_reply')
						{
							$field1 = 'forum_topic_id';
							$field2 = 'forum_reply_id';						
						}
						$this->dbl->run("INSERT INTO `user_notifications` SET `seen` = 0, `owner_id` = ?, `notifier_id` = ?, `$field1` = ?, `$field2` = ?, `type` = 'quoted'", array($quoted_user_id, $author_id, $extra_data['thread_id'], $extra_data['post_id']));
						$new_notification_id[$quoted_user_id] = $this->dbl->new_id();
					}
				}
			}
		}

		$new_notification_id['quoted_usernames'] = $quoted_users;

		return $new_notification_id;
	}

	// notify users of a new post to something they follow or they're quoted
	// NOT FINISHED
	// NEEDS TO PULL IN ARTICLE AND FORUM CLASS
	// UPDATE ALL STRINGS WITH DATA PULLED IN
	// THEN TEST
	function notify_users()
	{
		// update the news items comment count
		$dbl->run("UPDATE `articles` SET `comment_count` = (comment_count + 1) WHERE `article_id` = ?", array($article_id));

		// update the posting users comment count
		$dbl->run("UPDATE `users` SET `comment_count` = (comment_count + 1) WHERE `user_id` = ?", array((int) $_SESSION['user_id']));

		// check if they are subscribing
		if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
		{
			$emails = 0;
			if ($_POST['subscribe-type'] == 'sub-emails')
			{
				$emails = 1;
			}
			$article_class->subscribe($article_id, $emails);
		}
								
		$new_notification_id = $this->notifications->quote_notification($comment, $_SESSION['username'], $_SESSION['user_id'], array('type' => 'article_comment', 'thread_id' => $article_id, 'post_id' => $new_comment_id));

		/* gather a list of subscriptions for this article (not including yourself!)
		- Make an array of anyone who needs an email now
		- Additionally, send a notification to anyone subscribed
		*/
		$users_to_email = $dbl->run("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`email_options` FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.user_id != ?", array($article_id, (int) $_SESSION['user_id']))->fetch_all();
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
					$dbl->run("UPDATE `articles_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $email_user['user_id'], $article_id));
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
								if ((isset($new_notification_id['quoted_username']) && $email_user['username'] != $new_notification_id['quoted_username']) || !isset($new_notification_id['quoted_username']))
								{
									$get_note_info = $dbl->run("SELECT `id`, `article_id`, `seen` FROM `user_notifications` WHERE `article_id` = ? AND `owner_id` = ? AND `type` != 'liked' AND `type` != 'quoted'", array($article_id, $email_user['user_id']))->fetch();

									if (!$get_note_info)
									{
										$dbl->run("INSERT INTO `user_notifications` SET `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `total` = 1, `type` = 'article_comment'", array($email_user['user_id'], (int) $_SESSION['user_id'], $article_id, $new_comment_id));
										$new_notification_id[$email_user['user_id']] = $dbl->new_id();
									}
									else if ($get_note_info)
									{
										if ($get_note_info['seen'] == 1)
										{
											// they already have one, refresh it as if it's literally brand new (don't waste the row id)
											$dbl->run("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `last_date` = ?, `total` = 1, `seen_date` = NULL, `comment_id` = ? WHERE `id` = ?", array($_SESSION['user_id'], core::$sql_date_now, $new_comment_id, $get_note_info['id']));
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
								$subject = "New reply to article {$title['title']} on GamingOnLinux.com";

								$comment_email = $bbcode->email_bbcode($comment);

								// message
								$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
								<p><strong>{$_SESSION['username']}</strong> has replied to an article you follow on titled \"<strong><a href=\"" . $core->config('website_url') . "index.php?module=articles_full&aid=$article_id&comment_id={$new_comment_id}&clear_note={$new_notification_id[$email_user['user_id']]}\">{$title['title']}</a></strong>\". There may be more comments after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
								<div>
								<hr>
								{$comment_email}
								<hr>
								<p>You can unsubscribe from this article by <a href=\"" . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $core->config('website_url') . "usercp.php\">User Control Panel</a>.</p>";

								$plain_message = PHP_EOL."Hello {$email_user['username']}, {$_SESSION['username']} replied to an article on " . $core->config('website_url') . "index.php?module=articles_full&aid=$article_id&comment_id={$new_comment_id}&clear_note={$new_notification_id[$email_user['user_id']]}\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: " . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}";

								// Mail it
								if ($core->config('send_emails') == 1)
								{
									$mail = new mailer($core);
									$mail->sendMail($email_user['email'], $subject, $html_message, $plain_message);
								}

								// remove anyones send_emails subscription setting if they have it set to email once
								if ($email_user['email_options'] == 2)
								{
									$dbl->run("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($article_id, $email_user['user_id']));
								}
							}
	}
}
?>
