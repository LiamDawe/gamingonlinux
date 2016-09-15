<?php
// This file is for putting a new article directly into the admin review queue
$text = trim($_POST['text']);

// count how many editors picks we have
$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");

$editor_pick_count = $db->num_rows();

// make sure its not empty
if (empty($_POST['title']) || empty($_POST['tagline']) || empty($text))
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $_POST['aslug'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];
	$_SESSION['agames'] = $_POST['games'];

	header("Location: admin.php?module=articles&view=add&error=empty&temp_tagline=$temp_tagline");
}

else if (strlen($_POST['tagline']) < 100)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $_POST['aslug'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];
	$_SESSION['agames'] = $_POST['games'];

	header("Location: admin.php?module=articles&view=add&error=shorttagline&temp_tagline=$temp_tagline");
}

else if (strlen($_POST['tagline']) > 400)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $_POST['aslug'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];
	$_SESSION['agames'] = $_POST['games'];

	header("Location: admin.php?module=articles&view=add&error=taglinetoolong&temp_tagline=$temp_tagline");
}

else if (strlen($_POST['title']) < 10)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $_POST['aslug'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];
	$_SESSION['agames'] = $_POST['games'];

	header("Location: admin.php?module=articles&view=add&error=shorttitle&temp_tagline=$temp_tagline");
}

else if (isset($_POST['show_block']) && $editor_pick_count == 3)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['aslug'] = $_POST['aslug'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];
	$_SESSION['agames'] = $_POST['games'];

	header("Location: admin.php?module=articles&view=add&error=toomanypicks&temp_tagline=$temp_tagline");
}

else
{
	$title = strip_tags($_POST['title']);

	$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = 0, `active` = 0, `admin_review` = 1, `date` = ?, `preview_code` = ?", array($_SESSION['user_id'], $title, $_POST['slug'], $_POST['tagline'], $text, core::$date, $core->random_id()));

	$article_id = $db->grab_id();

	// update admin notifications
	$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `article_id` = ?", array("{$_SESSION['username']} sent a new article to the admin review queue.", core::$date, $article_id));

	// update any uploaded images to have this article id, stop any images not being attached to an article
	if (isset($_SESSION['uploads']))
	{
		foreach($_SESSION['uploads'] as $key)
		{
			$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($article_id, $key['image_name']));
		}
	}

	// add the category tags
	if (isset($_POST['categories']))
	{
		foreach($_POST['categories'] as $category)
		{
			$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($article_id, $category));
		}
	}

	// process game associations
	if (isset($_POST['games']))
	{
		foreach($_POST['games'] as $game)
		{
			$db->sqlquery("INSERT INTO `article_game_assoc` SET `article_id` = ?, `game_id` = ?", array($article_id, $game));
		}
	}

	// check if they are subscribing
	if (isset($_POST['subscribe']))
	{
		$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?", array($_SESSION['user_id'], $article_id));
	}

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

	// email all editors apart from yourself
	$db->sqlquery("SELECT `user_id`, `email`, `username` FROM `users` WHERE `user_group` IN (1,2) AND `user_id` != ?", array($_SESSION['user_id']));
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

			header("Location: /admin.php?module=reviewqueue&message=sentforreview");
		}
