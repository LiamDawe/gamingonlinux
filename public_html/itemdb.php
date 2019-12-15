<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

$game_sales = new game_sales($dbl, $templating, $user, $core);

$templating->set_previous('title', 'Linux Games Database', 1);
$templating->set_previous('meta_description', 'Linux Games Database', 1);

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
		$get_active = $dbl->run("SELECT `activated` FROM `".$dbl->table_prefix."users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
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

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message('sales_page', $_SESSION['message'], $extra);
}

$templating->load('itemdb');

$templating->block('itemdb_navigation', 'itemdb');

// count the total
$total_games = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `supports_linux` = 1 AND `approved` = 1 AND `is_emulator` = 0 AND `is_application` = 0 AND `bundle` = 0 AND `also_known_as` IS NULL")->fetchOne();
$templating->set('total', $total_games);

// LANDING PAGE TODO - NEED TO UPDATE ALL SEARCHES TO $_GET['view'] == 'mainlist'
if (!isset($_GET['view']))
{
	$featured = $dbl->run("SELECT i.`item_id`, i.`filename`, c.`name` FROM `itemdb_images` i JOIN `calendar` c ON c.id = i.item_id WHERE i.`featured` = 1 ORDER BY RAND() LIMIT 6")->fetch_all();

	$templating->block('featured', 'itemdb');

	$featured_output = '<ul style="text-align: center; padding: 0;">';
	foreach ($featured as $item)
	{
		$featured_output .= '<li style="display:inline;"><a href="/itemdb/'.$item['item_id'].'" title="'.$item['name'].'"><img src="/uploads/gamesdb/big/'.$item['item_id'].'/' . $item['filename'] . '" /></a></li>';
	}

	$featured_output .= '</ul>';

	$templating->set('featured', $featured_output);
}

if (isset($_GET['view']) && $_GET['view'] == 'mainlist')
{
	$templating->block('top', 'itemdb');
	
	$game_sales->display_all_games();

	$templating->block('filters', 'itemdb');

	$filters = [];
	foreach (range('A', 'Z') as $letter) 
	{
		$filters[] = '<option value="'.$letter.'">' . $letter . '</option>';
	}
	$templating->set('alpha_filters', implode(' ', $filters));

	// genre checkboxes
	$genres_res = $dbl->run("select count(*) as `total`, cat.category_name, cat.category_id FROM `calendar` c INNER JOIN `game_genres_reference` ref ON ref.game_id = c.id INNER JOIN `articles_categorys` cat ON cat.category_id = ref.genre_id where c.`is_application` = 0 AND c.`approved` = 1 AND c.`is_emulator` = 0 AND c.`bundle` = 0 AND c.`supports_linux` = 1 group by cat.category_name, cat.category_id")->fetch_all();
	$genres_output = '';
	$counter = 0;
	foreach ($genres_res as $genre)
	{
		$checked = '';
		if (isset($filters_sort['genres']) && in_array($genre['category_id'], $filters_sort['genres']))
		{
			$checked = 'checked';
		}
		$total = '';
		if ($genre['total'] > 0)
		{
			$total = ' <small>('.$genre['total'].')</small>';
		}
		$hidden = '';
		if ($counter > 4)
		{
			$hidden = 'class="hidden"';
		}
		$genres_output .= '<li '.$hidden.'><label><input type="checkbox" name="genres[]" value="'.$genre['category_id'].'" '.$checked.'> '.$genre['category_name'].$total.'</label></li>';
		$counter++;
	}
	if ($counter > 4)
	{
		$genres_output .= '<li><a class="show_all_filter_list" href="#">Show All</a></li>';
	}
	$templating->set('genres_output', $genres_output);

	$licenses = ['BSD', 'GPL', 'MIT', 'Closed Source'];
	$licenses_output = '';
	foreach ($licenses as $license)
	{
		$checked = '';
		if (isset($filters_sort['license']) && in_array($license['id'], $filters_sort['license']))
		{
			$checked = 'checked';
		}
		$licenses_output .= '<li><label><input type="checkbox" name="licenses[]" value="'.$license.'" '.$checked.'> '.$license.'</label></li>';	
	}
	$templating->set('licenses_output', $licenses_output);
}

include(APP_ROOT . '/includes/footer.php');