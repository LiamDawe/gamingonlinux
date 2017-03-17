<?php
if (isset($_GET['message']))
{
	$extra = NULL;
	if (isset($_GET['extra']))
	{
		$extra = $_GET['extra'];
	}
	$message = $message_map->get_message($_GET['message'], $extra);
	$core->message($message['message'], NULL, $message['error']);
}

$db->sqlquery("SELECT `email`, `username` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$grab_email = $db->fetch();

$templating->merge('usercp_modules/usercp_module_email');
$templating->block('main');
$templating->set('current_email', $grab_email['email']);

if (isset($_POST['Update']))
{
	if (empty($_POST['new_email']))
	{
		header("Location: /usercp.php?module=email&message=empty&extra=email");
		$core->message('If you want to update your email you need to fill the field in!', NULL, 1);
		die();
	}

	if (empty($_POST['password']))
	{
		header("Location: /usercp.php?module=email&message=empty&extra=password");
		die();
	}

	// find current password
	$db->sqlquery("SELECT `password`, `steam_id`, `oauth_uid` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$grab_current_password = $db->fetch();

	if (!empty($grab_current_password['password']))
	{
		// check the originals match
		if (!password_verify($_POST['password'], $grab_current_password['password']))
		{
			header("Location: /usercp.php?module=email&message=password-match");
			die();
		}
	}

	// if they have no password, they simply must have a steamid or a twitter oauth id
	if (empty($grab_current_password['password']))
	{
		if (empty($grab_current_password['steam_id']) && empty($grab_current_password['oauth_uid']))
		{
			$user->logout();
			die();
		}
	}

	// check to see if this email exists
	$db->sqlquery("SELECT `email` FROM `users` WHERE `email` = ?", array($_POST['new_email']));
	if ($db->num_rows() >= 1)
	{
		header("Location: /usercp.php?module=email&message=not-that-email");
		die();
	}
	
	$new_email = trim($_POST['new_email']);

	// update to the new email address
	$db->sqlquery("UPDATE `users` SET `email` = ? WHERE `user_id` = ?", array($new_email, $_SESSION['user_id']));

	// send an email to their old address to let them know
	$subject = "Email changed on GamingOnLinux.com";

	// message
	$html_message = "<p>Hello <strong>{$grab_email['username']}</strong>,</p>
	<p>Someone, hopefully you, has changed your email address on <a href=\"".core::config('website_url')."\">gamingonlinux.com</a> to: {$_POST['new_email']}. If this was you, please ignore this email as it's just a security measure.</p>
	<hr>
		<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> !</p>
		<p>Please don&#39;t reply to this automated message. We do not read any mails recieved on this email address.</p>
		<p>-----------------------------------------------------------------------------------------------------------</p>
	</div>";

	$plain_message = PHP_EOL."Hello {$grab_email['username']}! Someone, hopefully you, has changed your email address on ".core::config('website_url')." to: {$_POST['new_email']}. If this was you, please ignore this email as it's just a security measure.";

	// Mail it
	if (core::config('send_emails') == 1)
	{
		$mail = new mail($grab_email['email'], $subject, $html_message, $plain_message);
		$mail->send();
	}

	// redirect and tell them it's done
	header("Location: /usercp.php?module=email&message=saved&extra=email");
}
?>
