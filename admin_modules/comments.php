<?php
$templating->merge('articles_full');

if (isset($_GET['view']))
{
	$templating->set_previous('article_image', '', 1);
}
if (!isset($_GET['view']))
{
	// make sure the id is set
	if (!isset($_GET['aid']))
	{
		$core->message('That is not a correct article id!');
	}

	else
	{
		// get the article
		$db->sqlquery("SELECT a.article_id, a.title, a.draft, a.text, a.tagline, a.date, a.submitted_unapproved, a.admin_review, a.date_submitted, a.author_id, a.active, a.guest_username, a.views, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.comments_open, u.username, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u.avatar_gallery, u.article_bio, u.user_group, u.twitter_on_profile FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.article_id = ?", array($_GET['aid']), 'articles_full.php');
		$article = $db->fetch();

		if ($db->num_rows() == 0)
		{
			$templating->set_previous('meta_description', 'Article error on GamingOnLinux', 1);
			$templating->set_previous('title', 'Article Error', 1);
			$core->message('Sorry but that article doesn\'t exist, if you have followed an old it link it may need updating for the new website.');
		}

		else
		{
			$templating->set_previous('meta_description', $article['tagline'], 1);
			$templating->set_previous('title', $article['title'] . ' - PREVIEW', 1);

			$core->message('This is a preview.');

			// dummy as not live
			$article_meta_image = '';
			$twitter_card = '';
			$templating->set_previous('meta_data', '', 1);

			// make date human readable
			$date = $core->format_date($article['date']);

			$templating->block('article', 'articles_full');
			$templating->set('url', core::config('website_url'));
			$templating->set('share_url', "");

			$templating->set('rules', core::config('rules'));

			$page = 'admin.php?module=';
			if ($article['submitted_unapproved'])
			{
				$page .= 'articles&amp;view=Submitted';
			}
			if ($article['admin_review'])
			{
				$page .= 'reviewqueue';
			}
			if ($article['draft'] == 1)
			{
				$page .= 'articles&amp;view=drafts';
			}

			// we are using the live article page, remove the normal live edit link
			$templating->set('edit_link', '');
			$edit_link = '';
			if ($_SESSION['user_id'] == $article['author_id'])
			{
				$edit_link = ' <button type="submit" formaction="' . core::config('website_url') . $page . '&aid=' . $_GET['aid'] . '" class="btn btn-info">Edit</button></form>';
			}
			$templating->set('admin_button', "<form method=\"post\"><button type=\"submit\" class=\"btn btn-info\" formaction=\"" . core::config('website_url') . "{$page}\">Back</button>$edit_link");

			$templating->set('title', $article['title']);
			$templating->set('user_id', $article['author_id']);

			$view_more = '';
			if ($article['author_id'] == 0)
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
				$username = "<a rel=\"author\" href=\"/profiles/{$article['author_id']}\"><span class=\"glyphicon glyphicon-user\"></span> {$article['username']}</a>";
				$view_more = "<a href=\"/index.php?module=search&amp;author_id={$article['author_id']}\"><span class=\"glyphicon glyphicon-search\"></span> View more articles from {$article['username']}</a>";
			}

			$templating->set('username', $username);

			$templating->set('date', $date);

			$templating->set('article_views', $article['views']);

			$categories_list = '';
			// sort out the categories (tags)
			$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ?", array($article['article_id']), 'articles_full.php');
			while ($get_categories = $db->fetch())
			{
				$categories_list .= " <li><a href=\"/articles/category/{$get_categories['category_id']}\">{$get_categories['category_name']}</a></li> ";
			}

			$templating->set('categories_list', $categories_list);

			$article_bottom = '';
			if ($article['user_group'] != 1 && $article['user_group'] != 2 && $article['user_group'] != 5)
			{
				$article_bottom = "\n<br /><br /><p class=\"small muted\">This article was submitted by a guest, we encourage anyone to <a href=\"//www.gamingonlinux.com/submit-article/\">submit their own articles</a>.</p>";
			}

			$article_page = 1;
			if (isset($_GET['article_page']) && is_numeric($_GET['article_page']))
			{
				$article_page = $_GET['article_page'];
			}

			$templating->set('article_meta', '');

			if ($article['article_top_image'] == 1)
			{
				$tagline_bbcode = $article['article_top_image_filename'];
			}

			if (!empty($article['tagline_image']))
			{
				$tagline_bbcode  = $article['tagline_image'];
			}
			else
			{
				$tagline_bbcode = "";
			}

			if ($article_page == 1)
			{
				$templating->set('text', bbcode($article['text'], 1, 1, $tagline_bbcode) . $article_bottom);
			}

			else
			{
				$templating->set('text', bbcode($article['page'.$article_page], 1, 1, $tagline_bbcode) . $article_bottom);
			}

			$pages = 1;
			if (!empty($article['page2']))
			{
				$pages = 2;
			}
			if (!empty($article['page3']))
			{
				$pages = 3;
			}

			$article_link = "/admin.php?module=comments&amp;aid={$_GET['aid']}&";

			$article_pagination = $core->article_pagination($article_page, $pages, $article_link);

			$templating->set('paging', $article_pagination);

			if (!empty($article['article_bio']) && ($article['user_group'] == 1 || $article['user_group'] == 2 || $article['user_group'] == 5))
			{
				$templating->block('bio', 'articles_full');

				$avatar = $user->sort_avatar($article);
				$templating->set('avatar', $avatar);

				$templating->set('username', $username);
				$templating->set('view_more', $view_more);

				$bio = bbcode($article['article_bio']);

				$templating->set('article_bio', $bio);
			}

			// paging for pagination
			if (!isset($_GET['page']) || $_GET['page'] == 0)
			{
				$page = 1;
			}

			else if (is_numeric($_GET['page']))
			{
				$page = $_GET['page'];
			}

			/*
			EDITOR COMMENTS
			*/
			$templating->merge('admin_modules/admin_module_comments');
			$templating->block('comments_top', 'admin_modules/admin_module_comments');

			$db->sqlquery("SELECT a.*, u.username, u.user_group, u.secondary_user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u.avatar_gallery, u.steam, u.twitter_on_profile, u.website FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE a.`article_id` = ? ORDER BY a.`comment_id` ASC", array($_GET['aid']));
			while ($comments = $db->fetch())
			{
				// make date human readable
				$date = $core->format_date($comments['time_posted']);

				$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
				$quote_username = $comments['username'];

				$comment_avatar = $user->sort_avatar($comments);

				$templating->block('review_comments', 'admin_modules/admin_module_comments');
				$templating->set('user_id', $comments['author_id']);
				$templating->set('username', $username);
				$templating->set('plain_username', $quote_username);
				$templating->set('comment_avatar', $comment_avatar);
				$templating->set('date', $date);
				$templating->set('text', bbcode($comments['comment_text'], 0));
				$templating->set('text_plain', $comments['comment_text']);
				$templating->set('article_id', $_GET['aid']);
				$templating->set('comment_id', $comments['comment_id']);

				$comment_edit_link = '';
				if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $comments['author_id'] || $user->check_group(1,2) == true && $_SESSION['user_id'] != 0)
				{
					$comment_edit_link = "<a href=\"/index.php?module=articles_full&amp;view=Edit&amp;comment_id={$comments['comment_id']}\"><i class=\"icon-edit\"></i> Edit</a>";
				}
				$templating->set('edit', $comment_edit_link);

				$comment_delete_link = '';
				if ($user->check_group(1,2) == true)
				{
					$comment_delete_link = "<a href=\"/index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\"><i class=\"icon-remove\"></i> Delete</a>";
				}
				$templating->set('delete', $comment_delete_link);
			}

			$templating->block('bottom', 'admin_modules/admin_module_comments');

			if (isset($_GET['error']))
			{
				if ($_GET['error'] == 'emptycomment')
				{
					$core->message('You cannot post an empty comment dummy!', NULL, 1);
				}
			}

			// find if they have auto subscribe on
			$db->sqlquery("SELECT `auto_subscribe`,`auto_subscribe_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
			$subscribe_info = $db->fetch();

			$subscribe_check = '';
			if ($subscribe_info['auto_subscribe'] == 1)
			{
				$subscribe_check = 'checked';
			}

			$subscribe_email_check = '';
			if ($subscribe_info['auto_subscribe_email'] == 1)
			{
				$subscribe_email_check = 'checked';
			}

			$comment = '';
			if (isset($_SESSION['acomment']))
			{
				$comment = $_SESSION['acomment'];
			}
			$templating->set('comment', $comment);

			$templating->block('form_top');
			$templating->set('article_id', $_GET['aid']);

			$core->editor('text', $comment);

			$templating->block('form_bottom', 'admin_modules/admin_module_comments');
			$templating->set('subscribe_check', $subscribe_check);
			$templating->set('subscribe_email_check', $subscribe_email_check);
		}
	}
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
				header("Location: " . core::config('website_url') . "admin.php?module=comments&aid={$_GET['aid']}&error=doublecomment#commentbox");

				die();
			}

			if (empty($comment))
			{
				header("Location: " . core::config('website_url') . "admin.php?module=comments&aid={$_POST['aid']}&error=emptycomment#commentbox");

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
				<title>New reply to an article in admin review you follow on GamingOnLinux.com</title>
				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
				</head>
				<body>
				<img src=\"" . core::config('website_url') . "templates/default/images/icon.png\" alt=\"Gaming On Linux\">
				<br />
				<p>Hello <strong>{$email_user['username']}</strong>,</p>
				<p><strong>{$username}</strong> has replied to an admin review article you follow on titled \"<strong><a href=\"" . core::config('website_url') . "admin.php?module=comments&aid=$article_id#comments\">{$title_upper}</a></strong>\".</p>
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

		header("Location: " . core::config('website_url') . "admin.php?module=comments&aid=$article_id");

	}
}

	if ($_POST['act'] == 'editcomment')
	{
		$db->sqlquery("SELECT c.`author_id`, c.`comment_text`, a.`title`, a.`article_id` FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`comment_id` = ?", array($_POST['comment_id']), 'articles_full.php');
		$comment = $db->fetch();

		// check if author or editor/admin
		if ($_SESSION['user_id'] != $comment['author_id'] && $user->check_group(1,2) == false || $_SESSION['user_id'] == 0)
		{
			$nice_title = $core->nice_title($comment['title']);
			header("Location: /articles/$nice_title.{$comment['article_id']}#comments");
		}

		// do the edit since we are allowed
		else
		{
			$comment_text = trim($_POST['text']);
			// check empty
			if (empty($comment_text))
			{
				$core->message('You cannot post an empty comment');
			}

			// update comment
			else
			{
				$comment_text = htmlspecialchars($comment_text, ENT_QUOTES);

				$db->sqlquery("UPDATE `articles_comments` SET `comment_text` = ? WHERE `comment_id` = ?", array($comment_text, $_POST['comment_id']));

				$nice_title = $core->nice_title($comment['title']);

				if (core::config('pretty_urls') == 1)
				{
					header("Location: /articles/$nice_title.{$comment['article_id']}/page={$_GET['page']}#comments");
				}
				else {
					header("Location: ".url."index.php?module=articles_full&aid={$comment['article_id']}&page={$_GET['page']}#comments");
				}

			}
		}
	}
}
