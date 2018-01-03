<?php
$templating->block('top', 'goty');
$templating->set('total_votes', $core->config('goty_total_votes'));

$voting_text = '';
if ($core->config('goty_voting_open') == 1 && $core->config('goty_finished') == 0)
{
	$voting_text = '<br /><br />Voting is now open!';
}

else if ($core->config('goty_voting_open') == 0 && $core->config('goty_games_open') == 1 && $core->config('goty_finished') == 0)
{
	$voting_text = '<br /><br />Voting opens once we have allowed enough time for people to add game nominations. To nominate a game, please go to the category.';
}

else if ($core->config('goty_finished') == 1)
{
	$voting_text = '<br /><br />Voting is currently closed!';
}
$templating->set('voting_text', $voting_text);

$templating->block('category_top', 'goty');

$cats = $dbl->run("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC")->fetch_all();
foreach ($cats as $cat)
{
	$templating->block('category_row', 'goty');
	$templating->set('category_id', $cat['category_id']);
	$templating->set('category_name', $cat['category_name']);

	$tick = '';
	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$check_voted = $dbl->run("SELECT `user_id` FROM `goty_votes` WHERE `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $cat['category_id']))->fetch();
		if ($check_voted)
		{
			$tick = '&#10004;';
		}
	}
	$templating->set('tick', $tick);
}

$templating->block('category_bottom', 'goty');
?>