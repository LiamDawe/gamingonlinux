<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

$game_sales = new game_sales($dbl, $templating, $user, $core);

$templating->set_previous('title', 'Free Linux games', 1);
$templating->set_previous('meta_description', 'Free Linux games', 1);

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

$templating->load('free_games');

$total_free = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `free_game` = 1 AND `approved` = 1 AND `is_application` = 0 AND `is_emulator` = 0 AND `is_dlc` = 0")->fetchOne();

$templating->block('top', 'free_games');
$templating->set('total', $total_free);

$featured = $dbl->run("SELECT i.`item_id`, i.`filename`, i.`location`, c.`name` FROM `itemdb_images` i JOIN `calendar` c ON c.id = i.item_id WHERE i.`featured` = 1 AND c.`free_game` = 1 ORDER BY RAND() LIMIT 2")->fetch_all();

$featured_output = '<ul class="free-featured-list">';
foreach ($featured as $item)
{
	$location = $core->config('website_url');
	if ($item['location'] != NULL)
	{
		$location = $item['location'];
	}
	$featured_output .= '<li style="display: flex; margin: 5px; justify-content: center;"><div class="featured-container"><a href="/itemdb/'.$item['item_id'].'" title="'.$item['name'].'"><img src="'.$location.'uploads/gamesdb/big/'.$item['item_id'].'/' . $item['filename'] . '" class="featured-image" />  <div class="featured-overlay">
    <div class="overlay-text">'.$item['name'].'<br />Click for more</div>
  </div></a></div></li>';
}

$featured_output .= '</ul>';

$templating->set('featured_items', $featured_output);

$game_sales->display_free();

$templating->block('filters', 'free_games');

// genre checkboxes
$genres_res = $dbl->run("select count(*) as `total`, cat.category_name, cat.category_id from `calendar` c INNER JOIN `game_genres_reference` ref ON ref.game_id = c.id INNER JOIN `articles_categorys` cat ON cat.category_id = ref.genre_id where c.`free_game` = 1 group by cat.category_name, cat.category_id")->fetch_all();
$genres_output = '';
$genres_show = '';
$hidden_genres = '<li class="hidden-details"><a id="hide1" href="#hide1" class="hide">+ More</a>
<a id="show1" href="#show1" class="show">- Less</a><div class="details"><ul>';
$genres_counter = 0;
foreach ($genres_res as $genre)
{
	$genres_counter++;
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
	if ($genres_counter > 4)
	{
		$hidden_genres .= '<li><label><input type="checkbox" name="genres[]" value="'.$genre['category_id'].'" '.$checked.'> '.$genre['category_name'].$total.'</label></li>';
	}
	else
	{
		$genres_show .= '<li><label><input type="checkbox" name="genres[]" value="'.$genre['category_id'].'" '.$checked.'> '.$genre['category_name'].$total.'</label></li>';
	}
}
$hidden_genres .= '</ul></div></li>';
$templating->set('genres_output', $genres_show . $hidden_genres);

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

include(APP_ROOT . '/includes/footer.php');