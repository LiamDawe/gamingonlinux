<?php
$templating->set_previous('title', 'Review articles' . $templating->get('title', 1)  , 1);

$templating->merge('admin_modules/admin_articles_sections/admin_review');

if (!isset($_GET['aid']))
{
	if (isset($_GET['accepted']))
	{
		$core->message('That article has been approved!');
	}

	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'sentforreview')
		{
			$core->message('Your article has been sent to be reviewed by the others! Now go bug them and buy Liam cake.');
		}
	}

	if (isset($_GET['error']))
	{
		if ($_GET['error'] == 'doesntexist')
		{
			$core->message('Article doesn\'t exist, someone must have gotten to it first!', NULL, 1);
		}
		if ($_GET['error'] == 'alreadyapproved')
		{
			$core->message('Article already approved, someone must have gotten to it first!');
		}
	}

	$templating->block('review_top', 'admin_modules/admin_articles_sections/admin_review');

	$db->sqlquery("SELECT a.article_id, a.date, a.title, a.tagline, a.guest_username, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE `admin_review` = 1");
	while ($article = $db->fetch())
	{
		$templating->block('review_row', 'admin_modules/admin_articles_sections/admin_review');
		$templating->set('url', core::config('website_url'));
		$templating->set('article_id', $article['article_id']);
		$templating->set('article_title', $article['title']);
		$templating->set('username', $article['username']);

		$templating->set('date_submitted', $core->format_date($article['date']));
	}
}

else
{
	if (!isset($_GET['error']))
	{
		$_SESSION['image_rand'] = rand();
	}

	if (isset ($_GET['message']))
	{
		if ($_GET['message'] == 'editdone')
		{
			$core->message('Post Edited and the author has been emailed!');
		}

		if ($_GET['message'] == 'commentadded')
		{
			$core->message('You have added your comments and anyone else involved has been emailed!');
		}
	}
	if (isset ($_GET['error']))
	{
		if ($_GET['error'] == 'empty')
		{
			$core->message('You have to fill in a title, friendly url (under the title), tagline and text!', NULL, 1);
		}

		else if ($_GET['error'] == 'shorttagline')
		{
			$core->message('The tagline was too short, it needs to be at least 100 characters to be informative!', NULL, 1);
		}

		else if ($_GET['error'] == 'taglinetoolong')
		{
			$core->message('The tagline was too long, it needs to be 400 characters or less!', NULL, 1);
		}

		else if ($_GET['error'] == 'shorttitle')
		{
			$core->message('The title was too short, make it informative!', NULL, 1);
		}

		else if ($_GET['error'] == 'toomanypicks')
		{
			$core->message('There are already 3 articles set as editor picks!', NULL, 1);
		}
		else if ($_GET['error'] == 'noimageselected')
		{
			$core->message('You didn\'t select a tagline image to upload with the article, all articles must have one!', NULL, 1);
		}
	}

	$query = "SELECT a.article_id, a.preview_code, a.title, a.slug, a.text, a.tagline, a.show_in_menu, a.active, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.guest_username, a.author_id, a.locked, a.locked_by, a.locked_date, u.username, u2.username as username_lock FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `users` u2 ON a.locked_by = u2.user_id WHERE `article_id` = ?";

	$db->sqlquery($query, array($_GET['aid']));

	$article = $db->fetch();

	if ($article['locked'] == 1 && $_GET['unlock'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$db->sqlquery("UPDATE `articles` SET `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($article['article_id']));

		$core->message("You have unlocked the article for others to edit!");

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($query, array($_GET['aid']), 'view_articles.php admin review');

		$article = $db->fetch();
	}

	if ($article['locked'] == 0 && $_GET['lock'] == 1)
	{
		$db->sqlquery("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], $core->date, $article['article_id']));

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($query, array($_GET['aid']), 'view_articles.php admin review');

		$article = $db->fetch();
	}

	if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
	{
		$core->message("This post is now locked while you edit, please click Edit to unlock it once finished.", NULL, 1);

		// we need to re-catch the article info as we have changed lock status
		$db->sqlquery($query, array($_GET['aid']), 'view_articles.php admin review');

		$article = $db->fetch();
	}

	$templating->block('review_item_top', 'admin_modules/admin_articles_sections/admin_review');

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
	$templating->set('preview_url', core::config('website_url') . 'index.php?module=articles_full.php&aid=' . $article['article_id'] . '&preview_code=' . $article['preview_code']);
	$templating->set('edit_state', $edit_state);
	$templating->set('article_id', $article['article_id']);

	$templating->block('full_editor', 'admin_modules/article_form');
	$templating->set('max_filesize', core::readable_bytes(core::config('max_tagline_image_filesize')));
	$templating->set('edit_state', $edit_state);
	$templating->set('edit_state_textarea', $edit_state_textarea);
	$templating->set('main_formaction', '<form method="post" action="'.url.'admin.php?module=articles" enctype="multipart/form-data">');

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

	$templating->set('username', $article['username']);

	// if they have done it before set title, text and tagline
	if (isset($_GET['error']))
	{
		$templating->set('title', htmlentities($_SESSION['atitle'], ENT_QUOTES));
		$templating->set('tagline', $_SESSION['atagline']);
		$templating->set('slug', $_SESSION['aslug']);
	}
	else
	{
		$templating->set('title', htmlentities($article['title'], ENT_QUOTES));
		$templating->set('tagline', $article['tagline']);
		$templating->set('slug', $article['slug']);
	}

	$top_image = '';
	$top_image_delete = '';
	if ($article['article_top_image'] == 1)
	{
		$top_image = '<img src="'.core::config('website_url').'uploads/articles/topimages/'.$article['article_top_image_filename'].'" alt="[articleimage]" class="imgList"><br />';
	}
	if (!empty($article['tagline_image']))
	{
		$top_image = "<img src=\"{$config['website_url']}uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
Full Image Url: <a href=\"{$config['website_url']}uploads/articles/tagline_images/{$article['tagline_image']}\" target=\"_blank\">Click Me</a><br />";
	}

	$templating->set('tagline_image', $top_image);
	$templating->set('top_image_delete', $top_image_delete);

	$templating->set('max_height', core::config('article_image_max_height'));
	$templating->set('max_width', core::config('article_image_max_width'));

	// if they have done it before set title, text and tagline
	$text = $article['text'];
	if (isset($_GET['error']))
	{
		$text = $_SESSION['atext'];
	}

	$core->editor('text', $text, 1, $editor_disabled);

	$templating->block('review_bottom', 'admin_modules/admin_articles_sections/admin_review');
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
	$db->sqlquery("SELECT `filename`,`id` FROM `article_images` WHERE `article_id` = ? ORDER BY `id` ASC", array($article['article_id']));
	$article_images = $db->fetch_all_rows();

	foreach($article_images as $value)
	{
		$bbcode = "[img]{$config['website_url']}uploads/articles/article_images/{$value['filename']}[/img]";
		$previously_uploaded .= "<div class=\"box\"><div class=\"body group\"><div id=\"{$value['id']}\"><img src=\"/uploads/articles/article_images/{$value['filename']}\" class='imgList'><br />
		BBCode: <input type=\"text\" class=\"form-control\" value=\"{$bbcode}\" />
		<button data-bbcode=\"{$bbcode}\" class=\"add_button\">Add to editor</button> <button id=\"{$value['id']}\" class=\"trash\">Delete image</button>
		</div></div></div>";
	}

	$templating->set('previously_uploaded', $previously_uploaded);

	$db->sqlquery("SELECT u.`username`, u.`user_id`, a.`date` FROM `users` u INNER JOIN `article_history` a ON a.user_id = u.user_id WHERE a.article_id = ? ORDER BY a.id DESC LIMIT 10", array($_GET['aid']));
	$history = '';
	while ($grab_history = $db->fetch())
	{
		$date = $core->format_date($grab_history['date']);
		$history .= '<li><a href="/profiles/'. $grab_history['user_id'] .'">' . $grab_history['username'] . '</a> - ' . $date . '</li>';
	}

	$templating->block('edit_history', 'admin_modules/admin_articles_sections/admin_review');
	$templating->set('history', $history);
	$templating->block('edit_bottom_history', 'admin_modules/admin_articles_sections/admin_review');
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
			$title_nice = $core->nice_title($title['title']);

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
				header("Location: {$config['website_url']}admin.php?module=reviewqueue&aid={$_GET['aid']}&error=doublecomment#editor_comments");

				die();
			}

			if (empty($comment))
			{
				header("Location: {$config['website_url']}admin.php?module=reviewqueue&aid={$_GET['aid']}&error=emptycomment#editor_comments");

				die();
			}

			else
			{
				$comment = htmlspecialchars($comment, ENT_QUOTES);

				$article_id = $_GET['aid'];

				$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($_GET['aid'], $_SESSION['user_id'], $core->date, $comment), 'admin_module_comments.php');

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
				<img src=\"{$config['website_url']}templates/default/images/icon.png\" alt=\"Gaming On Linux\">
				<br />
				<p>Hello <strong>{$email_user['username']}</strong>,</p>
				<p><strong>{$username}</strong> has replied to an article you follow on titled \"<strong><a href=\"{$config['website_url']}articles/$title_nice.$article_id#comments\">{$title_upper}</a></strong>\".</p>
				<div>
				<hr>
				{$comment_email}
				<hr>
				You can unsubscribe from this article by <a href=\"{$config['website_url']}unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"{$config['website_url']}usercp.php\">User Control Panel</a>.
				<hr>
				<p>If you haven&#39;t registered at <a href=\"{$config['website_url']}\" target=\"_blank\">{$config['website_url']}</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
				<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
				</div>
				</body>
				</html>";

				$plain_message = PHP_EOL."Hello {$email_user['username']}, {$username} replied to an article on {$config['website_url']}articles/$title_nice.$article_id#comments\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: {$config['website_url']}unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}";

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
				if ($config['send_emails'] == 1)
				{
					mail($to, $subject, $message, $headers);
				}
			}

			// try to stop double postings, clear text
			unset($_POST['text']);

			// clear any comment or name left from errors
			unset($_SESSION['acomment']);
			unset($_SESSION['bad']);

			header("Location: {$config['website_url']}admin.php?module=reviewqueue&aid=$article_id#comments");

		}
	}
}
