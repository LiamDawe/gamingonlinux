<?php
$templating->set_previous('title', 'Home' . $templating->get('title', 1)  , 1);
$templating->merge('admin_modules/admin_home');

if (!isset($_GET['view']))
{
	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'added')
		{
			$core->message('Added the comment!');
		}

		if ($_GET['message'] == 'emptycomment')
		{
			$core->message('You can\'t submit an empty admin area comment silly!', NULL, 1);
		}
	}

	$templating->set('articles_css', "adminHome");
	$templating->block('main', 'admin_modules/admin_home');
	$templating->set('username', $_SESSION['username']);
	$templating->set('featured_max', core::config('editor_picks_limit'));

	$templating->set('featured_ctotal', core::config('total_featured'));

	// only show admin/editor comments to admins and editors
	if ($user->check_group(1,2) == true)
	{
		$templating->block('comments_top', 'admin_modules/admin_home');

		$grab_comments = $db->sqlquery("SELECT a.text, a.date_posted, u.user_id, u.username FROM `admin_discussion` a INNER JOIN `users` u ON a.user_id = u.user_id ORDER BY a.`id` DESC LIMIT 10");
		while ($comments = $grab_comments->fetch())
		{
			$templating->block('comment', 'admin_modules/admin_home');

			$comment_text = bbcode($comments['text'], 0, 1);
			$date = $core->format_date($comments['date_posted']);

			$templating->set('admin_comments', "<li><a href=\"/profiles/{$comments['user_id']}\">{$comments['username']}</a> - {$date}<br /> {$comment_text}</li>");
		}

		$templating->block('comments_bottom', 'admin_modules/admin_home');
	}

	// all editor private chat
	$templating->block('comments_alltop', 'admin_modules/admin_home');

	$editor_chat = $db->sqlquery("SELECT a.*, u.user_id, u.username FROM `editor_discussion` a INNER JOIN `users` u ON a.user_id = u.user_id ORDER BY `id` DESC LIMIT 10");
	while ($commentsall = $editor_chat->fetch())
	{
		$templating->block('commentall', 'admin_modules/admin_home');

		$commentall_text = bbcode($commentsall['text'], 0, 1);
		$dateall = $core->format_date($commentsall['date_posted']);

		$templating->set('editor_comments', "<li><a href=\"/profiles/{$commentsall['user_id']}\">{$commentsall['username']}</a> - {$dateall}<br /> {$commentall_text}</li>");
	}

	$templating->block('comments_bottomall', 'admin_modules/admin_home');

	// editor tracking
	$templating->block('editor_tracking', 'admin_modules/admin_home');

	$db->sqlquery("SELECT n.*, u.`username` FROM `admin_notifications` n LEFT JOIN `users` u ON n.user_id = u.user_id ORDER BY n.`id` DESC LIMIT 50");
	while ($tracking = $db->fetch())
	{
		$templating->block('tracking_row', 'admin_modules/admin_home');

		$username = '';
		if (empty($tracking['username']))
		{
			$username = 'Guest';
		}
		else
		{
			if (core::config('pretty_urls') == 1)
			{
				$username = '<a href="/profiles/'.$tracking['user_id'].'">'.$tracking['username'].'</a>';
			}
			else
			{
				$username = '<a href="/index.php?module=profile&user_id='.$tracking['user_id'].'">'.$tracking['username'].'</a>';
			}

		}

		$types_array = array(
			"comment_deleted" =>  ' deleted a comment',
			"closed_comments" => ' closed the comments on an article.',
			"reported_comment" => ' reported a comment.',
			"deleted_comment_report" => ' deleted a comment report.',

			"forum_topic_report" => ' reported a forum topic.',
			"forum_reply_report" => ' reported a forum reply',
			"deleted_topic_report" => ' deleted a forum topic report.',
			"deleted_reply_report" => ' deleted a forum reply report.',
			"mod_queue" => ' requires approval of their forum post.',
			"mod_queue_approved" => ' approved a forum post.',
			"mod_queue_removed" => ' removed a forum topic requesting approval.',
			"mod_queue_removed_ban" => ' removed a forum topic requesting approval and banned the user.',

			"edited_user" => ' edited a user.',
			"banned_user" => ' banned a user.',
			"unbanned_user" => ' unbanned a user.',
			"ip_banned" => ' banned an IP address.',
			"total_ban" => ' banned a user along with their IP address.',
			"unban_ip" => ' unbanned an IP address.',
			"delete_user" => ' deleted a user account.',
			"deleted_user_content" => ' deleted all the content from a user.',

			"calendar_submission" => ' submitted a game for the calendar and games database.',
			"approved_calendar" => ' approved a calendar and games database submission.',
			"game_database_addition" => ' added a new game to the calendar and games database',
			"game_database_edit" => ' edited a game in the calendar and games database',
			"game_database_deletion" => ' deleted a game from the calendar and games database',

			"deleted_article" => ' deleted an article.',
			"denied_submitted_article" => ' denied a user submitted article.',
			"approve_submitted_article" => ' approved a user submitted article.',
			"article_admin_queue_approved" => ' approved an article from the admin review queue.',
			"article_admin_queue" => ' sent a new article to the admin review queue.',
			"new_article_published" => ' published a new article.',
			"submitted_article" => ' submitted an article.',
			"article_correction" =>  ' sent in an article correction.',
			"deleted_correction" => ' deleted an article correction report.',
			"disabled_article" => ' disabled an article.',
			"enabled_article" => ' re-enabled an article.',

			"new_livestream_event" => ' added a new livestream event.',
			"edit_livestream_event" => ' edited a livestream event.',
			'deleted_livestream_event' => ' deleted a livestream event.',
			"new_livestream_submission" => ' sent a livestream event for review.',
			"accepted_livestream_submission" => ' accepted a livestream submission.',
			"denied_livestream_submission" => ' denied a livestream submission.',

			"goty_game_submission" => ' submitted a GOTY game for review.',
			"goty_game_added" => ' added a GOTY game.',
			"goty_accepted_game" => ' accepted a GOTY submission.',
			"goty_denied_game" => ' denied a GOTY submission.',
			"goty_finished" => ' closed the GOTY awards.'
		);

		$completed_indicator = '&#10004;';
		if ($tracking['completed'] == 0)
		{
			$completed_indicator = '<span class="badge badge-important">!</span>';
		}

		$templating->set('editor_action', '<li>' . $completed_indicator . ' ' . $username . $types_array[$tracking['type']] . ' When: ' . $core->format_date($tracking['created_date']) . '</li>');
	}
	$templating->block('tracking_bottom', 'admin_modules/admin_home');
}

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'comment')
	{
		$db->sqlquery("SELECT * FROM `admin_notifications` WHERE `comment_id` = ?", array($_GET['comment_id']));
		$content = $db->fetch();

		$templating->block('view_content');
		$templating->set('action', $content['action']);
		$templating->set('content', $content['content']);
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'edit')
	{
		$notes_text = trim($_POST['text']);
		$db->sqlquery("UPDATE `admin_notes` SET `text` = ? WHERE `user_id` = ?", array($notes_text, $_SESSION['user_id']));

		header('Location: /admin.php?message=updated');
	}

	if ($_POST['act'] == 'comment')
	{
		$text = trim($_POST['text']);

		if (empty($text))
		{
			header('Location: /admin.php?message=emptycomment');
			exit;
		}

		$date = core::$date;
		$db->sqlquery("INSERT INTO `admin_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$db->sqlquery("SELECT `username`, `email` FROM `users` WHERE `user_group` IN (1,2) AND `user_id` != ?", array($_SESSION['user_id']));

		while ($emailer = $db->fetch())
		{
			$to = $emailer['email'];

			$subject = "A new admin area comment on GamingOnLinux.com";

			// message
			$message = "
			<html>
			<head>
			<title>A new admin area comment on GamingOnLinux.com!</title>
			</head>
			<body>
			<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
			<br />
			<p>Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux <a href=\"http://www.gamingonlinux.com/admin.php\">admin panel</a>:</p>
			<hr>
			<p>{$text}</p>
			<br>
			<p>You can reply to this mail to make a new comment, replace all contents or comment below this line.</p>
			<p>".str_repeat('-', 98)."</p>
			</body>
			</html>
			";

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: ".core::genReplyAddress(66,'admin')."\r\n";

			// Mail it
			if (core::config('send_emails') == 1)
			{
				mail($to, $subject, $message, $headers);
			}
		}

		header('Location: /admin.php?message=added');
	}

	if ($_POST['act'] == 'commentall')
	{
		$text = trim($_POST['text']);

		if (empty($text))
		{
			header('Location: /admin.php?message=emptycomment');
			exit;
		}

		$date = core::$date;
		$db->sqlquery("INSERT INTO `editor_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$db->sqlquery("SELECT `username`, `email` FROM `users` WHERE `user_group` IN (1,2,5) AND `user_id` != ?", array($_SESSION['user_id']));

		while ($emailer = $db->fetch())
		{
			$to = $emailer['email'];

			$subject = "A new editor area comment on GamingOnLinux.com";

			// message
			$message = "
			<html>
			<head>
			<title>A new editor area comment on GamingOnLinux.com!</title>
			</head>
			<body>
			<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
			<br />
			<p>Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux <a href=\"http://www.gamingonlinux.com/admin.php\">editor panel</a>:</p>
			<hr>
			<p>{$text}</p>
			<br>
			<p>You can reply to this mail to make a new comment, replace all contents or comment below this line.</p>
			<p>".str_repeat('-', 98)."</p>
			</body>
			</html>
			";

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: ".core::genReplyAddress(365,'editor')."\r\n";

			// Mail it
			if (core::config('send_emails') == 1)
			{
				mail($to, $subject, $message, $headers);
			}
		}

		header('Location: /admin.php?message=added');
	}
}
