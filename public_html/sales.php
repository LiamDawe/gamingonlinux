<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);
include(APP_ROOT . '/includes/header.php');

$templating->set_previous('title', 'Linux game sales - BETA', 1);
$templating->set_previous('meta_description', 'Linux games and bundles on sale', 1);

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

// sales page content

$templating->load('sales');
$templating->block('top', 'sales');

// get bundles
$res_bundle = $dbl->run("SELECT b.`id`, b.`name`, s.`name` as `store_name`, b.`linux_total`, b.`link`, b.`end_date` FROM `sales_bundles` b LEFT JOIN `game_stores` s ON s.id = b.store_id WHERE b.`approved` = 1 ORDER BY b.`end_date` ASC")->fetch_all();

if ($res_bundle)
{
	$templating->block('bundles_top', 'sales');

	$manage = '';
	if ($user->check_group([1,2,5]))
	{
		$manage = '<span class="fright"><a href="/admin.php?module=sales&view=manage_bundles">Edit Bundles</a></span>';
	}
	$templating->set('manage', $manage);

	foreach ($res_bundle as $bundle)
	{
		$templating->block('bundles_row', 'sales');
		$templating->set('name', $bundle['name']);
		$templating->set('link', $bundle['link']);
		$templating->set('store_name', $bundle['store_name']);

		$total = '';
		if ($bundle['linux_total'] != NULL && $bundle['linux_total'] > 0)
		{
			$total = '| Linux total: ' . $bundle['linux_total'];
		}
		$templating->set('linux_total', $total);

		$machine_date = new DateTime($bundle['end_date']);

		// end timer
		$countdown = '<noscript>'.$bundle['end_date'].' UTC</noscript><span class="countdown" data-machine-time="'.$machine_date->format('c').'">'.$bundle['end_date'].'</span>';
		$templating->set('time_left', $countdown);
	}

	$templating->block('bundles_bottom', 'sales');
}

$templating->block('top_closing', 'sales');

$gamedb->display_normal();

$templating->block('filters', 'sales');

// stores
$stores_res = $dbl->run("SELECT COUNT(g.id) as `total`, c.`id`, c.`name` FROM `game_stores` c LEFT JOIN `sales` g ON c.id = g.store_id WHERE c.`show_normal_filter` = 1 GROUP BY c.id HAVING `total` > 0 ORDER BY c.`name` ASC")->fetch_all();
$stores_output = '';
if ($stores_res)
{
	foreach ($stores_res as $store)
	{
		$checked = '';
		if (isset($filters_sort['stores']) && in_array($store['id'], $filters_sort['stores']))
		{
			$checked = 'checked';
		}
		$stores_output .= '<li><label><input type="checkbox" name="stores[]" value="'.$store['id'].'" '.$checked.'> '.$store['name'].' <small>('.$store['total'].')</small></label></li>';
	}
}
else
{
	$stores_output = 'No sales found!';
}
$templating->set('stores_options', $stores_output);

include(APP_ROOT . '/includes/footer.php');
?>
