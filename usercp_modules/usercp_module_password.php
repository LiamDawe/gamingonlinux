<?php
$templating->merge('usercp_modules/usercp_module_password');
$templating->block('main');

if (isset($_POST['Update']))
{	
	// find current password
	$db->sqlquery("SELECT `password`, `password_salt` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$grab_current_password = $db->fetch();
	
	$current_password_test = hash('sha256', $grab_current_password['password_salt'] . $_POST['current_password']);
	$new_password_safe = hash('sha256', $grab_current_password['password_salt'] . $_POST['new_password']);
	
	if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['new_password_check']))
	{
		$core->message('If you want to update your password you need to fill all the fields in!', NULL, 1);
	}
	
	// check the originals match
	else if ($grab_current_password['password'] != $current_password_test)
	{
		$core->message('The original passwords did not match, please try again!', NULL, 1);
	}
	
	// check the new ones match
	else if ($_POST['new_password'] != $_POST['new_password_check'])
	{
		$core->message('The new password didn\'t match the checker, try again!', NULL, 1);
	}

	else
	{
		// make a new salt for the new password
		$salt = $user->salt();
		
		$proper_new_password = hash('sha256', $salt . $_POST['new_password']);
		
		$db->sqlquery("UPDATE `users` SET `password` = ?, `password_salt` = ? WHERE `user_id` = ?", array($proper_new_password, $salt, $_SESSION['user_id']));
		$core->message("Your password has been updated!");
	}
}
?>
