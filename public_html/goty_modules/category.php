<?php
if (!core::is_number($_GET['category_id']))
{
	$_SESSION['message'] = 'no_id';
	$_SESSION['message_extra'] = 'category';
	header('Location: /goty.php');
	die();
}
$cat = $dbl->run("SELECT `category_name`, `description` FROM `goty_category` WHERE `category_id` = ?", array($_GET['category_id']))->fetch();

$templating->block('category_bread', 'goty');
$templating->set('category_name', $cat['category_name']);

if (!empty($cat['description']))
{
	$templating->block('description', 'goty');
	$templating->set('category_description', $cat['description']);
}

if ($core->config('goty_games_open') == 1)
{
	$templating->block('add', 'goty');
	$picker = 'games';
	if ($_GET['category_id'] == 3)
	{
		$picker = 'devs';
	}
	$templating->set('picker', $picker);
	$templating->set('category', $_GET['category_id']);
}

$item_table = '';
if ($_GET['category_id'] == 3)
{
	$item_table = 'developers';
}
else
{
	$item_table = 'calendar';
}

// if finished, show top 3 games from this category
if ($core->config('goty_finished') == 1)
{
	$templating->block('top_games', 'goty');
	$templating->set('category_id', $_GET['category_id']);
	$templating->set('category_name', $cat['category_name']);

	$games_top = $dbl->run("SELECT coalesce(cl.name, d.name) name, g.`votes` as data, g.`id` FROM `goty_games` g left outer join `calendar` cl ON cl.id = g.game_id and g.category_id != 16 left outer join `developers` d ON d.id = g.game_id and g.category_id = 16 WHERE g.`accepted` = 1 AND g.`category_id` = ? ORDER BY g.`votes` DESC LIMIT 3", array($_GET['category_id']))->fetch_all();

	// work out the games total %
	$category_total_votes = 0;
	$total_res = $dbl->run("SELECT `votes` FROM `goty_games` WHERE `category_id` = ?", array($_GET['category_id']))->fetch_all();
	foreach ($total_res as $total)
	{
		$category_total_votes = $category_total_votes + $total['votes'];
	}

	foreach ($games_top as $game)
	{
		$templating->block('top_row', 'goty');
		$templating->set('category_id', $_GET['category_id']);
		$templating->set('game_name', $game['name']);
		$templating->set('game_counter', $game['data']);
		$templating->set('game_id', $game['id']);
		$templating->set('url', $core->config('website_url'));
		$templating->set('vote_button', '');

		$total_perc = round($game['data'] / $category_total_votes * 100);

		$leaderboard = 'Leaderboard: <div style="background:#CCCCCC; border:1px solid #666666;"><div style="padding-left: 5px; background: #28B8C0; width:'.$total_perc.'%;">'.$total_perc.'%</div></div>';

		$templating->set('leaderboard', $leaderboard);
	}
	$templating->block('top_end', 'goty');
}

// games list
$templating->block('filters_list', 'goty');
$filters = [];
foreach (range('A', 'Z') as $letter) 
{
    $filters[] = '<a href="/goty.php?module=category&amp;filter='.$letter.'&amp;category_id='.$_GET['category_id'].'">' . $letter . '</a>';
}
$templating->set('alpha_filters', implode(' ', $filters));

$grab_votes = NULL;
$reset_button = '';
if ($core->config('goty_voting_open') == 1 && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$current_votes = 0;
	$grab_votes = $dbl->run("SELECT `game_id` FROM `goty_votes` WHERE `category_id` = ? AND `user_id` = ?", array($_GET['category_id'], $_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN);
	if ($grab_votes)
	{
		$reset_button = '<form method="post"><button formaction="/goty.php" name="act" class="remove_vote" value="reset_category_vote">Reset vote in current category</button><input type="hidden" name="category_id" value="'.$_GET['category_id'].'" /></form>';
		$current_votes = count($grab_votes);
	}	
}

$templating->set('reset_button', $reset_button);

$templating->set('category_id', $_GET['category_id']);

// paging for pagination
if (!isset($_GET['page']) || $_GET['page'] <= 0)
{
	$page = 1;
}

else if (is_numeric($_GET['page']))
{
	$page = $_GET['page'];
}

// get the list
$filter_sql = '';
if (isset($_GET['filter']))
{
	$filter_sql = '&filter=' . $_GET['filter'];
}

if (isset($_GET['filter']))
{
	if ($_GET['filter'] != 'misc')
	{
		if (strlen($_GET['filter']) != 1)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'filter';
			header("Location: /goty.php?module=category&category_id=".$_GET['category_id']);
			die();
		}
		$games_get = $dbl->run("SELECT g.`id`, g.`game_id`, g.`votes`, c.`name` FROM `goty_games` g INNER JOIN `$item_table` c ON g.game_id = c.id WHERE g.`accepted` = 1 AND g.`category_id` = ? AND c.`name` LIKE ? ORDER BY c.`name` ASC", array($_GET['category_id'], $_GET['filter'] . '%'))->fetch_all();
	}

	else
	{
		$games_get = $dbl->run("SELECT g.`id`, g.`game_id`, g.`votes`, c.`name` FROM `goty_games` g INNER JOIN `$item_table` c ON g.game_id = c.id WHERE g.`accepted` = 1 AND g.`category_id` = ? AND c.`name` <= '@' OR c.`name` >= '{' ORDER BY c.`name` ASC", array($_GET['category_id']))->fetch_all();
	}
}

else
{
	$games_get = $dbl->run("SELECT g.`id`, c.`name`, g.`votes` FROM `goty_games` g INNER JOIN `$item_table` c ON g.game_id = c.id WHERE g.`accepted` = 1 AND g.`category_id` = ? ORDER BY c.`name` ASC", array($_GET['category_id']))->fetch_all();
}
if ($games_get)
{
	foreach ($games_get as $game)
	{
		$templating->block('game_row', 'goty');
		$templating->set('category_id', $_GET['category_id']);
		$templating->set('game_name', $game['name']);

		$votes = '';
		// show leaderboard if voting is open, or voting is closed because it's finished
		if ($core->config('goty_voting_open') == 0 && $core->config('goty_finished') == 1)
		{
			$votes = 'Votes: ' . $game['votes'] . '<br />';
		}

		$templating->set('votes', $votes);
		$templating->set('game_id', $game['id']);
		$templating->set('url', $core->config('website_url'));

		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && $core->config('goty_voting_open') == 1)
		{
			if (($grab_votes && $current_votes < $core->config('goty_votes_per_category') && !in_array($game['id'], $grab_votes)) || !$grab_votes)
			{
				$templating->set('vote_button', '<button name="votebutton" class="votebutton" data-category-id="'.$_GET['category_id'].'" data-game-id="'.$game['id'].'">Vote</button>');
			}
			else if ($grab_votes && in_array($game['id'], $grab_votes))
			{
				$templating->set('vote_button', '<form method="post"><button formaction="/goty.php" name="act" class="remove_vote" value="remove_single_vote">Remove Vote</button><input type="hidden" name="category_id" value="'.$_GET['category_id'].'" /><input type="hidden" name="game_id" value="'.$game['id'].'" /></form>');
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

		$leaderboard = '';
		// show top three if goty is over
		if ($core->config('goty_voting_open') == 0 && $core->config('goty_finished') == 1)
		{
			// work out the games total %
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
	}
}
else
{
	$core->message('There are no games in that selected search option!');
}

$templating->block('games_bottom', 'goty');
?>