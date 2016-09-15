<?php
$temp_tagline = 0;
if (!empty($_POST['temp_tagline_image']))
{
	$temp_tagline = 1;
}

// check it hasn't been accepted already
$db->sqlquery("SELECT a.tagline_image, a.`active`, a.`date_submitted`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']));
$check_article = $db->fetch();
if ($check_article['active'] == 1)
{
	header("Location: /admin.php?module=reviewqueue&error=alreadyapproved");
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
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		header("Location: admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=empty&self={$_POST['submit_as_self']}&temp_tagline=$temp_tagline");
	}

	else if (strlen($_POST['tagline']) < 100)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		header("Location: admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=shorttagline&self={$_POST['submit_as_self']}&temp_tagline=$temp_tagline");
	}

	else if (strlen($_POST['tagline']) > 400)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		header("Location: admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=taglinetoolong&self={$_POST['submit_as_self']}&temp_tagline=$temp_tagline");
	}

	else if (strlen($_POST['title']) < 10)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		header("Location: admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=shorttitle&self={$_POST['submit_as_self']}&temp_tagline=$temp_tagline");
	}

	else if (isset($_POST['show_block']) && $editor_pick_count == core::config('editor_picks_limit'))
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		header("Location: admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=toomanypicks&self={$_POST['submit_as_self']}&temp_tagline=$temp_tagline");
	}

	else if (!isset($_SESSION['uploads_tagline']) && $check_article['tagline_image'] == '')
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$url = "admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=noimageselected&temp_tagline=$temp_tagline";

		header("Location: $url");
	}

	else
	{
		// show in the editors pick block section
		$block = 0;
		if (isset($_POST['show_block']))
		{
			$block = 1;
		}

		if ($_SESSION['user_id'] == $_POST['author_id'])
		{
			$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($_POST['article_id']));
			if (isset($_POST['subscribe']))
			{
				$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = 1, `send_email` = 1", array($_SESSION['user_id'], $_POST['article_id']));
			}
		}

		$text = trim($_POST['text']);
		$tagline = trim($_POST['tagline']);

		$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `article_id` = ?", array("{$_SESSION['username']} approved an article from the admin review queue.", core::$date, $_POST['article_id']));

		// remove all the comments made by admins
		$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));

		// since it's now up we need to add 1 to total article count, it now exists, yaay have a beer on me, just kidding get your wallet!
		$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");

		$title = strip_tags($_POST['title']);

		$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `locked` = 0 WHERE `article_id` = ?", array($title, $slug, $tagline, $text, $block, core::$date, $_SESSION['user_id'], $_POST['article_id']));

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

		// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
		unset($_SESSION['atitle']);
		unset($_SESSION['aslug']);
		unset($_SESSION['atagline']);
		unset($_SESSION['atext']);
		unset($_SESSION['acategories']);
		unset($_SESSION['tagerror']);
		unset($_SESSION['uploads']);
		unset($_SESSION['image_rand']);
		unset($_SESSION['uploads_tagline']);

		// if the person publishing it is not the author then email them
		if ($_POST['author_id'] != $_SESSION['user_id'])
		{
			// find the authors email
			$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']));
			$author_email = $db->fetch();

			// sort out registration email
			$to = $author_email['email'];

			// subject
			$subject = 'Your article was reviewed and published on GamingOnLinux.com!';

			$nice_title = $core->nice_title($_POST['title']);

			// message
			$message = "
			<html>
			<head>
			<title>Your article was review and approved GamingOnLinux.com!</title>
			</head>
			<body>
			<img src=\"http://www.gamingonlinux.com/templates/default/images/logo.png\" alt=\"Gaming On Linux\">
			<br />
			<p><strong>{$_SESSION['username']}</strong> has reviewed and published your article \"<a href=\"http://www.gamingonlinux.com/articles/$nice_title.{$_POST['article_id']}/\">{$title}</a>\" on <a href=\"https://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>.</p>
			</body>
			</html>";

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

			// Mail it
			if (core::config('send_emails') == 1)
			{
				mail($to, $subject, $message, $headers);
			}
		}

		include(core::config('path') . 'includes/telegram_poster.php');

		if (core::config('pretty_urls') == 1 && !isset($_POST['show_block']))
		{
			$telegram_url = core::config('website_url') . "articles/" . $_POST['slug'] . '.' . $_POST['article_id'];
			header("Location: " . $telegram_url);
		}
		else if (core::config('pretty_urls') == 1 && isset($_POST['show_block']))
		{
			$telegram_url = core::config('website_url') . "articles/" . $_POST['slug'] . '.' . $_POST['article_id'];
			header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}&message=accepted");
		}
		else
		{
			if (!isset($_POST['show_block']))
			{
				$telegram_url = core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$_POST['slug']}";
				header("Location: " . $telegram_url);
			}
			else
			{
				$telegram_url = core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$_POST['slug']}";
				header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}&message=accepted");
			}
		}

		telegram($title . ' ' . $telegram_url);
	}
}
