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

	$db->sqlquery("SELECT count(article_id) as count FROM `articles` WHERE `show_in_menu` = 1");
	$featured_ctotal = $db->fetch();
	$templating->set('featured_ctotal', $featured_ctotal['count']);

	// only show admin/editor comments to admins and editors
	if ($user->check_group(1,2) == true)
	{
		$templating->block('comments_top', 'admin_modules/admin_home');

		$db->sqlquery("SELECT a.*, u.user_id, u.username FROM `admin_discussion` a INNER JOIN `users` u ON a.user_id = u.user_id ORDER BY `id` DESC LIMIT 10");
		while ($comments = $db->fetch())
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

	$db->sqlquery("SELECT a.*, u.user_id, u.username FROM `editor_discussion` a INNER JOIN `users` u ON a.user_id = u.user_id ORDER BY `id` DESC LIMIT 10");
	while ($commentsall = $db->fetch())
	{
		$templating->block('commentall', 'admin_modules/admin_home');

		$commentall_text = bbcode($commentsall['text'], 0, 1);
		$dateall = $core->format_date($commentsall['date_posted']);

		$templating->set('editor_comments', "<li><a href=\"/profiles/{$commentsall['user_id']}\">{$commentsall['username']}</a> - {$dateall}<br /> {$commentall_text}</li>");
	}

	$templating->block('comments_bottomall', 'admin_modules/admin_home');

	// editor tracking
	$templating->block('editor_tracking', 'admin_modules/admin_home');

	$db->sqlquery("SELECT * FROM `admin_notifications` ORDER BY `id` DESC LIMIT 50");
	while ($tracking = $db->fetch())
	{
		$date = $tracking['created'];
		if (!empty($tracking['completed_date']) || $tracking['completed_date'] != 0)
		{
			$date = $tracking['completed_date'];
		}

		// if the comment_id is set, then we should link to the comment within the admin to see what it was (reported or deleted comments)
		$link = '';
		if ($tracking['comment_id'] != 0 && $tracking['reported_comment'] == 0)
		{
			$link = '<br /> <a href="/admin.php?module=home&view=comment&comment_id='.$tracking['comment_id'].'">See Comment</a>';
		}
		if ($tracking['reported_comment'] == 1 && $tracking['completed'] == 0)
		{
			$link = '<br /> <a href="/admin.php?module=articles&view=comments">Deal with report</a>';
		}
		if ($tracking['reported_comment'] == 1 && $tracking['completed'] == 1)
		{
			$link = '<br /> <a href="/admin.php?module=home&view=comment&comment_id='.$tracking['comment_id'].'">See what it was</a>';
		}
		$templating->block('tracking_row', 'admin_modules/admin_home');
		$templating->set('editor_action', '<li>' . $tracking['action'] . ' When: ' . $core->format_date($date) . $link . '</li>');
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
			if ($config['send_emails'] == 1)
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
			if ($config['send_emails'] == 1)
			{
				mail($to, $subject, $message, $headers);
			}
		}

		header('Location: /admin.php?message=added');
	}
}
