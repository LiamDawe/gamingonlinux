<?php
define("APP_ROOT", dirname(__FILE__));

include(APP_ROOT . '/includes/header.php');

$templating->set_previous('title', 'Linux game sales', 1);
$templating->set_previous('meta_description', 'Linux games and bundles on sale', 1);

// TWITCH ONLINE INDICATOR
if (!isset($_COOKIE['gol_announce_gol_twitch'])) // if they haven't dissmissed it
{
	$templating->load('twitch_bar');
	$templating->block('main', 'twitch_bar');
}

$count_announcements = $dbl->run("SELECT count(id) as count FROM `announcements`")->fetchOne();
if ($count_announcements > 0)
{
	$templating->load('announcements');
	$templating->block('announcement_top', 'announcements');
	
	$get_announcements = $dbl->run("SELECT `id`, `text`, `user_groups`, `type`, `modules`, `can_dismiss` FROM `announcements` ORDER BY `id` DESC")->fetch_all();
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
}

// get normal sales
$templating->block('sales_top', 'sales');

$nodlc_checked = '';
$less5_selected = '';
$less10_selected = '';
if (isset($_GET['option']) && is_array($_GET['option']))
{
	$options_array = [];
	$options_link = [];
	foreach ($_GET['option'] as $option)
	{
		if ($option == '5less')
		{
			$options_array[] = ' s.`sale_dollars` <= 5 ';
			$less5_selected = 'selected';
			$options_link[] = 'option[]=5less';
		}
		if ($option == '10less')
		{
			$options_array[] = ' s.`sale_dollars` <= 10 ';
			$less10_selected = 'selected';
			$options_link[] = 'option[]=10less';
		}
		if ($option == 'nodlc')
		{
			$options_array[] = ' c.`is_dlc` = 0 ';
			$nodlc_checked = 'checked';
			$options_link[] = 'option[]=nodlc';
		}
	}
}
$templating->set('less5_selected', $less5_selected);
$templating->set('less10_selected', $less10_selected);
$templating->set('nodlc_checked', $nodlc_checked);

$where = '';
if (isset($_GET['q']))
{
	$options_sql = '';
	if (!empty($options_array))
	{
		$options_sql = implode(' AND ', $options_array);
	}

	$search_query = str_replace('+', ' ', $_GET['q']);
	$where = '%'.$search_query.'%';
	$sales_res = $dbl->run("SELECT c.id as game_id, c.name, c.is_dlc,s.`sale_dollars`, s.original_dollars, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id WHERE c.`name` LIKE ? $options_sql ORDER BY s.`sale_dollars` ASC", [$where])->fetch_all();
}
else
{
	$options_sql = '';
	if (!empty($options_array))
	{
		$options_sql = ' WHERE ' . implode(' AND ', $options_array);
	}
	$sales_res = $dbl->run("SELECT c.id as game_id, c.name, c.is_dlc, s.`sale_dollars`, s.original_dollars, g.name as store_name, s.link FROM `sales` s INNER JOIN calendar c ON c.id = s.game_id INNER JOIN game_stores g ON s.store_id = g.id $options_sql ORDER BY s.`sale_dollars` ASC")->fetch_all();
}

$sales_total = count($sales_res);
$templating->set('total', $sales_total);

$sales_merged = [];
foreach ($sales_res as $sale)
{
	$sales_merged[$sale['name']][] = ['game_id' => $sale['game_id'], 'store' => $sale['store_name'], 'sale_dollars' => $sale['sale_dollars'], 'original_dollars' => $sale['original_dollars'], 'link' => $sale['link'], 'is_dlc' => $sale['is_dlc']];
}

// paging for pagination
$page = isset($_GET['page'])?intval($_GET['page']-1):0;

$total_rows = count($sales_merged);

//foreach ($sales_merged as $name => $sales)
foreach (array_slice($sales_merged, $page*50, 50) as $name => $sales)
{
	$templating->block('sale_row', 'sales');
	$templating->set('name', $name);

	$stores_output = '';
	foreach ($sales as $store)
	{
		$edit = '';
		if ($user->check_group([1,2,5]))
		{
			$edit = '<a href="/admin.php?module=games&view=edit&id='.$store['game_id'].'"><span class="icon edit edit-sale-icon"></span></a> ';
		}
		$templating->set('edit', $edit);
		$savings_dollars = '';
		if ($store['original_dollars'] != 0)
		{
			$savings = 1 - ($store['sale_dollars'] / $store['original_dollars']);
			$savings_dollars = round($savings * 100) . '% off';
		}

		$dlc = '';
		if ($store['is_dlc'] == 1)
		{
			$dlc = '<span class="badge yellow">DLC</span>';
		}

		$stores_output .= ' <span class="badge"><a href="'.$store['link'].'">'.$store['store'].' - $'.$store['sale_dollars'] . ' | ' . $savings_dollars . '</a></span> ';
	}
	$templating->set('stores', $dlc . $stores_output);

	$templating->set('lowest_price', $sales[0]['sale_dollars']);
	$templating->set('name_sort', trim(strtolower($name)));
}

$templating->block('sales_bottom', 'sales');

$link_extra = '';
if (!empty($options_link) && is_array($options_link))
{
	$link_extra = '&' . implode('&', $options_link);
}

$pagination = $core->pagination_link(50, $total_rows, 'sales.php?', $page + 1, $link_extra);
$templating->set('pagination', $pagination);

include(APP_ROOT . '/includes/footer.php');
?>
