<?php
error_reporting(-1);

session_start();

date_default_timezone_set('UTC');

$timer_start = microtime(true);

include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

// get config
$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
}

include('includes/class_core.php');
$core = new core();

include('includes/class_mail.php');

define('url', core::config('website_url'));

include('includes/class_user.php');
$user = new user();

if (isset($_GET['act']) && $_GET['act'] == 'Logout')
{
	$user->logout();
}

if (!isset($_SESSION['per-page']))
{
	$_SESSION['per-page'] = 10;
}

if (!isset($_SESSION['articles-per-page']))
{
	$_SESSION['articles-per-page'] = 15;
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
		$_SESSION['infinite_scroll'] = 1;
		header("Location: /index.php");
	}
}

// get user group permissions
$objects = array($_SESSION['user_group']);
$get_permissions = $db->sqlquery("SELECT `name`,`value` FROM `group_permissions` WHERE `group` = ?", $objects, 'header.php');
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

if (($core->current_page() == 'admin.php') || (isset($_GET['module']) && $_GET['module'] == 'articles'))
{
	if (isset($_GET['view']) && $_GET['view'] != 'cat' || !isset($_GET['view']))
	{
		$templating->block('category_selection');
		$templating->set('url', url);
	}
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
	$sales_link = '/sales/';
	$forum_link = '/forum/';
	$contact_link = '/contact-us/';
	$submit_a = '/submit-article/';
	if (isset($_SESSION['user_group']))
	{
		if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
		{
			$submit_a = url . 'admin.php?module=articles&amp;view=add';
		}
	}
	$submit_e = '/email-us/';
}
else {
	$donate_link = url . 'index.php?module=support_us';
	$sales_link = url . 'sales.php';
	$forum_link = url . 'index.php?module=forum';
	$contact_link = url . 'index.php?module=contact';
	$submit_a = url . 'index.php?module=submit_article&view=Submit';
	if (isset($_SESSION['user_group']))
	{
		if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
		{
			$submit_a = url . 'admin.php?module=articles&amp;view=add';
		}
	}
	$submit_e = url . 'index.php?module=email_us';
}
$templating->set('donate_link', $donate_link);
$templating->set('sales_link', $sales_link);
$templating->set('forum_link', $forum_link);
$templating->set('contact_link', $contact_link);
$templating->set('submit_a', $submit_a);
$templating->set('submit_e', $submit_e);

// Get the categorys, for the jump list, also used in "block_article_categorys.php"
$articles_categorys = '';
$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
while ($categorys = $db->fetch())
{
	if (core::config('pretty_urls') == 1)
	{
		$category_jump_link = "/articles/category/{$categorys['category_id']}";
	}
	else {
		$category_jump_link = url . "index.php?module=articles&amp;view=cat&amp;catid={$categorys['category_id']}";
	}
	$articles_categorys .= "<option value=\"$category_jump_link\">{$categorys['category_name']}</option>\r\n";
}
$templating->set('category_links', $articles_categorys);

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

	$user_menu = "<a href=\"/index.php?module=login\">Account</a>
							<div>
								<form method=\"post\" action=\"".url."index.php?module=login\">
									<input name=\"username\" class=\"ays-ignore\" type=\"text\" value=\"$username\" placeholder=\"Username or Email\" />
									<input name=\"password\" class=\"ays-ignore\" type=\"password\" placeholder=\"Password\" />
									<label>
										<input name=\"remember_name\" class=\"ays-ignore\" type=\"checkbox\" $username_remembered/> Remember username
									</label>
									<label>
										<input name=\"stay\" class=\"ays-ignore\" checked=\"checked\" type=\"checkbox\" /> Stay logged in
									</label>
									<hr />
									<input type=\"submit\" name=\"action\" value=\"Login\" />
									<span class=\"group fright\" style=\"margin-top: 7px;\">Or login with...</span>
									<hr />
									<div class=\"group\">
										<a href=\"".url."index.php?module=login&amp;steam\" class=\"button fleft\">
											<img alt src=\"".url."templates/default/images/social/steam.svg\" width=\"18\" height=\"18\" />
											Steam
										</a>
										<a href=\"".url."index.php?module=login&amp;twitter\" class=\"button fright\">
											<img alt src=\"".url."templates/default/images/social/twitter.svg\" width=\"18\" height=\"18\" />
											Twitter
										</a>
									</div>
									<hr />
									<div class=\"group\">
										<a href=\"/register/\" class=\"button fleft\">Register</a>
										<a href=\"".url."index.php?module=login&amp;forgot\" class=\"button fright\">Forgot Login?</a>
									</div>
								</form>
							</div>";

	$templating->set('user_menu', $user_menu);
	$templating->set('username', "Account");
}

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
	if ($_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5 || $_SESSION['secondary_user_group'] == 5)
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

		$admin_link = '<li><a href="'.url.'admin.php">Admin CP '.$notifications_link.'</a></li>';
	}

	$notifications_total = $unread_counter;
	if ($user->check_group(1,2))
	{
		$notifications_total = $unread_counter + $admin_notes;
	}
	$notifications_show = 0;
	$notifications_username = '';
	if ($notifications_total > 0)
	{
		$notifications_username = "<span class=\"badge badge-important\">$notifications_total</span>";
	}

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

	$user_menu = "<a href=\"".url."index.php?module=account_links\">{$_SESSION['username']} $notifications_username</a>
	<ul><li><a href=\"$profile_link\">View Profile</a></li>
								<li><a href=\"".url."usercp.php\">User CP</a></li>
								<li><a href=\"$messages_html_link\">$messages_link</a></li>
								$admin_link
								<li><a href=\"".url."index.php?act=Logout\">Logout</a></li></ul>";

	$templating->set('user_menu', $user_menu);

}
