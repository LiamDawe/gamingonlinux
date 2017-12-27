<?php
// Viewing a game directly
if (!core::is_number($_GET['game_id']))
{
	$_SESSION['message'] = 'no_id';
	$_SESSION['message_extra'] = 'game';
	header('Location: /goty.php');
	die();
}

if (!isset($_GET['category_id']) || isset($_GET['category_id']) && !core::is_number($_GET['category_id']))
{
	$_SESSION['message'] = 'no_id';
	$_SESSION['message_extra'] = 'category';
	header('Location: /goty.php');
	die();
}

$templating->load('/goty_modules/direct');

$game = $dbl->run("SELECT g.`id`, g.`game_id`, g.`votes`, g.`category_id`, c.`category_name`, c.`description`, n.`name` FROM `goty_games` g INNER JOIN `calendar` n ON g.game_id = n.id LEFT JOIN `goty_category` c ON g.category_id = c.category_id WHERE g.`accepted` = 1 AND g.`id` = ?", array($_GET['game_id']))->fetch();

$templating->block('direct_top', '/goty_modules/direct');
$templating->set('category_id', $game['category_id']);
$templating->set('category_name', $game['category_name']);
$templating->set('game_name', $game['name']);

if (!empty($game['description']))
{
	$templating->block('description', 'goty');
	$templating->set('category_description', $game['description']);
}

if ($core->config('goty_voting_open') == 0)
{
	$core->message('Voting is not currently open, so check back soon!', 2);
}

$templating->block('direct_row', '/goty_modules/direct');
$templating->set('category_id', $game['category_id']);
$templating->set('game_name', $game['name']);
$votes = '';

if ($core->config('goty_voting_open') == 0 && $core->config('goty_finished') == 1)
{
	$votes = 'Votes: ' . $game['votes'] . '<br />';
}

$templating->set('votes', $votes);
$templating->set('game_id', $game['id']);
$templating->set('url', $core->config('website_url'));

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$count_votes = $dbl->run("SELECT COUNT(`user_id`) FROM `goty_votes` WHERE `user_id` = ? AND `category_id` = ?", array($_SESSION['user_id'], $_GET['category_id']))->fetchOne();
	if ($count_votes == 0 && $core->config('goty_voting_open') == 1)
	{
		$templating->set('vote_button', '<button name="votebutton" class="votebutton" data-category-id="'.$_GET['category_id'].'" data-game-id="'.$game['id'].'">Vote</button>');
	}
	else if ($core->config('goty_voting_open') == 1 && $count_votes == 1)
	{
		$templating->set('vote_button', '<form method="post"><button formaction="/goty.php" name="act" class="remove_vote" value="reset_category_vote">Remove Vote</button><input type="hidden" name="category_id" value="'.$_GET['category_id'].'" /><input type="hidden" name="game_id" value="'.$game['id'].'" /></form>');
	}
	else
	{
		$templating->set('vote_button', '');
	}
}
else
{
	$templating->set('vote_button', '');
}

// work out the games total %
$leaderboard = '';
if ($core->config('goty_voting_open') == 0 && $core->config('goty_finished') == 1)
{
	$total = $dbl->run("SELECT `votes` FROM `goty_games` WHERE `category_id` = ?", array($_GET['category_id']))->fetch_all();

	$total_votes = 0;
	foreach ($total as $votes)
	{
		$total_votes = $total_votes + $votes['votes'];
	}

	$total_perc = round($game['votes'] / $total_votes * 100);

	$leaderboard = 'Leaderboard: <div style="background:#CCCCCC; border:1px solid #666666;"><div style="padding-left: 5px; background: #28B8C0; width:'.$total_perc.'%;">'.$total_perc.'%</div></div>';
}
$templating->set('leaderboard', $leaderboard);
?>