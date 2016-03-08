<?php
// Article categorys block
$templating->merge('blocks/block_calendar');
$templating->block('list');

// count how many there is due this month
$db->sqlquery("SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) AND DAY(date) > DAY(CURDATE()) AND `approved` = 1");
$counter = $db->fetch();

if ($counter['count'] == 0)
{
	$counter_output = '';
}
else if ($counter['count'] > 0)
{
	$counter_output = '<li>'.$counter['count'].' games due this month</li>';
}
$templating->set('counter', $counter_output);

// count how many there is due today
$db->sqlquery("SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) AND DAY(date) = DAY(CURDATE()) AND `approved` = 1");
$counter_today = $db->fetch();

if ($counter_today['count'] == 0)
{
	$counter_today = '';
}
else if ($counter_today['count'] == 1)
{
	$counter_today = '<li>1 game due today</li>';
}
else if ($counter_today['count'] > 1)
{
	$counter_today = '<li>'.$counter_today['count'].' games due today</li>';
}
$templating->set('counter_today', $counter_today);
