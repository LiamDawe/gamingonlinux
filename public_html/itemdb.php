<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

$game_sales = new game_sales($dbl, $templating, $user, $core);

$templating->set_previous('title', 'Linux Games Database', 1);
$templating->set_previous('meta_description', 'Linux Games Database', 1);

if (isset($_GET['steamid']) && is_numeric($_GET['steamid']))
{
	$true_id = $dbl->run("SELECT `id` FROM `calendar` WHERE `steam_id` = ?", array($_GET['steamid']))->fetchOne();
	if ($true_id)
	{
		header("Location: /itemdb/".$true_id);
		die();
	}
	else
	{
		$_SESSION['message'] = 'none_found';
		$_SESSION['message_extra'] = 'GamingOnLinux database entries with that Steam ID';
		header("Location: /itemdb/");
		die();
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

$templating->load('itemdb');

$templating->block('itemdb_navigation', 'itemdb');
// count the total
$total_games = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `supports_linux` = 1 AND `approved` = 1 AND `is_application` = 0 AND `bundle` = 0 AND `also_known_as` IS NULL")->fetchOne();
$templating->set('total', number_format($total_games));

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_output = $message_map->display_message('itemdb', $_SESSION['message'], $extra, 'return_parsed');
	$templating->block('message', 'itemdb');
	$templating->set('message', $message_output);
}


// LANDING PAGE TODO - NEED TO UPDATE ALL SEARCHES TO $_GET['view'] == 'mainlist'
if (!isset($_GET['view']))
{
	$featured = $dbl->run("SELECT i.`item_id`, i.`filename`, i.`location`, c.`name` FROM `itemdb_images` i JOIN `calendar` c ON c.id = i.item_id WHERE i.`featured` = 1 ORDER BY RAND() LIMIT 3")->fetch_all();

	$templating->block('featured', 'itemdb');

	$featured_output = '<ul style="text-align: center; padding: 0;">';
	foreach ($featured as $item)
	{
		$location = $core->config('website_url');
		if ($item['location'] != NULL)
		{
			$location = $item['location'];
		}
		$featured_output .= '<li style="display:inline;"><a href="/itemdb/'.$item['item_id'].'" title="'.$item['name'].'"><img src="'.$location.'uploads/gamesdb/big/'.$item['item_id'].'/' . $item['filename'] . '" /></a></li>';
	}

	$featured_output .= '</ul>';

	$templating->set('featured', $featured_output);

	$popular = $dbl->run("SELECT `name`,`id`,`small_picture` FROM `calendar` ORDER BY `visits_today` DESC LIMIT 5")->fetch_all();
	if ($popular)
	{
		$popular_list = '';
		$templating->block('popular', 'itemdb');
		foreach ($popular as $item)
		{
			if ($item['small_picture'])
			{
				$small_pic = $core->config('website_url') . 'uploads/gamesdb/small/' . $item['small_picture'];
			}
			else
			{
				$small_pic = $core->config('website_url') . 'templates/default/images/gamesdb/default_smallpic.jpg';
			}
			$img = '<img src="'.$small_pic.'" alt="" />';
			$popular_list .= '<a class="itemdb-quick-list" href="/itemdb/'.$item['id'].'"><div class="flex-row">' . $img . '<div class="itemdb-quick-name">' . $item['name'] . '</div></div></a>';
		}
		$templating->set('popular_today', $popular_list);
	}

	$new = $dbl->run("SELECT `name`,`id`,`small_picture` FROM `calendar` WHERE `bundle` = 0 ORDER BY `id` DESC LIMIT 5")->fetch_all();
	if ($new)
	{
		$new_list = '';
		$templating->block('new', 'itemdb');
		foreach ($new as $item)
		{
			if ($item['small_picture'])
			{
				$small_pic = $core->config('website_url') . 'uploads/gamesdb/small/' . $item['small_picture'];
			}
			else
			{
				$small_pic = $core->config('website_url') . 'templates/default/images/gamesdb/default_smallpic.jpg';
			}
			$img = '<img src="'.$small_pic.'" alt="" />';
			$new_list .= '<a class="itemdb-quick-list" href="/itemdb/'.$item['id'].'"><div class="flex-row">' . $img . '<div class="itemdb-quick-name">' . $item['name'] . '</div></div></a>';
		}
		$templating->set('new_list', $new_list);
	}

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
	$genres_res = $dbl->run("select count(*) as `total`, cat.category_name, cat.category_id FROM `calendar` c INNER JOIN `game_genres_reference` ref ON ref.game_id = c.id INNER JOIN `articles_categorys` cat ON cat.category_id = ref.genre_id WHERE c.`is_application` = 0 AND c.`approved` = 1 AND c.`bundle` = 0 AND c.`supports_linux` = 1 group by cat.category_name, cat.category_id")->fetch_all();
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

	$licenses_res = $dbl->run("select count(*) as `total`, i.license_name, i.license_id FROM `item_licenses` i INNER JOIN `calendar` c ON c.license = i.license_name where c.`is_application` = 0 AND c.`approved` = 1 AND c.`bundle` = 0 AND c.`supports_linux` = 1 group by i.license_name, i.license_id ")->fetch_all();
	$licenses_output = '';
	$license_counter = 0;
	foreach ($licenses_res as $license)
	{
		$checked = '';
		if (isset($filters_sort['license']) && in_array($license['license_id'], $filters_sort['license']))
		{
			$checked = 'checked';
		}
		$total = '';
		if ($license['total'] > 0)
		{
			$total = ' <small>('.$license['total'].')</small>';
		}
		$hidden = '';
		if ($license_counter > 4)
		{
			$hidden = 'class="hidden"';
		}
		$licenses_output .= '<li '.$hidden.'><label><input type="checkbox" name="licenses[]" value="'.$license['license_name'].'" '.$checked.'> '.$license['license_name'].$total.'</label></li>';	
		$license_counter++;
	}
	if ($license_counter > 4)
	{
		$licenses_output .= '<li><a class="show_all_filter_list" href="#">Show All</a></li>';
	}
	$templating->set('licenses_output', $licenses_output);

	$engines_res = $dbl->run("select count(*) as `total`, e.engine_name, e.engine_id FROM `game_engines` e INNER JOIN `calendar` c ON c.game_engine_id = e.engine_id WHERE c.`is_application` = 0 AND c.`approved` = 1 AND c.`bundle` = 0 AND c.`supports_linux` = 1 group by e.`engine_name`, e.`engine_id`")->fetch_all();
	$engines_output = '';
	$engines_counter = 0;
	foreach ($engines_res as $engine)
	{
		$checked = '';
		if (isset($filters_sort['engine']) && in_array($engine['license_id'], $filters_sort['engine']))
		{
			$checked = 'checked';
		}
		$total = '';
		if ($engine['total'] > 0)
		{
			$total = ' <small>('.$engine['total'].')</small>';
		}
		$hidden = '';
		if ($engines_counter > 4)
		{
			$hidden = 'class="hidden"';
		}
		$engines_output .= '<li '.$hidden.'><label><input type="checkbox" name="engines[]" value="'.$engine['engine_id'].'" '.$checked.'> '.$engine['engine_name'].$total.'</label></li>';	
		$engines_counter++;
	}
	if ($engines_counter > 4)
	{
		$engines_output .= '<li><a class="show_all_filter_list" href="#">Show All</a></li>';
	}
	$templating->set('engines_output', $engines_output);
}

include(APP_ROOT . '/includes/footer.php');