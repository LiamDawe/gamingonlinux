<?php
// check it hasn't been published already
$db->sqlquery("SELECT a.tagline_image, a.`active`, a.`date_submitted`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']));
$check_article = $db->fetch();
if ($check_article['active'] == 1)
{
	header("Location: admin.php?module=articles&view=drafts&message=alreadypublished");
}
else
{
	$return_page = "/admin.php?module=articles&view=drafts&aid={$_POST['article_id']}";
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

		// remove all the comments made by admins
		$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));

		$text = trim($_POST['text']);
		$tagline = trim($_POST['tagline']);

		// since it's now up we need to add 1 to total article count, it now exists, yaay have a beer on me, just kidding get your wallet!
		$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_articles'");

		$title = strip_tags($_POST['title']);

		$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `admin_review` = 0, `reviewed_by_id` = ?, `locked` = 0, `draft` = 0 WHERE `article_id` = ?", array($checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, core::$date, $_SESSION['user_id'], $_POST['article_id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `created` = ?, `action` = ?, `completed_date` = ?, `article_id` = ?", array(core::$date, "{$_SESSION['username']} published a new article.", core::$date, $_POST['article_id']));

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

		include(core::config('path') . 'includes/telegram_poster.php');

		if (core::config('pretty_urls') == 1 && !isset($_POST['show_block']))
		{
			telegram($checked['title'] . ' ' . core::config('website_url') . "articles/" . $checked['slug'] . '.' . $_POST['article_id']);
			header("Location: /articles/" . $checked['slug'] . '.' . $_POST['article_id']);
		}
		else if (core::config('pretty_urls') == 1 && isset($_POST['show_block']))
		{
			telegram($checked['title'] . ' ' . core::config('website_url') . "articles/" . $checked['slug'] . '.' . $_POST['article_id']);
			header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
		}
		else
		{
			if (!isset($_POST['show_block']))
			{
				telegram($checked['title'] . ' ' . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$checked['slug']}");
				header("Location: " . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$_POST['slug']}");
			}
			else
			{
				telegram($checked['title'] . ' ' . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}&title={$checked['slug']}");
				header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
			}
		}
	}
}
