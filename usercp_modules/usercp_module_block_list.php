<?php
$templating->set_previous('title', 'Block List' . $templating->get('title', 1)  , 1);

$templating->load('usercp_modules/block_list');
$templating->block('main');

$templating->block('blocked_list');

$list = '';
foreach ($user->blocked_users as $username => $blocked_id)
{
	$list .= '<li>'.$username.' <form method="post"><button name="act" value="remove" formaction="/usercp.php?module=block_list">Unblock</button><input type="hidden" name="block_id" value="'.$blocked_id[0].'" /></form></li>';
}
$templating->set('list', $list);

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		// get their username
		$username = $dbl->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_POST['block_id']))->fetchOne();
			
		// check they're not on the list already
		$check = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_SESSION['user_id'], $_POST['block_id']))->fetchOne();
		if ($check)
		{
			$_SESSION['message'] = 'already_blocked';
			$_SESSION['message_extra'] = $username;
			
			header("Location: /usercp.php?module=block_list");
			die();
		}
		else
		{
			// add them to the block block list 
			$dbl->run("INSERT INTO `user_block_list` SET `user_id` = ?, `blocked_id` = ?", array($_SESSION['user_id'], $_POST['block_id']));

			$_SESSION['message'] = 'blocked';
			$_SESSION['message_extra'] = $username;
			
			header("Location: /usercp.php?module=block_list");
			die();
		}
	}
	
	if ($_POST['act'] == 'remove')
	{
		// get their username
		$username = $dbl->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_POST['block_id']))->fetchOne();
		
		$dbl->run("DELETE FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_SESSION['user_id'], $_POST['block_id']));
		
		$_SESSION['message'] = 'unblocked';
		$_SESSION['message_extra'] = $username;
			
		header("Location: /usercp.php?module=block_list");
		die();
	}
}
?>
