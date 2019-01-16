<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
// not logged in
if ((!isset($_SESSION['user_id'])) || ( isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 ) || ( isset($_SESSION['user_id']) && !core::is_number($_SESSION['user_id']) ))
{
	header("Location: /index.php?module=login");
}

// show links
else if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	// sort out private message unread counter
	$unread_counter = $dbl->run("SELECT COUNT(`conversation_id`) FROM `user_conversations_participants` WHERE `unread` = 1 AND `participant_id` = ?", array($_SESSION['user_id']))->fetchOne();

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
	if ($user->check_group([1,2,5]))
	{
		$admin_notes = $dbl->run("SELECT COUNT(`id`) FROM `admin_notifications` WHERE `completed` = 0")->fetchOne();
		if ($admin_notes > 0)
		{
			$notifications_link = "<span class=\"badge badge-important\">$admin_notes</span>";
		}
		
		$admin_link = "<li><a href=\"/admin.php\">Admin CP $notifications_link</a></li>";
	}

	$templating->set_previous('meta_description', 'Your account links', 1);
	$templating->set_previous('title', 'Your account links!', 1);

	$templating->load('account');
	$templating->block('main');
	$templating->set('admin_link', $admin_link);
	$templating->set('messages_link', $messages_link);
	$templating->set('user_id', $_SESSION['user_id']);
}
?>
