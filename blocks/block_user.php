<?php
// Article categorys block
$templating->merge('blocks/block_user');

if ($_SESSION['user_id'] == 0)
{
	$templating->block('menu_not_logged');
	$username = '';
	$username_remembered = '';
	if (isset($_COOKIE['remember_username']))
	{
		$username = $_COOKIE['remember_username'];
		$username_remembered = 'checked';
	}
		
	$templating->set('username', $username);
	$templating->set('username_remembered', $username_remembered);
}

else if ($_SESSION['user_id'] > 0)
{
	$templating->block('menu');

	// sort out private message unread counter
	$db->sqlquery("SELECT `conversation_id` FROM `user_conversations_participants` WHERE `unread` = 1 AND `participant_id` = ?", array($_SESSION['user_id']));
	$unread_counter = $db->num_rows();

	if ($unread_counter == 0)
	{
		$messages_link = 'Private Messages';
	}

	else if ($unread_counter > 0)
	{
		$messages_link = "Private Messages <span class=\"pm-count\">$unread_counter</a>";
	}

	// sort out admin red numbered notification for article submissions
	$admin_link = '';
	$notifications_link = '';
	if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2)
	{
		// now we set if we need to show notifications or not
		if ($config['admin_notifications'] > 0)
		{
			$notifications_link = "<span class=\"pm-count\">{$config['admin_notifications']}</a>";
		}
		
		$admin_link = "<li><a href=\"/admin.php\">Admin CP</a> $notifications_link</li>";
	}

	$templating->set('username', "<a href=\"/profiles/{$_SESSION['user_id']}\">{$_SESSION['username']}</a>");
	$templating->set('private_messages', "<a href=\"/private-messages/\">$messages_link</a>");
	$templating->set('admin_link', $admin_link);
}
