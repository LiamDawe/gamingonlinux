<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Editing a post', 1);

$templating->load('editpost');

// if its the topic
if (!isset($_POST['act']))
{
	if (!core::is_number($_GET['page']) || !core::is_number($_GET['forum_id']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'page id';
		header("Location: /index.php?module=forum");
		die();
	}

	$name = $dbl->run("SELECT `name` FROM `forums` WHERE forum_id = ?", array($_GET['forum_id']))->fetchOne();
	$templating->block('main_top', 'editpost');
	$templating->set('forum_name', $name);
	$templating->set('forum_id', $_GET['forum_id']);

	$templating->block('post');

	// editing the main topic
	if (isset($_GET['topic_id']) && is_numeric($_GET['topic_id']))
	{
		$topic = $dbl->run("SELECT t.`topic_id`, t.`author_id`, t.`topic_title`, r.`reply_text` FROM `forum_topics` t JOIN `forum_replies` r ON r.topic_id = t.topic_id AND r.is_topic = 1 WHERE t.`topic_id` = ?", array($_GET['topic_id']))->fetch();

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

			$core->editor(['name' => 'text', 'content' => $topic['reply_text'], 'editor_id' => 'post_text']);

			$templating->block('edit_bottom', 'editpost');
			$templating->set('page', $_GET['page']);
			$templating->set('topic_id', $topic['topic_id']);
			$templating->set('action', 'index.php?module=editpost&amp;topic_id='.$topic['topic_id'].'&reported=' . $reported);

			$templating->set('cancel_action', '/forum/topic/' . $topic['topic_id']);
			$templating->block('preview', 'editpost');
		}
		else
		{
			$_SESSION['message'] = 'not_authorized';
			header("Location: /forum/topic/" . $topic['topic_id']);
		}
	}

	// if its a reply
	if (isset($_GET['post_id']) && is_numeric($_GET['post_id']))
	{
		$post = $dbl->run("SELECT `post_id`, `topic_id`, `author_id`, `reply_text` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']))->fetch();

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
			$templating->set('cancel_action', '/forum/topic/' . $post['topic_id']);
			$templating->block('preview', 'editpost');
		}
		else
		{
			$_SESSION['message'] = 'not_authorized';
			header("Location: /forum/topic/" . $post['topic_id']);
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
			$core->message("You have to enter in a title and text to edit! Options: <a href=\"index.php?module=editpost&amp;topic_id={$_GET['topic_id']}&amp;page={$_POST['page']}\">Edit Again</a> - <a href=\"index.php?module=viewtopic&amp;topic_id={$_GET['topic_id']}&page={$_GET['page']}\">Return to topic</a>.");
		}

		else
		{
			$topic = $dbl->run("SELECT `author_id` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']))->fetch();

			if ($_SESSION['user_id'] == $topic['author_id'] || $user->check_group([1,2]) == true)
			{
				// update the topic
				$message = core::make_safe($_POST['text']);
				$dbl->run("UPDATE `forum_topics` SET `topic_title` = ? WHERE `topic_id` = ?", array($_POST['title'], $_GET['topic_id']));

				$dbl->run("UPDATE `forum_replies` SET `reply_text` = ? WHERE `topic_id` = ? AND `is_topic` = 1", array($message, $_GET['topic_id']));

				// get them to go back
				header("Location: index.php?module=viewtopic&topic_id={$_GET['topic_id']}&page={$_POST['page']}");
			}

			else
			{
				$_SESSION['message'] = 'not_authorized';
				header("Location: /forum/topic/" . $_GET['topic_id']);
			}
		}
	}

	// edit post
	if (isset($_GET['post_id']))
	{
		// check empty
		if (empty($_POST['text']))
		{
			$core->message("You have to enter in some text to edit! Options: <a href=\"index.php?module=editpost&amp;post_id={$_GET['post_id']}&amp;page={$_POST['page']}\">Edit Again</a> - <a href=\"index.php?module=viewtopic&amp;topic_id={$_POST['topic_id']}&page={$_GET['page']}\">Return to topic</a>.");
		}

		else
		{
			$post = $dbl->run("SELECT `author_id` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']))->fetch();

			if ($_SESSION['user_id'] == $post['author_id'] || $user->check_group([1,2]) == true)
			{
				// update the topic
				$message = htmlspecialchars($_POST['text'], ENT_QUOTES);
				$dbl->run("UPDATE `forum_replies` SET `reply_text` = ? WHERE `post_id` = ?", array($message, $_GET['post_id']));

				// get them to go back
				header("Location: index.php?module=viewtopic&topic_id={$_POST['topic_id']}&page={$_POST['page']}");
			}

			else
			{
				$_SESSION['message'] = 'not_authorized';
				header("Location: /forum/topic/" . $_POST['topic_id']);
			}
		}
	}
}
