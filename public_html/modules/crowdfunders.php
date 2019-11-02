<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Crowdfunded Linux games', 1);
$templating->set_previous('meta_description', 'Crowdfunded Linux games', 1);

$total_projects = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `is_crowdfunded` = 1 ORDER BY `name` ASC")->fetchOne();
$in_development = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `in_development` = 1 AND `is_crowdfunded` = 1 ORDER BY `name` ASC")->fetchOne();
$total = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `failed_linux` IN (0,1,2) AND `in_development` = 0 AND `is_crowdfunded` = 1 ORDER BY `name` ASC")->fetchOne();
$total_failed = $dbl->run("SELECT COUNT(*) FROM `calendar` WHERE `failed_linux` IN (1,2) AND `is_crowdfunded` = 1 ORDER BY `name` ASC")->fetchOne();

$templating->load('crowdfunders');
$templating->block('list_top');
$templating->set('total_projects', $total_projects);
$templating->set('in_development', $in_development);
$templating->set('total', $total);
$templating->set('total_failed', $total_failed);

$difference = $total - $total_failed;

$succeed_percentage = round($difference/$total*100);
$failed_percentage = round($total_failed/$total*100);

$templating->set('success_rate', $succeed_percentage . '%');
$templating->set('failed_percentage', $failed_percentage . '%');

$crowdfunders = $dbl->run("SELECT * FROM `calendar` WHERE `is_crowdfunded` = 1 ORDER BY `name` ASC")->fetch_all();

$game_id_array = array();
foreach ($crowdfunders as $game)
{
	$game_id_array[] = $game['id'];
}
$in  = str_repeat('?,', count($game_id_array) - 1) . '?';
$developers_list = $dbl->run("SELECT r.game_id,r.developer_id,d.name FROM `game_developer_reference` r LEFT JOIN `developers` d ON r.developer_id = d.id WHERE r.game_id IN ($in)", $game_id_array)->fetch_all(PDO::FETCH_GROUP);

foreach ($crowdfunders as $item)
{
	$templating->block('row');
	$templating->set('name', $item['name']);

	$edit = '';
	if ($user->check_group([1,2,5]))
	{
		$edit = '<a href="/admin.php?module=games&view=edit&id='.$item['id'].'"><span class="icon edit edit-sale-icon"></span></a> ';
	}
	$templating->set('edit', $edit);

	$dev_names = '';
	if (isset($developers_list[$item['id']]) && !empty($developers_list[$item['id']]))
	{
		$dev_names = array();
		foreach ($developers_list[$item['id']] as $developer)
		{
			$dev_names[] = '<a href="/index.php?module=items_database&amp;view=developer&amp;id='.$developer['developer_id'].'">'.$developer['name'] . '</a>';
		}

		$dev_names = implode(', ', $dev_names);
	}
	$templating->set('dev_name', $dev_names);

	$templating->set('link', $item['crowdfund_link']);

	$notes = '';
	if (!empty($item['crowdfund_notes']))
	{
		$notes = '<br /><em><small>'.$item['crowdfund_notes'].'</small></em>';
	}
	$templating->set('notes', $notes);

	$stretch_goal = 'No';
	if ($item['linux_stretch_goal'] == 1)
	{
		$stretch_goal = 'Yes';
	}
	$templating->set('stretch_goal', $stretch_goal);

	$status_text = '';
	if ($item['failed_linux'] == 1)
	{
		$status_text = 'Failed.';
	}
	else if ($item['failed_linux'] == 2)
	{
		$status_text = 'Failed.<br />
		<em>May come later.</em>';
	}
	else if ($item['failed_linux'] == 0)
	{
		$status_text = 'Linux build released.';
	}
	if ($item['in_development'] == 1)
	{
		$status_text = 'In development.';
	}
	$templating->set('linux_status', $status_text);
}

$templating->block('bottom');