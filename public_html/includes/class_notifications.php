<?php
class notifications
{
	// the required database connection
	private $dbl;
	// the requred core class
	private $core;
	
	function __construct($dbl, $core, $bbcode)
	{
		$this->dbl = $dbl;
		$this->core = $core;
		$this->bbcode = $bbcode;
	}
	
	// give a user a notification if their name was quoted in a post somewhere
	function quote_notification($text, $author_username, $author_id, $extra_data)
	{
		$new_notification_id = array();
		
		preg_match_all("~\[(quote)(?:=*[^]]*)\](?>(?R)|.)*?\[/quote]~si", $text, $matches);

		if (!empty($matches[0]))
		{
			$quoted_users = array();
			foreach ($matches[0] as $match)
			{
				preg_match("~\[quote=(.*?)]~si", $match, $username_match);
				if (isset($username_match[1]) && !in_array($username_match[1], $quoted_users))
				{
					$quoted_users[] = $username_match[1];
				}
			}

			if (!empty($quoted_users))
			{
				foreach ($quoted_users as $username)
				{
					if ($username != $author_username) // don't quote notification on the person posting
					{
						// get the user_id of each quoted user if they haven't blocked the user quoting them
						$quoted_user_id = $this->dbl->run("SELECT `user_id` FROM `users` WHERE `username` = ? AND NOT EXISTS (SELECT `user_id` FROM `user_block_list` WHERE `blocked_id` = ? AND `user_id` = users.user_id) AND `display_quote_alerts` = 1", array($username, $author_id))->fetchOne();
						if($quoted_user_id)
						{
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
				$new_notification_id['quoted_usernames'] = $quoted_users;
			}
		}

		return $new_notification_id;
	}

	// notify users of a new post to something they follow or they're quoted
	// NOT FINISHED
	// NEEDS TO ALSO HANDLE FORUM REPLIES
	function notify_users($post_title, $text, $post_author_id, $post_author_username, $type = 'article_comment', $thread_id, $post_id)
	{								
		$new_notification_id = $this->quote_notification($text, $post_author_username, $post_author_id, array('type' => $type, 'thread_id' => $thread_id, 'post_id' => $post_id));

		/* gather a list of subscriptions for this article (not including yourself!)
		- Make an array of anyone who needs an email now
		- Additionally, send a notification to anyone subscribed
		*/
		$users_to_email = $this->dbl->run("SELECT s.`user_id`, s.`emails`, s.`send_email`, s.`secret_key`, u.`email`, u.`username`, u.`email_options`, u.`display_comment_alerts` FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE s.`article_id` = ? AND s.user_id != ? AND NOT EXISTS (SELECT `user_id` FROM `user_block_list` WHERE `blocked_id` = ? AND `user_id` = s.user_id)", array($thread_id, (int) $post_author_id, (int) $post_author_id))->fetch_all();
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
					$this->dbl->run("UPDATE `articles_subscriptions` SET `secret_key` = ? WHERE `user_id` = ? AND `article_id` = ?", array($secret_key, $email_user['user_id'], $thread_id));
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

			// notify them, if they haven't been quoted and already given one and they have comment notifications turned on
			if ($email_user['display_comment_alerts'] == 1)
			{
				if (isset($new_notification_id['quoted_usernames']) && !in_array($email_user['username'], $new_notification_id['quoted_usernames']) || !isset($new_notification_id['quoted_usernames']))
				{
					$get_note_info = $this->dbl->run("SELECT `id`, `article_id`, `seen` FROM `user_notifications` WHERE `article_id` = ? AND `owner_id` = ? AND `type` != 'liked' AND `type` != 'quoted'", array($thread_id, $email_user['user_id']))->fetch();

					if (!$get_note_info)
					{
						$this->dbl->run("INSERT INTO `user_notifications` SET `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `total` = 1, `type` = 'article_comment'", array($email_user['user_id'], (int) $post_author_id, $thread_id, $post_id));
						$new_notification_id[$email_user['user_id']] = $this->dbl->new_id();
					}
					else if ($get_note_info)
					{
						if ($get_note_info['seen'] == 1)
						{
							// they already have one, refresh it as if it's literally brand new (don't waste the row id)
							$this->dbl->run("UPDATE `user_notifications` SET `notifier_id` = ?, `seen` = 0, `last_date` = ?, `total` = 1, `seen_date` = NULL, `comment_id` = ? WHERE `id` = ?", array($post_author_id, core::$sql_date_now, $post_id, $get_note_info['id']));
						}
						else if ($get_note_info['seen'] == 0)
						{
							// they haven't seen the last one yet, so only update the time and date
							$this->dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `total` = (total + 1) WHERE `id` = ?", array(core::$sql_date_now, $get_note_info['id']));
						}

						$new_notification_id[$email_user['user_id']] = $get_note_info['id'];
					}
				}
			}
		}

		// send the emails
		foreach ($users_array as $email_user)
		{
			// subject
			$subject = "New reply to article {$post_title} on GamingOnLinux.com";

			$comment_email = $this->bbcode->email_bbcode($text);

			// message
			$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
			<p><strong>{$post_author_username}</strong> has replied to an article you follow on titled \"<strong><a href=\"" . $this->core->config('website_url') . "index.php?module=articles_full&aid=$thread_id&comment_id={$post_id}&clear_note={$new_notification_id[$email_user['user_id']]}\">{$post_title}</a></strong>\". There may be more comments after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
			<div>
			<hr>
			{$comment_email}
			<hr>
			<p>You can unsubscribe from this article by <a href=\"" . $this->core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$thread_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $this->core->config('website_url') . "usercp.php\">User Control Panel</a>.</p>";

			$plain_message = PHP_EOL."Hello {$email_user['username']}, {$post_author_username} replied to an article on " . $this->core->config('website_url') . "index.php?module=articles_full&aid=$thread_id&comment_id={$post_id}&clear_note={$new_notification_id[$email_user['user_id']]}\r\n\r\n{$text}\r\n\r\nIf you wish to unsubscribe you can go here: " . $this->core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$thread_id}&email={$email_user['email']}&secret_key={$email_user['secret_key']}";

			// Mail it
			if ($this->core->config('send_emails') == 1)
			{
				$mail = new mailer($this->core);
				$mail->sendMail($email_user['email'], $subject, $html_message, $plain_message);
			}

			// remove anyones send_emails subscription setting if they have it set to email once
			if ($email_user['email_options'] == 2)
			{
				$this->dbl->run("UPDATE `articles_subscriptions` SET `send_email` = 0 WHERE `article_id` = ? AND `user_id` = ?", array($thread_id, $email_user['user_id']));
			}
		}
	}

	function load_admin_notifications($last_id = NULL)
	{
		// normal load
		if ($last_id == NULL)
		{
			$grab_notes = $this->dbl->run("SELECT n.*, u.username FROM `admin_notifications` n LEFT JOIN `users` u ON n.user_id = u.user_id ORDER BY n.`id` DESC LIMIT 50")->fetch_all();
		}
		// ajax load more
		else
		{
			$grab_notes = $this->dbl->run("SELECT n.*, u.username FROM `admin_notifications` n LEFT JOIN `users` u ON n.user_id = u.user_id WHERE `id` < ? ORDER BY `id` DESC LIMIT 50", array($_GET['last_id']))->fetch_all();
		}

		$notification_rows = array();

		foreach ($grab_notes as $tracking)
		{
			$completed_indicator = '&#10004;';
			if ($tracking['completed'] == 0)
			{
				$completed_indicator = '<span class="badge badge-important">!</span>';
			}
	
			if (isset($tracking['user_id']) && $tracking['user_id'] > 0)
			{
				$username = '<a href="/profiles/'.$tracking['user_id'].'">' . $tracking['username'] . '</a>';
			}
			else
			{
				$username = 'Guest';
			}

			$notification_rows[] = '<li data-id="'.$tracking['id'].'">' . $completed_indicator . ' ' . $username . ' ' . $tracking['content'] . ' When: ' . $this->core->human_date($tracking['created_date']) . '</li>';
	
			$last_id = $tracking['id'];
		}

		return array('rows' => $notification_rows, 'last_id' => $last_id);
	}
}
?>
