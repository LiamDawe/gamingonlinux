<?php
session_start();

header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{	
	// give admin link to who is allowed it, and sort out admin notifications
	$admin_line = '';
	$admin_indicator = '';
	$admin_notes = 0;
	if ($user->can('access_admin'))
	{
		$admin_notes = $dbl->run("SELECT count(*) FROM `admin_notifications` WHERE `completed` = 0")->fetchOne();
		if ($admin_notes > 0)
		{
			$admin_indicator = '<span class="badge badge-important">' . $admin_notes . '</span>';
		}
		else
		{
			$admin_indicator = 0;
		}
		$admin_line = '<li id="admin_notifications"><a href="'.$core->config('website_url').'admin.php">'.$admin_indicator.' new admin notifications</a></li>';
	}

	/* This section is for general user notifications, it covers:
	- article comments
	- forum replies TODO
	*/

	$alerts_counter = 0;

	// sort out private message unread counter
	$unread_messages_counter = $dbl->run("SELECT COUNT(`conversation_id`) FROM `user_conversations_participants` WHERE `unread` = 1 AND `participant_id` = ?", [$_SESSION['user_id']])->fetchOne();
	if ($unread_messages_counter == 0)
	{
		$messages_indicator = '<span id="pm_counter">0</span>';
	}

	else if ($unread_messages_counter > 0)
	{
		$messages_indicator = "<span id=\"pm_counter\" class=\"badge badge-important\">$unread_messages_counter</span>";
	}
	
	// set these by default as comment notifications can be turned off
	$new_comments_line = '';
	$unread_comments_counter = 0;
	$admin_comment_alerts = 0;
	$user_comment_alerts = $user->user_details['display_comment_alerts'];
	if ($user->check_group([1,2,5]))
	{
		$admin_comment_alerts = $user->user_details['admin_comment_alerts'];
	}
	if ($user_comment_alerts == 1 || $admin_comment_alerts == 1)
	{
		// sort out the number of unread comments
		$unread_comments_counter = $dbl->run("SELECT count(`id`) as `counter` FROM `user_notifications` WHERE `seen` = 0 AND owner_id = ?", [$_SESSION['user_id']])->fetchOne();

		if ($unread_comments_counter == 0)
		{
			$comments_indicator = 0;
		}

		else if ($unread_comments_counter > 0)
		{
			$comments_indicator = '<span class="badge badge-important">'.$unread_comments_counter.'</span>';
		}
		$new_comments_line = '<li id="normal_notifications"><a href="/usercp.php?module=notifications">'.$comments_indicator.' new notifications</a></li>';
	}

	// sort out the main navbar indicator
	$alerts_counter = $unread_messages_counter + $unread_comments_counter + $admin_notes;

	// sort out the styling for the alerts indicator
	$alerts_indicator = '';
	$alerts_icon = 'envelope-open';
	$alert_box_type = 'normal';
	if ($alerts_counter > 0)
	{
		$alerts_icon = 'envelope';
		$alert_box_type = 'new';
		$alerts_indicator = " <span id=\"notes-counter\" class=\"badge badge-important\">$alerts_counter</span>";
	}
	
	$dropdown_indicator = $alerts_indicator . ' ' . '<img src="'.$core->config('website_url') . 'templates/' . $core->config('template').'/images/comments/'.$alerts_icon.'.png" alt=""/>';
	
	echo json_encode(['title_total' => $alerts_counter, 'dropdown_indicator' => $dropdown_indicator, 'admin_badge' => $admin_line, 'normal_notifications' => $new_comments_line, 'pms_badge' => $messages_indicator]);
}
?>
