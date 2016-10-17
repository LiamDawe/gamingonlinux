<?php
$temp_tagline = 0;
if (!empty($_SESSION['uploads_tagline']['image_name']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
{
	$temp_tagline = 1;
}

// check it hasn't been published already
$db->sqlquery("SELECT a.tagline_image, a.`active`, a.`date_submitted`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']));
$check_article = $db->fetch();
if ($check_article['active'] == 1)
{
	header("Location: admin.php?module=articles&view=drafts&message=alreadypublished");
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
	if (empty($_POST['title']) || empty($_POST['tagline']) || empty($_POST['text']) || empty($slug))
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=empty&temp_tagline=$temp_tagline";

		header("Location: $url");
		die();
	}

	// make sure tagline isn't too short
	else if (strlen($_POST['tagline']) < 100)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=shorttagline&temp_tagline=$temp_tagline";

		header("Location: $url");
		die();
	}

	// if tagline is too long
	else if (strlen($_POST['tagline']) > 400)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=taglinetoolong&temp_tagline=$temp_tagline";

		header("Location: $url");
		die();
	}

	// if title is too short
	else if (strlen($_POST['title']) < 10)
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&message=shorttile&temp_tagline=$temp_tagline";

		header("Location: $url");
		die();
	}

	// if they try to make it an editor pick, and there's too many already
	else if (isset($_POST['show_block']) && $editor_pick_count == core::config('editor_picks_limit'))
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		$url = "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=toomanypicks&temp_tagline=$temp_tagline";

		header("Location: $url");
		die();
	}

	// if it's a draft and there's no uploaded tagline image, and no stored image already
	else if (!isset($_SESSION['uploads_tagline']) && $check_article['tagline_image'] == '')
	{
		$_SESSION['atitle'] = $_POST['title'];
		$_SESSION['aslug'] = $slug;
		$_SESSION['atagline'] = $_POST['tagline'];
		$_SESSION['atext'] = $_POST['text'];
		$_SESSION['acategories'] = $_POST['categories'];
		$_SESSION['agames'] = $_POST['games'];

		header("Location: admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&error=noimageselected&temp_tagline=$temp_tagline");
		die();
	}

	else
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

		// remove all the comments made by admins
		$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));

		$text = trim($_POST['text']);
		$tagline = trim($_POST['tagline']);

		// since it's now up we need to add 1 to total article count, it now exists, yaay have a beer on me, just kidding get your wallet!
		$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");

		$title = strip_tags($_POST['title']);

		$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `locked` = 0, `draft` = 0 WHERE `article_id` = ?", array($title, $slug, $tagline, $text, $block, core::$date, $_SESSION['user_id'], $_POST['article_id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `created` = ?, `action` = ?, `completed_date` = ?, `article_id` = ?", array(core::$date, "{$_SESSION['username']} published a new article.", core::$date, $_POST['article_id']));

		if (isset($_SESSION['uploads']))
		{
			foreach($_SESSION['uploads'] as $key)
			{
				$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($_POST['article_id'], $key['image_name']));
			}
		}

		$article_class->process_categories($_POST['article_id']);

		$article_class->article_game_assoc($_POST['article_id']);

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
		unset($_SESSION['agame']);
		unset($_SESSION['tagerror']);
		unset($_SESSION['uploads']);
		unset($_SESSION['image_rand']);
		unset($_SESSION['uploads_tagline']);

		include(core::config('path') . 'includes/telegram_poster.php');

		if (core::config('pretty_urls') == 1 && !isset($_POST['show_block']))
		{
			telegram($title . ' ' . core::config('website_url') . "articles/" . $_POST['slug'] . '.' . $_POST['article_id']);
			header("Location: /articles/" . $_POST['slug'] . '.' . $_POST['article_id']);
		}
		else if (core::config('pretty_urls') == 1 && isset($_POST['show_block']))
		{
			telegram($title . ' ' . core::config('website_url') . "articles/" . $_POST['slug'] . '.' . $_POST['article_id']);
			header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
		}
		else
		{
			if (!isset($_POST['show_block']))
			{
				telegram($title . ' ' . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$_POST['slug']}");
				header("Location: " . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$_POST['slug']}");
			}
			else
			{
				telegram($title . ' ' . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$_POST['slug']}");
				header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
			}
		}
	}
}
