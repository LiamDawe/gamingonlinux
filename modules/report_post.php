<?php
$templating->set_previous('title', "Reporting a forum post", 1);
$templating->set_previous('meta_description', 'Reporting a forum post', 1);

if (isset($_GET['view']))
{
	if  ($_GET['view'] == 'reporttopic')
	{
		if (!isset($_GET['topic_id']) || ( isset($_GET['topic_id']) && !core::is_number($_GET['topic_id']) ) )
		{
			header('Location: /index.php');
			die();
		}
	
		// first check it's not already reported
		$check_report = $dbl->run("SELECT `reported`, `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']))->fetch();
		// send them back
		if ($check_report['reported'] == 1)
		{
			$_SESSION['message'] = 'reported';
			$_SESSION['message_extra'] = 'topic';
			header("Location: /forum/topic/{$_GET['topic_id']}/");
			die();
		}

		// do the report if it isn't already
		else
		{
			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				$core->yes_no('Are you sure you want to report the topic titled "'.$check_report['topic_title'].'"?', '/index.php?module=report_post&topic_id='.$_GET['topic_id'], "reporttopic");
			}
		}
	}


	if ($_GET['view'] == 'reportreply')
	{
		if (!isset($_GET['post_id']) || (isset($_GET['post_id']) && !core::is_number($_GET['post_id']) ) )
		{
			header('Location: /index.php');
			die();
		}
	
		// first check it's not already reported
		$check_report = $dbl->run("SELECT `reported` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']))->fetch();
		// send them back
		if ($check_report['reported'] == 1)
		{
			$_SESSION['message'] = 'reported';
			$_SESSION['message_extra'] = 'post';
			header("Location: /forum/topic/{$_GET['topic_id']}/");
			die();
		}

		// do the report if it isn't already
		else
		{
			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				$core->yes_no('Are you sure you want to report that post?', '/index.php?module=report_post&post_id='.$_GET['post_id'] . '&topic_id=' . $_GET['topic_id'], "reportreply");
			}
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'reporttopic')
	{
		if (!isset($_GET['topic_id']) || ( isset($_GET['topic_id']) && !core::is_number($_GET['topic_id']) ) )
		{
			header('Location: /index.php');
			die();
		}
	
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			if (isset($_POST['no']))
			{
				header("Location: /forum/topic/{$_GET['topic_id']}/");
				die();
			}
			else if (isset($_POST['yes']))
			{
				// get the title
				$topic_title = $dbl->run("SELECT `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']))->fetchOne();

				// update admin notifications
				$core->new_admin_note(array('content' => ' reported a forum topic titled: <a href="/admin.php?module=forum&view=reportedtopics">'.$topic_title.'</a>.', 'type' => 'forum_topic_report', 'data' => $_GET['topic_id']));

				// give it a report
				$dbl->run("UPDATE `forum_topics` SET `reported` = 1, `reported_by_id` = ? WHERE `topic_id` = ?", array($_SESSION['user_id'], $_GET['topic_id']));

				$_SESSION['message'] = 'reported';
				$_SESSION['message_extra'] = 'topic';
				header("Location: /forum/topic/{$_GET['topic_id']}/");
			}
		}
	}
	if ($_POST['act'] == 'reportreply')
	{
		if (!isset($_GET['post_id']) || (isset($_GET['post_id']) && !core::is_number($_GET['post_id']) ) )
		{
			header('Location: /index.php');
			die();
		}
	
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			if (isset($_POST['no']))
			{
				header("Location: /forum/topic/{$_GET['topic_id']}/");
				die();
			}
			else if (isset($_POST['yes']))
			{
				// get the title
				$topic_title = $dbl->run("SELECT t.`topic_title` FROM `forum_replies` p INNER JOIN `forum_topics` t ON t.`topic_id` = p.`topic_id` WHERE p.`post_id` = ?", array($_GET['post_id']))->fetchOne();

				// update admin notifications
				$core->new_admin_note(array('content' => ' reported a post in the topic titled: <a href="/admin.php?module=forum&view=reportedreplies">'.$topic_title.'</a>.', 'type' => 'forum_reply_report', 'data' => $_GET['post_id']));

				// give it a report
				$dbl->run("UPDATE `forum_replies` SET `reported` = 1, `reported_by_id` = ? WHERE `post_id` = ?", array($_SESSION['user_id'], $_GET['post_id']));

				$_SESSION['message'] = 'reported';
				$_SESSION['message_extra'] = 'post';
				
				header("Location: /forum/topic/{$_GET['topic_id']}/");
			}
		}
	}
}
?>
