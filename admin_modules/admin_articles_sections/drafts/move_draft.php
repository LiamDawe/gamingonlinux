<?php
$text = trim($_POST['text']);
$title = strip_tags($_POST['title']);

$db->sqlquery("UPDATE `articles` SET `draft` = 0, `admin_review` = 1, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ? WHERE `article_id` = ?", array($title, $_POST['slug'], $_POST['tagline'], $text, $_POST['article_id']));

// update admin notifications
$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `article_id` = ?", array("{$_SESSION['username']} sent a new article to the admin review queue.", core::$date, $_POST['article_id']));

if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
{
	$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
}

$article_class->process_categories($_POST['article_id']);

$article_class->process_game_assoc($_POST['article_id']);

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

// email all editors apart from yourself
$db->sqlquery("SELECT `user_id`, `email`, `username` FROM `users` WHERE `user_group` IN (1,2) AND `user_id` != ?", array($_SESSION['user_id']));
$users_array = array();
while ($email_users = $db->fetch())
{
	if ($email_users['user_id'] != $_SESSION['user_id'] && $email_users['email'] == 1)
	{
		$users_array[$email_users['user_id']]['user_id'] = $email_users['user_id'];
		$users_array[$email_users['user_id']]['email'] = $email_users['email'];
		$users_array[$email_users['user_id']]['username'] = $email_users['username'];
	}
}

// set the title to upper case
$title = strip_tags($_POST['title']);
$title_upper = $title;

// subject
$subject = "GamingOnLinux.com article submitted for review by {$_SESSION['username']}";

foreach ($users_array as $email_user)
{
	$to = $email_user['email'];

	// message
	$message = "<html>
	<head>
	<title>GamingOnLinux.com article submitted for review by {$_SESSION['username']}</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
	</head>
	<body>
	<img src=\"" . core::config('website_url') . "templates/default/images/logo.png\" alt=\"Gaming On Linux\">
	<br />
	<p>Hello <strong>{$email_user['username']}</strong>,</p>
	<p><strong>{$_SESSION['username']}</strong> has sent an article to be reviewed before publishing \"<strong><a href=\"" . core::config('website_url') . "sadmin.php?module=articles&view=adminreview&aid={$article_id}\">{$title_upper}</a></strong>\".</p>
	</body>
	</html>";

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

	// Mail it
	if (core::config('send_emails') == 1) // only send emails if this is on, we turn it to 0 on the test site
	{
		mail($to, $subject, $message, $headers);
	}
}

header("Location: /admin.php?module=articles&view=drafts&message=moved");
