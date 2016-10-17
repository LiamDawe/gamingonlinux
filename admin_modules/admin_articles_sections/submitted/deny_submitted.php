<?php
$templating->merge('admin_modules/admin_articles_sections/submitted_articles');

// first check if there is a guest email or a users email
$db->sqlquery("SELECT a.`tagline_image`, a.`title`, a.`text`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email`, a.`article_top_image` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']));
$check = $db->fetch();

if ($db->num_rows() == 0)
{
	header("Location: /admin.php?module=articles&view=Submitted&error=doesntexist");
}

else
{
	if (!isset($_POST['yes']) && !isset($_POST['no']))
	{
		$templating->block('deny', 'admin_modules/admin_articles_sections/submitted_articles');
		$templating->set('article_id', $_POST['article_id']);
	}

	else if (isset($_POST['no']))
	{
		header("Location: admin.php?module=articles&view=manage");
	}

	else
	{
		$article_class->delete_article($check);

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?, `article_id` = ?", array("{$_SESSION['username']} denied a user submitted article.", core::$date, core::$date, $_GET['article_id']));

		if (isset($_POST['message']))
		{
			if (!empty($_POST['message']))
			{
				// pick the email to use
				$email = '';
				if (!empty($check['guest_email']))
				{
					$email = $check['guest_email'];
				}

				else if (!empty($check['email']))
				{
					$email = $check['email'];
				}

				// sort out registration email
				$to = $email;

				// subject
				$subject = 'Your article was denied on GamingOnLinux.com sorry!';

				// message
				$message = "
				<html>
				<head>
				<title>Your article was denied on GamingOnLinux.com sorry!</title>
				</head>
				<body>
				<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
				<br />
				<p>Sorry but this time we have denied publishing your article on <a href=\"http://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>, you are free to submit it again anytime it could just be minor issues but here is what the reviewer had to say:</p>
				<p>{$_POST['message']}</p>
				<br style=\"clear:both\">
				<div>
				<hr>
				<p>Article Title: {$check['title']}, text below:</p>
				<p>{$check['text']}</p>
				</div>
				</body>
				</html>";

				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

				// Mail it
				mail($to, $subject, $message, $headers);
			}
		}

		header("Location: admin.php?module=articles&view=Submitted&denied");
	}
}
