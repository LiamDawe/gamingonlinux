<?php
session_start();
if(!defined('golapp')) 
{
	die('Direct access not permitted: header');
}
$timer_start = microtime(true);

error_reporting(-1);

require APP_ROOT . "/includes/bootstrap.php";

if (isset($_GET['Logout']) && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$user->logout();
	die();
}

// site offline for whatever
if ($core->config('site_online') == 0)
{
	if (!$user->check_group(1))
	{
		include(APP_ROOT . '/templates/default/down.html');
		die();
	}
	else
	{
		$core->message('The website is currently in OFFLINE mode for maintenance!', 1);
	}
}

// have they come from a notification/alert box link?
if (isset($_GET['clear_note']) && isset($_SESSION['user_id']))
{
	$dbl->run("UPDATE `user_notifications` SET `seen` = 1, `seen_date` = ? WHERE `id` = ? AND `owner_id` = ? AND `seen` = 0", array(core::$date, (int) $_GET['clear_note'], (int) $_SESSION['user_id']));
}

$forum_class = new forum($dbl, $core, $user);

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	$theme = $user->user_details['theme'];
}

else
{
	$theme = 'default';
}

// get the header template html
$templating->load('header');
$templating->block('header', 'header');
$templating->set('url', $core->config('website_url'));
$templating->set('this_template', $core->config('website_url') . 'templates/' . $core->config('template'));

// if using the dark theme
$body_class = '';
if ($theme == 'dark')
{
	$body_class = 'class="dark"';
}
$templating->set('body_class', $body_class);

$templating->set('rss_link', '<link rel="alternate" type="application/rss+xml" title="RSS feed for GamingOnLinux" href="'.$core->config('website_url').'article_rss.php" />');

// set a blank article image as its set again in articles_full.php
if (!isset($_GET['module']) || isset($_GET['module']) && $_GET['module'] != 'articles_full' || $_GET['module'] == 'articles_full' && isset($_GET['go']))
{
	$templating->set('meta_data', '');;
}

$templating->load('mainpage');

$templating->block('top');
$templating->set('this_template', $core->config('website_url') . 'templates/' . $core->config('template'));
$templating->set('url', $core->config('website_url'));

$branding['icon'] = 'icon.svg';
// christmas
if (date('m') == '12')
{
	$branding['icon'] = 'icon_xmas.png';
}

$templating->set('icon', $branding['icon'] );

// Here we sort out what modules we are allowed to load, this also grabs links needed for the navbar
$core->load_modules(['db_table' => 'modules']);

$section_links = implode('', core::$top_bar_links);
$templating->set('sections_links', $section_links);

// sort the correct submit article link
$submit_a = '/submit-article/';
if ($user->check_group([1,2,5]))
{
	$submit_a = $core->config('website_url') . 'admin.php?module=add_article';
}

$templating->set('submit_a', $submit_a);

// sort out user box
if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0) || (!isset($_SESSION['user_id'])))
{
	$unread_counter = 0;

	$templating->set('user_link', '<li><a href="/index.php?module=login">Login</a></li><li><a href="/index.php?module=register">Register</a></li>');

	$login_menu = $templating->block_store('login_menu');
	$login_menu = $templating->store_replace($login_menu, array('url' => $core->config('website_url')));

	$templating->set('user_menu', $login_menu);
	$templating->set('notifications_menu', '');
}

else if ($_SESSION['user_id'] > 0)
{
	// give admin link to who is allowed it, and sort out admin notifications
	$admin_line = '';
	$admin_link = '';
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
		$admin_link = '<li><a href="'.$core->config('website_url').'admin.php">Admin CP</a></li>';
		$admin_line = '<li id="admin_notifications"><a href="'.$core->config('website_url').'admin.php">'.$admin_indicator.' new admin notifications</a></li>';
	}

	// for the mobile navigation
	$templating->set('user_link', '<li><a href="/index.php?module=account_links">'.$_SESSION['username'].'</a></li>');

	// for the user menu toggle
	$user_menu = $templating->block_store('user_menu', 'mainpage');

	$profile_link = "/profiles/{$_SESSION['user_id']}";
	$messages_html_link = '/private-messages/';
	
	$user_avatar = $user->sort_avatar($user->user_details);
	$username = $user->user_details['username'];
	
	$user_menu = $templating->store_replace($user_menu, array('avatar' => $user_avatar, 'username' => $username, 'profile_link' => $profile_link, 'admin_link' => $admin_link, 'url' => $core->config('website_url')));
	$templating->set('user_menu', $user_menu);

	/* This section is for general user notifications, it covers:
	- article comments
	- forum replies TODO
	*/

	$notifications_menu = $templating->block_store('notifications', 'mainpage');

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

	// replace everything in the notifications menu block
	$notifications_menu = $templating->store_replace($notifications_menu,
	array('alerts_icon' => $alerts_icon,
	'notifications_total' => $alerts_indicator,
	'alert_box_type' => $alert_box_type,
	'message_count' => $messages_indicator,
	'comments_line' => $new_comments_line,
	'messages_link' => $messages_html_link,
	'admin_line' => $admin_line,
	'this_template' => $core->config('website_url') . 'templates/' . $core->config('template')));

	$templating->set('notifications_menu', $notifications_menu);
}
