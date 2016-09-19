<?php
if (!isset($_GET['redo']))
{
	if (!isset($_GET['user_id']) || !isset($_GET['code']))
	{
		header("Location: /index.php");
		die();
	}
	else
	{
		$db->sqlquery("SELECT `user_id` FROM `users` WHERE `user_id` = ? AND `activation_code` = ?", array($_GET['user_id'], $_GET['code']));
		if ($db->num_rows() == 1)
		{
			$db->sqlquery("UPDATE `users` SET `activated` = 1 WHERE `user_id` = ?", array($_GET['user_id']));

			$_SESSION['activated'] = 1;

			header("Location: /index.php?module=home&message=activated");
		}
	}
}

else if (isset($_GET['redo']) && $_SESSION['user_id'] != 0)
{
	$db->sqlquery("SELECT `email`, `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$get_active = $db->fetch();

	if ($get_active['activated'] == 0)
	{
		// make random registration code
		$code = sha1(mt_rand(10000,99999).time().$_SESSION['user_id']);

		$db->sqlquery("UPDATE `users` SET `activation_code` = ? WHERE `user_id` = ?", array($code, $_SESSION['user_id']));

		// sort out registration email
		$to  = $get_active['email'];

		// subject
		$subject = 'Welcome to GamingOnLinux.com, activation needed!';

		// message
		$message = "
		<html>
		<head>
		<title>Welcome email for GamingOnLinux.com, activation needed!</title>
		</head>
		<body>
		<img src=\"" . core::config('website_url') . "templates/default/images/icon.png\" alt=\"Gaming On Linux\">
		<br />
		<p>Hello {$_SESSION['username']},</p>
		<p>Thanks for registering on <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, The best source for linux games and news.</p>
		<p><strong><a href=\"" . core::config('website_url') . "index.php?module=activate_user&user_id={$_SESSION['user_id']}&code=$code\">You need to activate your account before you can post! Click here to activate!</a></strong></p>
		<p>If you&#39;re new, consider saying hello in the <a href=\"" . core::config('website_url') . "forum/\" target=\"_blank\">forum</a>.</p>
		<br style=\"clear:both\">
		<div>
		<hr>
		<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:" . core::config('contact_email') . "\" target=\"_blank\">" . core::config('contact_email') . "</a> with some info about what you want us to do about it.</p>
		<p>Please, Don&#39;t reply to this automated message, We do not read any emails recieved on this email address.</p>
		</div>
		</body>
		</html>";

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: noreply@gamingonlinux.com\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

		// Mail it
		mail($to, $subject, $message, $headers);

		$core->message("We have re-sent a new activation code {$_SESSION['username']}, <strong>please check your emails to continue using the website properly</strong>! <a href=\"index.php\">Click here if you are not redirected.</a>", "index.php");
	}

	else if ($get_active['activated'] == 1)
	{
		header("Location: /index.php");
	}
}
?>
