<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$templating = new template($core, $core->config('template'));

$user = new user($dbl, $core);

if(isset($_POST))
{
	if ($_POST['type'] == 'add')
	{
		// make sure news id is a number
		if (!is_numeric($_POST['article_id']))
		{
			echo json_encode(array("result" => 'error', 'message' => 'The article ID was not a number, this is likely a bug. Please report it.'));
			return; 
		}

		else
		{
			// get article name for the email and redirect
			$title = $dbl->run("SELECT `title`, `comment_count` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();

			// check empty
			$comment = trim($_POST['text']);

			// check for double comment
			$db->sqlquery("SELECT `comment_text` FROM `articles_comments` WHERE `article_id` = ? ORDER BY `comment_id` DESC LIMIT 1", array($_POST['article_id']));
			$check_comment = $db->fetch();

			if ($check_comment['comment_text'] == $comment)
			{
				echo json_encode(array("result" => 'error', 'message' => 'You can\'t enter the same comment twice!'));
				return; 
			}

			if (empty($comment))
			{
				echo json_encode(array("result" => 'error', 'message' => 'The comment was empty!'));
				return; 
			}

			else
			{
				$comment = htmlspecialchars($comment, ENT_QUOTES);

				$article_id = $_POST['article_id'];

				$dbl->run("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($article_id, $_SESSION['user_id'], core::$date, $comment));

				$new_comment_id = $dbl->new_id();
				
				$dbl->run("UPDATE `articles` SET `comment_count` = (comment_count + 1) WHERE `article_id` = ?", array($article_id));

				// see if they are subscribed right now, if they are and they untick the subscribe box, remove their subscription as they are unsubscribing
				$db->sqlquery("SELECT `article_id`, `emails`, `send_email` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
				if ($db->num_rows() == 1)
				{
					if (!isset($_POST['subscribe']))
					{
						$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));
					}
				}

				// check if they are subscribing
				if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
				{
					// make sure we don't make lots of doubles
					$dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));

					$emails = 0;
					if ($_POST['subscribe-type'] == 'sub-emails')
					{
						$emails = 1;
					}
					
					$article_class->subscribe($article_id, $emails);
				}

				// email anyone subscribed which isn't you
				$fetch_users = $dbl->run("SELECT s.`user_id`, s.emails, s.`secret_key`, u.email, u.username FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE `article_id` = ?", array($article_id))->fetch_all();
				$users_array = array();
				foreach ($fetch_users as $users)
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
					// subject
					$subject = 'New reply to editor review article "' . $title['title'] . '" on GamingOnLinux.com';

					$comment_email = $bbcode->email_bbcode($comment);

					// message
					$html_message = "<p>Hello <strong>{$email_user['username']}</strong>,</p>
					<p><strong>{$_SESSION['username']}</strong> has replied to an editor review article you follow on titled \"<strong><a href=\"" . $core->config('website_url') . "admin.php?module=comments&aid=$article_id#comments\">{$title['title']}</a></strong>\".</p>
					<div>
					<hr>
					{$comment_email}
					<hr>
					You can unsubscribe from this article by <a href=\"" . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}\">clicking here</a>, you can manage your subscriptions anytime in your <a href=\"" . $core->config('website_url') . "usercp.php\">User Control Panel</a>.
					<hr>";

					$plain_message = PHP_EOL."Hello {$email_user['username']}, {$_SESSION['username']} replied to an editor review article on " . $core->config('website_url') . "admin.php?module=comments&aid=$article_id#comments\r\n\r\n{$_POST['text']}\r\n\r\nIf you wish to unsubscribe you can go here: " . $core->config('website_url') . "unsubscribe.php?user_id={$email_user['user_id']}&article_id={$article_id}&email={$email_user['email']}";

					// Mail it
					if ($core->config('send_emails') == 1)
					{
						$mail = new mailer($core);
						$mail->sendMail($email_user['email'], $subject, $html_message, $plain_message);
					}
				}
			}
		}
		
		// sort out what page the new comment is on
		$comment_page = 1;
		if ($title['comment_count'] >= $_SESSION['per-page'])
		{
			$new_total = $title['comment_count']+1;

			$comment_page = ceil($new_total/$_SESSION['per-page']);
		}

		// try to stop double postings, clear text
		unset($_POST['text']);

		// clear any comment or name left from errors
		unset($_SESSION['acomment']);
		
		echo json_encode(array("result" => 'done', 'article_id' => $article_id, 'page' => $comment_page, 'comment_id' => $new_comment_id));
		return; 
	}
	
	if ($_POST['type'] == 'reload')
	{
		$user->check_session();
		
		$templating->load('admin_modules/admin_module_comments');
		
		$templating->block('comments_top', 'admin_modules/admin_module_comments');
		
		// count how many there is in total
		$sql_count = "SELECT COUNT(`comment_id`) FROM `articles_comments` WHERE `article_id` = ? AND `approved` = 1";
		$total_comments = $dbl->run($sql_count, array($_POST['article_id']))->fetchOne();

		//lastpage is = total comments / items per page, rounded up.
		if ($total_comments <= 10)
		{
			$lastpage = 1;
		}
		else
		{
			$lastpage = ceil($total_comments/$_SESSION['per-page']);
		}

		// paging for pagination
		if (!isset($_POST['page']) || $_POST['page'] == 0)
		{
			$page = 1;
		}

		else if (is_numeric($_POST['page']))
		{
			$page = $_POST['page'];
		}

		if ($page > $lastpage)
		{
			$page = $lastpage;
		}
		
		$per_page = 15;
		if (isset($_SESSION['per-page']) && is_numeric($_SESSION['per-page']) && $_SESSION['per-page'] > 0)
		{
			$per_page = $_SESSION['per-page'];
		}
		
		$pagination_linking = 'test';
		
		$pagination = $core->pagination_link($_SESSION['per-page'], $total_comments, $pagination_linking, $page, '#comments');

		$get_comments = $dbl->run("SELECT a.*, u.username, u.user_group, u.secondary_user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u.avatar_gallery, u.steam, u.twitter_on_profile, u.website FROM `articles_comments` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE a.`article_id` = ? ORDER BY a.`comment_id` ASC LIMIT ?, ?", array($_POST['article_id'], $core->start, $per_page))->fetch_all();
		foreach ($get_comments as $comments)
		{
			// make date human readable
			$date = $core->format_date($comments['time_posted']);

			$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
			$quote_username = $comments['username'];

			$comment_avatar = $user->sort_avatar($comments['author_id']);

			$templating->block('review_comments', 'admin_modules/admin_module_comments');
			$templating->set('user_id', $comments['author_id']);
			$templating->set('username', $username);
			$templating->set('plain_username', $quote_username);
			$templating->set('comment_avatar', $comment_avatar);
			$templating->set('date', $date);
			$templating->set('text', $bbcode->parse_bbcode($comments['comment_text'], 0));
			$templating->set('text_plain', $comments['comment_text']);
			$templating->set('article_id', $_POST['article_id']);
			$templating->set('comment_id', $comments['comment_id']);

			$comment_edit_link = '';
			if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $comments['author_id'] || $user->check_group([1,2]) == true && $_SESSION['user_id'] != 0)
			{
				$comment_edit_link = "<a href=\"/index.php?module=articles_full&amp;view=Edit&amp;comment_id={$comments['comment_id']}\"><i class=\"icon-edit\"></i> Edit</a>";
			}
			$templating->set('edit', $comment_edit_link);

			$comment_delete_link = '';
			if ($user->check_group([1,2]) == true)
			{
				$comment_delete_link = "<a href=\"/index.php?module=articles_full&amp;go=deletecomment&amp;comment_id={$comments['comment_id']}\"><i class=\"icon-remove\"></i> Delete</a>";
			}
			$templating->set('delete', $comment_delete_link);
		}

		$templating->block('bottom', 'admin_modules/admin_module_comments');
		
		$templating->set('pagination', $pagination);
		
		echo $templating->output();
	}
}
