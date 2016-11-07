<?php
// Article categorys block
$templating->merge('blocks/block_livestreams');
$templating->block('list');

// count how many there is due this month and today
$count_query = "SELECT title FROM `livestreams` WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) AND DAY(date) > DAY(CURDATE()) AND HOUR(date) >= HOUR(CURDATE()) ORDER BY `date` ASC LIMIT 1";
$db->sqlquery($count_query);
if ($db->num_rows() == 1)
{
	$get_info = $db->fetch();
	$templating->set('title', $get_info['title']);
}
