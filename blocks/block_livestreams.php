<?php
// Article categorys block
$templating->merge('blocks/block_livestreams');
$templating->block('list');

// count how many there is due this month and today
$count_query = "SELECT `title`, `date` FROM `livestreams` ORDER BY `date` ASC LIMIT 1";
$db->sqlquery($count_query);
if ($db->num_rows() == 1)
{
	$get_info = $db->fetch();
	$templating->set('title', '<li><a href="https://www.twitch.tv/gamingonlinux">' . $get_info['title'] . '</a></li>');
	$templating->set('date', '<li>Date: ' . $get_info['date'] . ' UTC</li>');
}
else
{
	$templating->set('title', 'None currently');
	$templating->set('date', '');
}
