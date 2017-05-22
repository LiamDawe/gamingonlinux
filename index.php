<?php
error_reporting(E_ALL);

$file_dir = dirname(__FILE__);

include($file_dir . '/includes/header.php');

if (isset($_GET['featured']) && isset($_GET['aid']) && is_numeric($_GET['aid']))
{
	$db->sqlquery("SELECT `article_id`, `slug` FROM `articles` WHERE `article_id` = ?", array($_GET['aid']));
	$featured_grabber = $db->fetch();

	if (!empty($featured_grabber['article_id']))
	{
		$db->sqlquery("UPDATE `editor_picks` SET `hits` = (hits + 1) WHERE `article_id` = ?", array($_GET['aid']));
		
		header('Location: ' . $article_class->get_link($featured_grabber['article_id'], $featured_grabber['slug']));
	}
}

if (core::$current_module['module_file_name'] == 'home')
{
	$db->sqlquery("SELECT a.active, p.featured_image FROM `editor_picks` p INNER JOIN `articles` a ON a.article_id = p.article_id WHERE a.active = 1 AND p.featured_image <> ''");
	$count_total = $db->num_rows();

	if ($count_total == 1)
	{
		$db->sqlquery("SELECT a.article_id, a.`title`, a.active, p.featured_image, a.author_id, a.comment_count, u.username, u.user_id FROM `editor_picks` p INNER JOIN `articles` a ON a.article_id = p.article_id LEFT JOIN `".$dbl->table_prefix."users` u ON a.author_id = u.user_id WHERE a.active = 1 AND p.featured_image <> ''");
		$featured = $db->fetch();
	}
	if ($count_total > 1)
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

		$db->sqlquery("SELECT a.article_id, a.`title`, a.active, p.featured_image, a.author_id, a.comment_count, u.username, u.user_id FROM `editor_picks` p INNER JOIN `articles` a ON a.article_id = p.article_id LEFT JOIN `".$dbl->table_prefix."users` u ON a.author_id = u.user_id WHERE a.active = 1 AND p.featured_image <> '' $last_featured_sql ORDER BY RAND() LIMIT 1", array($_SESSION['last_featured_id']));
		$featured = $db->fetch();

		$_SESSION['last_featured_id'] = $featured['article_id'];
	}

	if ($count_total >= 1)
	{
		$templating->block('featured', 'mainpage');
		$templating->set('title', $featured['title']);
		$templating->set('image', $featured['featured_image']);

		if ($featured['author_id'] == 0)
		{
			if (empty($featured['guest_username']))
			{
				$username = 'Guest';
			}

			else
			{
				$username = $featured['guest_username'];
			}
		}

		else
		{
			$username = "<a href=\"/profiles/{$featured['author_id']}\">" . $featured['username'] . '</a>';
		}
		$templating->set('username', $username);
		$templating->set('comment_count', $featured['comment_count']);

		$article_link = url . 'index.php?featured&amp;aid=' . $featured['article_id'];

		$templating->set('article_link', $article_link);
		$templating->set('url', url);

		if ($user->check_group([1,2,5]) == true)
		{
			$templating->set('edit_link', "<a href=\"".url."admin.php?module=articles&amp;view=Edit&amp;article_id={$featured['article_id']}\"><strong>Edit</strong></a>");
			$templating->set('editors_pick_link', " <a href=\"".url."index.php?module=home&amp;view=removeeditors&amp;article_id={$featured['article_id']}\"><strong>Remove Editors Pick</strong></a>");
		}

		else
		{
			$templating->set('edit_link', '');
			$templating->set('editors_pick_link', '');
		}
	}
}

$get_announcements = $db->sqlquery("SELECT count(id) as count FROM `announcements`");
$count_announcements = $get_announcements->fetch();
if ($count_announcements['count'] > 0)
{
	$templating->merge('announcements');
	$templating->block('announcement_top', 'announcements');
	
	$get_announcements = $db->sqlquery("SELECT `text`, `user_groups`, `type`, `modules` FROM `announcements` ORDER BY `id` DESC");
	while ($announcement = $get_announcements->fetch())
	{
		$show = 0;
		
		// one to show to everyone (generic announcement)
		if ((empty($announcement['user_groups']) || $announcement['user_groups'] == NULL) && (empty($announcement['modules']) || $announcement['modules'] == NULL))
		{
			$show = 1;
		}
		// otherwise, we need to do some checks
		else
		{
			$module_show = 0;
			$group_show = 0;
			
			// check if the currently loaded module is allow to show it
			if (!empty($announcement['modules'] && $announcement['modules'] != NULL))
			{
				$modules_array = unserialize($announcement['modules']);
				
				if (in_array(core::$current_module['module_id'], $modules_array))
				{
					$module_show = 1;
				}
			}
			else
			{
				$module_show = 1;
			}
			
			// check their user group against the setting
			if (!empty($announcement['user_groups'] && $announcement['user_groups'] != NULL))
			{
				$group_ids_array = unserialize($announcement['user_groups']);
				
				// if this is to only be shown to specific groups, is the user in that group?
				if ($announcement['type'] == 'in_groups' && $user->check_group($group_ids_array) == true)
				{
					$group_show = 1;				
				}
				
				// if it's to only be shown if they aren't in those groups
				if ($announcement['type'] == 'not_in_groups' && $user->check_group($group_ids_array) == false)
				{
					$group_show = 1;			
				}
			}
			else
			{
				$group_show = 1;	
			}
		}
		
		if ($show == 1 || ($module_show == 1 && $group_show == 1))
		{
			$templating->block('announcement', 'announcements');
			$templating->set('text', $bbcode->parse_bbcode($announcement['text']));
		}
	}

	$templating->block('announcement_bottom', 'announcements');
}

// let them know they aren't activated yet
if (isset($_GET['user_id']))
{
	if (!isset($_SESSION['activated']) && $_SESSION['user_id'] != 0)
	{
		$db->sqlquery("SELECT `activated` FROM `".$dbl->table_prefix."users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		$get_active = $db->fetch();
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
if (core::$current_module['module_file_name'] == 'home' || (core::$current_module['module_file_name'] == 'articles' && isset($_GET['view']) && ($_GET['view'] == 'cat' || $_GET['view'] == 'multiple')))
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

// get the blocks
$db->sqlquery('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`');
$blocks = $db->fetch_all_rows();

foreach ($blocks as $block)
{
	// PHP BLOCKS
	if ($block['block_link'] != NULL)
	{
		include($file_dir . "/blocks/{$block['block_link']}.php");
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
			$templating->merge('blocks/block_custom');

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

include($file_dir . '/includes/footer.php');
?>
