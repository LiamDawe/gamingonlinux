<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'User blocking', 1);

if (isset($_GET['block']))
{
	if (!isset($_GET['block']) || !is_numeric($_GET['block']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'user';
		header('Location: /index.php');
		die();
	}

	// get their username
	$username = $dbl->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_GET['block']))->fetchOne();

	if (!isset($_POST['yes']) && !isset($_POST['no']))
	{
		$core->confirmation(['title' => 'Are you sure you wish to block them?', 'text' => 'Doing this will block ' . $username . ', are you sure you want to do this? You can UnBlock them any time.', 'act' => 'submit', 'action_url' => '/index.php?module=block_user&block=' . $_GET['block']]);		
	}
	else if (isset($_POST['no']))
	{
		header("Location: /usercp.php?module=block_list");
	}
	else if (isset($_POST['yes']))
	{
		$user->block_user($_GET['block']);
	}
}

if (isset($_GET['unblock']))
{
	if (!isset($_GET['unblock']) || !is_numeric($_GET['unblock']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'user';
		header('Location: /index.php');
		die();
	}

	// get their username
	$username = $dbl->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_GET['unblock']))->fetchOne();
	
	if (!isset($_POST['yes']) && !isset($_POST['no']))
	{
		$core->confirmation(['title' => 'Are you sure you wish to UnBlock them?', 'text' => 'Doing this will UnBlock ' . $username . ', are you sure you want to do this? You can block them again any time.', 'act' => 'submit', 'action_url' => '/index.php?module=block_user&unblock=' . $_GET['unblock']]);		
	}
	else if (isset($_POST['no']))
	{
		header("Location: /usercp.php?module=block_list");
	}
	else if (isset($_POST['yes']))
	{
		$user->unblock_user($_GET['unblock']);
	}
}
?>
