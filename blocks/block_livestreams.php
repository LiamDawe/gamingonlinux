<?php
// Article categorys block
$templating->merge('blocks/block_livestreams');
$templating->block('list');

// count how many there is due this month and today
$count_query = "SELECT `row_id`, `title`, `date` FROM `livestreams` WHERE `end_date` < CURDATE() ORDER BY `date` ASC LIMIT 1";
$db->sqlquery($count_query);
if ($db->num_rows() == 1)
{
	$get_info = $db->fetch();
	$templating->set('title', '<li><a href="https://www.twitch.tv/gamingonlinux">' . $get_info['title'] . '</a></li>');

	$countdown = '<noscript>'.$get_info['date'].' UTC</noscript><span id="livestream'.$get_info['row_id'].'"></span><script type="text/javascript">var livestream' . $get_info['row_id'] . ' = moment.tz("'.$get_info['date'].'", "UTC"); $("#livestream'.$get_info['row_id'].'").countdown(livestream'.$get_info['row_id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
	$templating->set('date', '<li>Date: ' . $countdown . '</li>');
}
else
{
	$templating->set('title', 'None currently');
	$templating->set('date', '');
}
