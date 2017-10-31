<?php
$templating->set_previous('title', 'Home' . $templating->get('title', 1)  , 1);
$templating->load('admin_modules/admin_home');

if (isset($_GET['wipe_note']) && is_numeric($_GET['wipe_note']))
{
	// check they own it
	$check = $dbl->run("SELECT `owner_id` FROM `user_notifications` WHERE `id` = ?", array($_GET['wipe_note']))->fetchOne();
	
	// wipe it
	if ($check == $_SESSION['user_id'])
	{
		$dbl->run("UPDATE `user_notifications` SET `seen` = 1, `seen_date` = ? WHERE `id` = ?", array(core::$date, $_GET['wipe_note']));
	}
}

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
			$core->message('You can\'t submit an empty admin area comment silly!', 1);
		}
	}

	$templating->set('articles_css', "adminHome");
	$templating->block('main', 'admin_modules/admin_home');
	$templating->set('username', $_SESSION['username']);
	$templating->set('featured_max', $core->config('editor_picks_limit'));

	$templating->set('featured_ctotal', $core->config('total_featured'));
	
	if ($user->check_group(1))
	{
		$cron_list = '';
		$templating->block('crons', 'admin_modules/admin_home');
		$crons = $dbl->run("SELECT `name`, `last_ran`, `data` FROM `crons` ORDER BY `last_ran` ASC")->fetch_all();
		$total_crons = count($crons);
		$counter = 0;
		foreach ($crons as $cron)
		{
			$data = '';
			if ($cron['data'] != NULL)
			{
				$data = '<strong>Data:</strong> ' . $cron['data'];
			}
			$cron_list .= '<strong>Name:</strong> ' . $cron['name'] . ' - <strong>Last Ran:</strong> ' . $cron['last_ran'] . $data;
			if ($counter < $total_crons)
			{
				$cron_list .= '<br />';
			}
			$counter++;
		}
		$templating->set('cron_list', $cron_list);
	}
	
	// editor plans
	$grab_comments = $dbl->run("SELECT p.`id`, p.`text`, p.`date_posted`, u.`user_id`, u.`username` FROM `editor_plans` p INNER JOIN `users` u ON p.`user_id` = u.`user_id` ORDER BY p.`id` DESC")->fetch_all();
	$templating->block('plans', 'admin_modules/admin_home');
	$plans_list = [];
	if ($grab_comments)
	{
		foreach ($grab_comments as $comments)
		{
			$comment_text = $bbcode->parse_bbcode($comments['text'], 0);
			$date = $core->human_date(strtotime($comments['date_posted']));
			
			$plans = $templating->block_store('plan_row', 'admin_modules/admin_home');
			
			$delete_icon = '';
			if (($_SESSION['user_id'] == $comments['user_id']) || $user->check_group(1))
			{
				$delete_icon = '<span class="fright"><a href="#" class="delete_editor_plan" title="Delete Plan" data-note-id="'.$comments['id'].'" data-owner-id="'.$comments['user_id'].'">&#10799;</a></span>';
			}
			
			$plans = $templating->store_replace($plans, array('id' => $comments['id'], 'user_id' => $comments['user_id'], 'username' => $comments['username'], 'date' => $date, 'text' => $comments['text'], 'delete_icon' => $delete_icon));
			
			$plans_list[] = $plans;
		}
		$templating->set('editor_plans', implode('', $plans_list));
	}
	else
	{
		$templating->set('editor_plans', '<li>None</li>');
	}

	// only show admin/editor comments to admins and editors
	if ($user->check_group([1,2]) == true)
	{
		$templating->block('comments_top', 'admin_modules/admin_home');

		$grab_comments = $dbl->run("SELECT a.`text`, a.`date_posted`, u.`user_id`, u.`username` FROM `admin_discussion` a INNER JOIN `users` u ON a.`user_id` = u.`user_id` ORDER BY a.`id` DESC LIMIT 10")->fetch_all();
		foreach ($grab_comments as $comments)
		{
			$templating->block('comment', 'admin_modules/admin_home');

			$comment_text = $bbcode->parse_bbcode($comments['text'], 0);
			$date = $core->human_date($comments['date_posted']);

			$templating->set('admin_comments', "<li><a href=\"/profiles/{$comments['user_id']}\">{$comments['username']}</a> - {$date}<br /> {$comment_text}</li>");
		}

		$templating->block('comments_bottom', 'admin_modules/admin_home');
	}

	// all editor private chat
	$templating->block('comments_alltop', 'admin_modules/admin_home');

	$editor_chat = $dbl->run("SELECT a.*, u.`user_id`, u.`username` FROM `editor_discussion` a INNER JOIN `users` u ON a.`user_id` = u.`user_id` ORDER BY `id` DESC LIMIT 10")->fetch_all();
	foreach ($editor_chat as $commentsall)
	{
		$templating->block('commentall', 'admin_modules/admin_home');

		$commentall_text = $bbcode->parse_bbcode($commentsall['text'], 0);
		$dateall = $core->human_date($commentsall['date_posted']);

		$templating->set('editor_comments', "<li><a href=\"/profiles/{$commentsall['user_id']}\">{$commentsall['username']}</a> - {$dateall}<br /> {$commentall_text}</li>");
	}

	$templating->block('comments_bottomall', 'admin_modules/admin_home');

	// editor tracking
	$templating->block('editor_tracking', 'admin_modules/admin_home');

	// get the different types of notifications
	$fetch_types = $dbl->run("SELECT `name`, `text`, `link` FROM `admin_notification_types`")->fetch_all();
	// make their key their name, so we can easily call them
	foreach ($fetch_types as $types_set)
	{
		$types[$types_set['name']] = $types_set;
	}

	$get_notifications = $dbl->run("SELECT n.*, u.`username` FROM `admin_notifications` n LEFT JOIN `users` u ON n.`user_id` = u.`user_id` ORDER BY n.`id` DESC LIMIT 50")->fetch_all();
	foreach ($get_notifications as $tracking)
	{
		$templating->block('tracking_row', 'admin_modules/admin_home');

		$username = '';
		if (empty($tracking['username']))
		{
			$username = 'Guest';
		}
		else
		{
			if ($core->config('pretty_urls') == 1)
			{
				$username = '<a href="/profiles/'.$tracking['user_id'].'">'.$tracking['username'].'</a>';
			}
			else
			{
				$username = '<a href="/index.php?module=profile&user_id='.$tracking['user_id'].'">'.$tracking['username'].'</a>';
			}

		}

		$completed_indicator = '&#10004;';
		if ($tracking['completed'] == 0)
		{
			$completed_indicator = '<span class="badge badge-important">!</span>';
		}

		// if their is a "View" link to see what item the action was done on
		$link = '';
		if (!empty($types[$tracking['type']]['link']))
		{
			$link = $types[$tracking['type']]['link'];

			// if it has a title in the URL for article, only do this if we have to so we save on queries
			// still need a better way so we don't need a query for each one, but it works for now
			if (preg_match('/{:title}/', $link))
			{
				$title = $dbl->run("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($tracking['data']))->fetch();
				$link = str_replace('{:title}', core::nice_title($title['title']), $link);
			}

			// replace id numbers
			$id_array = array('{:topic_id}','{:article_id}', '{:post_id}');
			$link = str_replace($id_array, $tracking['data'], $link);

			$link = ' <a href="'.$link.'">View</a> - ';
		}

		$templating->set('editor_action', '<li>' . $completed_indicator . ' ' . $username . ': ' . $types[$tracking['type']]['text'] . $link . ' When: ' . $core->human_date($tracking['created_date']) . '</li>');
	}
	$templating->block('tracking_bottom', 'admin_modules/admin_home');
}

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'comment')
	{
		$content = $dbl->run("SELECT * FROM `admin_notifications` WHERE `comment_id` = ?", array($_GET['comment_id']))->fetch();

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
		$dbl->run("UPDATE `admin_notes` SET `text` = ? WHERE `user_id` = ?", array($notes_text, $_SESSION['user_id']));

		header('Location: /admin.php?message=updated');
	}

	if ($_POST['act'] == 'comment')
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);

		if (empty($text))
		{
			header('Location: /admin.php?message=emptycomment');
			exit;
		}

		$date = core::$date;
		$dbl->run("INSERT INTO `admin_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$grab_admins = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username` FROM `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN (1,2) AND u.`user_id` != ?", [$_SESSION['user_id']])->fetch_all();
		foreach ($grab_admins as $emailer)
		{
			$subject = "A new admin area comment on GamingOnLinux.com";

			// message
			$html_message = "<p>Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux <a href=\"http://www.gamingonlinux.com/admin.php\">admin panel</a>:</p>
			<hr>
			<p>{$text}</p>";
			
			$plain_message = "Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux admin panel: https://www.gamingonlinux.com/admin.php";

			// Mail it
			if ($core->config('send_emails') == 1)
			{
				$mail = new mailer($core);
				$mail->sendMail($emailer['email'], $subject, $html_message, $plain_message);
			}
		}

		header('Location: /admin.php?message=added');
	}

	if ($_POST['act'] == 'commentall')
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);

		if (empty($text))
		{
			header('Location: /admin.php?message=emptycomment');
			exit;
		}

		$date = core::$date;
		$dbl->run("INSERT INTO `editor_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$grab_editors = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username` FROM `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN (1,2,5) AND u.`user_id` != ?", [$_SESSION['user_id']])->fetch_all();

		foreach ($grab_editors as $emailer)
		{
			$subject = "A new editor area comment on GamingOnLinux.com";

			// message
			$html_message = "<p>Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux <a href=\"https://www.gamingonlinux.com/admin.php\">editor panel</a>:</p>
			<hr>
			<p>{$text}</p>";

			$plain_message = "Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux editor panel: https://www.gamingonlinux.com/admin.php";
			
			// Mail it
			if ($core->config('send_emails') == 1)
			{
				$mail = new mailer($core);
				$mail->sendMail($emailer['email'], $subject, $html_message, $plain_message);
			}
		}

		header('Location: /admin.php?message=added');
	}
}
