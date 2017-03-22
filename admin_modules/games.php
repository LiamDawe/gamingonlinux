<?php
$templating->merge('admin_modules/games');

$licenses = array('', 'Closed Source', 'GPL', 'BSD', 'MIT');

if (isset($_GET['view']) && !isset($_POST['act']))
{
	if ($_GET['view'] == 'genres')
	{
		$templating->block('add_genre');
		
		$db->sqlquery("SELECT `id`, `name` FROM `game_genres` ORDER BY `name` ASC");
		while ($genres = $db->fetch())
		{
			$templating->block('genre_row');
			$templating->set('name', $genres['name']);
			$templating->set('genre_id', $genres['id']);
		}
	}
	
	if ($_GET['view'] == 'add')
	{
		$templating->set_previous('meta_description', 'Adding a new game', 1);
		$templating->set_previous('title', 'Adding a game to the database', 1);

		$templating->block('add_top', 'admin_modules/games');

		$templating->block('item', 'admin_modules/games');

		// all these need to be empty, as it's a new game
		$set_empty = array('id', 'name', 'link', 'steam_link', 'gog_link', 'itch_link', 'date', 'guess_guess', 'dlc_check', 'base_game', 'free_game');
		foreach ($set_empty as $make_empty)
		{
			$templating->set($make_empty, '');
		}

		$license_options = '';
		foreach ($licenses as $license)
		{
			$license_options .= '<option value="' . $license . '">'.$license.'</option>';
		}
		$templating->set('license_options', $license_options);

		$core->editor(['name' => 'text', 'editor_id' => 'game_text']);

		$templating->block('add_bottom', 'admin_modules/games');
	}
	if ($_GET['view'] == 'edit')
	{
		if (!isset($_GET['id']) || !is_numeric($_GET['id']))
		{
			$core->message('Not ID set, you shouldn\'t be here!');
		}
		else
		{
			$db->sqlquery("SELECT c.*, b.name as base_game_name, b.id as base_game_id FROM `calendar` c LEFT JOIN `calendar` b ON c.base_game_id = b.id WHERE c.`id` = ?", array($_GET['id']));
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
						$core->message('Please fill a name, a release date and one link at a minimum!', null, 1);
					}
				}

				$templating->set_previous('meta_description', 'Editing: '.$game['name'], 1);
				$templating->set_previous('title', 'Editing: ' . $game['name'], 1);

				$templating->block('edit_top', 'admin_modules/games');
				$templating->set('id', $game['id']);

				$templating->block('item', 'admin_modules/games');

				$return = '';
				if (isset($_GET['return']))
				{
					$return = $_GET['return'];
				}

				$stores = array('steam', 'gog', 'itch');
				foreach ($stores as $store)
				{
					$templating->set($store . '_link', $game[$store . '_link']);
				}

				$templating->set('id', $game['id']);
				$templating->set('name', $game['name']);
				$templating->set('link', $game['link']);

				$date = new DateTime($game['date']);
				$templating->set('date', $date->format('d-m-Y'));

				$guess = '';
				if ($game['best_guess'] == 1)
				{
					$guess = 'checked';
				}
				$templating->set('guess_check', $guess);

				$dlc_check = '';
				if ($game['is_dlc'] == 1)
				{
					$dlc_check = 'checked';
				}
				$templating->set('dlc_check', $dlc_check);

				$free_check = '';
				if ($game['free_game'] == 1)
				{
					$free_check = 'checked';
				}
				$templating->set('free_check', $free_check);

				$license_options = '';
				foreach ($licenses as $license)
				{
					$selected = '';
					if ($game['license'] == $license)
					{
						$selected = 'selected';
					}
					$license_options .= '<option value="' . $license . '" '.$selected.'>'.$license.'</option>';
				}
				$templating->set('license_options', $license_options);

				$base_game = '';
				if ($game['base_game_id'] != NULL && $game['base_game_id'] != 0)
				{
					$base_game = '<option value="'.$game['base_game_id'].'" selected>'.$game['base_game_name'].'</option>';
				}
				$templating->set('base_game', $base_game);
				
				// sort out genre tags
				$genre_list = $core->display_game_genres($game['id']);
				$templating->set('genre_list', $genre_list);

				$text = $game['description'];

				$core->editor(['name' => 'text', 'content' => $text, 'editor_id' => 'game_text']);

				$templating->block('edit_bottom', 'admin_modules/games');
				$templating->set('return', $return);
				$templating->set('id', $game['id']);
			}
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add_genre')
	{
		$genre = trim($_POST['name']);
		$genre = core::make_safe($genre);
		
		if (empty($genre))
		{
			header("Location: /admin.php?module=games&view=genres&message=missing&extra=name");
			die();
		}
		
		$db->sqlquery("SELECT `name` FROM `game_genres` WHERE `name` = ?", array($genre));
		if ($db->num_rows() == 1)
		{
			header("Location: /admin.php?module=games&view=genres&message=genre_exists");
			die();	
		}
		
		$db->sqlquery("INSERT INTO `game_genres` SET `name` = ?, `accepted` = 1", array($genre));
		
		header("Location: /admin.php?module=games&view=genres&message=saved&extra=genre");
		die();
	}
	
	if ($_POST['act'] == 'edit_genre')
	{
		$genre = trim($_POST['name']);
		$genre = core::make_safe($genre);
		if (!core::is_number((int) $_POST['genre_id']))
		{
			header("Location: /admin.php?module=games&view=genres&message=missing&extra=id");
			die();
		}
		
		if (empty($genre))
		{
			header("Location: /admin.php?module=games&view=genres&message=missing&extra=name");
			die();
		}
		
		$db->sqlquery("UPDATE `game_genres` SET `name` = ? WHERE `id` = ?", array($genre, $_POST['genre_id']));
		
		header("Location: /admin.php?module=games&view=genres&message=edited&extra=genre");
		die();
	}
	
	if ($_POST['act'] == 'delete_genre')
	{
		$return = '/admin.php?module=games&view=genres';
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$genre_id = (int) $_POST['genre_id'];
			
			if (!core::is_number($genre_id))
			{
				header("Location: " . $return);
				die();
			}
			
			$db->sqlquery("SELECT `name` FROM `game_genres` WHERE `id` = ?", array($genre_id));
			$name = $db->fetch();

			$core->yes_no('Are you sure you want to delete ' . $name['name'] . ' from the game genres list?', "admin.php?module=games&genre_id={$_POST['genre_id']}&return=" . $return, "delete_genre");
		}

		else if (isset($_POST['no']))
		{
			header("Location: " . $return);
		}

		else if (isset($_POST['yes']))
		{
			$genre_id = (int) $_GET['genre_id'];
			
			if (!core::is_number($genre_id))
			{
				header("Location: " . $return);
				die();
			}
			
			$db->sqlquery("DELETE FROM `game_genres` WHERE `id` = ?", array($genre_id));
			$db->sqlquery("DELETE FROM `game_genres_reference` WHERE `genre_id` = ?", array($genre_id));
			
			header("Location: " . $return . '&message=deleted&extra=genre');
		}
	}
	
	if ($_POST['act'] == 'Add')
	{
		$name = trim($_POST['name']);
		$date = trim($_POST['date']);
		$link = trim($_POST['link']);
		$steam_link = trim($_POST['steam_link']);
		$gog_link = trim($_POST['gog_link']);
		$itch_link = trim($_POST['itch_link']);
		
		// make sure its not empty
		$empty_check = core::mempty(compact('name', 'date'));
		if ($empty_check !== true)
		{
			header("Location: /admin.php?module=games&view=add&message=empty&extra=".$empty_check);
			die();
		}
		
		if (empty($link) && empty($steam_link) && empty($gog_link) && empty($itch_link))
		{
			$_SESSION['message'] = 'one_link_needed';
			header("Location: /admin.php?module=games&view=add");
			die();
		}

		$db->sqlquery("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($_POST['name']));
		if ($db->num_rows() == 1)
		{
			$get_game = $db->fetch();
			header("Location: /admin.php?module=games&view=add&message=game_submit_exists&extra={$get_game['id']}");
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$dlc = 0;
		if (isset($_POST['dlc']))
		{
			$dlc = 1;
		}

		$free_game = 0;
		if (isset($_POST['free_game']))
		{
			$free_game = 1;
		}

		$base_game = NULL;
		if (isset($_POST['game']) && is_numeric($_POST['game']))
		{
			$base_game = $_POST['game'];
		}

		$license = NULL;
		if (!empty($_POST['license']))
		{
			$license = $_POST['license'];
		}
		
		$description = trim($_POST['text']);

		$db->sqlquery("INSERT INTO `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `best_guess` = ?, `approved` = 1, `is_dlc` = ?, `base_game_id` = ?, `free_game` = ?, `license` = ?", array($name, $description, $date->format('Y-m-d'), $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $guess, $dlc, $base_game, $free_game, $license));
		$new_id = $db->grab_id();

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_addition', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $new_id));

		header("Location: /admin.php?module=games&view=add&&message=saved&extra=game");
	}
	if ($_POST['act'] == 'Edit')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=games&view=manage&message=missing_id");
			die();
		}

		if (empty($_POST['name']) || empty($_POST['date']) || (empty($_POST['link']) && empty($_POST['steam_link']) && empty($_POST['gog_link']) && empty($_POST['itch_link'])))
		{
			header("Location: /admin.php?module=games&view=edit&message=missing&id=" . $_POST['id']);
			die();
		}

		$date = new DateTime($_POST['date']);
		$edit_date = core::$sql_date_now;

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$dlc = 0;
		if (isset($_POST['dlc']))
		{
			$dlc = 1;
		}

		$free_game = 0;
		if (isset($_POST['free_game']))
		{
			$free_game = 1;
		}

		$base_game = NULL;
		if (isset($_POST['game']) && is_numeric($_POST['game']))
		{
			$base_game = $_POST['game'];
		}

		$license = NULL;
		if (!empty($_POST['license']))
		{
			$license = $_POST['license'];
		}

		$name = trim($_POST['name']);
		$description = trim($_POST['text']);

		$db->sqlquery("UPDATE `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `best_guess` = ?, `edit_date` = ?, `is_dlc` = ?, `base_game_id` = ?, `free_game` = ?, `license` = ? WHERE `id` = ?", array($name, $description, $date->format('Y-m-d'), $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $guess, $edit_date, $dlc, $base_game, $free_game, $license, $_POST['id']));
		
		$core->process_game_genres($_POST['id']);

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_edit', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['id']));
	
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

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_deletion', `created_date` = ?, `completed_date` = ?, `data` = ?, `content` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_GET['id'], $name['name']));

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
