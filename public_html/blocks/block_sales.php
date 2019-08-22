<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
// Article categorys block
$templating->load('blocks/block_sales');
$templating->block('block');

// bundles
$total_bundles = $dbl->run("SELECT COUNT(*) FROM `sales_bundles` WHERE `approved` = 1 AND `end_date` > NOW()")->fetchOne();

$bundles_text = '';
if ($total_bundles > 0)
{
	$bundles_text = '<li class="list-group-item"><a href="/sales/">Bundles: '.$total_bundles.'</a></li>';
}
$templating->set('bundles', $bundles_text);

// normal sales
$total_sales = $dbl->run("SELECT COUNT(*) FROM `sales` WHERE `accepted` = 1")->fetchOne();

$sales_text = '';
if ($total_sales > 0)
{
	$sales_text = '<li class="list-group-item"><a href="/sales/">Games: '.$total_sales.'</a></li>';
}
$templating->set('games', $sales_text);