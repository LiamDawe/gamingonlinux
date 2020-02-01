<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: admin goty config.');
}

$templating->load('admin_modules/admin_module_goty');

if (isset($_GET['view']) && !isset($_POST['act']))
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
		$cats = $dbl->run("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC")->fetch_all();
		foreach( $cats as $category )
		{
			$category_list .= '<option value="' . $category['category_id'] . '">' . $category['category_name'] . '</option>';
		}

		$templating->block('add', 'admin_modules/admin_module_goty');
		$templating->set('url', $core->config('website_url'));
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
			if ($_GET['message'] == 'empty')
			{
				$core->message('Your can\'t add a game with no name!');
			}
		}

		if (!isset($_GET['id']))
		{
			$templating->block('manage_category', 'admin_modules/admin_module_goty');

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

			foreach ($groups as $group)
			{
				$templating->block('group_row');
				$templating->set('category_name', $group['category_name']);

				foreach ($categories as $cat)
				{
					if ($cat['group_id'] == $group['category_id'])
					{
						$templating->block('category_manage_row');
						$templating->set('category_id', $cat['category_id']);
						$templating->set('category_name', $cat['category_name']);
					}
				}
			}
		}
		else if (isset($_GET['id']))
		{
			$templating->block('manage_top', 'admin_modules/admin_module_goty');
			

			$get_games = $dbl->run("SELECT c.`name`, g.`id`, g.`category_id` FROM `goty_games` g INNER JOIN `calendar` c ON c.id = g.game_id WHERE g.`accepted` = 1 AND g.`category_id` = ? ORDER BY c.`name` ASC", array($_GET['id']))->fetch_all();
			$cats = $dbl->run("SELECT `category_id`, `category_name` FROM `goty_category` ORDER BY `category_name` ASC")->fetch_all(PDO::FETCH_KEY_PAIR);

			$templating->set('category_name', $cats[$_GET['id']]);

			foreach ($get_games as $games)
			{
				$category_list = '';
				
				foreach( $cats as $key => $category )
				{
					$selected = '';
					if ($games['category_id'] == $key)
					{
						$selected = 'SELECTED';
					}
					$category_list .= '<option value="' . $key . '" ' . $selected . '>' . $category . '</option>';
				}
				$templating->block('manage_row', 'admin_modules/admin_module_goty');
				$templating->set('game_name', $games['name']);
				$templating->set('url', $core->config('website_url'));
				$templating->set('id', $games['id']);
				$templating->set('category_id', $games['category_id']);
				$templating->set('options', $category_list);
			}
		}
	}

	if ($_GET['view'] == 'config')
	{
		$templating->set_previous('title', 'GOTY Config', 1);
		
		$page = '';
		$games = '';
		$voting = '';
		$votes_per_category = $core->config('goty_votes_per_category');

		if ($core->config('goty_page_open') == 1)
		{
			$page = 'checked';
		}
		if ($core->config('goty_games_open') == 1)
		{
			$games = 'checked';
		}

		if ($core->config('goty_voting_open') == 1)
		{
			$voting = 'checked';
		}

		$end_awards = '';
		if ($core->config('goty_finished') == 0)
		{
			$end_awards = '<button class="fnone" name="act" value="end">End Awards</button>';
		}

		$templating->block('config', 'admin_modules/admin_module_goty');
		$templating->set('page_check', $page);
		$templating->set('games_check', $games);
		$templating->set('voting_check', $voting);
		$templating->set('votes_per_category', $votes_per_category);
		$templating->set('end_awards', $end_awards);

		// categories managing
		$templating->block('config_category_top');
		$options = '';
		$groups = $dbl->run("SELECT `category_id`, `category_name` FROM `goty_category` WHERE `is_group` = 1 ORDER BY `category_name` ASC")->fetch_all();
		foreach ($groups as $group)
		{
			$options .= '<option value="'.$group['category_id'].'">'.$group['category_name'].'</option>';
		}
		$templating->set('group_options', $options);

		$getcats = $dbl->run("SELECT `category_id`, `category_name`, `description`, `is_group`, `group_id` FROM `goty_category` ORDER BY `is_group` DESC, `category_name` ASC")->fetch_all();
		foreach ($getcats as $cat)
		{
			$templating->block('config_category_row');
			$templating->set('category', $cat['category_name']);
			$templating->set('description', $cat['description']);
			$templating->set('cat_id', $cat['category_id']);

			$group_check = '';
			if ($cat['is_group'] == 1)
			{
				$group_check = 'checked';
			}
			$templating->set('group_check', $group_check);

			$none = '';
			if($cat['group_id'] == 0)
			{
				$none = 'selected';
			}

			$options = '';
			$options .= '<option value="0" '.$none.'>None</option>';
			foreach ($groups as $group)
			{
				$selected = '';
				if ($cat['group_id'] == $group['category_id'])
				{
					$selected = 'selected';
				}
				$options .= '<option value="'.$group['category_id'].'" '.$selected.'>'.$group['category_name'].'</option>';
			}
			$templating->set('group_options', $options);
		}
	}

	if ($_GET['view'] == 'top10')
	{
		if (!isset($_GET['category_id']))
		{
			$_SESSION['message'] = 'missing';
			$_SESSION['message_extra'] = 'category_id';
			header("Location: /admin.php?module=goty&view=manage");
			die();
		}
		if (isset($_GET['category_id']))
		{
			$cat = $dbl->run("SELECT `category_name` FROM `goty_category` WHERE `category_id` = ?", array($_GET['category_id']))->fetch();

			$templating->block('game_list', 'admin_modules/admin_module_goty');
			$templating->set('name', $cat['category_name']);

			$games_top = $dbl->run("SELECT coalesce(cl.name, d.name) name, g.`votes` as data FROM `goty_games` g left outer join `calendar` cl ON cl.id = g.game_id and g.category_id != 16 left outer join `developers` d ON d.id = g.game_id and g.category_id = 16 WHERE g.`accepted` = 1 AND g.`category_id` = ? ORDER BY g.`votes` DESC LIMIT 10", array($_GET['category_id']))->fetch_all();

			$charts = new charts($dbl);
			$top_chart = $charts->render(NULL, ['name' => $cat['category_name'], 'grouped' => 0, 'data' => $games_top, 'h_label' => 'Total Votes']);

			$templating->block('topchart', 'admin_modules/admin_module_goty');
			$templating->set('chart', $top_chart);

			$templating->block('games_bottom', 'admin_modules/admin_module_goty');
		}
	}

	if ($_GET['view'] == 'submitted')
	{
		$templating->set_previous('title', 'GOTY Submissions', 1);

		$templating->block('submitted_top', 'admin_modules/admin_module_goty');

		$get_games = $dbl->run("SELECT coalesce(cl.name, d.name) name, g.`id`, g.`game_id`, c.`category_name`, g.`category_id` FROM `goty_games` g left outer join `calendar` cl ON cl.id = g.game_id and g.category_id != 3 left outer join `developers` d ON d.id = g.game_id and g.category_id = 3 LEFT JOIN `goty_category` c ON c.category_id = g.category_id WHERE g.`accepted` = 0")->fetch_all();
		foreach ($get_games as $games)
		{
			$templating->block('submitted_row', 'admin_modules/admin_module_goty');
			$templating->set('game_name', '<a href="/itemdb/'.$games['game_id'].'">'.$games['name'] . '</a>');
			$templating->set('category_name', $games['category_name']);
			$templating->set('category_id', $games['category_id']);
			$templating->set('url', $core->config('website_url'));
			$templating->set('id', $games['id']);
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'reset_goty')
	{
		$return = '/admin.php?module=goty&view=config';
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Reset the awards?', 1);
			$core->confirmation(array('title' => 'Are you absolutely sure you wish to reset the awards?', 'text' => 'This will remove all votes! Do not do this unless you have been told to do so, this resets everything!', 'action_url' => '/admin.php?module=goty', 'act' => 'reset_goty'));
		}

		else if (isset($_POST['no']))
		{
			header("Location: " . $return);
			die();
		}

		else if (isset($_POST['yes']))
		{
			$dbl->run("TRUNCATE TABLE `goty_votes`");
			$core->set_config('0','goty_finished');
			$core->set_config('0','goty_total_votes');

			$core->new_admin_note(array('completed' => 1, 'content' => ' Reset the entire GOTY awards.'));
			$_SESSION['message'] = 'goty_reset';
			header('Location: ' . $return);
			die();
		}			
	}
	if ($_POST['act'] == 'add')
	{
		if (!empty($_POST['name']))
		{
			// check if it exists
			$check = $dbl->run("SELECT `game` FROM `goty_games` WHERE `game` = ? AND `category_id` = ?", array($_POST['name'], $_POST['category']))->fetch();

			// add it
			if (!$check)
			{
				$dbl->run("INSERT INTO `goty_games` SET `game` = ?, `accepted` = 1, `category_id` = ?", array($_POST['name'], $_POST['category']));

				$core->new_admin_note(array('completed' => 1, 'content' => ' added a game '.$_POST['name'].' to the GOTY awards.'));

				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=add&message=added");
			}

			else
			{
				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=add&message=exists");
			}
		}
		else
		{
			header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=add&message=empty");
		}
	}

	if ($_POST['act'] == 'delete')
	{
		if (isset($_POST['id']))
		{
			// check if it exists
			$check = $dbl->run("SELECT `id` FROM `goty_games` WHERE `id` = ?", array($_POST['id']))->fetchOne();

			// delete it
			if ($check)
			{
				$dbl->run("DELETE FROM `goty_games` WHERE `id` = ?", array($_POST['id']));

				$core->new_admin_note(array('completed' => 1, 'content' => ' deleted a game from the GOTY awards.'));

				$_SESSION['message'] = 'deleted';
				$_SESSION['message_extra'] = 'GOTY game';
				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=manage&id=".$_POST['category_id']);
			}

			else
			{
				$_SESSION['message'] = 'none_found';
				$_SESSION['message_extra'] = 'submissions with that ID';
				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=manage");
			}
		}
	}

	if ($_POST['act'] == 'edit')
	{
		if (isset($_POST['id']))
		{
			// check if it exists
			$check = $dbl->run("SELECT 1 FROM `goty_games` WHERE `id` = ?", array($_POST['id']))->fetchOne();

			// complete the edit
			if ($check)
			{
				$dbl->run("UPDATE `goty_games` SET `category_id` = ? WHERE `id` = ?", array($_POST['category'], $_POST['id']));

				$core->new_admin_note(array('completed' => 1, 'content' => ' edited a game in the GOTY awards.'));

				$_SESSION['message'] = 'saved';
				$_SESSION['message_extra'] = 'game\'s goty category';
				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=manage");
			}

			else
			{
				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=manage&message=exists");
			}
		}
		else
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'game id';
			header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=manage");
		}
	}

	if ($_POST['act'] == 'accept')
	{
		if (isset($_POST['id']))
		{
			// check if it exists
			$check = $dbl->run("SELECT `accepted` FROM `goty_games` WHERE `id` = ?", array($_POST['id']))->fetch();

			// add it
			if ($check)
			{
				if ($check['accepted'] == 1)
				{
					$_SESSION['message'] = 'already_accepted';
					header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=submitted");
				}
				else
				{
					$dbl->run("UPDATE `goty_games` SET `accepted` = 1, `accepted_by` = ? WHERE `id` = ?", array($_SESSION['user_id'], $_POST['id']));

					// notify editors you did this
					$core->update_admin_note(array('type' => 'goty_game_submission', 'data' => $_POST['id']));
					$core->new_admin_note(array('completed' => 1, 'content' => ' accepted a game for the GOTY awards.'));

					$_SESSION['message'] = 'accepted';
					$_SESSION['message_extra'] = 'GOTY game submission';
					header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=submitted");
				}
			}
		}
		else
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'game id';
			header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=submitted");
		}
	}

	if ($_POST['act'] == 'deny')
	{
		if (isset($_POST['id']))
		{
			// check if it exists
			$check = $dbl->run("SELECT 1 FROM `goty_games` WHERE `id` = ?", array($_POST['id']))->fetchOne();

			// remove it
			if ($check)
			{
				$dbl->run("DELETE FROM `goty_games` WHERE `id` = ?", array($_POST['id']));

				// notify editors you did this
				$core->update_admin_note(array('type' => 'goty_game_submission', 'data' => $_POST['id']));
				$core->new_admin_note(array('completed' => 1, 'content' => ' denied a game for the GOTY awards.'));

				$_SESSION['message'] = 'deleted';
				$_SESSION['message_extra'] = 'GOTY game submission';
				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=submitted");
			}
			else
			{
				$_SESSION['message'] = 'none_found';
				$_SESSION['message_extra'] = 'submissions with that ID';
				header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=submitted");
			}
		}
		else
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'game id';
			header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=submitted");
		}
	}

	if ($_POST['act'] == 'config')
	{
		$page = 0;
		$games = 0;
		$voting = 0;
		$votes_per_category = 1;

		if (isset($_POST['page_open']))
		{
			$page = 1;
		}
		if (isset($_POST['games']))
		{
			$games = 1;
		}
		if (isset($_POST['voting']))
		{
			$voting = 1;
		}
		if (isset($_POST['votes_per_category']) && is_numeric($_POST['votes_per_category']))
		{
			$votes_per_category = $_POST['votes_per_category'];
		}

		$core->set_config($page, 'goty_page_open');
		$core->set_config($games, 'goty_games_open');
		$core->set_config($voting, 'goty_voting_open');
		$core->set_config($votes_per_category, 'goty_votes_per_category');

		// notify editors you did this
		$core->new_admin_note(array('completed' => 1, 'content' => ' edited the GOTY awards settings.'));

		header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=config");
		die();
	}

	if ($_POST['act'] == 'add_category')
	{
		$name = trim($_POST['category']);
		$description = trim($_POST['description']);

		// make sure its not empty
		$empty_check = core::mempty(compact('title', 'description'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: /admin.php?module=goty&view=config");
			die();
		}

		// check if it exists
		$check = $dbl->run("SELECT `category_id` FROM `goty_category` WHERE `category_name` = ?", array($name))->fetch();
		if ($check)
		{
			$_SESSION['message'] = 'category_exists';
			header("Location: /admin.php?module=goty&view=config");
			die();
		}

		$is_group = 0;
		if (isset($_POST['is_group']))
		{
			$is_group = 1;
		}

		$group_id = 0;
		if ($_POST['group_id'] != 0 && is_numeric($_POST['group_id']))
		{
			$group_id = $_POST['group_id'];
		}

		// add it
		$dbl->run("INSERT INTO `goty_category` SET `category_name` = ?, `description` = ?, `is_group` = ?, `group_id` = ?", array($name, $description, $is_group, $group_id));
		$core->new_admin_note(array('completed' => 1, 'content' => ' Added a new category to the GOTY Awards.'));
		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'category';
		header("Location: /admin.php?module=goty&view=config");
		die();			
	}

	if ($_POST['act'] == 'edit_category')
	{
		$name = trim($_POST['category']);
		$description = trim($_POST['description']);
		$category_id = $_POST['cat_id'];

		// make sure its not empty
		$empty_check = core::mempty(compact('title', 'description', 'category_id'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: /admin.php?module=goty&view=config");
			die();
		}	
		
		// make sure we don't double up categories
		$check = $dbl->run("SELECT `category_id` FROM `goty_category` WHERE `category_name` = ? AND `category_id` != ?", array($name, $category_id))->fetch();
		if ($check)
		{
			$_SESSION['message'] = 'category_exists';
			header("Location: /admin.php?module=goty&view=config");
			die();
		}

		$is_group = 0;
		if (isset($_POST['is_group']))
		{
			$is_group = 1;
		}

		$group_id = 0;
		if ($_POST['group_id'] != 0 && is_numeric($_POST['group_id']))
		{
			$group_id = $_POST['group_id'];
		}

		// edit it
		$dbl->run("UPDATE `goty_category` SET `category_name` = ?, `description` = ?, `is_group` = ?, `group_id` = ? WHERE `category_id` = ?", array($name, $description, $is_group, $group_id, $category_id));
		$core->new_admin_note(array('completed' => 1, 'content' => ' Edited a category in the GOTY Awards.'));
		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'category';
		header("Location: /admin.php?module=goty&view=config");
		die();				
	}

	if ($_POST['act'] == 'delete_category')
	{
		$return = '/admin.php?module=goty&view=config';
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Deleting a GOTY category', 1);
			$core->confirmation(array('title' => 'Are you sure you want to delete that GOTY category?', 'text' => 'Category: '.$_POST['category']. '.<br /> This cannot be undone! It will NOT remove any games from the GOTY Award, their category will need to be changed.', 'action_url' => '/admin.php?module=goty&cat_id='.$_POST['cat_id'], 'act' => 'delete_category'));
		}

		else if (isset($_POST['no']))
		{
			header("Location: " . $return);
			die();
		}

		else if (isset($_POST['yes']))
		{
			$dbl->run("DELETE FROM `goty_category` WHERE `category_id` = ?", array($_GET['cat_id']));
			$core->new_admin_note(array('completed' => 1, 'content' => ' Deleted a category from the GOTY Awards.'));
			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'category';
			header('Location: ' . $return);
			die();
		}		
	}

	if ($_POST['act'] == 'end')
	{
		if ($core->config('goty_finished') == 0)
		{
			$dbl->run("UPDATE `config` SET `data_value` = 0 WHERE `data_key` = 'goty_games_open'");
			$dbl->run("UPDATE `config` SET `data_value` = 0 WHERE `data_key` = 'goty_voting_open'");
			$dbl->run("UPDATE `config` SET `data_value` = 1 WHERE `data_key` = 'goty_finished'");

			// make the chart for each category
			$result = $dbl->run("SELECT `category_name`, `category_id` FROM `goty_category` ORDER BY `category_id` ASC")->fetch_all();
			foreach ($result as $category)
			{
				$dbl->run("INSERT INTO `charts` SET `name` = ?, `h_label` = ?, `owner` = ?", array($category['category_name'], 'Total Votes' , $_SESSION['user_id']));
				$chart_key = $dbl->new_id();

				$games = $dbl->run("SELECT coalesce(cl.name, d.name) name, g.`votes` as data FROM `goty_games` g left outer join `calendar` cl ON cl.id = g.game_id and g.category_id != 16 left outer join `developers` d ON d.id = g.game_id and g.category_id = 16 WHERE g.`accepted` = 1 AND g.`category_id` = ? ORDER BY g.`votes` DESC LIMIT 10", array($category['category_id']))->fetch_all();
				foreach ($games as $game)
				{
					$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($chart_key, $game['name']));
					$game_key = $dbl->new_id();

					$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($chart_key, $game_key, $game['data']));
				}
			}

			// notify editors you did this
			$core->new_admin_note(array('completed' => 1, 'content' => ' closed the GOTY awards.'));

			$_SESSION['message'] = 'goty_ended';
			header("Location: " . $core->config('website_url') . "admin.php?module=goty&view=config");
		}
	}
}
