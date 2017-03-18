<?php
$templating->merge('admin_modules/admin_module_articles');
$templating->set('article_css', 'articleadmin');

if (!isset($_GET['view']) && !isset($_POST['act']))
{
	$core->message("Looks like you took a wrong turn!");
}

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'Edit')
	{
		$templating->set_previous('title', 'Edit article' . $templating->get('title', 1)  , 1);
		if (!isset($message_map::$error) || $message_map::$error == 0)
		{
			$_SESSION['image_rand'] = rand();

			$article_class->reset_sessions();
		}
		$article_id = $_GET['article_id'];

		// make sure its a number
		if (!is_numeric($_GET['article_id']))
		{
			$core->message('That is not a correct Article ID!');
		}

		else
		{
			$article_info_sql = "SELECT
			a.`article_id`,
			a.`title`,
			a.`slug`,
			a.`tagline`,
			a.`text`,
			a.`show_in_menu`,
			a.`active`,
			a.`guest_username`,
			a.`tagline_image`,
			a.`locked`,
			a.`locked_by`,
			a.`locked_date`,
			a.`gallery_tagline`,
			t.`filename` as `gallery_tagline_filename`,
			u.`username`,
			u2.`username` as `username_lock`
			FROM
			`articles` a
			LEFT JOIN
			`users` u on a.author_id = u.user_id
			LEFT JOIN
			`users` u2 ON a.locked_by = u2.user_id
			LEFT JOIN
			`articles_tagline_gallery` t ON t.id = a.gallery_tagline
			WHERE `article_id` = ?";
			$db->sqlquery($article_info_sql, array($_GET['article_id']));

			$article = $db->fetch();

			if (isset($_GET['unlock']) && $article['locked'] == 1 && $_GET['unlock'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$db->sqlquery("UPDATE `articles` SET `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($article['article_id']));

				$core->message("You have unlocked the article for others to edit!");

				// we need to re-catch the article info as we have changed lock status
				$db->sqlquery($article_info_sql, array($article_id));

				$article = $db->fetch();
			}

			if (isset($_GET['lock']) && $_GET['lock'] == 1 && $article['locked'] == 0)
			{
				$db->sqlquery("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], core::$date, $article['article_id']));

				// we need to re-catch the article info as we have changed lock status
				$db->sqlquery($article_info_sql, array($article_id));

				$article = $db->fetch();
			}

			if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$core->message("This post is now locked while you edit, please click Edit to unlock it once finished.", NULL, 1);

				// we need to re-catch the article info as we have changed lock status
				$db->sqlquery($article_info_sql, array($article_id));

				$article = $db->fetch();
			}

			$_SESSION['original_text'] = $article['text'];

			$edit_state = '';
			$edit_state_textarea = '';
			$editor_disabled = 0;
			if ($article['locked'] == 1)
			{
				if ($article['locked_by'] != $_SESSION['user_id'])
				{
					$templating->block('edit_locked');
					$templating->set('locked_username', $article['username_lock']);

					$lock_date = $core->format_date($article['locked_date']);

					$templating->set('locked_date', $lock_date);

					$edit_state = 'disabled="disabled"';
					$edit_state_textarea = 'disabled';
					$editor_disabled = 1;
				}
			}
			else if ($article['locked'] == 0)
			{
				$edit_state = 'disabled="disabled"';
				$edit_state_textarea = 'disabled';
				$editor_disabled = 1;
			}

			$templating->block('edit_top', 'admin_modules/admin_module_articles');
			$lock_button = '';
			if ($article['locked'] == 0)
			{
				$lock_button = '<a class="button_link" href="/admin.php?module=articles&view=Edit&article_id=' . $article['article_id'] . '&lock=1">Lock For Editing</a><hr />';
			}
			else if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$lock_button = '<a class="button_link" href="/admin.php?module=articles&view=Edit&article_id=' . $article['article_id'] . '&unlock=1">Unlock Article For Others</a><hr />';
			}
			$templating->set('lock_button', $lock_button);

			// get the edit row
			$templating->merge('admin_modules/article_form');
			$templating->block('full_editor', 'admin_modules/article_form');
			$templating->set('max_filesize', core::readable_bytes(core::config('max_tagline_image_filesize')));
			$templating->set('edit_state', $edit_state);
			$templating->set('edit_state_textarea', $edit_state_textarea);

			$brandnew = '';
			if (isset($_GET['brandnew']))
			{
				$brandnew = '&brandnew=1';
			}

			$templating->set('brandnew_check', $brandnew);

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
				if (isset($_GET['error']))
				{
					if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
					}
				}

				else
				{

					if (isset($categories_check_array) && in_array($categorys['category_id'], $categories_check_array))
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
					}
				}
			}

			$templating->set('categories_list', $categorys_list);

			// get games list
			$games_list = $article_class->display_game_assoc($article['article_id']);

			$templating->set('games_list', $games_list);

			$text = $article['text'];
			$previously_uploaded = '';
			// if they have done it before set title, text and tagline
			if (isset($message_map::$error) && $message_map::$error == 1)
			{
				die();
				$templating->set('title', htmlentities($_SESSION['atitle'], ENT_QUOTES));
				$templating->set('tagline', $_SESSION['atagline']);
				$templating->set('slug', $_SESSION['aslug']);

				$text = $_SESSION['atext'];

				// sort out previously uploaded images
				$previously_uploaded	= $article_class->display_previous_uploads();
			}

			else
			{
				$templating->set('title', htmlentities($article['title'], ENT_QUOTES));
				$templating->set('tagline', $article['tagline']);
				$templating->set('slug', $article['slug']);
			}

			$templating->set('main_formaction', '<form method="post" action="'.core::config('website_url').'admin.php?module=articles" enctype="multipart/form-data">');

			if (empty($article['username']))
			{
				$username = $article['guest_username'];
			}

			else
			{
				$username = $article['username'];
			}

			$templating->set('username', $username);

			$tagline_image = $article_class->display_tagline_image($article);
			$templating->set('tagline_image', $tagline_image);

			$tagline_image = '';
			$temp_tagline_image = '';

			// add in uploaded images from database
			$previously_uploaded	= $article_class->display_previous_uploads($article['article_id']);

			$templating->set('previously_uploaded', $previously_uploaded);

			$templating->set('temp_tagline_image', $temp_tagline_image);

			$templating->set('max_height', core::config('article_image_max_height'));
			$templating->set('max_width', core::config('article_image_max_width'));

			$core->editor(['name' => 'text', 'content' => $text, 'disabled' => $editor_disabled, 'editor_id' => 'article_text']);

			$templating->block('edit_bottom', 'admin_modules/admin_module_articles');
			$templating->set('edit_state', $edit_state);

			$templating->set('previously_uploaded', $previously_uploaded);

			// check if we need to set article appear in the articles block
			if ($article['show_in_menu'] == 1)
			{
				$templating->set('show_block_check', 'checked');
			}

			else
			{
				$templating->set('show_block_check', '');
			}

			if (isset($_GET['error']) && $_GET['error'] == 'tagline_image')
			{
				if ($_SESSION['aactive'] == 1)
				{
					$templating->set('show_article_check', 'checked');
				}

				else
				{
					$templating->set('show_article_check', '');
				}
			}

			else
			{
				if ($article['active'] == 1)
				{
					$templating->set('show_article_check', 'checked');
				}

				else
				{
					$templating->set('show_article_check', '');
				}
			}

			$templating->set('article_id', $article['article_id']);

			$article_class->article_history($article['article_id']);
		}
	}

	// manage articles
	if ($_GET['view'] == 'manage')
	{
		$templating->set_previous('title', 'Articles' . $templating->get('title', 1)  , 1);
		if (!isset($_GET['category_id']) && !isset($_GET['category']))
		{
			$templating->block('manage_cat_top');

			// list categorys and all option
			$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
			while ($category = $db->fetch())
			{
				$templating->block('manage_cat');
				$templating->set('category_id', $category['category_id']);
				$templating->set('category_name', $category['category_name']);
			}

		}

		// For viewing inactive/all articles
		if (!isset($_GET['category_id']) && isset($_GET['category']))
		{
			// paging for pagination
			if (!isset($_GET['page']) || $_GET['page'] <= 0)
			{
				$page = 1;
			}

			else if (is_numeric($_GET['page']))
			{
				$page = $_GET['page'];
			}

			if ($_GET['category'] == 'inactive')
			{
				$active = 0;
				$paginate_link = "admin.php?module=articles&view=manage&category=inactive&";
				$article_query = "SELECT a.article_id, a.title, a.tagline, a.text, a.date, a.comment_count, a.views, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id  WHERE a.`active` = 0 AND a.`admin_review` = 0 AND a.`draft` = 0 AND a.submitted_unapproved = 0 ORDER BY a.`date` DESC LIMIT ?, 9";
				$count_query = "SELECT `article_id` FROM `articles` WHERE `active` = 0 AND `admin_review` = 0 AND `draft` = 0 AND `submitted_unapproved` = 0";
			}

			else if ($_GET['category'] == 'all')
			{
				$active = 1;
				$paginate_link = "admin.php?module=articles&view=manage&category=all&";
				$article_query = "SELECT a.article_id, a.title, a.tagline, a.text, a.date, a.comment_count, a.views, u.username FROM `articles` a JOIN `users` u on a.author_id = u.user_id ORDER BY a.`date` DESC LIMIT ?, 9";
				$count_query = "SELECT `article_id` FROM `articles`";
			}

			// count how many there is in total
			$db->sqlquery($count_query);
			$total = $db->num_rows();

			if ($total == 0)
			{
				$core->message('Category empty!');
			}

			else
			{
				// sort out the pagination link
				$pagination = $core->pagination_link(9, $total, $paginate_link, $page);

				$db->sqlquery($article_query, array($core->start));
				$article_manage = $db->fetch_all_rows();

				foreach ($article_manage as $article)
				{
					// make date human readable
					$date = $core->format_date($article['date']);

					// get the article row template
					$templating->block('manage_row');

					// sort out the categories (tags)
					$categories_list = '';
					$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? LIMIT 4", array($article['article_id']));
					while ($get_categories = $db->fetch())
					{
						$categories_list .= " <a href=\"/articles/category/{$get_categories['category_id']}\"><span class=\"label label-info\">{$get_categories['category_name']}</span></a> ";
					}

					if (!empty($categories_list))
					{
						$categories_list = '<p class="small muted">In: ' . $categories_list . '</p>';
					}
					$templating->set('categories_list', $categories_list);

					$templating->set('title', $article['title']);
					$templating->set('username', $article['username']);
					$templating->set('date', $date);
					$templating->set('text', bbcode($article['tagline']));
					$templating->set('article_id', $article['article_id']);
					$templating->set('comment_count', $article['comment_count']);
					$templating->set('views', $article['views']);
					$templating->set('article_link', core::nice_title($article['title']) . '.' . $article['article_id']);
				}

				$templating->block('manage_bottom');
				$templating->set('pagination', $pagination);
			}
		}

		// For viewing per-category
		else if (isset($_GET['category_id']))
		{
			// paging for pagination
			if (!isset($_GET['page']) || $_GET['page'] <= 0)
			{
				$page = 1;
			}

			else if (is_numeric($_GET['page']))
			{
				$page = $_GET['page'];
			}

			// count how many there is in total
			$db->sqlquery("SELECT `article_id` FROM `articles`");
			$total_pages = $db->num_rows();

			// sort out the pagination link
			$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=articles&view=manage&category_id={$_GET['category_id']}&", $page);

			$db->sqlquery("SELECT c.article_id, a.author_id, a.title, a.tagline, a.text, a.date, a.comment_count, a.guest_username, a.show_in_menu, a.views, u.username FROM `article_category_reference` c JOIN `articles` a ON a.article_id = c.article_id LEFT JOIN `users` u on a.author_id = u.user_id WHERE c.category_id = ? AND a.active = 1 ORDER BY a.`date` DESC LIMIT ?, 9", array($_GET['category_id'], $core->start));
			$article_get = $db->fetch_all_rows();

			foreach ($article_get as $article)
			{
				// make date human readable
				$date = $core->format_date($article['date']);

				// get the article row template
				$templating->block('manage_row');

				// sort out the categories (tags)
				$categories_list = '';
				$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? LIMIT 4", array($article['article_id']));
				while ($get_categories = $db->fetch())
				{
					$categories_list .= " <a href=\"/articles/category/{$get_categories['category_id']}\"><span class=\"label label-info\">{$get_categories['category_name']}</span></a> ";
				}

				if (!empty($categories_list))
				{
					$categories_list = '<p class="small muted">In: ' . $categories_list . '</p>';
				}
				$templating->set('categories_list', $categories_list);

				$templating->set('title', $article['title']);
				$templating->set('username', $article['username']);
				$templating->set('date', $date);
				$templating->set('text', bbcode($article['tagline']));
				$templating->set('article_id', $article['article_id']);
				$templating->set('comment_count', $article['comment_count']);
				$templating->set('views', $article['views']);
				$templating->set('article_link', core::nice_title($article['title']) . '.' . $article['article_id']);
			}

			$templating->block('manage_bottom');
			$templating->set('pagination', $pagination);
		}
	}

	// View all submitted articles that have not yet been approved
	if ($_GET['view'] == 'Submitted')
	{
		$templating->set_previous('title', 'User submitted articles' . $templating->get('title', 1)  , 1);
		include('admin_articles_sections/submitted/view_articles.php');
	}

	// View all submitted articles that have not yet been approved
	if ($_GET['view'] == 'drafts')
	{
		$templating->set_previous('title', 'Article drafts' . $templating->get('title', 1)  , 1);
		include('admin_articles_sections/drafts/view_drafts.php');
	}
}

// this section will load the correct module for what action has been requested
else if (isset($_POST['act']))
{
	/*

	// SUBMITTING AN ARTICLE FOR ADMIN REVIEW

	*/
	if ($_POST['act'] == 'review')
	{
		include('admin_articles_sections/review/new_article.php');
	}

	/*
	// Publishing a draft article, used by drafts for publishing directly
	*/
	if ($_POST['act'] == 'add_draft')
	{
		$return_page = '/admin.php?module=articles&view=drafts&aid=' . $_POST['article_id'];
		article_class::publish_article(['return_page' => $return_page, 'type' => 'draft', 'new_notification_type' => 'new_article_published', 'clear_notification_type' => 'draft']);
	}

	if ($_POST['act'] == 'Add_Review_Comment')
	{
		// make sure news id is a number
		if (!is_numeric($_POST['aid']))
		{
			$core->message('Article id was not a number! Stop trying to do something naughty!');
		}

		else if ($parray['comment_on_articles'] == 0)
		{
			$core->message('You do not have permisions to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else
		{
			// get article name for the email and redirect
			$db->sqlquery("SELECT `title`, `comment_count` FROM `articles` WHERE `article_id` = ?", array($_POST['aid']));
			$title = $db->fetch();
			$title_nice = core::nice_title($title['title']);

			$page = 1;
			if ($title['comment_count'] > 9)
			{
				$page = ceil($title['comment_count']/9);
			}

			// check empty
			if (empty($_POST['text']))
			{
				header("Location: /admin.php?module=reviewqueue&aid={$_POST['aid']}&error=emptycomment");
			}

			else
			{
				$comment = htmlspecialchars($_POST['text'], ENT_QUOTES);
				$article_id = $_POST['aid'];

				$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($_POST['aid'], $_SESSION['user_id'], core::$date, $comment));

				$new_comment_id = $db->grab_id();

				// check if they are subscribing
				if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
				{
					// make sure we don't make lots of doubles
					$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));

					$emails = 0;
					if (isset($_POST['emails']))
					{
						$emails = 1;
					}

					$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?", array($_SESSION['user_id'], $article_id, $emails));
				}

				// email anyone subscribed which isn't you
				$db->sqlquery("SELECT s.`user_id`, s.emails, u.email, u.username FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE `article_id` = ?", array($article_id));
				$users_array = array();
				while ($users = $db->fetch())
				{
					if ($users['user_id'] != $_SESSION['user_id'] && $users['emails'] == 1)
					{
						$users_array[$users['user_id']]['user_id'] = $users['user_id'];
						$users_array[$users['user_id']]['email'] = $users['email'];
						$users_array[$users['user_id']]['username'] = $users['username'];
					}
				}

				// send the emails
				foreach ($users_array as $email_user)
				{
					$to = $email_user['email'];

					// set the title to upper case
					$title_upper = $title['title'];

					// subject
					$subject = "New comment on your unpublished article {$title_upper} on GamingOnLinux.com";

					$username = $_SESSION['username'];

					$comment_email = email_bbcode($comment);

					// message
					$message = "
					<html>
					<head>
					<title>New comment on your unpublished article on GamingOnLinux.com</title>
					<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
					</head>
					<body>
					<img src=\"" . core::config('website_url') . "/templates/default/images/logo.png\" alt=\"Gaming On Linux\">
					<br />
					<p>Hello <strong>{$email_user['username']}</strong>,</p>
					<p><strong>{$username}</strong> has replied to an article you sent for review on titled \"<strong><a href=\"" . core::config('website_url') . "/admin.php?module=reviewqueue&aid={$_POST['aid']}\">{$title_upper}</a></strong>\".</p>
					<div>
				 	<hr>
				 	{$comment_email}
				 	<hr>
				 	You can manage your subscriptions anytime in your <a href=\"" . core::config('website_url') . "/usercp.php\">User Control Panel</a>.
				 	<hr>
				  	<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
				  	<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
					</div>
					</body>
					</html>
					";

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

				// try to stop double postings, clear text
				unset($_POST['text']);

				// clear any comment or name left from errors
				unset($_SESSION['acomment']);

				header("Location: /admin.php?module=reviewqueue&aid={$_POST['aid']}&message=commentadded");
			}
		}
	}

	if ($_POST['act'] == 'Preview_Submitted')
	{
		include('admin_articles_sections/submitted/preview_submitted.php');
	}

	if ($_POST['act'] == 'Edit')
	{
		if ($checked = $article_class->check_article_inputs("/admin.php?module=articles&view=Edit&article_id={$_POST['article_id']}"))
		{
			$block = 0;
			if (isset($_POST['show_block']))
			{
				$block = 1;
			}

			$show = 0;
			if (isset($_POST['show_article']))
			{
				$show = 1;
			}

			$article_class->gallery_tagline($checked);

			// first check if it was disabled
			$db->sqlquery("SELECT `active` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
			$enabled_check = $db->fetch();

			$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = ?, `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, $show, $_POST['article_id']));

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

			// update admin notes if it was disabled
			if (!isset($_POST['show_article']) && $enabled_check['active'] == 1)
			{
				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `created_date` = ?, `completed_date` = ?, `type` = 'disabled_article', `data` = ?, `completed` = 1", array($_SESSION['user_id'], core::$date, core::$date, $_POST['article_id']));
			}
			if (isset($_POST['show_article']) && $enabled_check['active'] == 0)
			{
				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `created_date` = ?, `completed_date` = ?, `type` = 'enabled_article', `data` = ?, `completed` = 1", array($_SESSION['user_id'], core::$date, core::$date, $_POST['article_id']));
			}

			// update history
			$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));

			// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
			unset($_SESSION['atitle']);
			unset($_SESSION['aslug']);
			unset($_SESSION['atagline']);
			unset($_SESSION['atext']);
			unset($_SESSION['acategories']);
			unset($_SESSION['agames']);
			unset($_SESSION['aactive']);
			unset($_SESSION['uploads']);
			unset($_SESSION['uploads_tagline']);
			unset($_SESSION['image_rand']);
			unset($_SESSION['original_text']);
			unset($_SESSION['gallery_tagline_id']);
			unset($_SESSION['gallery_tagline_rand']);

			if (core::config('pretty_urls') == 1)
			{
				header("Location: /articles/{$checked['slug']}.{$_POST['article_id']}/");
			}
			else
			{
				if (!isset($_POST['show_block']))
				{
					header("Location: " . core::config('website_url') . "index.php?module=articles_full&aid={$_POST['article_id']}");
				}
				else
				{
					header("Location: " . core::config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
				}
			}
		}
	}

	if ($_POST['act'] == 'Delete')
	{
		if (isset($_GET['review']) && $_GET['review'] == 1)
		{
			$return_page = "/admin.php?module=reviewqueue";
			$post_page = "/admin.php?module=articles&article_id={$_GET['article_id']}&review=1";
		}
		else
		{
			$post_page = $return_page = "/admin.php?module=articles&amp;article_id={$_POST['article_id']}";
		}

		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$db->sqlquery("SELECT `active` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
			$check = $db->fetch();

			// anti-cheese deleting the wrong article feature
			if ($check['active'] == 1)
			{
				$core->message("WARNING: You are about to delete a live article!", NULL, 1);
			}

			$core->yes_no('Are you sure you want to delete that article?', $post_page, "Delete");
		}

		else if (isset($_POST['no']))
		{
			header("Location: $return_page");
		}

		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_GET['article_id']))
			{
				$core->message('That is not a correct id!');
			}

			else
			{
				// check post exists
				$db->sqlquery("SELECT `article_id`, `date`, `author_id`, `title`, 'tagline_image' FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
				$check = $db->fetch();

				if ($db->num_rows() != 1)
				{
					$core->message("That is not a correct id! Options: <a href=\"$return_page\">Go back</a>.");
				}

				// Delete now
				else
				{
					if ($check['author_id'] == 1 && $_SESSION['user_id'] != 1)
					{
						header("Location: $return_page");
					}

					else
					{
						$article_class->delete_article($check);

						$core->message("That article has now been deleted! Options: <a href=\"$return_page\">Go back</a>.");
					}
				}
			}
		}
	}

	if ($_POST['act'] == 'Deny')
	{
		include('admin_articles_sections/submitted/deny_submitted.php');
	}

	/*
	// APPROVE A USER SUBMITTED ARTICLE
	*/

	if ($_POST['act'] == 'Approve')
	{
		$return_page = '/admin.php?module=articles&view=Submitted&aid=' . $_POST['article_id'];
		article_class::publish_article(['return_page' => $return_page, 'type' => 'submitted_article', 'new_notification_type' => 'approve_submitted_article', 'clear_notification_type' => 'submitted_article']);
	}

	// For editing a post from another admin in the review pool
	if ($_POST['act'] == 'Edit_Submitted')
	{
		include('admin_articles_sections/submitted/edit_submitted.php');
	}

	// For editing a post from another admin in the review pool
	if ($_POST['act'] == 'Edit_Draft')
	{
		include('admin_articles_sections/drafts/edit_draft.php');
	}

	if ($_POST['act'] == 'Move_Draft')
	{
		include('admin_articles_sections/drafts/move_draft.php');
	}

	if ($_POST['act'] == 'Save_Draft')
	{
		include('admin_articles_sections/drafts/save_draft.php');
	}

	if ($_POST['act'] == 'delete_draft')
	{
		include('admin_articles_sections/drafts/delete_draft.php');
	}

	if ($_POST['act'] == 'deletetopimage')
	{
		if (!isset($_POST['article_id']))
		{
			$core->message("Not a correct article id set!", NULL, 1);
		}

		else
		{
			$db->sqlquery("SELECT `title`,`tagline_image`, `slug` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
			$article = $db->fetch();

			// remove old image
			if (!empty($article['tagline_image']))
			{
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']);
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']);
			}

			$db->sqlquery("UPDATE `articles` SET `tagline_image` = '' WHERE `article_id` = ?", array($_POST['article_id']));

			$core->message("The articles top image has now been deleted from \"{$article['title']}\"! <a href=\"/articles/{$article['slug']}.{$_POST['article_id']}\">Click here to view the article.</a>");
		}
	}
}
