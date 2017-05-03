<?php
$templating->set_previous('title', 'Forum moderation queue', 1);

$templating->merge('admin_modules/mod_queue');

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
				$templating->set('username', $results['username']);
				$templating->set('topic_id', $results['topic_id']);
				$templating->set('post_id', '');
				$templating->set('is_topic', 1);
				$templating->set('topic_title', $results['topic_title']);
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
				$templating->set('username', $results['username']);
				$templating->set('topic_id', $results['topic_id']);
				$templating->set('post_id', $results['post_id']);
				$templating->set('is_topic', 0);
				$templating->set('topic_title', '<a href="/index.php?module=viewtopic&topic_id='.$results['topic_id'].'">'.$results['topic_title'].'</a>');
				$templating->set('text', $bbcode->parse_bbcode($results['reply_text']));
				$templating->set('author_id', $results['author_id']);
				$templating->set('forum_id', $results['forum_id']);
				$templating->set('creation_date', $results['creation_date']);
			}
		}
	}

	if ($reply_counter == 0 && $topic_counter == 0)
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
		if ($_POST['is_topic'] == 1)
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

		else if ($_POST['is_topic'] == 0)
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
					<p><strong>{$author_username['username']}</strong> has replied to a forum topic you follow on titled \"<strong><a href=\"" . core::config('website_url') . "forum/topic/{$_POST['topic_id']}/post_id={$_POST['post_id']}\">{$topic_info['topic_title']}</a></strong>\". There may be more replies after this one, and you may not get any more emails depending on your email settings in your UserCP.</p>
					<div>
					<hr>
					{$email_message}
					<hr>
					You can unsubscribe from this topic by <a href=\"" . core::config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&topic_id={$_POST['topic_id']}&email={$email_user['email']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . core::config('website_url') . "usercp.php\">User Control Panel</a>.";

					$plain_message = "Hello {$email_user['username']}, {$author_username['username']} has replied to a forum topic you follow on titled \"{$topic_info['topic_title']}\". There may be more replies after this one, and you may not get any more emails depending on your email settings in your UserCP. See this new message here: " . core::config('website_url') . "forum/topic/{$_POST['topic_id']}/post_id={$_POST['post_id']}";

					// Mail it
					if (core::config('send_emails') == 1)
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
		}
		else if ($find_post['approved'] == 1)
		{
			$_SESSION['message'] = 'already_approved';
			$_SESSION['message_extra'] = 'post';
			header("Location: /admin.php?module=mod_queue&view=manage");
			die();
		}
	}

	// remove a plain topic, if in mod queue but not spam (eg a message attacking someone, not actually spam, but we don't want it either way)
	if ($_POST['action'] == 'remove')
	{
		if ($_POST['is_topic'] == 1)
		{
			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue'", array(core::$date, $_POST['topic_id']));
		}
		else if ($_POST['is_topic'] == 0)
		{
			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_reply'", array(core::$date, $_POST['post_id']));
		}

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = 'mod_queue_removed', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['topic_id']));

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'post';
		header("Location: /admin.php?module=mod_queue&view=manage");
		die();
	}

	// ban them and remove the topic
	if ($_POST['action'] == 'remove_ban')
	{
		if ($_POST['is_topic'] == 1)
		{
			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue'", array(core::$date, $_POST['topic_id']));
		}
		else if ($_POST['is_topic'] == 0)
		{
			// now we can remove the topic
			$db->sqlquery("DELETE FROM `forum_replies` WHERE `post_id` = ?", array($_POST['post_id']));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'mod_queue_reply'", array(core::$date, $_POST['post_id']));
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
