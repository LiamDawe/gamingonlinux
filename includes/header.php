<?php
session_start();

error_reporting(-1);

$timer_start = microtime(true);

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_mysql.php');
$db = new mysql($db_conf['host'], $db_conf['username'], $db_conf['password'], $db_conf['database']);

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_messages.php');
$message_map = new message_map();

include($file_dir . '/includes/class_plugins.php');
$plugins = new plugins($dbl, $core, $file_dir);

include($file_dir . '/includes/class_article.php');
$article_class = new article_class($dbl, $core, $plugins);

include($file_dir . '/includes/bbcode.php');
$bbcode = new bbcode($dbl, $core, $plugins);

include($file_dir . '/includes/class_mail.php');

define('url', $core->config('website_url'));

include($file_dir . '/includes/class_user.php');
$user = new user($dbl, $core);
if (isset($_GET['act']) && $_GET['act'] == 'Logout')
{
	$user->logout();
}
$user->check_session();
$user->grab_user_groups();

include($file_dir . '/includes/class_forum.php');
$forum_class = new forum_class($dbl, $core, $user);

include($file_dir . '/includes/class_charts.php');

// setup the templating, if not logged in default theme, if logged in use selected theme
include($file_dir . '/includes/class_template.php');

$templating = new template($core, $core->config('template'));

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	$theme = $user->get('theme', $_SESSION['user_id']);
}

else
{
	$theme = 'default';
}

// get the header template html
$templating->load('header');
$templating->block('header', 'header');
$templating->set('site_title', $core->config('site_title'));
$templating->set('meta_keywords', $core->config('meta_keywords'));
$templating->set('url', $core->config('website_url'));
$templating->set('this_template', $core->config('website_url') . 'templates/' . $core->config('template'));

// add a gol premium class tag to the body html tag, this is used to ignore gol premium and editors from the ad-blocking stats gathering
$body_class = '';
if ($theme == 'default')
{
	$body_class = '';
}
else if ($theme != 'default' && $user->check_group(6) == true)
{
	$body_class = 'class="dark"';
}

else
{
	$body_class = '';
}

$templating->set('body_class', $body_class);

$templating->set('rss_link', '<link rel="alternate" type="application/rss+xml" title="RSS feed for '.$core->config('site_title').'" href="'.$core->config('website_url').'article_rss.php" />');

// set a blank article image as its set again in articles_full.php
if (!isset($_GET['module']) || isset($_GET['module']) && $_GET['module'] != 'articles_full' || $_GET['module'] == 'articles_full' && isset($_GET['go']))
{
	$templating->set('meta_data', '');;
}

$templating->merge('mainpage');

$templating->block('top');
$templating->set('this_template', $core->config('website_url') . 'templates/' . $core->config('template'));
$templating->set('url', $core->config('website_url'));

$branding['icon'] = $core->config('navbar_logo_icon');
$branding['title'] = $core->config('site_title');

$icon_plugin = $plugins->do_hooks('icon_hook');

if (is_array($icon_plugin))
{
	if (isset($icon_plugin['icon']) && !empty($icon_plugin['icon']))
	{
		$branding['icon'] = $icon_plugin['icon'];
	}
	if (isset($icon_plugin['title']) && !empty($icon_plugin['title']))
	{
		$branding['title'] = $icon_plugin['title'];
	}
}

$templating->set('icon', $branding['icon'] );
$templating->set('site_title', $branding['title']);

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
		$admin_line = '<li><a href="'.$core->config('website_url').'admin.php">'.$admin_indicator.' new admin notifications</a></li>';
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
	
	$user_avatar = $user->sort_avatar($_SESSION['user_id']);
	$username = $user->get('username', $_SESSION['user_id']);
	
	$user_menu = $templating->store_replace($user_menu, array('avatar' => $user_avatar, 'username' => $username, 'profile_link' => $profile_link, 'admin_link' => $admin_link, 'url' => $core->config('website_url')));
	$templating->set('user_menu', $user_menu);

	/* This section is for general user notifications, it covers:
	- article comments
	- comment likes TODO
	- forum replies TODO
	*/

	$notifications_menu = $templating->block_store('notifications', 'mainpage');

	$alerts_counter = 0;

	// sort out private message unread counter
	$unread_messages_counter = $dbl->run("SELECT COUNT(`conversation_id`) FROM `user_conversations_participants` WHERE `unread` = 1 AND `participant_id` = ?", [$_SESSION['user_id']])->fetchOne();
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
	$unread_comments_counter = 0;
	$user_comment_alerts = $user->get('display_comment_alerts', $_SESSION['user_id']);
	if ($user_comment_alerts == 1)
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
		$new_comments_line = '<li><a href="/usercp.php?module=notifications">'.$comments_indicator.' new notifications</a></li>';
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
