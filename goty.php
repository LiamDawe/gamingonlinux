<?php
include('includes/header.php');

if (core::config('goty_page_open') == 0)
{
	if ($user->check_group(1,2) == false && $user->check_group(5) == false)
	{
		header("Location: /index.php");
		die();
	}
	else
	{
		$core->message('The GOTY page is currently turned off, this is only accessible by editors!', NULL, 1);
	}
}

$templating->set_previous('title', 'Linux Game Of The Year Awards', 1);
$templating->set_previous('meta_description', 'Vote for your favourite Linux game of the past year', 1);

$templating->merge('goty');

$templating->block('vote_popover', 'goty');

$templating->block('main', 'goty');

if (isset($_GET['category_id']) && !isset($_GET['view']) && !isset($_GET['direct']))
{
	$db->sqlquery("SELECT `category_name`, `description` FROM `goty_category` WHERE `category_id` = ?", array($_GET['category_id']));
	$cat = $db->fetch();

	$templating->block('category_bread', 'goty');
	$templating->set('category_name', $cat['category_name']);

	if (!empty($cat['description']))
	{
		$templating->block('description', 'goty');
		$templating->set('category_description', $cat['description']);
	}
}

if (isset($_GET['category_id']) && isset($_GET['view']) && $_GET['view'] == 'top10')
{
	if (core::config('goty_finished') == 1)
	{
		$db->sqlquery("SELECT `category_name` FROM `goty_category` WHERE `category_id` = ?", array($_GET['category_id']));
		$cat = $db->fetch();

		$templating->block('top10_bread', 'goty');
		$templating->set('category_name', $cat['category_name']);
		$templating->set('category_id', $_GET['category_id']);
	}
	else
	{
		$core->message('Voting is currently open! You can only see the top 10 when it is finished to help prevent a voting bias.');
		include('includes/footer.php');
		die();
	}
}

if (!isset($_POST['act']))
{
	if (isset($_GET['view']) && $_GET['view'] == 'top10')
	{
		if (core::config('goty_voting_open') == 0 && core::config('goty_finished') == 1)
		{
			$templating->block('top10', 'goty');
			$templating->set('category_name', $cat['category_name']);

			require_once('./includes/SVGGraph/SVGGraph.php');
			$labels = array();

			$settings = array('graph_title' => $cat['category_name'], 'auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside', 'show_grid_h' => false, 'label_h' => 'Total Votes');
			$graph = new SVGGraph(400, 300, $settings);
			$colours = array(array('rgb(151,187,205):0.90','rgb(113,140,153):'), array('rgb(152,125,113):0.90','rgb(114,93,84)'));
			$graph->colours = $colours;

			$db->sqlquery("SELECT `id`, `game`, `votes` FROM `goty_games` WHERE `accepted` = 1  AND `category_id` = ? ORDER BY `votes` DESC LIMIT 10", array($_GET['category_id']));
			$games_top = $db->fetch_all_rows();

			foreach ($games_top as $label_loop)
			{
				$labels[$label_loop['game']] = $label_loop['votes'];
			}

			$graph->Values($labels);
			$get_graph = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';

			$templating->block('topchart', 'goty');
			$templating->set('chart', $get_graph);
		}
		$templating->block('games_bottom', 'goty');
	}

	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'added')
		{
			$core->message('You have added that game to the list! It will be reviewed by an editor first before it appears, so sit tight!');
		}
		if ($_GET['message'] == 'added_editor')
		{
			$core->message('You have added that game to the list! It has been auto accepted as you\'re an Editor!');
		}
		if ($_GET['message'] == 'exists')
		{
			$core->message('That game already exists in our GOTY list!');
		}

		if ($_GET['message'] == 'empty')
		{
			$core->message('Your can\'t add a game with no name!');
		}
	}

	/*
	Viewing a game directly (by itself)
	*/
	if (isset($_GET['direct']) && isset($_GET['game_id']))
	{
		$db->sqlquery("SELECT g.`id`, g.`game`, g.`votes`, g.`category_id`, c.`category_name`, c.`description` FROM `goty_games` g LEFT JOIN `goty_category` c ON g.category_id = c.category_id WHERE g.`accepted` = 1 AND g.`id` = ?", array($_GET['game_id']));
		$game = $db->fetch();

		$templating->block('direct_crumb', 'goty');
		$templating->set('category_id', $game['category_id']);
		$templating->set('category_name', $game['category_name']);
		$templating->set('game_name', $game['game']);

		if (!empty($game['category_description']))
		{
			$templating->block('description', 'goty');
			$templating->set('category_description', $game['category_description']);
		}

		$templating->block('direct', 'goty');
		$templating->set('game_name', $game['game']);
		$templating->set('category_name', $game['category_name']);

		if (core::config('goty_voting_open') == 0)
		{
			$templating->block('direct_closed', 'goty');
		}

		$templating->block('direct_row', 'goty');
		$templating->set('category_id', $game['category_id']);
		$templating->set('game_name', $game['game']);
		$votes = '';

		// show leaderboard if voting is open, or voting is closed because it's finished
		if (core::config('goty_voting_open') == 0 && core::config('goty_finished') == 1)
		{
			$votes = 'Votes: ' . $game['votes'] . '<br />';
		}

		$templating->set('votes', $votes);
		$templating->set('game_id', $game['id']);
		$templating->set('url', core::config('website_url'));

		$db->sqlquery("SELECT `ip` FROM `goty_votes` WHERE `ip` = ? AND `category_id` = ?", array(core::$ip, $_GET['category_id']));
		if ($db->num_rows() == 0 && core::config('goty_voting_open') == 1)
		{
			$templating->set('vote_button', '<button name="votebutton" class="votebutton" data-category-id="'.$_GET['category_id'].'" data-game-id="'.$game['id'].'">Vote</button>');
		}
		else
		{
			$templating->set('vote_button', '');
		}

		// work out the games total %
		$db->sqlquery("SELECT `votes` FROM `goty_games` WHERE `category_id` = ?", array($_GET['category_id']));
		$total = $db->fetch_all_rows();

		$leaderboard = '';
		if (core::config('goty_voting_open') == 0 && core::config('goty_finished') == 1)
		{
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

	else if (!isset($_GET['view']))
	{
		// paging for pagination
		if (!isset($_GET['page']) || $_GET['page'] <= 0)
		{
			$page = 1;
		}

		else if (is_numeric($_GET['page']))
		{
			$page = $_GET['page'];
		}

		if (!isset($_GET['category_id']))
		{
			$templating->block('top', 'goty');
			$templating->set('total_votes', core::config('goty_total_votes'));

			$voting_text = '';
			if (core::config('goty_voting_open') == 1 && core::config('goty_finished') == 0)
			{
				$voting_text = '<br /><br />Voting is now open!';
			}

			else if (core::config('goty_voting_open') == 0 && core::config('goty_games_open') == 1 && core::config('goty_finished') == 0)
			{
				$voting_text = '<br /><br />Voting opens once we have allowed enough time for people to add game nominations.';
			}

			else if (core::config('goty_finished') == 1)
			{
				$voting_text = '<br /><br />Voting is now over!';
			}
			$templating->set('voting_text', $voting_text);

			if (core::config('goty_games_open') == 1)
			{
				$category_list = '';
				$cats = $db->sqlquery("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC");
				foreach( $cats as $category )
				{
					$category_list .= '<option value="' . $category['category_id'] . '">' . $category['category_name'] . '</option>';
				}
				$templating->block('add', 'goty');
				$templating->set('options', $category_list);
			}

			$templating->block('category_top', 'goty');

			$db->sqlquery("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC");
			$cats = $db->fetch_all_rows();

			foreach ($cats as $cat)
			{
				$templating->block('category_row', 'goty');
				$templating->set('category_id', $cat['category_id']);
				$templating->set('category_name', $cat['category_name']);

				$tick = '';
				$db->sqlquery("SELECT `ip` FROM `goty_votes` WHERE `ip` = ? AND `category_id` = ?", array(core::$ip, $cat['category_id']));
				if ($db->num_rows() == 1)
				{
					$tick = '&#10004;';
				}
				$templating->set('tick', $tick);
			}

			$templating->block('category_bottom', 'goty');
		}

		else
		{

			if (core::config('goty_finished') == 1)
				{
					$templating->block('top_games', 'goty');
					$templating->set('category_id', $_GET['category_id']);
					$templating->set('category_name', $cat['category_name']);

					$db->sqlquery("SELECT `id`, `game`, `votes` FROM `goty_games` WHERE `accepted` = 1  AND `category_id` = ? ORDER BY `votes` DESC LIMIT 3", array($_GET['category_id']));
					$games_top = $db->fetch_all_rows();

					foreach ($games_top as $game)
					{
						$templating->block('top_row', 'goty');
						$templating->set('category_id', $_GET['category_id']);
						$templating->set('game_name', $game['game']);
						$templating->set('game_counter', $game['votes']);
						$templating->set('game_id', $game['id']);
						$templating->set('url', core::config('website_url'));
						$templating->set('vote_button', '');

						// work out the games total %
						$db->sqlquery("SELECT `votes` FROM `goty_games` WHERE `category_id` = ?", array($_GET['category_id']));
						$total = $db->fetch_all_rows();

						$total_votes = 0;
						foreach ($total as $votes)
						{
							$total_votes = $total_votes + $votes['votes'];
						}
						$total_perc = round($game['votes'] / $total_votes * 100);

						$leaderboard = 'Leaderboard: <div style="background:#CCCCCC; border:1px solid #666666;"><div style="padding-left: 5px; background: #28B8C0; width:'.$total_perc.'%;">'.$total_perc.'%</div></div>';

						$templating->set('leaderboard', $leaderboard);
					}

					$templating->block('top_end', 'goty');
				}

			// games list
			$templating->block('games_list', 'goty');
			$templating->set('category_id', $_GET['category_id']);
			$templating->set('url', core::config('website_url'));

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
					$db->sqlquery("SELECT `id`, `game`, `votes` FROM `goty_games` WHERE `accepted` = 1 AND `category_id` = ? AND `game` LIKE ? ORDER BY `game` ASC", array($_GET['category_id'], $_GET['filter'] . '%'));
				}

				else
				{
					$db->sqlquery("SELECT `id`, `game`, `votes` FROM `goty_games` WHERE `accepted` = 1 AND `category_id` = ? AND `game` <= '@' OR `game` >= '{' ORDER BY `game` ASC", array($_GET['category_id']));
				}
			}

			else
			{
				$db->sqlquery("SELECT `id`, `game`, `votes` FROM `goty_games` WHERE `accepted` = 1 AND `category_id` = ? ORDER BY `game` ASC", array($_GET['category_id']));
			}
			if ($db->num_rows() > 0)
			{
				$games_get = $db->fetch_all_rows();

				foreach ($games_get as $game)
				{
					$templating->block('game_row', 'goty');
					$templating->set('category_id', $_GET['category_id']);
					$templating->set('game_name', $game['game']);

					$votes = '';
					// show leaderboard if voting is open, or voting is closed because it's finished
					if (core::config('goty_voting_open') == 0 && core::config('goty_finished') == 1)
					{
						$votes = 'Votes: ' . $game['votes'] . '<br />';
					}

					$templating->set('votes', $votes);
					$templating->set('game_id', $game['id']);
					$templating->set('url', core::config('website_url'));

					$db->sqlquery("SELECT `ip` FROM `goty_votes` WHERE `ip` = ? AND `category_id` = ?", array(core::$ip, $_GET['category_id']));
					if ($db->num_rows() == 0 && core::config('goty_voting_open') == 1)
					{
						$templating->set('vote_button', '<button name="votebutton" class="votebutton" data-category-id="'.$_GET['category_id'].'" data-game-id="'.$game['id'].'">Vote</button>');
					}
					else
					{
						$templating->set('vote_button', '');
					}

					$leaderboard = '';
					// show leaderboard if voting is open, or voting is closed because it's finished
					if (core::config('goty_voting_open') == 0 && core::config('goty_finished') == 1)
					{
						// work out the games total %
						$db->sqlquery("SELECT `votes` FROM `goty_games` WHERE `category_id` = ?", array($_GET['category_id']));
						$total = $db->fetch_all_rows();

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
		}
	}
}

// add game
if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		if (core::config('goty_games_open') == 1)
		{
			if (!empty($_POST['name']))
			{
				// check if it exists
				$db->sqlquery("SELECT `game` FROM `goty_games` WHERE `game` = ? AND `category_id` = ?", array($_POST['name'], $_POST['category']));

				// add it
				if ($db->num_rows() != 1)
				{
					if ($user->check_group(1,2) == false && $user->check_group(5) == false)
					{
						$db->sqlquery("INSERT INTO `goty_games` SET `game` = ?, `category_id` = ?", array($_POST['name'], $_POST['category']));
						$game_id = $db->grab_id();

						$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 0, `created` = ?, `goty_game_id` = ?", array('A user has submitted a GOTY game for review.', core::$date, $game_id));
						header("Location: " . core::config('website_url') . "goty.php?message=added");
					}
					else if ($user->check_group(1,2) == true || $user->check_group(5) == true)
					{
						$db->sqlquery("INSERT INTO `goty_games` SET `game` = ?, `category_id` = ?, `accepted` = 1", array($_POST['name'], $_POST['category']));
						$game_id = $db->grab_id();

						$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 1, `created` = ?, `completed_date` = ?, `goty_game_id` = ?", array($_SESSION['username'] . ' added a GOTY game.', core::$date, core::$date, $game_id));
						header("Location: " . core::config('website_url') . "goty.php?message=added_editor");
					}
				}

				else
				{
					header("Location: " . core::config('website_url') . "goty.php?message=exists");
				}
			}
			else
			{
				header("Location: " . core::config('website_url') . "goty.php?message=empty");
			}
		}

		else
		{
			header("Location: " . core::config('website_url') . "goty.php");
		}
	}
}

$templating->block('bottom', 'goty');

include('includes/footer.php');
