<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
// Article categorys block
$templating->load('blocks/block_livestreams');
$templating->block('block');

// official gol streams
$count_query = "SELECT `row_id`, `title`, `date` FROM `livestreams` WHERE NOW() < `end_date` AND `community_stream` = 0 ORDER BY `date` ASC LIMIT 1";
$get_official = $dbl->run($count_query)->fetch();

if ($get_official)
{
	if ($get_official['date'] <= date('Y-m-d H:i:s'))
	{
		$countdown = 'Happening now!';
	}
	else
	{
		$countdown = '<noscript>'.$get_official['date'].' UTC</noscript><span id="livestream'.$get_official['row_id'].'"></span><script type="text/javascript">var livestream' . $get_official['row_id'] . ' = moment.tz("'.$get_official['date'].'", "UTC"); $("#livestream'.$get_official['row_id'].'").countdown(livestream'.$get_official['row_id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
	}

	$official = $templating->block_store('official_streams');
	$official = $templating->store_replace($official, array('title' => $get_official['title'], 'date' => $countdown));
	$templating->set('official', $official);
}
else
{
	$templating->set('official', '');
}

// community streams
$count_query = "SELECT `row_id`, `title`, `date`, `stream_url` FROM `livestreams` WHERE NOW() < `end_date` AND `community_stream` = 1 ORDER BY `date` ASC LIMIT 1";
$get_info = $dbl->run($count_query)->fetch();

if ($get_info)
{
	if ($get_info['date'] <= date('Y-m-d H:i:s'))
	{
		$countdown = 'Happening now!';
	}
	else
	{
		$countdown = '<noscript>'.$get_info['date'].' UTC</noscript><span id="livestream'.$get_info['row_id'].'"></span><script type="text/javascript">var livestream' . $get_info['row_id'] . ' = moment.tz("'.$get_info['date'].'", "UTC"); $("#livestream'.$get_info['row_id'].'").countdown(livestream'.$get_info['row_id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
	}

	$community = $templating->block_store('community_streams');
	$community = $templating->store_replace($community, array('title' => $get_info['title'], 'date' => $countdown, 'url' => $get_info['stream_url']));
	$templating->set('community', $community);
}
else
{
	$templating->set('community', '');
}

if (empty($get_official) && empty($get_info))
{
	$templating->set('none', '<div class="body group">None currently, <a href="/index.php?module=livestreams">submit yours here!</a></div>');
}
else
{
	$templating->set('none', '');
}