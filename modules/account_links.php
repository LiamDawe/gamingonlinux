<?php
// not logged in
if ($_SESSION['user_id'] == 0 || !isset($_SESSION['user_id']))
{
	header("Location: /index.php?module=login");
}

// show links
else if ($_SESSION['user_id'] > 0)
{
	// sort out private message unread counter
	$db->sqlquery("SELECT `conversation_id` FROM `user_conversations_participants` WHERE `unread` = 1 AND `participant_id` = ?", array($_SESSION['user_id']), 'header.php');
	$unread_counter = $db->num_rows();

	if ($unread_counter == 0)
	{
		$messages_link = 'Private Messages';
	}

	else if ($unread_counter > 0)
	{
		$messages_link = "Private Messages <span class=\"badge badge-important\">$unread_counter</span>";
	}

	// sort out admin red numbered notification for article submissions
	$admin_link = '';
	$notifications_link = '';
	if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
	{
		// now we set if we need to show notifications or not
		if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2)
		{
			$db->sqlquery("SELECT `id` FROM `admin_notifications` WHERE `completed` = 0");
			$admin_notes = $db->num_rows();
			if ($admin_notes > 0)
			{
				$notifications_link = "<span class=\"badge badge-important\">$admin_notes</span>";
			}
		}
		
		$admin_link = "<li><a href=\"/admin.php\">Admin CP $notifications_link</a></li>";
	}

	$templating->set_previous('meta_description', 'Your account links', 1);
	$templating->set_previous('title', 'Your account links!', 1);

	$templating->merge('account');
	$templating->block('main');
	$templating->set('admin_link', $admin_link);
	$templating->set('messages_link', $messages_link);
	$templating->set('user_id', $_SESSION['user_id']);
}
?>
