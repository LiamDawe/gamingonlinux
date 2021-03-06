<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Change Email' . $templating->get('title', 1)  , 1);

$grab_email = $dbl->run("SELECT `email`, `username`, `supporter_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

$templating->load('usercp_modules/usercp_module_email');
$templating->block('main');
$templating->set('current_email', $grab_email['email']);
$templating->set('supporter_email', $grab_email['supporter_email']);

if (isset($_POST['Update']))
{
	if (empty($_POST['new_email']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'email';
		header("Location: /usercp.php?module=email");
		die();
	}

	if (empty($_POST['password']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'password';
		header("Location: /usercp.php?module=email");
		die();
	}

	// find current password
	$grab_current_password = $dbl->run("SELECT `password`, `steam_id`, `oauth_uid` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

	if (!empty($grab_current_password['password']))
	{
		// check the originals match
		if (!password_verify($_POST['password'], $grab_current_password['password']))
		{
			$_SESSION['message'] = 'password-match';
			header("Location: /usercp.php?module=email");
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
	$check_email = $dbl->run("SELECT `email` FROM `users` WHERE `email` = ?", array($_POST['new_email']))->fetch();
	if ($check_email)
	{
		$_SESSION['message'] = 'email_used';
		header("Location: /usercp.php?module=email");
		die();
	}
	
	$new_email = trim($_POST['new_email']);

	// update to the new email address
	$dbl->run("UPDATE `users` SET `email` = ? WHERE `user_id` = ?", array($new_email, $_SESSION['user_id']));

	// send an email to their old address to let them know
	$subject = "Email changed on GamingOnLinux";

	// message
	$html_message = "<p>Hello <strong>{$grab_email['username']}</strong>,</p>
	<p>Someone, hopefully you, has changed your email address on <a href=\"".$core->config('website_url')."\">GamingOnLinux</a> to: {$_POST['new_email']}. If this was you, please ignore this email as it's just a security measure.</p>";

	$plain_message = PHP_EOL."Hello {$grab_email['username']}! Someone, hopefully you, has changed your email address on ".$core->config('website_url')." to: {$_POST['new_email']}. If this was you, please ignore this email as it's just a security measure.";

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mailer($core);
		$mail->sendMail($grab_email['email'], $subject, $html_message, $plain_message);
	}

	$_SESSION['message'] = 'saved';
	$_SESSION['message_extra'] = 'email address';
	// redirect and tell them it's done
	header("Location: /usercp.php?module=email");
}

if (isset($_POST['supporter_update']))
{
	if (empty($_POST['supporter_email']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'supporter email';
		header("Location: /usercp.php?module=email");
		die();
	}
	
	$new_email = trim($_POST['supporter_email']);

	// update to the new email address
	$dbl->run("UPDATE `users` SET `supporter_email` = ? WHERE `user_id` = ?", array($new_email, $_SESSION['user_id']));

	$_SESSION['message'] = 'saved';
	$_SESSION['message_extra'] = 'supporter email address';
	// redirect and tell them it's done
	header("Location: /usercp.php?module=email");
}

if (isset($_POST['remove_supporter_email']))
{
	$dbl->run("UPDATE `users` SET `supporter_email` = NULL WHERE `user_id` = ?", array($_SESSION['user_id']));

	$_SESSION['message'] = 'deleted';
	$_SESSION['message_extra'] = 'supporter email address';
	// redirect and tell them it's done
	header("Location: /usercp.php?module=email");
}
?>
