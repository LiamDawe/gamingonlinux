<?php
// Article categorys block
$templating->load('blocks/block_games');
$templating->block('list');

$templating->set('url', $core->config('website_url'));

// count how many there is due this month and today
$count_query = "SELECT (select COUNT(id) as count FROM `calendar` WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) AND DAY(date) > DAY(CURDATE()) AND `approved` = 1) as this_month,
(SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) AND DAY(date) = DAY(CURDATE()) AND `approved` = 1) as today";
$db->sqlquery($count_query);
$counter = $db->fetch();

// show due this month
if ($counter['this_month'] == 0)
{
	$counter_output = '';
}
else if ($counter['this_month'] > 0)
{
	$counter_output = '<li>'.$counter['this_month'].' games due this month</li>';
}
$templating->set('counter', $counter_output);

// show due today

if ($counter['today'] == 0)
{
	$counter_today = '';
}
else if ($counter['today'] == 1)
{
	$counter_today = '<li>1 game due today</li>';
}
else if ($counter['today'] > 1)
{
	$counter_today = '<li>'.$counter['today'].' games due today</li>';
}
$templating->set('counter_today', $counter_today);
