<?php
$games_database = new game_sales($dbl, $templating, $user, $core);

$templating->load('admin_modules/games');

$licenses = array('', 'Closed Source', 'GPL', 'BSD', 'MIT');

if (isset($_GET['view']) && !isset($_POST['act']))
{
	if ($_GET['view'] == 'genres')
	{
		$templating->block('add_genre');
		
		$res = $dbl->run("SELECT `id`, `name` FROM `game_genres` ORDER BY `name` ASC")->fetch_all();
		foreach ($res as $genres)
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
		$set_empty = array('id', 'name', 'link', 'steam_link', 'gog_link', 'itch_link', 'date', 'guess_guess', 'dlc_check', 'base_game', 'free_game', 'trailer', 'trailer_link');
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
			$game = $dbl->run("SELECT c.*, b.name as base_game_name, b.id as base_game_id FROM `calendar` c LEFT JOIN `calendar` b ON c.base_game_id = b.id WHERE c.`id` = ?", array($_GET['id']))->fetch();

			if (!$game)
			{
				$core->message('That ID does not exist!');
			}
			else
			{
				if (!isset($message_map::$error) || $message_map::$error == 0)
				{
					$_SESSION['gamesdb_image_rand'] = rand();
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
				$small_pic = '';
				if ($game['small_picture'] != NULL && $game['small_picture'] != '')
				{
					$small_pic = '<img src="/uploads/gamesdb/small/'.$game['small_picture'].'" alt="" />';
				}
				$templating->set('small_pic', $small_pic);

				$templating->set('id', $game['id']);
				$templating->set('name', $game['name']);
				$templating->set('link', $game['link']);
				$templating->set('trailer', $game['trailer']);

				$trailer_link = '';
				if ($game['trailer'] != NULL && $game['trailer'] != '')
				{
					$trailer_link = '<a href="'.$game['trailer'].'" target="_blank">Trailer Link</a>';
				}
				$templating->set('trailer_link', $trailer_link);

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
		
		$res_test = $dbl->run("SELECT `name` FROM `game_genres` WHERE `name` = ?", array($genre))->fetch();
		if ($res_test)
		{
			header("Location: /admin.php?module=games&view=genres&message=genre_exists");
			die();	
		}
		
		$dbl->run("INSERT INTO `game_genres` SET `name` = ?, `accepted` = 1", array($genre));
		
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
		
		$dbl->run("UPDATE `game_genres` SET `name` = ? WHERE `id` = ?", array($genre, $_POST['genre_id']));
		
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
			
			$name = $dbl->run("SELECT `name` FROM `game_genres` WHERE `id` = ?", array($genre_id))->fetchOne();

			$core->yes_no('Are you sure you want to delete ' . $name . ' from the game genres list?', "admin.php?module=games&genre_id={$_POST['genre_id']}&return=" . $return, "delete_genre");
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
			
			$dbl->run("DELETE FROM `game_genres` WHERE `id` = ?", array($genre_id));
			$dbl->run("DELETE FROM `game_genres_reference` WHERE `genre_id` = ?", array($genre_id));
			
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

		$add_res = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($_POST['name']))->fetch();
		if ($add_res)
		{
			header("Location: /admin.php?module=games&view=add&message=game_submit_exists&extra={$add_res['id']}");
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

		$trailer = NULL;
		if (!empty(trim($_POST['trailer'])))
		{
			$trailer = $_POST['trailer'];
		}
		
		$description = trim($_POST['text']);

		$dbl->run("INSERT INTO `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `best_guess` = ?, `approved` = 1, `is_dlc` = ?, `base_game_id` = ?, `free_game` = ?, `license` = ?, `trailer` = ?", array($name, $description, $date->format('Y-m-d'), $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $guess, $dlc, $base_game, $free_game, $license, $trailer));
		$new_id = $dbl->new_id();

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_addition', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $new_id));

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'game';
		header("Location: /admin.php?module=games&view=add");
	}
	if ($_POST['act'] == 'Edit')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'game';
			header("Location: /admin.php?module=games&view=manage");
			die();
		}

		$name = $_POST['name'];
		$date = $_POST['date'];
		$empty_check = core::mempty(compact('name', 'date'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;

			header("Location: /admin.php?module=games&view=edit&id=" . $_POST['id']);
			die();
		}

		if (empty($_POST['link']) && empty($_POST['steam_link']) && empty($_POST['gog_link']) && empty($_POST['itch_link']))
		{
			$_SESSION['message'] = 'link_needed';

			header("Location: /admin.php?module=games&view=edit&id=" . $_POST['id']);
			die();
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

		$trailer = NULL;
		if (!empty(trim($_POST['trailer'])))
		{
			$trailer = $_POST['trailer'];
		}

		$name = trim($_POST['name']);
		$description = trim($_POST['text']);

		$dbl->run("UPDATE `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `best_guess` = ?, `is_dlc` = ?, `base_game_id` = ?, `free_game` = ?, `license` = ?, `trailer` = ? WHERE `id` = ?", array($name, $description, $date->format('Y-m-d'), $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $guess, $dlc, $base_game, $free_game, $license, $trailer, $_POST['id']));
		
		$core->process_game_genres($_POST['id']);

		if (isset($_SESSION['gamesdb_smallpic']) && $_SESSION['gamesdb_smallpic']['image_rand'] == $_SESSION['gamesdb_image_rand'])
		{
			$games_database->move_small($_POST['id'], $_SESSION['gamesdb_smallpic']['image_name']);
		}

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_edit', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['id']));
	
		if (isset($_GET['return']) && !empty($_GET['return']))
		{
			if ($_GET['return'] == 'calendar')
			{
				$_SESSION['message'] = 'edited';
				$_SESSION['message_extra'] = 'game';
				header("Location: /index.php?module=calendar");
			}
			if ($_GET['return'] == 'game')
			{
				$_SESSION['message'] = 'edited';
				$_SESSION['message_extra'] = 'game';
				header("Location: /index.php?module=game&game-id=" . $_POST['id']);
			}
		}
		else
		{
			$_SESSION['message'] = 'edited';
			$_SESSION['message_extra'] = 'game';
			header("Location: /admin.php?module=games&view=edit&id=" . $_POST['id']);
		}
	}
	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$name = $dbl->run("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_POST['id']))->fetchOne();

			$return = '';
			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				$return = $_GET['return'];
			}

			$core->yes_no('Are you sure you want to delete ' . $name . ' from the games database and calendar?', "admin.php?module=games&id={$_POST['id']}&return=" . $return, "Delete");
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
			$name = $dbl->run("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_GET['id']))->fetchOne();

			$dbl->run("DELETE FROM `calendar` WHERE `id` = ?", array($_GET['id']));

			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_deletion', `created_date` = ?, `completed_date` = ?, `data` = ?, `content` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_GET['id'], $name));

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
