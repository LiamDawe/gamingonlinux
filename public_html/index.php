<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

if (isset($_GET['featured']) && isset($_GET['aid']) && is_numeric($_GET['aid']))
{
	$featured_grabber = $dbl->run("SELECT `article_id`, `slug` FROM `articles` WHERE `article_id` = ?", array($_GET['aid']))->fetch();

	if (!empty($featured_grabber['article_id']))
	{
		$dbl->run("UPDATE `editor_picks` SET `hits` = (hits + 1) WHERE `article_id` = ?", array($_GET['aid']));
		
		header('Location: ' . $article_class->get_link($featured_grabber['article_id'], $featured_grabber['slug']));
		die();
	}
}

if (core::$current_module['module_file_name'] == 'home')
{
	$total_featured = $core->config('total_featured');

	if ($total_featured == 1)
	{
		$featured = $dbl->run("SELECT a.article_id, a.`title`, a.active, p.featured_image FROM `editor_picks` p INNER JOIN `articles` a ON a.article_id = p.article_id WHERE a.active = 1 AND p.featured_image <> '' AND `end_date` > now()")->fetch();
	}
	if ($total_featured > 1)
	{
		if (!isset($_SESSION['last_featured_id']))
		{
			$_SESSION['last_featured_id'] = 0;
		}

		$last_featured_sql = '';
		if ($core->config('total_featured') > 1)
		{
			$last_featured_sql = 'AND a.article_id != ?';
		}

		$featured = $dbl->run("SELECT a.article_id, a.`title`, a.active, p.featured_image FROM `editor_picks` p INNER JOIN `articles` a ON a.article_id = p.article_id WHERE a.active = 1 AND p.featured_image <> '' AND p.`end_date` > now() $last_featured_sql ORDER BY RAND() LIMIT 1", array($_SESSION['last_featured_id']))->fetch();

		$_SESSION['last_featured_id'] = $featured['article_id'];
	}

	if ($total_featured >= 1 && $featured)
	{
		$templating->block('featured', 'mainpage');
		$templating->set('title', $featured['title']);
		$templating->set('image', $featured['featured_image']);

		$article_link = url . 'index.php?featured&amp;aid=' . $featured['article_id'];

		$templating->set('article_link', $article_link);
		$templating->set('url', url);
	}
}

// TWITCH ONLINE INDICATOR
if (!isset($_COOKIE['gol_announce_gol_twitch'])) // if they haven't dissmissed it
{
	$templating->load('twitch_bar');
	$templating->block('main', 'twitch_bar');
}

/* announcement bars */
$announcements = $announcements_class->get_announcements();

if (!empty($announcements))
{
	$templating->load('announcements');
	$templating->block('announcement_top', 'announcements');
	$templating->block('announcement', 'announcements');
	$templating->set('text', $bbcode->parse_bbcode($announcements['text']));
	$templating->set('dismiss', $announcements['dismiss']);
	$templating->block('announcement_bottom', 'announcements');
}

// let them know they aren't activated yet
if (isset($_GET['user_id']))
{
	if (!isset($_SESSION['activated']) && $_SESSION['user_id'] != 0)
	{
		$get_active = $dbl->run("SELECT `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
		$_SESSION['activated'] = $get_active['activated'];
	}
}

if (isset($_SESSION['activated']) && $_SESSION['activated'] == 0)
{
	if ( (isset($_SESSION['message']) && $_SESSION['message'] != 'new_account') || !isset($_SESSION['message']))
	{
		$templating->block('activation', 'mainpage');
		$templating->set('url', $core->config('website_url'));
	}
}

$templating->block('left', 'mainpage');

// so mainpage.html knows to put "articles" class in the left block or not
if (core::$current_module['module_file_name'] == 'home' || core::$current_module['module_file_name'] == 'search' || (core::$current_module['module_file_name'] == 'articles' && isset($_GET['view']) && ($_GET['view'] == 'cat' || $_GET['view'] == 'multiple')))
{
	$articles_css = 'articles';
}
else 
{
	$articles_css = '';
}
$templating->set('articles_css', $articles_css);

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message(core::$current_module['module_file_name'], $_SESSION['message'], $extra);
}

include('modules/'.core::$current_module['module_file_name'].'.php');

$templating->block('left_end', 'mainpage');

// The block that starts off the html for the left blocks
$templating->block('right', 'mainpage');

/* get the blocks */

if (($blocks = unserialize($core->get_dbcache('index_blocks'))) === false) // there's no cache
{
	$blocks = $dbl->run('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`')->fetch_all();
	$core->set_dbcache('index_blocks', serialize($blocks)); // no expiry as shown blocks hardly ever changes
}

foreach ($blocks as $block)
{
	// PHP BLOCKS
	if ($block['block_link'] != NULL)
	{
		include(APP_ROOT . "/blocks/{$block['block_link']}.php");
	}

	// CUSTOM BLOCKS
	else if ($block['block_link'] == NULL)
	{
		$show = 1;

		// this is to make sure the google ad only shows up for non ad-free people
		if ($block['nonpremium_only'] == 1)
		{
			if ($user->check_group(6) == true)
			{
				$show = 0;
			}
		}

		if ($block['homepage_only'] == 1)
		{
			$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$check_url = parse_url($actual_link);
			if ($check_url['path'] != '/')
			{
				$show = 0;
			}
		}

		if ($show == 1)
		{
			$templating->load('blocks/block_custom');

			if ($block['style'] == 'block')
			{
				$templating->block('block');
			}
			if ($block['style'] == 'block_plain')
			{
				$templating->block('block_plain');
			}
			$title = '';
			// any title link?
			if (!empty($block['block_title_link']))
			{
				$title = "<a href=\"{$block['block_title_link']}\" target=\"_blank\">{$block['block_title']}</a>";
			}
			else if (!empty($block['block_title']))
			{
				$title = $block['block_title'];
			}

			$templating->set('block_title', $title);
			$templating->set('block_content', $bbcode->parse_bbcode($block['block_custom_content']));
		}
	}
}


$templating->block('right_end', 'mainpage');

include(APP_ROOT . '/includes/footer.php');
?>
