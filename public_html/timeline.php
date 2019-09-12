<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);
include(APP_ROOT . '/includes/header.php');

$templating->set_previous('title', 'Linux Gaming Timeline', 1);
$templating->set_previous('meta_description', 'Linux Gaming Timeline', 1);

// TWITCH ONLINE INDICATOR
if (!isset($_COOKIE['gol_announce_gol_twitch'])) // if they haven't dissmissed it
{
	$templating->load('twitch_bar');
	$templating->block('main', 'twitch_bar');
}

$templating->load('timeline');
$templating->block('timeline_top');

$counter = 0;
$year_anchors = array();

$events_list = $dbl->run("SELECT `id`, `date`, `title`, `link`, `image` FROM `timeline_events` ORDER BY `date` DESC")->fetch_all();
$total = count($events_list);
foreach ($events_list as $event)
{
	$templating->block('row');

	$this_year = date('Y', strtotime($event['date']));
	$year_anchor_html = '';
	if (!in_array($this_year, $year_anchors))
	{
		$year_anchors[] = $this_year;
		$year_anchor_html = 'id="'.$this_year.'"';
	}
	$templating->set('year_anchor', $year_anchor_html);
	
	if ($counter % 2 == 0)
	{
		$position = 'left';
	}
	else
	{
		$position = 'right';
	}
	
	$templating->set('position', $position);

	$templating->set('date', date('F Y', strtotime($event['date'])));

	$title = $event['title'];
	if (isset($event['link']) && !empty($event['link']))
	{
		$title = '<a href="'.$event['link'].'">'.$event['title'].'</a>';
	}
	$templating->set('title', $title);
	$counter++;

	$start_anchor = '';
	if ($counter == $total)
	{
		$start_anchor = 'id="start"';
	}
	$templating->set('start_anchor', $start_anchor);
}

$templating->block('timeline_bottom');

include(APP_ROOT . '/includes/footer.php');
