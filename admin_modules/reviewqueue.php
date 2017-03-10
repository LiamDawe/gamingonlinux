<?php
$templating->set_previous('title', 'Review articles' . $templating->get('title', 1)  , 1);

$templating->merge('admin_modules/reviewqueue');

if (!isset($_GET['aid']))
{
	$templating->block('review_top', 'admin_modules/reviewqueue');

	$db->sqlquery("SELECT a.article_id, a.date, a.title, a.tagline, a.guest_username, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE `admin_review` = 1");
	while ($article = $db->fetch())
	{
		$templating->block('review_row', 'admin_modules/reviewqueue');
		$templating->set('url', core::config('website_url'));
		$templating->set('article_id', $article['article_id']);
		$templating->set('article_title', $article['title']);
		$templating->set('username', $article['username']);

		$templating->set('date_submitted', $core->format_date($article['date']));
	}
}

else
{
	if (!isset($message_map::$error) || $message_map::$error == 0)
	{
		$_SESSION['image_rand'] = rand();
		$article_class->reset_sessions();
	}

	$query = "SELECT
	a.`article_id`,
	a.`preview_code`,
	a.`title`,
	a.`slug`,
	a.`text`,
	a.`tagline`,
	a.`show_in_menu`,
	a.`active`,
	a.`tagline_image`,
	a.`guest_username`,
	a.`author_id`,
	a.`locked`,
	a.`locked_by`,
	a.`locked_date`,
	a.`gallery_tagline`,
	t.`filename` as gallery_tagline_filename,
	u.`username`,
	u2.`username` as username_lock
	FROM `articles` a
	LEFT JOIN `users` u on a.`author_id` = u.`user_id`
	LEFT JOIN `users` u2 ON a.`locked_by` = u2.`user_id`
	LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
	WHERE a.`article_id` = ?";

	$db->sqlquery($query, array($_GET['aid']));

	$article = $db->fetch();

	if (isset($_GET['unlock']) && $article['locked'] == 1 && $_GET['unlock'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$db->sqlquery("UPDATE `articles` SET `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($article['article_id']));

		$core->message("You have unlocked the article for others to edit!");

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($query, array($_GET['aid']), 'view_articles.php admin review');

		$article = $db->fetch();
	}

	if ((isset($_GET['lock']) && $_GET['lock'] == 1) && $article['locked'] == 0)
	{
		$db->sqlquery("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], core::$date, $article['article_id']));

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($query, array($_GET['aid']), 'view_articles.php admin review');

		$article = $db->fetch();
	}

	if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$core->message("This post is now locked while you edit, please click Edit to unlock it once finished.", NULL, 1);

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($query, array($_GET['aid']));

		$article = $db->fetch();
	}

	$_SESSION['original_text'] = $article['text'];

	$templating->block('review_item_top', 'admin_modules/reviewqueue');

	$edit_state = '';
	$edit_state_textarea = '';
	$editor_disabled = 0;
	if ($article['locked'] == 1)
	{
		// if it's locked, and you didn't lock it, no editing
		if ($article['locked_by'] != $_SESSION['user_id'])
		{
			$templating->block('edit_locked');
			$templating->set('locked_username', $article['username_lock']);

			$lock_date = $core->format_date($article['locked_date']);

			$templating->set('locked_date', $lock_date);

			$edit_state = 'disabled="disabled"';
			$edit_state_textarea = 'disabled';
			$editor_disabled = 1;

			$preview_action = 'formaction="admin.php?module=comments&aid=' . $_GET['aid'] . '"';
			$preview_text = 'Preview & Comments';
		}
		$preview_action = 'formaction="/admin.php?module=preview"';
		$preview_text = 'Preview & Edit More';
	}
	// if it's not locked, no editing
	else if ($article['locked'] == 0)
	{
		$edit_state = 'disabled="disabled"';
		$edit_state_textarea = 'disabled';
		$editor_disabled = 1;

		$preview_action = 'formaction="admin.php?module=comments&aid=' . $_GET['aid'] . '"';
		$preview_text = 'Preview & Comments';
	}

	$lock_button = '';
	if ($article['locked'] == 0)
	{
		$lock_button = '<a class="button_link" href="/admin.php?module=reviewqueue&aid=' . $article['article_id'] . '&lock=1">Lock For Editing</a><hr />';
	}
	else if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$lock_button = '<a class="button_link" href="/admin.php?module=reviewqueue&aid=' . $article['article_id'] . '&unlock=1">Unlock Article For Others</a><hr />';
	}
	$templating->set('lock_button', $lock_button);

	// get the edit row
	$templating->merge('admin_modules/article_form');

	$templating->block('preview_code', 'admin_modules/article_form');
	$templating->set('preview_url', core::config('website_url') . 'index.php?module=articles_full&aid=' . $article['article_id'] . '&preview_code=' . $article['preview_code']);
	$templating->set('edit_state', $edit_state);
	$templating->set('article_id', $article['article_id']);

	$templating->block('full_editor', 'admin_modules/article_form');
	$templating->set('max_filesize', core::readable_bytes(core::config('max_tagline_image_filesize')));
	$templating->set('edit_state', $edit_state);
	$templating->set('edit_state_textarea', $edit_state_textarea);
	$templating->set('main_formaction', '<form method="post" action="'.url.'admin.php?module=reviewqueue" enctype="multipart/form-data">');

	// get categorys
	$db->sqlquery("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']));
	while($categories_check = $db->fetch())
	{
		$categories_check_array[] = $categories_check['category_id'];
	}

	$categorys_list = '';
	$db->sqlquery("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC");
	while ($categorys = $db->fetch())
	{
		if (isset($message_map::$error) && $message_map::$error == 1)
		{
			if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
			{
				$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
			}

			else
			{
				$categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
			}
		}

		else
		{

			if (isset($categories_check_array) && in_array($categorys['category_id'], $categories_check_array))
			{
				$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
			}

			else
			{
				$categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
			}
		}
	}

	$templating->set('categories_list', $categorys_list);

	$games_list = $article_class->display_game_assoc($article['article_id']);

	$templating->set('games_list', $games_list);

	$templating->set('username', $article['username']);

	// if they have done it before set title, text and tagline
	if (isset($message_map::$error) && $message_map::$error == 1)
	{
		$templating->set('title', htmlentities($_SESSION['atitle'], ENT_QUOTES));
		$templating->set('tagline', $_SESSION['atagline']);
		$templating->set('slug', $_SESSION['aslug']);
		$text = $_SESSION['atext'];
	}
	else
	{
		$templating->set('title', htmlentities($article['title'], ENT_QUOTES));
		$templating->set('tagline', $article['tagline']);
		$templating->set('slug', $article['slug']);
		$text = $article['text'];
	}

	$tagline_image = $article_class->display_tagline_image($article);

	$templating->set('tagline_image', $tagline_image);

	$templating->set('max_height', core::config('article_image_max_height'));
	$templating->set('max_width', core::config('article_image_max_width'));

	$core->editor('text', $text, 1, $editor_disabled);

	$templating->block('review_bottom', 'admin_modules/reviewqueue');
	$templating->set('edit_state', $edit_state);

	$subscribe_check = '';
	if ($article['author_id'] == $_SESSION['user_id'])
	{
		$send_email = '';
		$db->sqlquery("SELECT `user_id`,`send_email` FROM `articles_subscriptions` WHERE `article_id` = ?", array($article['article_id']));
		$check_sub = $db->fetch();

		if ($db->num_rows() == 1)
		{
			if ($check_sub['send_email'] == 1)
			{
				$send_email = 'checked';
			}
		}
		$subscribe_check = 	'<label class="checkbox"><input type="checkbox" name="subscribe" '.$send_email.'/> Subscribe to article to receive comment replies via email</label>';
	}
	$templating->set('subscribe_box', $subscribe_check);

	$templating->set('preview_action', $preview_action);
	$templating->set('preview_text', $preview_text);

	$templating->set('article_id', $article['article_id']);
	$templating->set('author_id', $article['author_id']);

	$previously_uploaded = '';
	// add in uploaded images from database
	$previously_uploaded = $article_class->display_previous_uploads($article['article_id']);

	$templating->set('previously_uploaded', $previously_uploaded);

	$article_class->article_history($article['article_id']);
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Approve_Admin')
	{
		$return_page = "/admin.php?module=reviewqueue&aid={$_POST['article_id']}";
		article_class::publish_article(['return_page' => $return_page, 'type' => 'admin_review', 'new_notification_type' => 'article_admin_queue_approved', 'clear_notification_type' => 'article_admin_queue']);
	}
	
	// For editing a post from another admin in the review pool
	if ($_POST['act'] == 'edit')
	{
		if ($checked = $article_class->check_article_inputs("/admin.php?module=reviewqueue&aid={$_POST['article_id']}"))
		{
			$block = 0;
			if (isset($_POST['show_block']))
			{
				$block = 1;
			}

			$article_class->gallery_tagline($checked);

			$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `locked` = 0 WHERE `article_id` = ?", array($checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, $_POST['article_id']));

			if (isset($_SESSION['uploads']))
			{
				foreach($_SESSION['uploads'] as $key)
				{
					$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($_POST['article_id'], $key['image_name']));
				}
			}

			article_class::process_categories($_POST['article_id']);

			article_class::process_game_assoc($_POST['article_id']);

			if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
			{
				$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
			}

			// update history
			$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));

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
			unset($_SESSION['aslug']);
			unset($_SESSION['original_text']);
			unset($_SESSION['gallery_tagline_id']);
			unset($_SESSION['gallery_tagline_rand']);
			unset($_SESSION['gallery_tagline_filename']);

			if ($_POST['author_id'] != $_SESSION['user_id'])
			{
				// find the authors email
				$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']));
				$author_email = $db->fetch();


				// subject
				$subject = 'Your article was reviewed and edited on GamingOnLinux.com!';

				$nice_title = core::nice_title($_POST['title']);

				// message
				$message = "
				<html>
				<head>
				<title>$subject</title>
				</head>
				<body>
				<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
				<br />
				<p>{$_SESSION['username']} has reviewed and edited your article on <a href=\"http://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>, here's a link to the article: <a href=\"http://www.gamingonlinux.com/admin.php?module=reviewqueue&aid={$_POST['article_id']}/\">{$_POST['title']}</a></p>
				</body>
				</html>";

				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: GamingOnLinux.com Editor Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

				// Mail it
				if (core::config('send_emails') == 1)
				{
					mail($author_email['email'], $subject, $message, $headers);
				}
			}

			$_SESSION['message'] = 'admin_edited';
			header("Location: /admin.php?module=reviewqueue&aid={$_POST['article_id']}&lock=0");
		}
	}
}
