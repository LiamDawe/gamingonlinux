<?php
$templating->set_previous('title', 'Crowdfunded Linux games', 1);
$templating->set_previous('meta_description', 'Crowdfunded Linux games', 1);

$total = $dbl->run("SELECT COUNT(*) FROM `kickstarters` ORDER BY `name` ASC")->fetchOne();
$total_failed = $dbl->run("SELECT COUNT(*) FROM `kickstarters` WHERE `failed_linux` = 1 ORDER BY `name` ASC")->fetchOne();

$templating->load('crowdfunders');
$templating->block('list_top');
$templating->set('total', $total);
$templating->set('total_failed', $total_failed);

$difference = $total - $total_failed;

$succeed_percentage = round($difference/$total*100);
$failed_percentage = round($total_failed/$total*100);

$templating->set('success_rate', $succeed_percentage . '%');
$templating->set('failed_percentage', $failed_percentage . '%');

$crowdfunders = $dbl->run("SELECT * FROM `kickstarters` ORDER BY `name` ASC")->fetch_all();

foreach ($crowdfunders as $item)
{
	$templating->block('row');
	$templating->set('name', $item['name']);
	$templating->set('link', $item['link']);
	$failed = '';
	if ($item['failed_linux'] == 1)
	{
		$failed = 'Failed.';
	}
	else if ($item['failed_linux'] == 0)
	{
		$failed = 'Linux build released.';
	}
	$templating->set('linux_status', $failed);
}

$templating->block('bottom');