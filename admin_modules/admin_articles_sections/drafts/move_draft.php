<?php
$db->sqlquery("SELECT `article_id`, `author_id`, `tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
$grab_author = $db->fetch();
if ($grab_author['author_id'] == $_SESSION['user_id'])
{
	$text = trim($_POST['text']);
	$title = strip_tags($_POST['title']);

	$article_class->gallery_tagline($grab_author);

	$db->sqlquery("UPDATE `articles` SET `draft` = 0, `admin_review` = 1, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ? WHERE `article_id` = ?", array($title, $_POST['slug'], $_POST['tagline'], $text, $_POST['article_id']));

	// update admin notifications
	$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'article_admin_queue', core::$date, $_POST['article_id']));

	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
	}

	article_class::process_categories($_POST['article_id']);

	article_class::process_game_assoc($_POST['article_id']);

	// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
	unset($_SESSION['atitle']);
	unset($_SESSION['atagline']);
	unset($_SESSION['atext']);
	unset($_SESSION['acategories']);
	unset($_SESSION['agames']);
	unset($_SESSION['aactive']);
	unset($_SESSION['uploads']);
	unset($_SESSION['uploads_tagline']);
	unset($_SESSION['image_rand']);
	unset($_SESSION['gallery_tagline_id']);
	unset($_SESSION['gallery_tagline_rand']);
	unset($_SESSION['gallery_tagline_filename']);

	// email all editors apart from yourself
	$db->sqlquery("SELECT `user_id`, `email`, `username` FROM `users` WHERE `user_group` IN (1,2) AND `user_id` != ?", array($_SESSION['user_id']));
	$users_array = array();
	while ($email_users = $db->fetch())
	{
		if ($email_users['user_id'] != $_SESSION['user_id'] && $email_users['email'] == 1)
		{
			$users_array[$email_users['user_id']]['user_id'] = $email_users['user_id'];
			$users_array[$email_users['user_id']]['email'] = $email_users['email'];
			$users_array[$email_users['user_id']]['username'] = $email_users['username'];
		}
	}

	// subject
	$subject = core::config('site_title') . ": article submitted for review by {$_SESSION['username']}";

	foreach ($users_array as $email_user)
	{
		$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
		<p><strong>{$_SESSION['username']}</strong> has sent an article to be reviewed before publishing \"<strong><a href=\"" . core::config('website_url') . "admin.php?module=articles&view=adminreview&aid={$_POST['article_id']}\">{$title}</a></strong>\".</p>
		</body>
		</html>";
		
		$plain_message = "Hello {$email_user['username']}, {$_SESSION['username']} has sent an article to be reviewed before publishing on: " . core::config('website_url') . " You can review it here: " . core::config('website_url') . "admin.php?module=articles&view=adminreview&aid={$_POST['article_id']}";

		if (core::config('send_emails') == 1)
		{
			$mail = new mail($email_user['email'], $subject, $html_message, $plain_message);
			$mail->send();
		}
	}

	header("Location: /admin.php?module=articles&view=drafts&message=article_in_review");
}
else
{
	header("Location: " . core::config('website_url') . "admin.php?module=articles&view=drafts");
}
