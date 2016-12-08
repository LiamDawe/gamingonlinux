<?php
error_reporting(-1);

session_start();

date_default_timezone_set('UTC');

$timer_start = microtime(true);

include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

include('includes/class_article.php');
$article_class = new article_class();

include('includes/class_mail.php');

define('url', core::config('website_url'));

include('includes/class_user.php');
$user = new user();

if (isset($_GET['act']) && $_GET['act'] == 'Logout')
{
	$user->logout();
}

// can be removed eventually, stop-gap to stop errors for people already logged in that don't get the new options
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	if (!isset($_SESSION['display_comment_alerts']))
	{
		$_SESSION['display_comment_alerts'] = 1;
	}
	if (!isset($_SESSION['avatar']))
	{
		$db->sqlquery("SELECT `avatar_gravatar`, `gravatar_email`, `avatar_gallery`, `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		$temp_user = $db->fetch();
		$_SESSION['avatar'] = user::sort_avatar($temp_user);
	}
}

// If they are not logged in make them a guest (group 4)
if (!isset($_SESSION['logged_in']))
{
	if (isset($_COOKIE['gol_stay']) && isset($_COOKIE['gol_session']) && isset($_COOKIE['gol-device']) && $user->stay_logged_in() == true)
	{
		header("Location: " . $_SERVER['REQUEST_URI']);
	}

	else
	{
		$_SESSION['user_id'] = 0;
		$_SESSION['username'] = 'Guest'; // not even sure why I set this
		$_SESSION['user_group'] = 4;
		$_SESSION['secondary_user_group'] = 4;
		$_SESSION['theme'] = 'light';
		$_SESSION['per-page'] = 10;
		$_SESSION['articles-per-page'] = 15;
		$_SESSION['forum_type'] = 'normal_forum';
		$_SESSION['single_article_page'] = 0;
	}
}

// setup the templating, if not logged in default theme, if logged in use selected theme
include('includes/class_template.php');

$templating = new template('default');

if ($_SESSION['user_id'] != 0 && $_SESSION['theme'] != 'default')
{
	$theme = $_SESSION['theme'];
}

else if ($_SESSION['user_id'] == 0 || $_SESSION['theme'] == 'default')
{
	$theme = 'light';
}

include('includes/bbcode.php');

// if you are logged in check for banning
if ($_SESSION['user_id'] != 0)
{
	$db->sqlquery("SELECT `banned` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']), 'header.php');
	$banning_check = $db->fetch();

	if ($banning_check['banned'] == 1)
	{
		$db->sqlquery("DELETE FROM `saved_sessions` WHERE `user_id` = ?", array($_SESSION['user_id']), 'header.php');
		setcookie('gol_stay', "",  time()-60, '/');
		$_SESSION['user_id'] = 0;
		$_SESSION['user_group'] = 4;
		$_SESSION['secondary_user_group'] = 4;
		header("Location: /index.php");
	}
}

// get user group permissions
$get_permissions = $db->sqlquery("SELECT `name`,`value` FROM `group_permissions` WHERE `group` = ?", array($_SESSION['user_group']));
$fetch_permissions = $db->fetch_all_rows();

$parray = array();
foreach ($fetch_permissions as $permissions_set)
{
	$parray[$permissions_set['name']] = $permissions_set['value'];
}

// check for gol premium
if ($_SESSION['user_id'] != 0 && $user->check_group(6) == false)
{
	$db->sqlquery("SELECT `user_group`, `secondary_user_group` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$check_new_prem = $db->fetch();

	if ($check_new_prem['user_group'] == 6 || $check_new_prem['secondary_user_group'] == 6)
	{
		$_SESSION['secondary_user_group'] = 6;
	}
}

// get the header template html
$templating->load('header');
$templating->block('header', 'header');
$templating->set('url', url);

// add a gol premium class tag to the body html tag, this is used to ignore gol premium and editors from the ad-blocking stats gathering
$body_class = '';
if ($theme == 'light')
{
	$body_class = '';
}
else if ($theme != 'light' && $user->check_group(6) == true)
{
	$body_class = 'class="dark"';
}

else
{
	$body_class = '';
}

$templating->set('body_class', $body_class);

$templating->set('rss_link', '<link rel="alternate" type="application/rss+xml" title="RSS feed for GamingOnLinux" href="https://www.gamingonlinux.com/article_rss.php" />');

// set a blank article image as its set again in articles_full.php
if (!isset($_GET['module']) || isset($_GET['module']) && $_GET['module'] != 'articles_full' || $_GET['module'] == 'articles_full' && isset($_GET['go']))
{
	$templating->set('meta_data', '');;
}

$templating->merge('mainpage');

$templating->block('top');
$templating->set('url', url);

// april fools, because why not
if (date('dm') == '0104')
{
	$icon = 'windows_logo.png';
	$site_title = 'Gaming On Windows 10';
}
else if (date('m') == '12')
{
	$icon = 'icon_xmas.png';
	$site_title = 'Gaming On Linux';
}
else
{
	$icon = 'icon.svg';
	$site_title = 'Gaming On Linux';
}
$templating->set('icon', $icon);
$templating->set('site_title', $site_title);

// sort the links out
if (core::config('pretty_urls') == 1)
{
	$donate_link = '/support-us/';
	$statistics_link = '/users/statistics';
	$forum_link = '/forum/';
	$irc_link = '/irc/';
	$contact_link = '/contact-us/';
	$submit_a = '/submit-article/';
	if (isset($_SESSION['user_group']))
	{
		if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
		{
			$submit_a = url . 'admin.php?module=add_article';
		}
	}
	$submit_e = '/email-us/';
}
else {
	$donate_link = url . 'index.php?module=support_us';
	$statistics_link = url . 'index.php?module=statistics';
	$forum_link = url . 'index.php?module=forum';
	$irc_link = url . 'irc.php';
	$contact_link = url . 'index.php?module=contact';
	$submit_a = url . 'index.php?module=submit_article&view=Submit';
	if (isset($_SESSION['user_group']))
	{
		if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
		{
			$submit_a = url . 'admin.php?module=add_article';
		}
	}
	$submit_e = url . 'index.php?module=email_us';
}
$templating->set('donate_link', $donate_link);
$templating->set('statistics_link', $statistics_link);
$templating->set('forum_link', $forum_link);
$templating->set('irc_link', $irc_link);
$templating->set('contact_link', $contact_link);
$templating->set('submit_a', $submit_a);
$templating->set('submit_e', $submit_e);

// sort out user box
if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0) || (!isset($_SESSION['user_id'])))
{
	$unread_counter = 0;
	$username = '';
	$username_remembered = '';
	if (isset($_COOKIE['remember_username']))
	{
		$username = $_COOKIE['remember_username'];
		$username_remembered = 'checked';
	}

	$templating->set('user_link', '<li><a href="/index.php?module=login">Login</a></li><li><a href="/index.php?module=register">Register</a></li>');

	$login_menu = $templating->block_store('login_menu');

	$templating->set('user_menu', $login_menu);
	$templating->set('notifications_menu', '');

	}

else if ($_SESSION['user_id'] > 0)
{
	// give admin link to who is allowed it, and sort out admin notifications
	$admin_line = '';
	$admin_link = '';
	$admin_indicator = '';
	$admin_notes['counter'] = 0;
	if ($user->check_group(1,2) || $user->check_group(5))
	{
		$db->sqlquery("SELECT count(id) as counter FROM `admin_notifications` WHERE `completed` = 0");
		$admin_notes = $db->fetch();
		if ($admin_notes['counter'] > 0)
		{
			$admin_indicator = '<span class="badge badge-important">' . $admin_notes['counter'] . '</span>';
		}
		else
		{
			$admin_indicator = 0;
		}
		$admin_link = '<li><a href="'.url.'admin.php">Admin CP</a></li>';
		$admin_line = '<li><a href="'.url.'admin.php">'.$admin_indicator.' new admin notifications</a></li>';
	}

	// for the mobile navigation
	$templating->set('user_link', '<li><a href="/index.php?module=account_links">'.$_SESSION['username'].'</a></li>');

	// for the user menu toggle
	$user_menu = $templating->block_store('user_menu', 'mainpage');

	if (core::config('pretty_urls') == 1)
	{
		$profile_link = "/profiles/{$_SESSION['user_id']}";
		$messages_html_link = '/private-messages/';
	}
	else
	{
		$profile_link = url . "index.php?module=profile&user_id={$_SESSION['user_id']}";
		$messages_html_link = url . "index.php?module=messages";
	}

	$user_menu = $templating->store_replace($user_menu, array('avatar' => $_SESSION['avatar'], 'username' => $_SESSION['username'], 'profile_link' => $profile_link, 'admin_link' => $admin_link));
	$templating->set('user_menu', $user_menu);

	/* This section is for general user notifications, it covers:
	- article comments
	- comment likes TODO
	- forum replies TODO
	*/

	$notifications_menu = $templating->block_store('notifications', 'mainpage');

	$alerts_counter = 0;

	// sort out private message unread counter
	$db->sqlquery("SELECT `conversation_id` FROM `user_conversations_participants` WHERE `unread` = 1 AND `participant_id` = ?", array($_SESSION['user_id']), 'header.php');
	$unread_messages_counter = $db->num_rows();

	if ($unread_messages_counter == 0)
	{
		$messages_indicator = 0;
	}

	else if ($unread_messages_counter > 0)
	{
		$messages_indicator = "<span class=\"badge badge-important\">$unread_messages_counter</span>";
	}

	// set these by default as comment notifications can be turned off
	$new_comments_line = '';
	$unread_comments_counter['counter'] = 0;
	if (isset($_SESSION['display_comment_alerts']) && $_SESSION['display_comment_alerts'] == 1)
	{
		// sort out the number of unread comments
		$db->sqlquery("SELECT count(`id`) as `counter` FROM `user_notifications` WHERE `seen` = 0 AND owner_id = ?", array($_SESSION['user_id']));
		$unread_comments_counter = $db->fetch();

		if ($unread_comments_counter['counter'] == 0)
		{
			$comments_indicator = 0;
		}

		else if ($unread_comments_counter['counter'] > 0)
		{
			$comments_indicator = '<span class="badge badge-important">'.$unread_comments_counter['counter'].'</span>';
		}
		$new_comments_line = '<li><a href="/usercp.php?module=notifications">'.$comments_indicator.' new notifications</a></li>';
	}

	// sort out the main navbar indicator
	$alerts_counter = $unread_messages_counter + $unread_comments_counter['counter'] + $admin_notes['counter'];

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
	'admin_line' => $admin_line));

	$templating->set('notifications_menu', $notifications_menu);
}
