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
		$machine_date = new DateTime($get_official['date']);

		$countdown = '<noscript>'.$get_official['date'].' UTC</noscript><span class="countdown" data-machine-time="'.$machine_date->format('c').'">'.$get_official['date'].'</span>';
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
$count_total = $dbl->run("SELECT COUNT(*) FROM `livestreams` WHERE NOW() < `end_date` AND `community_stream` = 1 AND `accepted` = 1 ORDER BY `date` ASC")->fetchOne();

$more_text = '';
if ($count_total <= 3)
{
	$more_text = 'Add your own or view all';
}
else if ($count_total > 3)
{
	$more_text = 'See all, there\'s ' . $count_total . '!';
}

$templating->set('more_text', $more_text);

$livestream_query = "SELECT t1.`row_id`, t1.`title`, t1.`date`, t1.`stream_url` FROM livestreams t1 WHERE t1.date = (SELECT MIN(t2.date) FROM livestreams t2 WHERE t2.author_id = t1.author_id AND NOW() < `end_date` AND t2.`community_stream` = 1 ORDER BY `t2`.`date` ASC) ORDER BY `date` ASC LIMIT 3";
$get_info = $dbl->run($livestream_query)->fetch_all();

if ($get_info)
{
	$community = $templating->block_store('community_streams');

	$community_output = '';
	foreach ($get_info as $info)
	{
		if ($info['date'] <= date('Y-m-d H:i:s'))
		{
			$countdown = 'Happening now!';
		}
		else
		{
			$machine_date = new DateTime($info['date']);

			$countdown = '<noscript>'.$info['date'].' UTC</noscript><span class="countdown" data-machine-time="'.$machine_date->format('c').'">'.$info['date'].'</span>';
		}

		$community_output .= '<li><a href="'.$info['stream_url'].'" target="_blank">'.$info['title'].'</a><br />
		<small>'.$countdown.'</small>';
	}

	$community = $templating->store_replace($community, array('community_list' => $community_output));
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