<?php
$templating->merge('admin_modules/admin_module_goty');

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'add')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'added')
			{
				$core->message('You have added that game to the list!');
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

		$category_list = '';
		$cats = $db->sqlquery("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC");
		foreach( $cats as $category )
		{
			$category_list .= '<option value="' . $category['category_id'] . '">' . $category['category_name'] . '</option>';
		}

		$templating->block('add', 'admin_modules/admin_module_goty');
		$templating->set('url', $config['path']);
		$templating->set('options', $category_list);
	}

	if ($_GET['view'] == 'manage')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'exists')
			{
				$core->message('That game already exists in our GOTY list!');
			}
			if ($_GET['message'] == 'edited')
			{
				$core->message('You have edited that game!');
			}
			if ($_GET['message'] == 'deleted')
			{
				$core->message('Your have deleted that game!');
			}
			if ($_GET['message'] == 'empty')
			{
				$core->message('Your can\'t add a game with no name!');
			}
		}

		if (!isset($_GET['id']))
		{
			$templating->block('manage_category', 'admin_modules/admin_module_goty');

			$cats = $db->sqlquery("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC");
			foreach( $cats as $category )
			{
				$templating->block('category_manage_row', 'admin_modules/admin_module_goty');
				$templating->set('category_id', $category['category_id']);
				$templating->set('category_name', $category['category_name']);
			}
		}
		else if (isset($_GET['id']))
		{
			$templating->block('manage_top', 'admin_modules/admin_module_goty');

			$get_games = $db->sqlquery("SELECT `game`, `id`, `category_id` FROM `goty_games` WHERE `accepted` = 1 AND `category_id` = ? ORDER BY `game` ASC", array($_GET['id']));
			foreach ($get_games as $games)
			{
				$category_list = '';
				$cats = $db->sqlquery("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC");
				foreach( $cats as $category )
				{
					$selected = '';
					if ($games['category_id'] == $category['category_id'])
					{
						$selected = 'SELECTED';
					}
					$category_list .= '<option value="' . $category['category_id'] . '" ' . $selected . '>' . $category['category_name'] . '</option>';
				}
				$templating->block('manage_row', 'admin_modules/admin_module_goty');
				$templating->set('game_name', $games['game']);
				$templating->set('url', $config['path']);
				$templating->set('id', $games['id']);
				$templating->set('options', $category_list);
			}
		}
	}

	if ($_GET['view'] == 'config')
	{
		$games = '';
		$voting = '';

		if ($config['goty_games_open'] == 1)
		{
			$games = 'checked';
		}

		if ($config['goty_voting_open'] == 1)
		{
			$voting = 'checked';
		}

		$end_awards = '';
		if (core::config('goty_finished') == 0)
		{
			$end_awards = '<button class="fnone" name="act" value="end">End Awards</button>';
		}

		$templating->block('config', 'admin_modules/admin_module_goty');
		$templating->set('games_check', $games);
		$templating->set('voting_check', $voting);
		$templating->set('end_awards', $end_awards);
	}

	if ($_GET['view'] == 'top10')
	{
		if (!isset($_GET['category_id']))
		{
			$templating->block('category_top', 'admin_modules/admin_module_goty');

			$cats = $db->sqlquery("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC");
			foreach ($cats as $cat)
			{
				$templating->block('category_row', 'admin_modules/admin_module_goty');
				$templating->set('category_id', $cat['category_id']);
				$templating->set('category_name', $cat['category_name']);
			}

			$templating->block('category_bottom', 'admin_modules/admin_module_goty');
		}
		if (isset($_GET['category_id']))
		{
			$db->sqlquery("SELECT `category_name` FROM `goty_category` WHERE `category_id` = ?", array($_GET['category_id']));
			$cat = $db->fetch();

			$templating->block('game_list', 'admin_modules/admin_module_goty');
			$templating->set('name', $cat['category_name']);

			require_once('./includes/SVGGraph/SVGGraph.php');
			$labels = array();
			$settings = array('auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside');
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
			$get_graph = '<div style="width: 80%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';

			$templating->block('topchart', 'admin_modules/admin_module_goty');
			$templating->set('chart', $get_graph);

			$templating->block('games_bottom', 'admin_modules/admin_module_goty');
		}
	}

	if ($_GET['view'] == 'submitted')
	{

		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'added')
			{
				$core->message('You have added that game to the list!');
			}
			if ($_GET['message'] == 'exists')
			{
				$core->message('That game already exists in our GOTY list!');
			}
			if ($_GET['message'] == 'nope')
			{
				$core->message('That game doesn\'t exist in our GOTY list!');
			}
			if ($_GET['message'] == 'deleted')
			{
				$core->message('You gave denied that game being listed in the GOTY awards!');
			}
		}

		$templating->block('submitted_top', 'admin_modules/admin_module_goty');

		$db->sqlquery("SELECT g.`game`, g.`id`, c.`category_name` FROM `goty_games` g LEFT JOIN `goty_category` c ON c.category_id = g.category_id WHERE `accepted` = 0");
		while ($games = $db->fetch())
		{
			$templating->block('submitted_row', 'admin_modules/admin_module_goty');
			$templating->set('game_name', $games['game']);
			$templating->set('category_name', $games['category_name']);
			$templating->set('url', $config['path']);
			$templating->set('id', $games['id']);
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		if (!empty($_POST['name']))
		{
			// check if it exists
			$db->sqlquery("SELECT `game` FROM `goty_games` WHERE `game` = ? AND `category_id = ?`", array($_POST['name'], $_POST['category']));

			// add it
			if ($db->num_rows() != 1)
			{
				$db->sqlquery("INSERT INTO `goty_games` SET `game` = ?, `accepted` = 1, `category_id` = ?", array($_POST['name'], $_POST['category']));
				header("Location: {$config['path']}admin.php?module=goty&view=add&message=added");
			}

			else
			{
				header("Location: {$config['path']}admin.php?module=goty&view=add&message=exists");
			}
		}
		else
		{
			header("Location: {$config['path']}admin.php?module=goty&view=add&message=empty");
		}
	}

	if ($_POST['act'] == 'delete')
	{
		if (!empty($_POST['id']))
		{
			// check if it exists
			$db->sqlquery("SELECT `id` FROM `goty_games` WHERE `id` = ?", array($_POST['id']));

			// delete it
			if ($db->num_rows() == 1)
			{
				$db->sqlquery("DELETE FROM `goty_games` WHERE `id` = ?", array($_POST['id']));
				header("Location: {$config['path']}admin.php?module=goty&view=manage&message=deleted");
			}

			else
			{
				header("Location: {$config['path']}admin.php?module=goty&view=manage&message=nope");
			}
		}
		else
		{
			header("Location: {$config['path']}admin.php?module=goty&view=manage&message=empty");
		}
	}

	if ($_POST['act'] == 'edit')
	{
		if (!empty($_POST['game']))
		{
			// check if it exists
			$db->sqlquery("SELECT `game` FROM `goty_games` WHERE `id` = ?", array($_POST['id']));

			// complete the edit
			if ($db->num_rows() == 1)
			{
				$db->sqlquery("UPDATE `goty_games` SET `game` = ?, `category_id` = ? WHERE `id` = ?", array($_POST['game'], $_POST['category'], $_POST['id']));
				header("Location: {$config['path']}admin.php?module=goty&view=manage&message=edited");
			}

			else
			{
				header("Location: {$config['path']}admin.php?module=goty&view=manage&message=exists");
			}
		}
		else
		{
			header("Location: {$config['path']}admin.php?module=goty&view=manage&message=empty");
		}
	}

	if ($_POST['act'] == 'accept')
	{
		if (!empty($_POST['id']))
		{
			// check if it exists
			$db->sqlquery("SELECT `game` FROM `goty_games` WHERE `accepted` = 1 AND `id` = ?", array($_POST['id']));

			// add it
			if ($db->num_rows() != 1)
			{
				$db->sqlquery("UPDATE `goty_games` SET `game` = ?, `accepted` = 1 WHERE `id` = ?", array($_POST['name'], $_POST['id']));
				$db->sqlquery("DELETE FROM `admin_notifications` WHERE `goty_game_id` = ?", array($_POST['id']));
				$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 1, `created` = ?, `completed_date` = ?, `goty_game_id` = ?", array($_SESSION['username'] . ' accepted a user submitted GOTY game.', core::$date, core::$date, $_POST['id']));
				header("Location: {$config['path']}admin.php?module=goty&view=submitted&message=added");
			}

			else
			{
				header("Location: {$config['path']}admin.php?module=goty&view=submitted&message=exists");
			}
		}
		else
		{
			header("Location: {$config['path']}admin.php?module=goty&view=submitted");
		}
	}

	if ($_POST['act'] == 'deny')
	{
		if (!empty($_POST['id']))
		{
			// check if it exists
			$db->sqlquery("SELECT `id` FROM `goty_games` WHERE `id` = ?", array($_POST['id']));

			// remove it
			if ($db->num_rows() == 1)
			{
				$db->sqlquery("DELETE FROM `goty_games` WHERE `id` = ?", array($_POST['id']));
				$db->sqlquery("DELETE FROM `admin_notifications` WHERE `goty_game_id` = ?", array($_POST['id']));
				$db->sqlquery("INSERT INTO `admin_notifications` SET `action` = ?, `completed` = 1, `created` = ?, `completed_date` = ?, `goty_game_id` = ?", array($_SESSION['username'] . ' denied a user submitted GOTY game.', core::$date, core::$date, $_POST['id']));
				header("Location: {$config['path']}admin.php?module=goty&view=submitted&message=deleted");
			}

			else
			{
				header("Location: {$config['path']}admin.php?module=goty&view=submitted&message=nope");
			}
		}
		else
		{
			header("Location: {$config['path']}admin.php?module=goty&view=submitted");
		}
	}

	if ($_POST['act'] == 'config')
	{
		$games = 0;
		$voting = 0;
		if (isset($_POST['games']))
		{
			$games = 1;
		}
		if (isset($_POST['voting']))
		{
			$voting = 1;
		}

		$db->sqlquery("UPDATE `config` SET `data_value` = ? WHERE `data_key` = 'goty_games_open'", array($games));
		$db->sqlquery("UPDATE `config` SET `data_value` = ? WHERE `data_key` = 'goty_voting_open'", array($voting));

		header("Location: {$config['path']}admin.php?module=goty&view=config");
	}

	if ($_POST['act'] == 'end')
	{
		if (core::config('goty_finished') == 0)
		{
			$db->sqlquery("UPDATE `config` SET `data_value` = 0 WHERE `data_key` = 'goty_games_open'");
			$db->sqlquery("UPDATE `config` SET `data_value` = 0 WHERE `data_key` = 'goty_voting_open'");
			$db->sqlquery("UPDATE `config` SET `data_value` = 1 WHERE `data_key` = 'goty_finished'");

			$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array("{$_SESSION['username']} closed the GOTY Awards.", core::$date, core::$date));

			// make the chart for each category
			$result = $db->sqlquery("SELECT `category_name`, `category_id` FROM `goty_category` ORDER BY `category_id` ASC");
			foreach ($result as $category)
			{
				$db->sqlquery("INSERT INTO `charts` SET `name` = ?, `h_label` = ?, `owner` = ?", array($category['category_name'], 'Total Votes' , $_SESSION['user_id']));
				$chart_key = $db->grab_id();

				$games = $db->sqlquery("SELECT `game`, `votes` FROM `goty_games` WHERE `category_id` = ? ORDER BY `votes` DESC LIMIT 10", array($category['category_id']));
				foreach ($games as $game)
				{
					$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($chart_key, $game['game']));
					$game_key = $db->grab_id();

					$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($chart_key, $game_key, $game['votes']));
				}
			}

			header("Location: {$config['path']}admin.php?module=goty&view=config");
		}
	}
}
