<?php // this page is for brand new articles instantly being sent into the review queue
$return_page = "admin.php?module=add_article";
if ($checked = $article_class->check_article_inputs($return_page))
{
	$gallery_tagline_sql = $article_class->gallery_tagline();

	$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = 0, `active` = 0, `admin_review` = 1, `date` = ?, `preview_code` = ? $gallery_tagline_sql", array($_SESSION['user_id'], $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], core::$date, core::random_id()));

	$article_id = $db->grab_id();

	// update admin notifications
	$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'article_admin_queue', core::$date, $article_id));

	// update any uploaded images to have this article id, stop any images not being attached to an article
	if (isset($_SESSION['uploads']))
	{
		foreach($_SESSION['uploads'] as $key)
		{
			$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($article_id, $key['image_name']));
		}
	}

	article_class::process_categories($article_id);

	article_class::process_game_assoc($article_id);

	// force subscribe, so they don't lose editors comments
	$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = 1, `send_email` = 1", array($_SESSION['user_id'], $article_id));

	// upload tagline image
	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name']);
	}

	// article has been posted, remove any saved info from errors (so the fields don't get populated if you post again)
	unset($_SESSION['atitle']);
	unset($_SESSION['aslug']);
	unset($_SESSION['atagline']);
	unset($_SESSION['atext']);
	unset($_SESSION['acategories']);
	unset($_SESSION['agames']);
	unset($_SESSION['uploads_tagline']);
	unset($_SESSION['image_rand']);
	unset($_SESSION['original_text']);
	unset($_SESSION['gallery_tagline_id']);
	unset($_SESSION['gallery_tagline_rand']);
	unset($_SESSION['gallery_tagline_filename']);

	// email all editors apart from yourself
	$db->sqlquery("SELECT `user_id`, `email`, `username` FROM `users` WHERE `user_group` IN (1,2,5) AND `user_id` != ?", array($_SESSION['user_id']));
	$users_array = array();
	while ($users = $db->fetch())
	{
		if ($users['user_id'] != $_SESSION['user_id'] && $users['email'] == 1)
		{
			$users_array[$users['user_id']]['user_id'] = $users['user_id'];
			$users_array[$users['user_id']]['email'] = $users['email'];
			$users_array[$users['user_id']]['username'] = $users['username'];
		}
	}

	// subject
	$subject = "GamingOnLinux.com article submitted for review by {$_SESSION['username']}";

	foreach ($users_array as $email_user)
	{
		$to = $email_user['email'];

		// message
		$message = '
		<html>
		<head>
		<title>GamingOnLinux.com article submitted for review by ' . $_SESSION['username'] . '</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		</head>
		<body>
		<img src="' . core::config('website_url') . 'templates/default/images/logo.png" alt="Gaming On Linux">
		<br />
		<p>Hello <strong>' . $email_user['username'] . '</strong>,</p>
		<p><strong>' . $_SESSION['username'] . '</strong> has sent an article to be reviewed before publishing "<strong><a href="' . core::config('website_url') . 'admin.php?module=articles&view=adminreview&aid=' . $article_id . '">' . $title . '</a></strong>".</p>
		</body>
		</html>';

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

		// Mail it
		if (core::config('send_emails') == 1)
		{
			mail($to, $subject, $message, $headers);
		}
	}

	header("Location: /admin.php?module=reviewqueue&message=article_in_review");
}
