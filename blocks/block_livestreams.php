<?php
// Article categorys block
$templating->load('blocks/block_livestreams');
$templating->block('list');

// count how many there is due this month and today
$count_query = "SELECT `row_id`, `title`, `date`, `community_stream`, `stream_url` FROM `livestreams` WHERE NOW() < `end_date` ORDER BY `date`";
$get_info = $dbl->run($count_query)->fetch();
$total = count($get_info);
if ($total > 1)
{
	$templating->set('title', '<li>Next: <a href="https://www.gamingonlinux.com/index.php?module=livestreams">There\'s more than one at the same time!</a></li>');
	$templating->set('date', '');
}
else if ($total == 1)
{
	$stream_url = 'https://www.twitch.tv/gamingonlinux';
	if ($get_info['community_stream'] == 1)
	{
		$stream_url = $get_info['stream_url'];
	}

	$templating->set('title', '<li><a href="'.$stream_url.'">' . $get_info['title'] . '</a></li>');

	if ($get_info['date'] <= date('Y-m-d H:i:s'))
	{
		$countdown = 'Happening now!';
	}
	else
	{
		$countdown = '<noscript>'.$get_info['date'].' UTC</noscript><span id="livestream'.$get_info['row_id'].'"></span><script type="text/javascript">var livestream' . $get_info['row_id'] . ' = moment.tz("'.$get_info['date'].'", "UTC"); $("#livestream'.$get_info['row_id'].'").countdown(livestream'.$get_info['row_id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
	}
	$templating->set('date', '<li>Date: ' . $countdown . '</li>');
}
else
{
	$templating->set('title', '<li>None currently</li>');
	$templating->set('date', '');
}
