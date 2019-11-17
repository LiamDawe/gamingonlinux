<?php
$templating->block('top', 'goty');
$templating->set('votes_per_category', $core->config('goty_votes_per_category'));

if ($core->config('goty_votes_per_category') == 1)
{
	$vote_times_text = 'vote';
}
else if ($core->config('goty_votes_per_category') > 1)
{
	$vote_times_text = 'votes';
}
$templating->set('vote_times_text', $vote_times_text);

$templating->set('total_votes', $core->config('goty_total_votes'));

$voting_text = '';
$category_status_text = '';
if ($core->config('goty_voting_open') == 1 && $core->config('goty_finished') == 0)
{
	$voting_text = '<br /><br />Voting is now open!';
	$category_status_text = ' and vote now!';
}
else if ($core->config('goty_voting_open') == 0 && $core->config('goty_games_open') == 1 && $core->config('goty_finished') == 0)
{
	$voting_text = '<br /><br />Voting opens once we have allowed enough time for people to add game nominations. To nominate a game, please go to the category.';
	$category_status_text = ' and nominate a game for when voting opens!';
}
else if ($core->config('goty_finished') == 1)
{
	$voting_text = '<br /><br />Voting is currently closed!';
	$category_status_text = ' and view the results!';
}
else if ($core->config('goty_finished') == 0 && $core->config('goty_voting_open') == 0)
{

}
$templating->set('voting_text', $voting_text);

$templating->block('category_top', 'goty');
$templating->set('category_status_text', $category_status_text);

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