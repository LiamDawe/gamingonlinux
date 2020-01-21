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

$get_all_cats = $dbl->run("SELECT `category_id`, `category_name`, `is_group`, `group_id` FROM `goty_category` ORDER BY `category_name` ASC")->fetch_all();

// sort them into top level groups and then voting categories
$groups = array();
$categories = array();

foreach ($get_all_cats as $sort_cat)
{
	if ($sort_cat['is_group'] == 1)
	{
		$groups[] = $sort_cat;
	}
	else
	{
		$categories[] = $sort_cat;
	}
}

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$current_votes = $dbl->run("select `category_id`, count(*) FROM `goty_votes` WHERE `user_id` = ? group by `category_id`", array($_SESSION['user_id']))->fetch_all(PDO::FETCH_KEY_PAIR);
}

foreach ($groups as $group)
{
	$templating->block('group', 'goty');
	$templating->set('group_name', $group['category_name']);
	
	$templating->block('goty_categories', 'goty');

	foreach ($categories as $cat)
	{
		if ($cat['group_id'] == $group['category_id'])
		{
			$templating->block('category_column', 'goty');
			$templating->set('category_id', $cat['category_id']);
			$templating->set('category_name', $cat['category_name']);

			$tick = '';
			if ($current_votes && array_key_exists($cat['category_id'], $current_votes))
			{
				$tick = '<br /><em>Your votes: ('.$current_votes[$cat['category_id']].'/'.$core->config('goty_votes_per_category').')</em>';
				if ($core->config('goty_votes_per_category') == $current_votes[$cat['category_id']])
				{
					$tick .= '&#10004;';
				}
			}
			$templating->set('tick', $tick);
		}
	}
	$templating->block('goty_categories_end','goty');
}
?>