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

	article::process_categories($article_id);

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
	unset($_SESSION['uploads_tagline']);
	unset($_SESSION['image_rand']);
	unset($_SESSION['original_text']);
	unset($_SESSION['gallery_tagline_id']);
	unset($_SESSION['gallery_tagline_rand']);
	unset($_SESSION['gallery_tagline_filename']);
	
	$sql_replace = $user->get_group_ids('article_submission_emails');
                
	$in = str_repeat('?,', count($sql_replace) - 1) . '?';
	
	$sql_replace[] = $_SESSION['user_id'];
	
	// email all editors apart from yourself
	$users_array = $dbl->run("SELECT m.user_id, u.email, u.username from `user_group_membership` m INNER JOIN `users` u ON m.user_id = u.user_id WHERE m.group_id IN ($in) AND u.`submission_emails` = 1 AND u.`user_id` != ?", $sql_replace)->fetch_all();

	// subject
	$subject = $core->config('site_title') . ": article submitted for review by {$_SESSION['username']}";

	foreach ($users_array as $email_user)
	{
		$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
		<p><strong>{$_SESSION['username']}</strong> has sent an article to be reviewed before publishing \"<strong><a href=\"" . $core->config('website_url') . "admin.php?module=articles&view=adminreview&aid={$article_id}\">{$checked['title']}</a></strong>\".</p>
		</body>
		</html>";
		
		$plain_message = "Hello {$email_user['username']}, {$_SESSION['username']} has sent an article to be reviewed before publishing on: " . $core->config('website_url') . " You can review it here: " . $core->config('website_url') . "admin.php?module=articles&view=adminreview&aid={$article_id}";

		if ($core->config('send_emails') == 1)
		{
			$mail = new mailer($core);
			$mail->sendMail($email_user['email'], $subject, $html_message, $plain_message);
		}
	}

	header("Location: /admin.php?module=reviewqueue&message=article_in_review");
}
