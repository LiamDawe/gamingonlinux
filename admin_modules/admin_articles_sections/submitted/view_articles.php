<?php
$templating->merge('admin_modules/admin_articles_sections/submitted_articles');
if (!isset($_GET['aid']))
{
	if (isset($_GET['denied']))
	{
		$core->message('You have denied publishing that submitted article!');
	}

	if (isset($_GET['accepted']))
	{
		$core->message('That article has been approved!');
	}

	if (isset($_GET['error']) && $_GET['error'] == 'doesntexist')
	{
		$core->message('Article doesn\'t exist, someone must have gotten to it first!', NULL, 1);
	}

	if (isset($_GET['error']) && $_GET['error'] == 'alreadyapproved')
	{
		$core->message('Article already approved, someone must have gotten to it first!');
	}

	$templating->block('submitted_top', 'admin_modules/admin_articles_sections/submitted_articles');

	$db->sqlquery("SELECT a.article_id, a.date_submitted, a.title, a.tagline, a.guest_username, u.username, u.user_id FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE `submitted_article` = 1 AND `submitted_unapproved` = 1");
	while ($article = $db->fetch())
	{
		$templating->block('submitted_row', 'admin_modules/admin_articles_sections/submitted_articles');
		$templating->set('url', core::config('website_url'));
		$templating->set('article_id', strip_tags($article['article_id']));
		$templating->set('article_title', $article['title']);
		if (empty($article['username']))
		{
			if (empty($article['guest_username']))
			{
				$username = 'Guest';
			}

			else
			{
				$username = $article['guest_username'];
			}
		}

		else
		{
			$username = '<a href="/profiles/'.$article['user_id'].'">' . $article['username'] . '</a>';
		}

		$templating->set('username', $username);

		$templating->set('date_submitted', $core->format_date($article['date_submitted']));
	}
}

else if (isset($_GET['aid']))
{
	if (!isset($message_map::$error) || $message_map::$error == 0)
	{
		$_SESSION['image_rand'] = rand();
		$article_class->reset_sessions();
	}
	if (isset ($_GET['message']))
	{
		if ($_GET['message'] == 'editdone')
		{
			$core->message('Edit completed!');
		}
	}

	$templating->block('submitted_top', 'admin_modules/admin_articles_sections/submitted_articles');

	$article_sql = "SELECT
	a.`article_id`,
	a.`preview_code`,
	a.`title`,
	a.`text`,
	a.`tagline`,
	a.`show_in_menu`,
	a.`active`,
	a.`tagline_image`,
	a.`guest_username`,
	a.`author_id`,
	a.`guest_ip`,
	a.`locked`,
	a.`locked_by`,
	a.`locked_date`,
	a.`gallery_tagline`,
	t.`filename` as gallery_tagline_filename,
	u.`username`, u2.`username` as `username_lock`
	FROM
	`articles` a
	LEFT JOIN `users` u on a.`author_id` = u.`user_id`
	LEFT JOIN `users` u2 ON a.`locked_by` = u2.`user_id`
	LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
	WHERE `submitted_article` = 1 AND `active` = 0 AND `article_id` = ?";
	$db->sqlquery($article_sql, array($_GET['aid']));

	$article = $db->fetch();

	if (isset($_GET['unlock']) && $article['locked'] == 1 && $_GET['unlock'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$db->sqlquery("UPDATE `articles` SET `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($article['article_id']));

		$core->message("You have unlocked the article for others to edit!");

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($article_sql, array($_GET['aid']));

		$article = $db->fetch();
	}

	if (isset($_GET['lock']) && isset($_GET['lock']) && $_GET['lock'] == 1 && $article['locked'] == 0)
	{
		$db->sqlquery("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], core::$date, $article['article_id']));

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($article_sql, array($_GET['aid']));

		$article = $db->fetch();
	}

	if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$core->message("This post is now locked while you edit, please click Edit to unlock it once finished.", NULL, 1);

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($article_sql, array($_GET['aid']));

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

			$preview_action = 'formaction="admin.php?module=comments&aid=' . $_GET['aid'] . '"';
		}
		$preview_action = 'formaction="/admin.php?module=preview"';
	}
	else if ($article['locked'] == 0)
	{
		$edit_state = 'disabled="disabled"';
		$edit_state_textarea = 'disabled';
		$editor_disabled = 1;

		$preview_action = 'formaction="admin.php?module=comments&aid=' . $_GET['aid'] . '"';
	}

	$templating->block('item_top', 'admin_modules/admin_articles_sections/submitted_articles');
	$lock_button = '';
	if ($article['locked'] == 0)
	{
		$lock_button = '<a class="button_link" href="'.core::config('website_url').'admin.php?module=articles&view=Submitted&aid=' . $article['article_id'] . '&lock=1">Lock For Editing</a><hr />';
	}
	else if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$lock_button = '<a class="button_link" href="'.core::config('website_url').'admin.php?module=articles&view=Submitted&aid=' . $article['article_id'] . '&unlock=1">Unlock Article For Others</a><hr />';
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
	$templating->set('main_formaction', '<form method="post" action="'.core::config('website_url').'admin.php?module=articles" enctype="multipart/form-data">');
	$templating->set('edit_state', $edit_state);
	$templating->set('edit_state_textarea', $edit_state_textarea);

	// get categorys
	$db->sqlquery("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']));
	while($categories_check = $db->fetch())
	{
		$categories_check_array[] = $categories_check['category_id'];
	}

	$categorys_list = '';
	$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
	while ($categorys = $db->fetch())
	{
		if (isset($_GET['error']))
		{
			if (isset($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
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

	$article_form_top = plugins::do_hooks('article_form_top', $article['article_id']);
	$templating->set('article_form_top', $article_form_top);

	if (empty($article['username']))
	{
		if (empty($article['guest_username']))
		{
			$username = 'Guest';
		}

		else
		{
			$username = $article['guest_username'];
		}
	}

	else
	{
		$username = '<a href="/profiles/'.$article['author_id'].'">' . $article['username'] . '</a>';
	}

	$templating->set('username', $username);

	if (!empty($article['guest_ip']))
	{
		$user_ip = $article['guest_ip'];
	}
	else 
	{
		$user_ip = 'No IP was found';
	}
	$templating->set('ip_address', $user_ip);

	// if they have done it before set title, text and tagline
	if (isset($message_map::$error) && $message_map::$error == 1)
	{
		$templating->set('title', htmlentities($_SESSION['atitle'], ENT_QUOTES));
		$templating->set('tagline', $_SESSION['atagline']);
		$templating->set('slug', $_SESSION['aslug']);
	}

	else
	{
		$templating->set('title', htmlentities($article['title'], ENT_QUOTES));
		$templating->set('tagline', $article['tagline']);
		$templating->set('slug', core::nice_title($article['title']));
	}

	$tagline_image = $article_class->display_tagline_image($article);
	$templating->set('tagline_image', $tagline_image);

	$templating->set('max_height', core::config('article_image_max_height'));
	$templating->set('max_width', core::config('article_image_max_width'));

	// if they have done it before set the text
	$text = $article['text'];
	if (isset($message_map::$error) && $message_map::$error == 1)
	{
		$text = $_SESSION['atext'];
	}

	$core->editor(['name' => 'text', 'content' => $text, 'editor_id' => 'comment_text', 'article_editor' => 1, 'disabled' => $editor_disabled]);

	$templating->block('submitted_bottom', 'admin_modules/admin_articles_sections/submitted_articles');
	$templating->set('edit_state', $edit_state);

	$templating->set('preview_action', $preview_action);

	$templating->set('article_id', $article['article_id']);
	$templating->set('author_id', $article['author_id']);
	$previously_uploaded = '';

	// add in uploaded images from database
	$previously_uploaded = $article_class->display_previous_uploads($article['article_id']);

	$templating->set('previously_uploaded', $previously_uploaded);
	$self_check = '';
	if (isset($_GET['self']) && $_GET['self'] == 'on')
	{
		$self_check = 'checked';
	}

	$templating->set('self_check', $self_check);

	$article_class->article_history($_GET['aid']);
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'comment')
	{
		// make sure news id is a number
		if (!is_numeric($_GET['aid']))
		{
			$core->message('Article id was not a number! Stop trying to do something naughty!');
		}

		else
		{
			// get article name for the email and redirect
			$db->sqlquery("SELECT `title`, `comment_count` FROM `articles` WHERE `article_id` = ?", array($_GET['aid']), 'articles_full.php');
			$title = $db->fetch();
			$title_nice = core::nice_title($title['title']);

			$page = 1;
			if ($title['comment_count'] > 9)
			{
				$page = ceil($title['comment_count']/9);
			}

			// check empty
			$comment = trim($_POST['text']);

			// check for double comment
			$db->sqlquery("SELECT `comment_text` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `comment_id` DESC LIMIT 1", array($_GET['aid']));
			$check_comment = $db->fetch();

			if ($check_comment['comment_text'] == $comment)
			{
				header("Location: " . core::config('website_url') . "admin.php?module=articles&view=Submitted&aid={$_GET['aid']}&error=doublecomment#editor_comments");

				die();
			}

			if (empty($comment))
			{
				header("Location: " . core::config('website_url') . "admin.php?module=articles&view=Submitted&aid={$_GET['aid']}&error=emptycomment#editor_comments");

				die();
			}

			else
			{
				$comment = htmlspecialchars($comment, ENT_QUOTES);

				$article_id = $_GET['aid'];

				$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($_GET['aid'], $_SESSION['user_id'], core::$date, $comment), 'admin_module_comments.php');

				$new_comment_id = $db->grab_id();

				// check if they are subscribing
				if (isset($_POST['subscribe']))
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
					$subject = "New reply to article {$title_upper} on GamingOnLinux.com";

					$username = $_SESSION['username'];
				}

				$comment_email = email_bbcode($comment);

				$message = '';

				// message
				$html_message = "
				<html>
				<head>
				<title>New reply to an article you follow on GamingOnLinux.com</title>
				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
				</head>
				<body>
				<img src=\"" . core::config('website_url') . "templates/default/images/icon.png\" alt=\"Gaming On Linux\">
				<br />
				<p>Hello <strong>{$email_user['username']}</strong>,</p>
				<p><strong>{$username}</strong> has replied to an article you follow on titled \"<strong><a href=\"" . core::config('website_url') . "articles/$title_nice.$article_id#comments\">{$title_upper}</a></strong>\".</p>
				<div>
				<hr>
				{$comment_email}
				<hr>
				You can unsubscribe from this article by <a href=\"" . core::config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . core::config('website_url') . "usercp.php\">User Control Panel</a>.
				<hr>
				<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
				<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
				</div>
				</body>
				</html>";

				$plain_message = PHP_EOL."Hello {$email_user['username']}, {$username} replied to an article on " . core::config('website_url') . "articles/$title_nice.$article_id#comments\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: " . core::config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}";

				$boundary = uniqid('np');

				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= "Content-Type: multipart/alternative;charset=utf-8;boundary=" . $boundary . "\r\n";
				$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

				$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
				$message .= "Content-Type: text/plain;charset=utf-8".PHP_EOL;
				$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
				$message .= $plain_message;

				$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
				$message .= "Content-Type: text/html;charset=utf-8".PHP_EOL;
				$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
				$message .= "$html_message";

				$message .= "\r\n\r\n--" . $boundary . "--";

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

			header("Location: " . core::config('website_url') . "admin.php?module=articles&view=Submitted&aid=$article_id#comments");

		}
	}
}
?>
