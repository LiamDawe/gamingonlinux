<?php
$templating->merge('blocks/block_sales');
$templating->block('main');

// Latest sales box on the main page
$sales = '';
$sale_counter = 0;
$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`provider_id`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1 ORDER BY s.`id` DESC LIMIT 4");
while ($home_list = $db->fetch())
{
	$sale_name = $home_list['info'];
	if (strlen($sale_name) > 50)
	{
		$sale_name = substr($sale_name,0,50)."...";
	}
	
	// check to see if we need to put in the category name or not
	$provider = '';
	if ($home_list['provider_id'] != 0)
	{
		$provider = "<dt>{$home_list['name']}</dt>";
	}

	if ($config['pretty_urls'] == 1)
	{
		$sales .= $provider . "<dd><a href=\"/sales/{$home_list['id']}\">{$sale_name}</a></dd>";
	}
	else
	{
				$sales .= $provider . "<dd><a href=\"{$config['path']}sales.php?sale_id={$home_list['id']}\">{$sale_name}</a></dd>";
	}
}

$templating->set('sales_list', $sales);

if ($config['pretty_urls'] == 1)
{
	$sales_link = '/sales/';
}
else {
	$sales_link = $config['path'] . 'sales.php';
}
$templating->set('sales_link', $sales_link);
