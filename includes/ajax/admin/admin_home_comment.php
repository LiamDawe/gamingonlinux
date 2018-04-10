<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_POST))
{
	if ($_POST['type'] == 'admin')
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);

		if (empty($text))
		{
			echo json_encode(array("message" => 'Empty comment'));
			return;
		}

		$date = core::$date;
		$dbl->run("INSERT INTO `admin_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$grab_admins = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username`, u.`admin_comment_alerts` FROM `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN (1,2) AND u.`user_id` != ?", [$_SESSION['user_id']])->fetch_all();
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
			if ($emailer['admin_comment_alerts'] == 1)
			{			
				// check for existing notification
				$check_notes = $dbl->run("SELECT `id` FROM `user_notifications` WHERE `type` = 'admin_comment' AND `seen` = 0 AND `owner_id` = ?", array($emailer['user_id']))->fetchOne();
				// they have one, add to the total + set you as the last person
				if ($check_notes)
				{
					// they already have one, refresh it as if it's literally brand new (don't waste the row id)
					$dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `seen` = 0, `total` = (total + 1), `notifier_id` = ? WHERE `id` = ?", array(core::$sql_date_now, $_SESSION['user_id'], $check_notes));
				}
				// insert notification as there was none
				else
				{
					$dbl->run("INSERT INTO `user_notifications` SET `type` = 'admin_comment', `owner_id` = ?, `notifier_id` = ?, `total` = 1", array($emailer['user_id'], $_SESSION['user_id']));
				}
			}
		}
		
		$templating->load('admin_modules/admin_home');

		$grab_comments = $dbl->run("SELECT a.`text`, a.`date_posted`, u.`user_id`, u.`username` FROM `admin_discussion` a INNER JOIN `users` u ON a.`user_id` = u.`user_id` ORDER BY a.`id` DESC LIMIT 10")->fetch_all();
		foreach ($grab_comments as $comments)
		{
			$templating->block('comment', 'admin_modules/admin_home');

			$comment_text = $bbcode->parse_bbcode($comments['text'], 0);
			$date = $core->human_date($comments['date_posted']);

			$templating->set('admin_comments', "<li><a href=\"/profiles/{$comments['user_id']}\">{$comments['username']}</a> - {$date}<br /> {$comment_text}</li>");
		}
		
		echo json_encode(array("result" => 'done', 'text' => $templating->output(), 'type' => 'admin'));
	}
	
	if ($_POST['type'] == 'editor')
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);

		if (empty($text))
		{
			echo json_encode(array("message" => 'Empty comment'));
			return;
		}

		$date = core::$date;
		$dbl->run("INSERT INTO `editor_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$grab_editors = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username`, u.`admin_comment_alerts` FROM `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN (1,2,5) AND u.`user_id` != ?", [$_SESSION['user_id']])->fetch_all();

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
			
			if ($emailer['admin_comment_alerts'] == 1)
			{
				// check for existing notification
				$check_notes = $dbl->run("SELECT `id` FROM `user_notifications` WHERE `type` = 'editor_comment' AND `seen` = 0 AND `owner_id` = ?", array($emailer['user_id']))->fetchOne();
				// they have one, add to the total + set you as the last person
				if ($check_notes)
				{
					// they already have one, refresh it as if it's literally brand new (don't waste the row id)
					$dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `seen` = 0, `total` = (total + 1), `notifier_id` = ? WHERE `id` = ?", array(core::$sql_date_now, $_SESSION['user_id'], $check_notes));
				}
				// insert notification as there was none
				else
				{
					$dbl->run("INSERT INTO `user_notifications` SET `type` = 'editor_comment', `owner_id` = ?, `notifier_id` = ?, `total` = 1", array($emailer['user_id'], $_SESSION['user_id']));
				}
			}
		}
		
		$templating->load('admin_modules/admin_home');

		$editor_chat = $dbl->run("SELECT a.*, u.`user_id`, u.`username` FROM `editor_discussion` a INNER JOIN `users` u ON a.`user_id` = u.`user_id` ORDER BY `id` DESC LIMIT 10")->fetch_all();
		foreach ($editor_chat as $commentsall)
		{
			$templating->block('commentall', 'admin_modules/admin_home');

			$commentall_text = $bbcode->parse_bbcode($commentsall['text'], 0);
			$dateall = $core->human_date($commentsall['date_posted']);

			$templating->set('editor_comments', "<li><a href=\"/profiles/{$commentsall['user_id']}\">{$commentsall['username']}</a> - {$dateall}<br /> {$commentall_text}</li>");
		}
		
		echo json_encode(array("result" => 'done', 'text' => $templating->output(), 'type' => 'editor'));
	}
}
