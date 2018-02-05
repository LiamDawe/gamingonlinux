<?php
$templating->load('admin_modules/admin_articles_sections/submitted_articles');

// first check if there is a guest email or a users email
$check = $dbl->run("SELECT a.`article_id`, a.`tagline_image`, a.`title`, a.`text`, a.`guest_username`, a.`guest_email`, a.`author_id`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']))->fetch();

if (!$check)
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

		$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'submitted_article' AND `data` = ?", array(core::$date, $_GET['article_id']));
		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], 'denied_submitted_article', core::$date, core::$date, $_GET['article_id']));

		if (isset($_POST['message']))
		{
			$message = trim($_POST['message']);
			if (!empty($message))
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

				// subject
				$subject = 'Your article was denied on GamingOnLinux';
				
				$html_message = '<p>Your article submission on GamingOnLinux was denied and the editor left a message for you below:</p>
				<p>'.$_POST['message'].'</p>
				<p>Here is a copy below are your article:<br />
				Title: '.$check['title'].'<br />
				' . $check['text']. '</p>';

				// message
				$plain_message = 'Your article submission on GamingOnLinux was denied and the editor said this' . "\n\n" . $_POST['message'] . "\n\n" . 'Here\'s a copy of your article below' . "\n\n" . $check['text'];
				
				if ($core->config('send_emails') == 1)
				{
					$mail = new mailer($core);
					$mail->sendMail($email, $subject, $html_message, $plain_message);
				}
			}
		}

		header("Location: admin.php?module=articles&view=Submitted&denied");
	}
}
