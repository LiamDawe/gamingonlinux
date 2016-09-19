<?php
// check it hasn't been accepted already
$db->sqlquery("SELECT a.tagline_image, a.`active`, a.`date_submitted`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']));
$check_article = $db->fetch();
if ($check_article['active'] == 1)
{
	header("Location: /admin.php?module=articles&view=Submitted&error=alreadyapproved");
}

else
{
	// count how many editors picks we have
	$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");

	$editor_pick_count = $db->num_rows();

	// check its set, if not hard-set it based on the article title
	if (isset($_POST['slug']) && !empty($_POST['slug']))
	{
		$slug = $core->nice_title($_POST['slug']);
	}
	else
	{
		$slug = $core->nice_title($_POST['title']);
	}

	// make sure its not empty
	if (empty($_POST['title']) || empty($_POST['tagline']) || empty($_POST['text']) || empty($_POST['article_id']) || empty($slug))
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['atext2'] = $_POST['text2'];
		$_SESSION['atext3'] = $_POST['text3'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$self = 0;
		if (isset($_POST['submit_as_self']))
		{
			$self = 1;
		}

		header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=empty&self={$self}");
	}

	else if (strlen($_POST['tagline']) < 100)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['atext2'] = $_POST['text2'];
		$_SESSION['atext3'] = $_POST['text3'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$self = 0;
		if (isset($_POST['submit_as_self']))
		{
			$self = 1;
		}

		header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=shorttagline&self={$self}&temp_tagline=$temp_tagline");
	}

	else if (strlen($_POST['tagline']) > 400)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['atext2'] = $_POST['text2'];
		$_SESSION['atext3'] = $_POST['text3'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$self = 0;
		if (isset($_POST['submit_as_self']))
		{
			$self = 1;
		}

		header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=taglinetoolong&self={$self}&temp_tagline=$temp_tagline");
	}

	else if (strlen($_POST['title']) < 10)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['atext2'] = $_POST['text2'];
		$_SESSION['atext3'] = $_POST['text3'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$self = 0;
		if (isset($_POST['submit_as_self']))
		{
			$self = 1;
		}

		header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=shorttitle&self={$self}&temp_tagline=$temp_tagline");
	}

	else if (!isset($_SESSION['uploads_tagline']) && $check_article['tagline_image'] == '')
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['atext2'] = $_POST['text2'];
		$_SESSION['atext3'] = $_POST['text3'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$url = "admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=noimageselected&self={$self}&temp_tagline=$temp_tagline";

		header("Location: $url");
	}

	else if (isset($_POST['show_block']) && $editor_pick_count == 3)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['atext2'] = $_POST['text2'];
		$_SESSION['atext3'] = $_POST['text3'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$self = 0;
		if (isset($_POST['submit_as_self']))
		{
			$self = 1;
		}

		header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=toomanypicks&self={$self}&temp_tagline=$temp_tagline");
	}

	else
	{
		// show in the editors pick block section
		$block = 0;
		if (isset($_POST['show_block']))
		{
			$block = 1;
		}

		// if they are resetting the article to be published by themselves
		$text = trim($_POST['text']);
		$author_id = $_POST['author_id'];
		$submission_date = $check_article['date_submitted'];
		$tagline = trim($_POST['tagline']);

		if (isset($_POST['submit_as_self']))
		{
			$author_id = $_SESSION['user_id'];
			$submission_date = '';

			if (!empty($check_article['username']))
			{
				$submitted_by_user = $check_article['username'];
			}

			else
			{
				$submitted_by_user = $check_article['guest_username'];
			}

			$text = $_POST['text'] . "\r\n\r\n[i]Thanks to " . $submitted_by_user . ' for letting us know![/i]';
		}
		$db->sqlquery("DELETE FROM `admin_notifications` WHERE `article_id` = ?", array($_POST['article_id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?, `article_id` = ?", array("{$_SESSION['username']} approved a user submitted article.", core::$date, core::$date, $_POST['article_id']));

		// remove all the comments made by admins
		$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));

		$title = strip_tags($_POST['title']);

		$db->sqlquery("UPDATE `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `date_submitted` = ?, `submitted_unapproved` = 0, `locked` = 0 WHERE `article_id` = ?", array($author_id, $title, $slug, $tagline, $text, $block, core::$date, $submission_date, $_POST['article_id']));

		if (isset($_SESSION['uploads']))
		{
			foreach($_SESSION['uploads'] as $key)
			{
				$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($_POST['article_id'], $key['image_name']));
			}
		}

		$db->sqlquery("DELETE FROM `article_category_reference` WHERE `article_id` = ?", array($_POST['article_id']));

		if (isset($_POST['categories']))
		{
			foreach($_POST['categories'] as $category)
			{
				$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($_POST['article_id'], $category));
			}
		}

		// process game associations
		$db->sqlquery("DELETE FROM `article_game_assoc` WHERE `article_id` = ?", array($_POST['article_id']));

		if (isset($_POST['games']))
		{
			foreach($_POST['games'] as $game)
			{
				$db->sqlquery("INSERT INTO `article_game_assoc` SET `article_id` = ?, `game_id` = ?", array($_POST['article_id'], $game));
			}
		}

		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
		}

		unset($_SESSION['atitle']);
		unset($_SESSION['atagline']);
		unset($_SESSION['atext']);
		unset($_SESSION['aslug']);
		unset($_SESSION['acategories']);
		unset($_SESSION['tagerror']);
		unset($_SESSION['aactive']);
		unset($_SESSION['uploads']);
		unset($_SESSION['uploads_tagline']);
		unset($_SESSION['image_rand']);

		// pick the email to use
		$email = '';
		if (!empty($check_article['guest_email']))
		{
			$email = $check_article['guest_email'];
		}

		else if (!empty($check_article['email']))
		{
			$email = $check_article['email'];
		}

		// sort out registration email
		$to = $email;

		// subject
		$subject = 'Your article was approved on GamingOnLinux.com!';

		$nice_title = $core->nice_title($_POST['title']);

		// message
		$message = "
		<html>
		<head>
		<title>Your article was approved GamingOnLinux.com!</title>
		</head>
		<body>
		<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
		<br />
		<p>We have accepted your article \"<a href=\"http://www.gamingonlinux.com/articles/$slug.{$_POST['article_id']}/\">{$title}</a>\" on <a href=\"http://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>. Thank you for taking the time to send us news we really appreciate the help, you are awesome.</p>
		</body>
		</html>";

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

		include(core::config('path') . 'includes/telegram_poster.php');

		// Mail it
		mail($to, $subject, $message, $headers);

		telegram($title . ' ' . core::config('website_url') . "articles/" . $slug . '.' . $_POST['article_id']);

		if (!isset($_POST['show_block']))
		{
			header("Location: " . core::config('website_url') . "admin.php?module=articles&view=Submitted&accepted");
		}
		else {
			header("Location: ". core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
		}


	}
}
