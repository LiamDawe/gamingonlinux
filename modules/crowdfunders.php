<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Crowdfunded Linux games', 1);
$templating->set_previous('meta_description', 'Crowdfunded Linux games', 1);

$total = $dbl->run("SELECT COUNT(*) FROM `crowdfunders` WHERE `failed_linux` IN (0,1) ORDER BY `name` ASC")->fetchOne();
$total_failed = $dbl->run("SELECT COUNT(*) FROM `crowdfunders` WHERE `failed_linux` = 1 ORDER BY `name` ASC")->fetchOne();

$templating->load('crowdfunders');
$templating->block('list_top');
$templating->set('total', $total);
$templating->set('total_failed', $total_failed);

$difference = $total - $total_failed;

$succeed_percentage = round($difference/$total*100);
$failed_percentage = round($total_failed/$total*100);

$templating->set('success_rate', $succeed_percentage . '%');
$templating->set('failed_percentage', $failed_percentage . '%');

$crowdfunders = $dbl->run("SELECT c.*,d.name AS dev_name FROM `crowdfunders` c LEFT JOIN `developers` d ON d.id = c.developer_id ORDER BY c.`name` ASC")->fetch_all();

foreach ($crowdfunders as $item)
{
	$templating->block('row');
	$templating->set('name', $item['name']);

	$dev_name = '';
	if (isset($item['dev_name']))
	{
		$dev_name = $item['dev_name'];
	}
	$templating->set('dev_name', $dev_name);

	$templating->set('link', $item['link']);

	$notes = '';
	if (!empty($item['notes']))
	{
		$notes = '<br /><em>'.$item['notes'].'</em>';
	}
	$templating->set('notes', $notes);

	$stretch_goal = 'No';
	if ($item['linux_stretch_goal'] == 1)
	{
		$stretch_goal = 'Yes';
	}
	$templating->set('stretch_goal', $stretch_goal);

	$failed = '';
	if ($item['failed_linux'] == 1)
	{
		$failed = 'Failed.';
	}
	else if ($item['failed_linux'] == 0)
	{
		$failed = 'Linux build released.';
	}
	else if ($item['failed_linux'] == 2)
	{
		$failed = 'In development.';
	}
	$templating->set('linux_status', $failed);
}

$templating->block('bottom');