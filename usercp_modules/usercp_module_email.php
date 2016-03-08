<?php
$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$grab_email = $db->fetch();

$templating->merge('usercp_modules/usercp_module_email');
$templating->block('main');
$templating->set('current_email', $grab_email['email']);

if (isset($_POST['Update']))
{
	if (empty($_POST['new_email']))
	{
		$core->message('If you want to update your email you need to fill the field in!', NULL, 1);
	}

	else
	{
		$db->sqlquery("UPDATE `users` SET `email` = ? WHERE `user_id` = ?", array($_POST['new_email'], $_SESSION['user_id']), 'usercp_module_email.php');
		$core->message("Your email is now updated to {$_POST['new_email']}!");
	}
}
?>
