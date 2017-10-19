<?php
// check it hasn't been accepted already
$check_article = $dbl->run("SELECT a.tagline_image, a.`active`, a.`date_submitted`, a.`guest_username`, a.`guest_email`, u.`username`, u.`email` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE `article_id` = ?", array($_POST['article_id']))->fetch();
if ($check_article['active'] == 1)
{
	header("Location: /admin.php?module=articles&view=Submitted&error=alreadyapproved");
}

else
{
	$return_page = "admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}";
	if ($checked = $article_class->check_article_inputs($return_page))
	{
		// show in the editors pick block section
		$block = 0;
		if (isset($_POST['show_block']))
		{
			$block = 1;
		}

		$author_id = $_POST['author_id'];
		$submission_date = $check_article['date_submitted'];
		// if the author is submitting it as themselves
		if (isset($_POST['submit_as_self']))
		{
			$author_id = $_SESSION['user_id'];
		}
		$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = ?", array(core::$date, $_POST['article_id'], 'submitted_article'));

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], 'approve_submitted_article', core::$date, core::$date, $_POST['article_id']));

		// remove all the comments made by admins
		$dbl->run("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_POST['article_id']));

		$article_class->gallery_tagline($checked);

		$dbl->run("UPDATE `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = 1, `date` = ?, `date_submitted` = ?, `submitted_unapproved` = 0, `locked` = 0 WHERE `article_id` = ?", array($author_id, $checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, core::$date, $submission_date, $_POST['article_id']));

		if (isset($_SESSION['uploads']))
		{
			foreach($_SESSION['uploads'] as $key)
			{
				$dbl->run("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($_POST['article_id'], $key['image_name']));
			}
		}

		// since they are approving and not neccisarily editing, check if the text matches, if it doesnt they have edited it
		if ($_SESSION['original_text'] != $checked['text'])
		{
			$dbl->run("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));
		}

		$article_class->process_categories($_POST['article_id']);

		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name'], $checked['text']);
		}

		$article_class->reset_sessions();
		unset($_SESSION['original_text']);

		// pick the email to use
		$email = '';
		if (!empty($check_article['guest_email']))
		{
			$email = $check_article['guest_email'];
		}

		else if (!empty($check_article['email']))
		{
			$email = $check_article['email'];
		}
		
		$article_link = article::get_link($_POST['article_id'], $checked['slug']);

		// subject
		$subject = 'Your article was approved and published on GamingOnLinux';
		
		$html_message = '<p>We have accepted your article titled "<a href="'.$article_link.'">'.$checked['title'].'</a>" on <a href="'.$core->config('website_url').'" target="_blank">GamingOnLinux</a>. Thank you for taking the time to send us news we really appreciate the help, you are awesome!</p>';

		// message
		$plain_message = 'We have accepted your article titled "'.$checked['title'].'" on GamingOnLinux, you can see it here: '.$article_link;
		
		if ($core->config('send_emails') == 1)
		{			
			$mail = new mailer($core);
			$mail->sendMail($email, $subject, $html_message, $plain_message);
		}

		include($core->config('path') . 'includes/telegram_poster.php');

		telegram($checked['title'] . ' ' . $article_link);

		if (!isset($_POST['show_block']))
		{
			header("Location: " . $core->config('website_url') . "admin.php?module=articles&view=Submitted&accepted");
		}
		else
		{
			header("Location: ". $core->config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
		}
	}
}
