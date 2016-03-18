<?php
$templating->set_previous('title', 'Create new topic', 1);
$templating->merge('newtopic');

$forum_id = $_GET['forum_id'];

$core->forum_permissions($forum_id);

if (!isset($_SESSION['activated']))
{
	$db->sqlquery("SELECT `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$get_active = $db->fetch();
	$_SESSION['activated'] = $get_active['activated'];
}

$tenMinAgo = time() - 600;
$db->sqlquery("SELECT COUNT(author_id) as c FROM `forum_topics` WHERE `author_id` = ? AND `creation_date` >= ?", array($_SESSION['user_id'], $tenMinAgo));
$amountOfPosts = $db->fetch();

// permissions for forum
if($parray['topic'] == 0)
{
	$core->message('You do not have permission to post in this forum!');
}

else if ($amountOfPosts['c'] > 5)
{
	if ($config['pretty_urls'] == 1)
	{
		header("Location: /forum/{$_GET['forum_id']}/message=toomany");
	}
	else
	{
		header("Location: /index.php?module=viewforum&forum_id={$_GET['forum_id']}&message=toomany");
	}
}

else if (!isset($_POST['act']))
{
	$db->sqlquery("SELECT `name` FROM `forums` WHERE forum_id = ?", array($forum_id));
	$name = $db->fetch();

	$title = '';
	$text = '';
	if (isset($_GET['error']))
	{
		if ($_GET['error'] == 'missing')
		{
			$core->message('You have to enter a title and message to post a new topic!', NULL, 1);
		}
		if ($_GET['error'] == 'moreoptions')
		{
			$core->message('Polls need at least two options!', NULL, 1);
		}

		$title = $_SESSION['atitle'];
		$text = $_SESSION['atext'];
	}

	$templating->block('main', 'newtopic');
	$templating->set('forum_name', $name['name']);
	$templating->set('forum_id', $forum_id);

	$options = 'Moderator Options<br />
	<select name="moderator_options"><option value=""></option>';
	$options_count = 0;

	if ($parray['sticky'] == 1)
	{
		$options .= '<option value="sticky">Sticky Topic</option>';
		$options_count++;
	}

	if ($parray['lock'] == 1)
	{
		$options .= '<option value="lock">Lock Topic</option>';
		$options_count++;
	}

	if ($parray['sticky'] == 1 && $parray['lock'] == 1)
	{
		$options .= '<option value="both">Lock & Sticky Topic</option>';
		$options_count++;
	}

	if ($options_count > 0)
	{
		$options .= '</select><br />';
	}

	// if they have no moderator ability then remove the select box altogether
	else
	{
		$options = '';
	}

	// see if we will allow them to make polls
	$db->sqlquery("SELECT `in_mod_queue` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$check_queue = $db->fetch();

	if ($check_queue['in_mod_queue'] == 0)
	{
		$templating->block('poll', 'newtopic');
	}

	$templating->block('top', 'newtopic');

	if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
	{
		$core->editor('text', $text);
	}

	$templating->block('bottom', 'newtopic');
	$templating->set('options', $options);
	$templating->set('forum_id', $forum_id);

}

else if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Add')
	{
		// make safe
		$title = strip_tags($_POST['title']);
		$message = htmlspecialchars($_POST['text'], ENT_QUOTES);
		$message = trim($message);
		$author = $_SESSION['user_id'];

		$mod_sql = '';
		if (!empty($_POST['moderator_options']))
		{
			if ($_POST['moderator_options'] == 'sticky')
			{
				$mod_sql = '`is_sticky` = 1,';
			}

			if ($_POST['moderator_options'] == 'lock')
			{
				$mod_sql = '`is_locked` = 1,';
			}

			if ($_POST['moderator_options'] == 'both')
			{
				$mod_sql = '`is_locked` = 1,`is_sticky` = 1,';
			}
		}

		// check empty
		if (empty($title) || empty($message))
		{
			$_SESSION['atitle'] = $title;
			$_SESSION['atext'] = $message;

			header("Location: /index.php?module=newtopic&forum_id=$forum_id&error=missing");
		}

		else
		{
			// see if we need to add it into the mod queue
			$db->sqlquery("SELECT `in_mod_queue` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
			$check_queue = $db->fetch();

			$approved = 1;
			if ($check_queue['in_mod_queue'] == 1)
			{
				$approved = 0;
			}

			// update user post counter
			if ($approved == 1)
			{
				$db->sqlquery("UPDATE `users` SET `forum_posts` = (forum_posts + 1) WHERE `user_id` = ?", array($author));
			}

			// add the topic
			$db->sqlquery("INSERT INTO `forum_topics` SET `forum_id` = ?, `author_id` = ?, $mod_sql `topic_title` = ?, `topic_text` = ?, `creation_date` = ?, `last_post_date` = ?, `last_post_id` = ?, `approved` = ?", array($forum_id, $author, $title, $message, core::$date, core::$date, $author, $approved));
			$topic_id = $db->grab_id();

			// update forums post counter and last post info
			if ($approved == 1)
			{
				$db->sqlquery("UPDATE `forums` SET `posts` = (posts + 1), `last_post_user_id` = ?, `last_post_time` = ?, `last_post_topic_id` = ? WHERE `forum_id` = ?", array($author, core::$date, $topic_id, $forum_id));
			}

			// if they are subscribing
			if (isset($_POST['subscribe']))
			{
				$db->sqlquery("INSERT INTO `forum_topics_subscriptions` SET `user_id` = ?, `topic_id` = ?", array($_SESSION['user_id'], $topic_id));
			}

			if ($approved == 1)
			{
				// input any polls as required
				if (isset($_POST['pquestion']))
				{
					if (!empty($_POST['pquestion']))
					{
						// if there's actually two or more options (let's not allow single question or broken polls eh!)
						if (count($_POST['poption']) >= 2)
						{
							$db->sqlquery("INSERT INTO `polls` SET `author_id` = ?, `poll_question` = ?, `topic_id` = ?", array($_SESSION['user_id'], $_POST['pquestion'], $topic_id));
							$poll_id = $db->grab_id();

							foreach ($_POST['poption'] as $option)
							{
								// don't add in empty left-over options for voting
								$option = trim($option);

								if (!empty($option))
								{
									$db->sqlquery("INSERT INTO `poll_options` SET `poll_id` = ?, `option_title` = ?", array($poll_id, $option));
								}
							}
						}
						else
						{
							$_SESSION['atitle'] = $title;
							$_SESSION['atext'] = $message;

							header("Location: /index.php?module=newtopic&forum_id=$forum_id&error=moreoptions");
							die();
						}
					}
				}

				unset($_SESSION['atitle']);
				unset($_SESSION['atext']);

				if ($config['pretty_urls'] == 1)
				{
					header("Location: /forum/topic/{$topic_id}");
				}
				else {
					header("Location: " . core::config('website_url') . "index.php?module=viewtopic&topic_id={$topic_id}");
				}
			}

			else if ($approved == 0)
			{
				$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `created` = ?, `topic_id` = ?, `mod_queue` = 1", array("A new forum topic was added to the moderation queue", core::$date, $topic_id));

				header("Location: " . core::config('website_url') . "index.php?module=viewforum&forum_id=$forum_id&message=queue");
			}
		}
	}
}
?>
