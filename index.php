<?php
error_reporting(E_ALL);

include('includes/header.php');

// Here we sort out what modules we are allowed to load
$modules_allowed = '';
$module_links = '';
$db->sqlquery('SELECT `module_file_name` FROM `modules` WHERE `activated` = 1');
while ($modules = $db->fetch())
{
	// modules allowed for loading
	$modules_allowed .= " {$modules['module_file_name']} ";
}

// modules loading, first are we asked to load a module, if not use the default
if (isset($_GET['module']))
{
	$module = $_GET['module'];
}

else
{
	$module = core::config('default_module');
}

if ($module == 'home')
{
	// pick a random editors pick
	$db->sqlquery("SELECT a.article_id, a.`title`, a.active, a.featured_image, a.author_id, a.comment_count, u.username, u.user_id FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE a.active = 1 AND a.show_in_menu = 1 AND a.featured_image <> '' ORDER BY RAND() LIMIT 1");
	$featured = $db->fetch();
	if ($db->num_rows() >= 1)
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

		if (core::config('pretty_urls') == 1)
		{
			$article_link = "/articles/" . $core->nice_title($featured['title']) . '.' . $featured['article_id'];
		}
		else
		{
			$article_link = url . 'index.php?module=articles_full&amp;aid=' . $featured['article_id'];
		}

		$templating->set('article_link', $article_link);
		$templating->set('url', url);

		if ($user->check_group(1,2) == true || $user->check_group(5))
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

if ($user->check_group(6,5) == false && $user->check_group(1,2) == false)
{
	$templating->block('patreon');
}

$templating->block('left');

// so mainpage.html knows to put "articles" class in the left block or not
if ($module == 'home' || ($module == 'articles' && isset($_GET['view']) && $_GET['view'] == 'cat'))
{
	$articles_css = 'articles';
}
else {
	$articles_css = '';
}
$templating->set('articles_css', $articles_css);

$modules_check = explode(" ", $modules_allowed);

if (in_array($module, $modules_check))
{
	include("modules/$module.php");
}

else
{
	$templating->set_previous('title', ' - Error', 1);
	$core->message('Not a valid module name or the module may not be active!');
}

$templating->block('left_end', 'mainpage');

// The block that starts off the html for the left blocks
$templating->block('right', 'mainpage');

// get the blocks
$db->sqlquery('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`');
$blocks = $db->fetch_all_rows();

// Latest sales box on the main page
$sales = '';
$sale_counter = 0;
$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`provider_id`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1 ORDER BY s.`id` DESC LIMIT 4");
while ($home_list = $db->fetch())
{
	$sale_counter++;
	$sale_name = $home_list['info'];
	if (strlen($sale_name) > 50)
	{
		$sale_name = substr($sale_name,0,50)."...";
	}

	// check to see if we need to put in the category name or not
	$provider = '';
	if ($home_list['provider_id'] != 0)
	{
		$provider = "<span class=\"label label-info\">{$home_list['name']}</span>";
	}

	if ($sale_counter != 4)
	{
		$sales .= "<a href=\"/sales/{$home_list['id']}\">{$provider} {$sale_name}</a>, ";
	}

	else
	{
		$sales .= "<a href=\"/sales/{$home_list['id']}\">{$provider} {$sale_name}</a> ";
	}
}

$templating->set('sale_list', $sales);

foreach ($blocks as $block)
{
	// PHP BLOCKS
	if ($block['block_link'] != NULL)
	{
		include("blocks/{$block['block_link']}.php");
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
			$templating->set('block_content', bbcode($block['block_custom_content']));
		}
	}
}


$templating->block('right_end', 'mainpage');

include('includes/footer.php');
?>
