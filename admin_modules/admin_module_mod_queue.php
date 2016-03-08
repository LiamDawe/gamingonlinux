<?php
$templating->merge('admin_modules/admin_module_mod_queue');

if (!isset($_GET['view']) && !isset($_POST['action']))
{
	$templating->block('top');

	$templating->block('bottom');
}

if (isset($_GET['view']))
{
	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'approved')
		{
			$core->message('You have approved that message');
		}

		if ($_GET['message'] == 'removed')
		{
			$core->message('You have removed that message');
		}
	}

	if ($_GET['view'] == 'forum_topics')
	{
		$db->sqlquery("SELECT t.`topic_id`, t.`topic_title`, t.`topic_text`, t.`author_id`, t.`forum_id`, t.`creation_date`, u.username FROM `forum_topics` t INNER JOIN `users` u ON t.author_id = u.user_id WHERE t.`approved` = 0");

		if ($db->num_rows() == 0)
		{
			$core->message('No topics to display!');
		}
		else
		{
			while ($results = $db->fetch())
			{
					$templating->block('approve_topic', 'admin_modules/admin_module_mod_queue');
					$templating->set('username', $results['username']);
					$templating->set('topic_id', $results['topic_id']);
					$templating->set('topic_title', $results['topic_title']);
					$templating->set('topic_text', $results['topic_text']);
					$templating->set('author_id', $results['author_id']);
					$templating->set('forum_id', $results['forum_id']);
					$templating->set('creation_date', $results['creation_date']);
			}
		}
	}
}

if (isset($_POST['action']))
{
	// approve it, up their approval rating +1
	if ($_POST['action'] == 'approve_topic')
	{
		$db->sqlquery("UPDATE `forum_topics` SET `approved` = 1 WHERE `topic_id` = ?", array($_POST['topic_id']));

		$db->sqlquery("UPDATE `users` SET `mod_approved` = (mod_approved + 1) WHERE `user_id` = ?", array($_POST['author_id']));

		$db->sqlquery("SELECT `mod_approved` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']));
		$user_get = $db->fetch();

		if ($user_get['mod_approved'] >= 2)
		{
			$db->sqlquery("UPDATE `users` SET `in_mod_queue` = 0 WHERE `user_id` = ?", array($_POST['author_id']));
		}

		$db->sqlquery("DELETE FROM `admin_notifications` WHERE `topic_id` = ? AND `mod_queue` = 1", array($_POST['topic_id']));
		$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 1, `created` = ?, `completed_date` = ?, `topic_id` = ?, `mod_queue` = 1", array("{$_SESSION['username']} approved a forum topic", $core->date, $core->date, $_POST['topic_id']));

		// finally check if this is the latest topic we are approving to update the latest topic info for the forum
		$db->sqlquery("SELECT `last_post_time` FROM `forums` WHERE `forum_id` = ?", array($_POST['forum_id']));
		$last_post = $db->fetch();

		// if it is then we need to update the forum to have this topic as it's latest info
		if ($_POST['creation_date'] > $last_post['last_post_time'])
		{
			$db->sqlquery("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($_POST['creation_date'], $_POST['author_id'], $_POST['topic_id'], $_POST['forum_id']));
		}

		$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 1, `created` = ?, `completed_date` = ?, `topic_id` = ?, `mod_queue` = 1", array("{$_SESSION['username']} approved a forum topic to be visible.", $core->date, $core->date, $_POST['topic_id']));

		header("Location: /admin.php?module=mod_queue&view=forum_topics&message=approved");
	}

	// remove a plain topic, if in mod queue but not spam (eg a message attacking someone, not actually spam, but we don't want it either way)
	if ($_POST['action'] == 'remove_topic')
	{
		// now we can remove the topic
		$db->sqlquery("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));

		// now update the forums post count
		$db->sqlquery("UPDATE `forums` SET `posts` = (posts - 1) WHERE `forum_id` = ?", array($_POST['forum_id']));

		// finally check if this is the latest topic we are deleting to update the latest topic info for the forum
		$db->sqlquery("SELECT `last_post_topic_id` FROM `forums` WHERE `forum_id` = ?", array($_POST['forum_id']));
		$last_post = $db->fetch();

		// if it is then we need to get the *now* newest topic and update the forums info
		if ($last_post['last_post_topic_id'] == $_POST['topic_id'])
		{
			$db->sqlquery("SELECT `topic_id`, `last_post_date`, `last_post_id` FROM `forum_topics` WHERE `forum_id` = ? ORDER BY `last_post_date` DESC LIMIT 1", array($_POST['forum_id']));
			$new_info = $db->fetch();

			$db->sqlquery("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($new_info['last_post_date'], $new_info['last_post_id'], $new_info['topic_id'], $_POST['forum_id']));
		}

		$db->sqlquery("DELETE FROM `admin_notifications` WHERE `topic_id` = ? AND `mod_queue` = 1", array($_POST['topic_id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 1, `created` = ?, `completed_date` = ?, `topic_id` = ?, `mod_queue` = 1", array("{$_SESSION['username']} removed a forum topic from the moderation queue.", $core->date, $core->date, $_POST['topic_id']));

		header("Location: /admin.php?module=mod_queue&view=forum_topics&message=removed");
	}

	// ban them and remove the topic
	if ($_POST['action'] == 'remove_topic_ban')
	{
		// now we can remove the topic
		$db->sqlquery("DELETE FROM `forum_topics` WHERE `topic_id` = ?", array($_POST['topic_id']));

		// now update the forums post count
		$db->sqlquery("UPDATE `forums` SET `posts` = (posts - 1) WHERE `forum_id` = ?", array($_POST['forum_id']));

		// finally check if this is the latest topic we are deleting to update the latest topic info for the forum
		$db->sqlquery("SELECT `last_post_topic_id` FROM `forums` WHERE `forum_id` = ?", array($_POST['forum_id']));
		$last_post = $db->fetch();

		// if it is then we need to get the *now* newest topic and update the forums info
		if ($last_post['last_post_topic_id'] == $_POST['topic_id'])
		{
			$db->sqlquery("SELECT `topic_id`, `last_post_date`, `last_post_id` FROM `forum_topics` WHERE `forum_id` = ? ORDER BY `last_post_date` DESC LIMIT 1", array($_POST['forum_id']));
			$new_info = $db->fetch();

			$db->sqlquery("UPDATE `forums` SET `last_post_time` = ?, `last_post_user_id` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($new_info['last_post_date'], $new_info['last_post_id'], $new_info['topic_id'], $_POST['forum_id']));
		}

		// do the ban as well
		$db->sqlquery("SELECT `ip` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']));
		$get_ip = $db->fetch();

		$db->sqlquery("UPDATE `users` SET `banned` = 1 WHERE `user_id` = ?", array($_POST['author_id']));

		$db->sqlquery("INSERT INTO `ipbans` SET `ip` = ?", array($get_ip['ip']));

		$db->sqlquery("DELETE FROM `admin_notifications` WHERE `topic_id` = ? AND `mod_queue` = 1", array($_POST['topic_id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 1, `created` = ?, `completed_date` = ?, `topic_id` = ?, `mod_queue` = 1", array("{$_SESSION['username']} removed a forum topic from the moderation queue, and banned that user.", $core->date, $core->date, $_POST['topic_id']));

		header("Location: /admin.php?module=mod_queue&view=forum_topics&message=removed");
	}
}
