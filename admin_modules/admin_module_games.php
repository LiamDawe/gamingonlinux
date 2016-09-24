<?php
$templating->merge('admin_modules/admin_module_games');

if (isset($_GET['view']) && !isset($_POST['act']))
{
	if ($_GET['view'] == 'add')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'added')
			{
				$core->message("Game addition completed! <a href=\"/index.php?module=game&game-id={$_GET['id']}\">View in live database</a>.");
			}
			if ($_GET['message'] == 'missing')
			{
				$core->message('Please fill a name, a release date and an official website link at a minimum!', null, 1);
			}
			if ($_GET['message'] == 'exists')
			{
				$core->message('That game already exists! <a href="/index.php?module=game&game-id=' . $_GET['id'] . '">View in live database</a>.', NULL, 1);
			}
		}

		$templating->set_previous('meta_description', 'Adding a new game', 1);
		$templating->set_previous('title', 'Adding a game to the database', 1);

		$templating->block('add_top', 'admin_modules/admin_module_games');

		$templating->block('item', 'admin_modules/admin_module_games');
		$templating->set('id', '');
		$templating->set('name', '');
		$templating->set('link', '');
		$templating->set('steam_link', '');
		$templating->set('gog_link', '');
		$templating->set('date', '');
		$templating->set('guess_check', '');
		$core->editor('text', '');

		$templating->block('add_bottom', 'admin_modules/admin_module_games');
	}
	if ($_GET['view'] == 'edit')
	{
		if (!isset($_GET['id']) || !is_numeric($_GET['id']))
		{
			$core->message('Not ID set, you shouldn\'t be here!');
		}
		else
		{
			$db->sqlquery("SELECT * FROM `calendar` WHERE `id` = ?", array($_GET['id']));
			$count = $db->num_rows();

			if ($count == 0)
			{
				$core->message('That ID does not exist!');
			}
			else if ($count == 1)
			{
				$game = $db->fetch();

				if (isset($_GET['message']))
				{
					if ($_GET['message'] == 'edited')
					{
						$core->message('Game edit completed!');
					}
					if ($_GET['message'] == 'missing')
					{
						$core->message('Please fill a name, a release date and an official website link at a minimum!', null, 1);
					}
				}

				$templating->set_previous('meta_description', 'Editing: '.$game['name'], 1);
				$templating->set_previous('title', 'Editing: ' . $game['name'], 1);

				$templating->block('edit_top', 'admin_modules/admin_module_games');
				$templating->set('id', $game['id']);

				$templating->block('item', 'admin_modules/admin_module_games');

				$return = '';
				if (isset($_GET['return']))
				{
					$return = $_GET['return'];
				}

				$templating->set('id', $game['id']);
				$templating->set('name', $game['name']);
				$templating->set('link', $game['link']);
				$templating->set('steam_link', $game['steam_link']);
				$templating->set('gog_link', $game['gog_link']);

				$date = new DateTime($game['date']);
				$templating->set('date', $date->format('d-m-Y'));

				$guess = '';
				if ($game['best_guess'] == 1)
				{
					$guess = 'checked';
				}
				$templating->set('guess_check', $guess);

				$text = $game['description'];

				$core->editor('text', $text);

				$templating->block('edit_bottom', 'admin_modules/admin_module_games');
				$templating->set('return', $return);
				$templating->set('id', $game['id']);
			}
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Add')
	{
		if (empty($_POST['name']) || empty($_POST['date']) || empty($_POST['link']))
		{
			header("Location: /admin.php?module=games&view=add&error=missing");
			exit;
		}

		$db->sqlquery("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($_POST['name']));
		if ($db->num_rows() == 1)
		{
			$get_game = $db->fetch();
			header("Location: /admin.php?module=games&view=add&message=exists&id={$get_game['id']}");
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$name = trim($_POST['name']);
		$description = trim($_POST['text']);

		$db->sqlquery("INSERT INTO `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `best_guess` = ?, `approved` = 1", array($name, $description, $date->format('Y-m-d'), $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $guess));
		$new_id = $db->grab_id();

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array($_SESSION['username'] . ' added ' . $_POST['name'] . ' to the games database.', core::$date, core::$date));

		header("Location: /admin.php?module=games&view=add&&message=added&id={$new_id}");
	}
	if ($_POST['act'] == 'Edit')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=games&view=manage&message=missing_id");
			exit;
		}

		if (empty($_POST['name']) || empty($_POST['date']) || empty($_POST['link']))
		{
			header("Location: /admin.php?module=games&view=edit&message=missing&id=" . $_POST['id']);
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$name = trim($_POST['name']);
		$description = trim($_POST['text']);

		$db->sqlquery("UPDATE `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `best_guess` = ?, `edit_date` = ? WHERE `id` = ?", array($name, $description, $date->format('Y-m-d'), $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $guess, date('Y-m-d'), $_POST['id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array($_SESSION['username'] . ' edited ' . $_POST['name'] . ' in the games database.', core::$date, core::$date));

		if (isset($_GET['return']) && !empty($_GET['return']))
		{
			if ($_GET['return'] == 'calendar')
			{
				header("Location: /index.php?module=calendar&message=edited");
			}
			if ($_GET['return'] == 'game')
			{
				header("Location: /index.php?module=game&game-id=" . $_POST['id'] . '&message=edited');
			}
		}
		else
		{
			header("Location: /admin.php?module=games&view=edit&id=" . $_POST['id'] . '&message=edited');
		}
	}
	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$db->sqlquery("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_POST['id']));
			$name = $db->fetch();

			$return = '';
			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				$return = $_GET['return'];
			}

			$core->yes_no('Are you sure you want to delete ' . $name['name'] . ' from the games database and calendar?', "admin.php?module=games&id={$_POST['id']}&return=" . $return, "Delete");
		}

		else if (isset($_POST['no']))
		{
			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				if ($_GET['return'] == 'calendar')
				{
					header("Location: /index.php?module=calendar");
					die();
				}
				if ($_GET['return'] == 'game')
				{
					header("Location: /index.php?module=game&game-id=" . $_GET['id']);
					die();
				}
				if ($_GET['return'] == 'submitted')
				{
					header("Location: /admin.php?module=calendar&view=submitted");
					die();
				}
			}
			else
			{
				header("Location: /index.php?module=calendar");
			}
		}

		else if (isset($_POST['yes']))
		{
			$db->sqlquery("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_GET['id']));
			$name = $db->fetch();

			$db->sqlquery("DELETE FROM `calendar` WHERE `id` = ?", array($_GET['id']));

			$db->sqlquery("DELETE FROM `admin_notifications` WHERE `calendar_id` = ?", array($_GET['id']));

			$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?, `calendar_id` = ?", array($_SESSION['username'] . ' removed ' . $name['name'] . ' from the games database and calendar.', core::$date, core::$date, $_GET['id']));

			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				if ($_GET['return'] == 'submitted')
				{
					header("Location: /admin.php?module=calendar&view=submitted&message=deleted");
					die();
				}
				if ($_GET['return'] == 'game')
				{
					header("Location: /index.php?module=calendar&message=deleted");
					die();
				}
			}
			else
			{
				header("Location: /index.php?module=calendar&message=deleted");
			}
		}
	}
}
