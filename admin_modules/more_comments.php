<?php
$templating->merge('admin_modules/admin_module_more_comments');

if (isset($_GET['view']) && $_GET['view'] == 'editors')
{
	// paging for pagination
	if (!isset($_GET['page']) || $_GET['page'] == 0)
	{
		$page = 1;
	}

	else if (is_numeric($_GET['page']))
	{
		$page = $_GET['page'];
	}

	// count how many there is in total
	$sql_count = "SELECT `id` FROM `editor_discussion`";
	$db->sqlquery($sql_count);
	$total_comments = $db->num_rows();

	$comments_per_page = core::config('default-comments-per-page');
	if (isset($_SESSION['per-page']))
	{
		$comments_per_page = $_SESSION['per-page'];
	}

	// sort out the pagination link
	$pagination = $core->pagination_link($comments_per_page, $total_comments, "admin.php?module=more_comments&view=editors&", $page);

	// all editor private chat
	$templating->block('comments_alltop', 'admin_modules/admin_module_more_comments');
	$templating->set('pagination', $pagination);

	$result = $db->sqlquery("SELECT a.*, u.user_id, u.username, u.avatar_gravatar, u.gravatar_email, u.avatar, u.avatar_uploaded, u.avatar_gallery FROM `editor_discussion` a INNER JOIN ".$core->db_tables['users']." u ON a.user_id = u.user_id ORDER BY a.`id` DESC LIMIT ?,?", array($core->start, $_SESSION['per-page']));
	while ($commentsall = $result->fetch())
	{
		$templating->block('commentall', 'admin_modules/admin_module_more_comments');

		// sort out the avatar
		// either no avatar (gets no avatar from gravatars redirect) or gravatar set
		$comment_avatar = $user->sort_avatar($commentsall);

		$commentall_text = $bbcode->parse_bbcode($commentsall['text'], 0, 1);
		$dateall = $core->format_date($commentsall['date_posted']);
		$templating->set('username', '<a href="/profiles/' . $commentsall['user_id'] . '">' . $commentsall['username'] . '</a>');
		$templating->set('date', $dateall);
		$templating->set('tzdate', date('c',$commentsall['date_posted']) ); //piratelv timeago
		$templating->set('editor_comments', $commentall_text);
		$templating->set('comment_avatar', $comment_avatar);
	}

	$templating->block('comments_bottomall', 'admin_modules/admin_module_more_comments');
	$templating->set('pagination', $pagination);
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'commentall')
	{
		$text = trim($_POST['text']);

		if (empty($text))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'comment';
			header('Location: /admin.php?module=more_comments&view=editors');
			die();
		}

		$db->sqlquery("INSERT INTO `editor_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, core::$date));

		$db->sqlquery("SELECT `username`, `email` FROM ".$core->db_tables['users']." WHERE `user_group` IN (1,2,5) AND `user_id` != ?", array($_SESSION['user_id']));

		while ($emailer = $db->fetch())
		{
			$subject = "A new editor area comment on GamingOnLinux.com";

			$comment_email = $bbcode->email_bbcode($text);

			// message
			$html_message = "<p>Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux <a href=\"" . core::config('website_url') . "admin.php\">editor panel</a>:</p>
			<hr>
			<p>{$text}</p>";

			$plain_message = PHP_EOL."Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux editor panel: " . core::config('website_url') . "admin.php\r\n\r\n{$_POST['text']}\r\n\r\n";

			// Mail it
			if (core::config('send_emails') == 1)
			{
				$mail = new mail($emailer['email'], $subject, $html_message, $plain_message);
				$mail->send();
			}
		}

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'comment';
		header('Location: /admin.php?module=more_comments&view=editors');
	}
}
