<?php
define("APP_ROOT", dirname(__FILE__));

include(APP_ROOT . '/includes/header.php');

$game_sales = new game_sales($dbl, $templating, $user, $core);

$templating->set_previous('title', 'Linux game sales - BETA', 1);
$templating->set_previous('meta_description', 'Linux games and bundles on sale', 1);

// TWITCH ONLINE INDICATOR
if (!isset($_COOKIE['gol_announce_gol_twitch'])) // if they haven't dissmissed it
{
	$templating->load('twitch_bar');
	$templating->block('main', 'twitch_bar');
}

$get_announcements = $dbl->run("SELECT `id`, `text`, `user_groups`, `type`, `modules`, `can_dismiss` FROM `announcements` ORDER BY `id` DESC")->fetch_all();
if ($get_announcements)
{
	$templating->load('announcements');
	$templating->block('announcement_top', 'announcements');
	foreach ($get_announcements as $announcement)
	{
		if (!isset($_COOKIE['gol_announce_'.$announcement['id']]))
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
				
				$dismiss = '';
				if ($announcement['can_dismiss'] == 1)
				{
					$dismiss = '<span class="fright"><a href="#" class="remove_announce" title="Hide Announcement" data-announce-id="'.$announcement['id'].'">&#10799;</a></span>';
				}
				$templating->set('dismiss', $dismiss);
			}
		}
	}

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

$free_games_url = '/index.php?module=free_games';
if ($core->config('pretty_urls') == 1)
{
	$free_games_url = '/free-games/';
}
$templating->set('free_games_url', $free_games_url);

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
		$templating->set('linux_total', $bundle['linux_total']);

		// end timer
		$countdown = '<noscript>'.$bundle['end_date'].' UTC</noscript><span id="bundle'.$bundle['id'].'"></span><script type="text/javascript">var bundle' . $bundle['id'] . ' = moment.tz("'.$bundle['end_date'].'", "UTC"); $("#bundle'.$bundle['id'].'").countdown(bundle'.$bundle['id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
		$templating->set('time_left', $countdown);
	}

	$templating->block('bundles_bottom', 'sales');
}

$templating->block('top_closing', 'sales');

$game_sales->display_normal();

$templating->block('filters', 'sales');

// stores
$stores_res = $dbl->run("SELECT `id`, `name` FROM `game_stores` WHERE `show_normal_filter` = 1 ORDER BY `name` ASC")->fetch_all();
$stores_output = '';
foreach ($stores_res as $store)
{
	$checked = '';
	if (isset($filters_sort['stores']) && in_array($store['id'], $filters_sort['stores']))
	{
		$checked = 'checked';
	}
	$stores_output .= '<label><input type="checkbox" name="stores[]" value="'.$store['id'].'" '.$checked.'> '.$store['name'].'</label>';
}
$templating->set('stores_options', $stores_output);

include(APP_ROOT . '/includes/footer.php');
?>
