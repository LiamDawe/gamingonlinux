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
	if (!isset($_GET['page']) || !isset($_GET['forum_id']) || !core::is_number($_GET['page']) || !core::is_number($_GET['forum_id']))
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
		$topic = $dbl->run("SELECT t.`topic_id`, t.`author_id`, t.`topic_title`, r.`reply_text`, r.`lock_timer`, r.`locked_by_id` FROM `forum_topics` t JOIN `forum_replies` r ON r.topic_id = t.topic_id AND r.is_topic = 1 WHERE t.`topic_id` = ?", array($_GET['topic_id']))->fetch();

		if ($_SESSION['user_id'] == $topic['author_id'] || $user->check_group([1,2]) == true)
		{
			if (!is_null($topic['lock_timer']) && strtotime("-5 minutes") <= strtotime($topic['lock_timer']) && $topic['locked_by_id'] != $_SESSION['user_id'])
			{
				$_SESSION['message'] = 'lock_timer';
				header("Location: /forum/topic/{$_GET['topic_id']}");
				die();
			}
		
			$dbl->run("UPDATE `forum_replies` SET `lock_timer` = ?, `locked_by_id` = ? WHERE `is_topic` = 1 AND `topic_id` = ?", array(core::$sql_date_now, $_SESSION['user_id'], $_GET['topic_id']));

			$reported = 0;
			if (isset($_GET['reported']))
			{
				$reported = 1;
			}
			$templating->set('reported', $reported);

			$templating->block('edit_top');
			$templating->set('title', htmlentities($topic['topic_title'], ENT_QUOTES));

			$comment_editor = new editor($core, $templating, $bbcode);
			$comment_editor->editor(['name' => 'text', 'content' => $topic['reply_text'], 'editor_id' => 'post_text']);

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
		$post = $dbl->run("SELECT `post_id`, `topic_id`, `author_id`, `reply_text`, `lock_timer`, `locked_by_id` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']))->fetch();

		if ($_SESSION['user_id'] == $post['author_id'] || $user->check_group([1,2]) == true)
		{
			if (!is_null($post['lock_timer']) && strtotime("-5 minutes") <= strtotime($post['lock_timer']) && $post['locked_by_id'] != $_SESSION['user_id'])
			{
				$_SESSION['message'] = 'lock_timer';
				header("Location: /forum/topic/{$post['topic_id']}");
				die();
			}
		
			$dbl->run("UPDATE `forum_replies` SET `lock_timer` = ?, `locked_by_id` = ? WHERE `post_id` = ?", array(core::$sql_date_now, $_SESSION['user_id'], $_GET['post_id']));

			$reported = 0;
			if (isset($_GET['reported']))
			{
				$reported = 1;
			}

			$comment_editor = new editor($core, $templating, $bbcode);
			$comment_editor->editor(['name' => 'text', 'content' => $post['reply_text'], 'editor_id' => 'post_text']);

			$templating->block('edit_bottom', 'editpost');
			$templating->set('page', $_GET['page']);
			$templating->set('topic_id', $post['topic_id']);
			$templating->set('action', 'index.php?module=editpost&amp;post_id=' . $post['post_id'] . '&reported=' . $reported);
			$templating->set('cancel_action', '/forum/topic/' . $post['topic_id'] . '/post_id=' . $post['post_id']);
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
			$topic = $dbl->run("SELECT t.`author_id`, r.`lock_timer`, r.`locked_by_id` FROM `forum_topics` t INNER JOIN `forum_replies` r ON t.topic_id = r.topic_id WHERE t.`topic_id` = ?", array($_GET['topic_id']))->fetch();

			if ($_SESSION['user_id'] == $topic['author_id'] || $user->check_group([1,2]) == true)
			{
				if (!is_null($topic['lock_timer']) && strtotime("-5 minutes") <= strtotime($topic['lock_timer']) && $topic['locked_by_id'] != $_SESSION['user_id'])
				{
					$_SESSION['message'] = 'lock_timer';
					header("Location: /forum/topic/{$_GET['topic_id']}");
					die();
				}

				// update the topic
				$message = core::make_safe($_POST['text']);
				$dbl->run("UPDATE `forum_topics` SET `topic_title` = ?, `last_edited` = ?, `last_edited_time` = ? WHERE `topic_id` = ?", array($_POST['title'], $_SESSION['user_id'], core::$sql_date_now, $_GET['topic_id']));

				$dbl->run("UPDATE `forum_replies` SET `reply_text` = ?, `lock_timer` = NULL, `locked_by_id` = NULL WHERE `topic_id` = ? AND `is_topic` = 1", array($message, $_GET['topic_id']));

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
			$post = $dbl->run("SELECT `author_id`, `lock_timer`, `locked_by_id`, `topic_id` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']))->fetch();

			if ($_SESSION['user_id'] == $post['author_id'] || $user->check_group([1,2]) == true)
			{
				if (!is_null($post['lock_timer']) && strtotime("-5 minutes") <= strtotime($post['lock_timer']) && $post['locked_by_id'] != $_SESSION['user_id'])
				{
					$_SESSION['message'] = 'lock_timer';
					header("Location: /forum/topic/{$post['topic_id']}");
					die();
				}

				// update the topic
				$message = htmlspecialchars($_POST['text'], ENT_QUOTES);
				$dbl->run("UPDATE `forum_replies` SET `reply_text` = ?, `last_edited` = ?, `last_edited_time` = ?, `lock_timer` = NULL, `locked_by_id` = NULL WHERE `post_id` = ?", array($message, $_SESSION['user_id'], $core::$sql_date_now, $_GET['post_id']));

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
