<?php
$timer_start = microtime(true);

session_start();

error_reporting(-1);

require APP_ROOT . "/includes/bootstrap.php";

if (isset($_GET['Logout']))
{
	$user->logout();
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

// add a gol premium class tag to the body html tag, this is used to ignore gol premium and editors from the ad-blocking stats gathering
$body_class = '';
if ($theme == 'default')
{
	$body_class = '';
}
else if ($theme != 'default' && $user->can('premium_features') == true)
{
	$body_class = 'class="dark"';
}

else
{
	$body_class = '';
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

// sort the links out
if ($core->config('pretty_urls') == 1)
{
	$forum_link = '/forum/';
	$irc_link = '/irc/';
	$contact_link = '/contact-us/';
	$submit_a = '/submit-article/';
	$sales_link = '/sales/';
	$free_link = '/free-games/';
	if ($user->check_group([1,2,5]))
	{
		$submit_a = $core->config('website_url') . 'admin.php?module=add_article';
	}
	$submit_e = '/email-us/';
}
else 
{
	$forum_link = $core->config('website_url') . 'index.php?module=forum';
	$irc_link = $core->config('website_url') . 'index.php?module=irc';
	$contact_link = $core->config('website_url') . 'index.php?module=contact';
	$submit_a = $core->config('website_url') . 'index.php?module=submit_article&view=Submit';
	$sales_link = $core->config('website_url') . 'sales.php';
	$free_link = $core->config('website_url') . 'free_games.php';
	if ($user->check_group([1,2,5]))
	{
		$submit_a = $core->config('website_url') . 'admin.php?module=add_article';
	}
	$submit_e = $core->config('website_url') . 'index.php?module=email_us';
}
$templating->set('forum_link', $forum_link);
$templating->set('irc_link', $irc_link);
$templating->set('contact_link', $contact_link);
$templating->set('submit_a', $submit_a);
$templating->set('submit_e', $submit_e);
$templating->set('sales_link', $sales_link);
$templating->set('free_link', $free_link);

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

	if ($core->config('pretty_urls') == 1)
	{
		$profile_link = "/profiles/{$_SESSION['user_id']}";
		$messages_html_link = '/private-messages/';
	}
	else
	{
		$profile_link = $core->config('website_url') . "index.php?module=profile&user_id={$_SESSION['user_id']}";
		$messages_html_link = $core->config('website_url') . "index.php?module=messages";
	}
	
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
