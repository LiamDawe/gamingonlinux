<?php
$templating->set_previous('title', 'Editing a post', 1);

$templating->load('editpost');

// if its the topic
if (!isset($_POST['act']))
{
	if (!core::is_number($_GET['page']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'page id';
		header("Location: /index.php?module=forum");
		die();
	}
	
	$templating->block('post');

	// editing the main topic
	if (isset($_GET['topic_id']) && is_numeric($_GET['topic_id']))
	{
		$db->sqlquery("SELECT `topic_id`, `author_id`, `topic_title`, `topic_text` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']));
		$topic = $db->fetch();

		if ($_SESSION['user_id'] == $topic['author_id'] || $user->check_group([1,2]) == true)
		{

			$reported = 0;
			if (isset($_GET['reported']))
			{
				$reported = 1;
			}
			$templating->set('reported', $reported);

			$templating->block('edit_top');
			$templating->set('title', htmlentities($topic['topic_title'], ENT_QUOTES));

			$core->editor(['name' => 'text', 'content' => $topic['topic_text'], 'editor_id' => 'post_text']);

			$templating->block('edit_bottom', 'editpost');
			$templating->set('page', $_GET['page']);
			$templating->set('topic_id', $topic['topic_id']);
			$templating->set('action', 'index.php?module=editpost&amp;topic_id='.$topic['topic_id'].'&reported=' . $reported);
			if ($core->config('pretty_urls') == 1)
			{
				$cancel_action = '/forum/topic/' . $topic['topic_id'];
			}
			else
			{
				$cancel_action = '/index.php?module=viewtopic&topic_id=' . $topic['topic_id'];
			}
			$templating->set('cancel_action', $cancel_action);
			$templating->block('preview', 'editpost');
		}
		else
		{
			$_SESSION['message'] = 'not_authorized';
			
			if ($core->config('pretty_urls') == 1)
			{
				$redirect = '/forum/topic/' . $topic['topic_id'];
			}
			else
			{
				$redirect = '/index.php?module=viewtopic&topic_id=' . $topic['topic_id'];
			}
			
			header("Location: $redirect");
		}
	}

	// if its a reply
	if (isset($_GET['post_id']) && is_numeric($_GET['post_id']))
	{
		$db->sqlquery("SELECT `post_id`, `topic_id`, `author_id`, `reply_text` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']));
		$post = $db->fetch();

		if ($_SESSION['user_id'] == $post['author_id'] || $user->check_group([1,2]) == true)
		{
			$reported = 0;
			if (isset($_GET['reported']))
			{
				$reported = 1;
			}

			$core->editor(['name' => 'text', 'content' => $post['reply_text'], 'editor_id' => 'post_text']);

			$templating->block('edit_bottom', 'editpost');
			$templating->set('page', $_GET['page']);
			$templating->set('topic_id', $post['topic_id']);
			$templating->set('action', 'index.php?module=editpost&amp;post_id=' . $post['post_id'] . '&reported=' . $reported);
			if ($core->config('pretty_urls') == 1)
			{
				$cancel_action = '/forum/topic/' . $post['topic_id'];
			}
			else
			{
				$cancel_action = '/index.php?module=viewtopic&topic_id=' . $post['topic_id'];
			}
			$templating->set('cancel_action', $cancel_action);
			$templating->block('preview', 'editpost');
		}
		else
		{
			$_SESSION['message'] = 'not_authorized';
			
			if ($core->config('pretty_urls') == 1)
			{
				$redirect = '/forum/topic/' . $post['topic_id'];
			}
			else
			{
				$redirect = '/index.php?module=viewtopic&topic_id=' . $post['topic_id'];
			}
			
			header("Location: $redirect");
		}
	}
}

if (isset($_POST['act']) && $_POST['act'] == 'Edit')
{
	if (!core::is_number($_POST['page']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'page id';
		header("Location: /index.php?module=forum");
	}
	
	// edit topic
	if (isset($_GET['topic_id']) && is_numeric($_GET['topic_id']))
	{
		// check empty
		if (empty($_POST['title']) || empty($_POST['text']))
		{
			$core->message("You have to enter in a title and text to edit! Options: <a href=\"index.php?module=editpost&amp;topic_id={$_GET['topic_id']}\">Edit Again</a> - <a href=\"index.php?module=viewtopic&amp;topic_id={$_GET['topic_id']}&page={$_GET['page']}\">Return to topic</a>.");
		}

		else
		{
			$db->sqlquery("SELECT `author_id` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']));
			$topic = $db->fetch();

			if ($_SESSION['user_id'] == $topic['author_id'] || $user->check_group([1,2]) == true)
			{
				// update the topic
				$message = core::make_safe($_POST['text']);
				$db->sqlquery("UPDATE `forum_topics` SET `topic_title` = ?, `topic_text` = ? WHERE `topic_id` = ?", array($_POST['title'], $message, $_GET['topic_id']));

				// get them to go back
				header("Location: index.php?module=viewtopic&topic_id={$_GET['topic_id']}&page={$_POST['page']}");
			}

			else
			{
				$_SESSION['message'] = 'not_authorized';
				
				if ($core->config('pretty_urls') == 1)
				{
					$redirect = '/forum/topic/' . $_GET['topic_id'];
				}
				else
				{
					$redirect = '/index.php?module=viewtopic&topic_id=' . $_GET['topic_id'];
				}
				
				header("Location: $redirect");
			}
		}
	}

	// edit post
	if (isset($_GET['post_id']))
	{
		// check empty
		if (empty($_POST['text']))
		{
			$core->message("You have to enter in some text to edit! Options: <a href=\"index.php?module=editpost&amp;post_id={$_GET['post_id']}\">Edit Again</a> - <a href=\"index.php?module=viewtopic&amp;topic_id={$_POST['topic_id']}&page={$_GET['page']}\">Return to topic</a>.");
		}

		else
		{
			$db->sqlquery("SELECT `author_id` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']));
			$post = $db->fetch();

			if ($_SESSION['user_id'] == $post['author_id'] || $user->check_group([1,2]) == true)
			{
				// update the topic
				$message = htmlspecialchars($_POST['text'], ENT_QUOTES);
				$db->sqlquery("UPDATE `forum_replies` SET `reply_text` = ? WHERE `post_id` = ?", array($message, $_GET['post_id']));

				// get them to go back
				header("Location: index.php?module=viewtopic&topic_id={$_POST['topic_id']}&page={$_POST['page']}");
			}

			else
			{
				$_SESSION['message'] = 'not_authorized';
				
				if ($core->config('pretty_urls') == 1)
				{
					$redirect = '/forum/topic/' . $_POST['topic_id'];
				}
				else
				{
					$redirect = '/index.php?module=viewtopic&topic_id=' . $_POST['topic_id'];
				}
				
				header("Location: $redirect");
			}
		}
	}
}
