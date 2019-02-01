<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

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
				$data = ' <strong>Data:</strong> ' . $cron['data'];
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

	$last_id = 0;
	$get_notifications = $dbl->run("SELECT * FROM `admin_notifications` ORDER BY `id` DESC LIMIT 50")->fetch_all();
	foreach ($get_notifications as $tracking)
	{
		$templating->block('tracking_row', 'admin_modules/admin_home');

		$completed_indicator = '&#10004;';
		if ($tracking['completed'] == 0)
		{
			$completed_indicator = '<span class="badge badge-important">!</span>';
		}

		$templating->set('editor_action', '<li data-id="'.$tracking['id'].'">' . $completed_indicator . ' ' . $tracking['content'] . ' When: ' . $core->human_date($tracking['created_date']) . '</li>');

		$last_id = $tracking['id'];
	}
	$templating->block('tracking_bottom', 'admin_modules/admin_home');

	$total_notifications = $dbl->run("SELECT COUNT(*) FROM `admin_notifications`")->fetchOne();
	$load_more_link = '';
	if ($total_notifications > 50)
	{
		$load_more_link = '<a class="load_admin_notifications" data-last-id="'.$last_id.'" href="#">Load More</a>';
	}
	$templating->set('more_link', $load_more_link);
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
