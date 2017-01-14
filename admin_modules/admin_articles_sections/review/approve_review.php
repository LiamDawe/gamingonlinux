<?php
// check it hasn't been accepted already
$db->sqlquery("SELECT a.tagline_image, a.`active`, a.`date_submitted`, a.`guest_username`, a.`guest_email`, a.gallery_tagline, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']));
$check_article = $db->fetch();
if ($check_article['active'] == 1)
{
	header("Location: /admin.php?module=reviewqueue&error=alreadyapproved");
}

else
{
	$return_page = "/admin.php?module=reviewqueue&aid={$_POST['article_id']}";
	if ($checked = $article_class->check_article_inputs($return_page))
	{
		// show in the editors pick block section
		$block = 0;
		if (isset($_POST['show_block']))
		{
			$block = 1;
		}

		// clean up subscriptions from admin comments
		if ($_SESSION['user_id'] == $_POST['author_id'])
		{
			$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($_POST['article_id']));
			if (isset($_POST['subscribe']))
			{
				$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = 1, `send_email` = 1", array($_SESSION['user_id'], $_POST['article_id']));
			}
		}

		$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = ?", array(core::$date, $_POST['article_id'], 'article_admin_queue'));
		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], 'article_admin_queue_approved', core::$date, core::$date, $_POST['article_id']));

		// remove all the comments made by admins
		$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));

		// since it's now up we need to add 1 to total article count, it now exists, yaay have a beer on me, just kidding get your wallet!
		$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");

		$article_class->gallery_tagline($checked);

		$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `locked` = 0 WHERE `article_id` = ?", array($checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, core::$date, $_SESSION['user_id'], $_POST['article_id']));

		if (isset($_SESSION['uploads']))
		{
			foreach($_SESSION['uploads'] as $key)
			{
				$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($_POST['article_id'], $key['image_name']));
			}
		}

		$article_class->process_categories($_POST['article_id']);

		$article_class->process_game_assoc($_POST['article_id']);

		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
		}

		// since they are approving and not neccisarily editing, check if the text matches, if it doesnt they have edited it
		if ($_SESSION['original_text'] != $checked['text'])
		{
			$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));
		}

		// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
		unset($_SESSION['atitle']);
		unset($_SESSION['aslug']);
		unset($_SESSION['atagline']);
		unset($_SESSION['atext']);
		unset($_SESSION['acategories']);
		unset($_SESSION['agame']);
		unset($_SESSION['uploads']);
		unset($_SESSION['image_rand']);
		unset($_SESSION['uploads_tagline']);
		unset($_SESSION['original_text']);
		unset($_SESSION['gallery_tagline_id']);
		unset($_SESSION['gallery_tagline_rand']);
		unset($_SESSION['gallery_tagline_filename']);

		// if the person publishing it is not the author then email them
		if ($_POST['author_id'] != $_SESSION['user_id'])
		{
			// find the authors email
			$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']));
			$author_email = $db->fetch();

			// subject
			$subject = 'Your article was reviewed and published on GamingOnLinux.com!';

			// message
			$message = "
			<html>
			<head>
			<title>Your article was review and approved GamingOnLinux.com!</title>
			</head>
			<body>
			<img src=\"http://www.gamingonlinux.com/templates/default/images/logo.png\" alt=\"Gaming On Linux\">
			<br />
			<p><strong>{$_SESSION['username']}</strong> has reviewed and published your article \"<a href=\"http://www.gamingonlinux.com/articles/{$checked['slug']}.{$_POST['article_id']}/\">{$checked['title']}</a>\" on <a href=\"https://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>.</p>
			</body>
			</html>";

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

			// Mail it
			if (core::config('send_emails') == 1)
			{
				mail($author_email['email'], $subject, $message, $headers);
			}
		}

		include(core::config('path') . 'includes/telegram_poster.php');

		if (core::config('pretty_urls') == 1 && !isset($_POST['show_block']))
		{
			$telegram_url = core::config('website_url') . "articles/" . $checked['slug'] . '.' . $_POST['article_id'];
			header("Location: " . $telegram_url);
		}
		else if (core::config('pretty_urls') == 1 && isset($_POST['show_block']))
		{
			$telegram_url = core::config('website_url') . "articles/" . $checked['slug'] . '.' . $_POST['article_id'];
			header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}&message=accepted");
		}
		else
		{
			if (!isset($_POST['show_block']))
			{
				$telegram_url = core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$checked['slug']}";
				header("Location: " . $telegram_url);
			}
			else
			{
				$telegram_url = core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$checked['slug']}";
				header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}&message=accepted");
			}
		}

		telegram($checked['title'] . ' ' . $telegram_url);
	}
}
