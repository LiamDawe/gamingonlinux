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
		$count_rows = $db->num_rows();
		if ($count_rows == 1)
		{
			$db->sqlquery("UPDATE `users` SET `activated` = 1 WHERE `user_id` = ?", array($_GET['user_id']));

			$_SESSION['activated'] = 1;
			$_SESSION['message'] = 'activated';
			header("Location: ".$core->config('website_url')."index.php?module=home");
		}
		else
		{
			$_SESSION['message'] = 'cannot_activate';
			header("Location: ".$core->config('website_url')."index.php?module=home");
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

		// subject
		$subject = 'Welcome to '.$core->config('site_title').', activation needed!';

		// message
		$html_message = "<p>Hello {$_SESSION['username']},</p>
		<p>Thanks for registering on <a href=\"" . $core->config('website_url') . "\" target=\"_blank\">" . $core->config('website_url') . "</a>!</p>
		<p><strong><a href=\"" . $core->config('website_url') . "index.php?module=activate_user&user_id={$_SESSION['user_id']}&code=$code\">You need to activate your account before you can post! Click here to activate!</a></strong></p>
		<p>If you're new, consider saying hello in the <a href=\"" . $core->config('website_url') . "forum/\" target=\"_blank\">forum</a>.</p>";
		
		$plain_message = 'Hello ' . $_SESSION['username'] . ', thanks for registering on ' . $core->config('website_url') . '! You need to activate your account before you can post, do so here: ' . $core->config('website_url') . 'index.php?module=activate_user&user_id=' . $_SESSION['user_id'] . '&code=' . $code;
		
		$mail = new mailer($core);
		$mail->sendMail($get_active['email'], $subject, $html_message, $plain_message);

		$core->message("We have re-sent a new activation code {$_SESSION['username']}, <strong>please check your emails for the activation link</strong>! <a href=\"index.php\">Click here to return</a>");
	}

	else if ($get_active['activated'] == 1)
	{
		header("Location: ".$core->config('website_url'));
	}
}
?>
