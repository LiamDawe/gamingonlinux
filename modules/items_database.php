<?php
$templating->set_previous('title', 'Linux Games & Software List', 1);
$templating->set_previous('meta_description', 'Linux Games & Software List', 1);

$templating->load('items_database');
$templating->block('quick_links');

if (!isset($_GET['view']) && !isset($_POST['act']))
{
	$core->message("This is not the page you're looking for!");
}

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'submit')
	{
		$templating->block('submit_picker');
	}
	if ($_GET['view'] == 'submit_dev')
	{
		$templating->block('submit_developer');
	}
	if ($_GET['view'] == 'suggest_tags')
	{
		if (isset($_GET['id']))
		{
			// check exists and grab info
			$game_res = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `id` = ?", [$_GET['id']])->fetch();
			if ($game_res)
			{
				$templating->block('suggest_tags');
				$templating->set('name', $game_res['name']);
				$templating->set('id', $_GET['id']);

				$current_genres = 'None!';
				$get_genres = $core->display_game_genres($_GET['id'], false);
				if (is_array($get_genres))
				{
					$current_genres = implode(', ', $get_genres);
				}
				$templating->set('current_genres', $current_genres);
			}
		}
		else
		{
			$core->message("This is not the page you're looking for!");
		}
	}
	if ($_GET['view'] == 'submit_item')
	{
		$templating->block('submit_item');
		$licenses = $dbl->run("SELECT `license_name` FROM `item_licenses` ORDER BY `license_name` ASC")->fetch_all();
		$license_options = '';
		foreach ($licenses as $license)
		{
			$license_options .= '<option value="'.$license['license_name'].'">'.$license['license_name'].'</option>';
		}
		$templating->set('license_options', $license_options);
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'suggest_tags')
	{
		if (isset($_POST['id']))
		{
			// check exists and grab info
			$game_res = $dbl->run("SELECT `name` FROM `calendar` WHERE `id` = ?", [$_POST['id']])->fetch();
			if ($game_res)
			{
				// get fresh list of suggestions, and insert any that don't exist
				$current_suggestions = $dbl->run("SELECT `genre_id` FROM `game_genres_suggestions` WHERE `game_id` = ?", array($_POST['id']))->fetch_all(PDO::FETCH_COLUMN, 0);

				// get fresh list of current tags, insert any that don't exist
				$current_genres = $dbl->run("SELECT `genre_id` FROM `game_genres_reference` WHERE `game_id` = ?", array($_POST['id']))->fetch_all(PDO::FETCH_COLUMN, 0);				

				if (isset($_POST['genre_ids']) && !empty($_POST['genre_ids']) && core::is_number($_POST['genre_ids']))
				{
					$total_added = 0;
					foreach($_POST['genre_ids'] as $genre_id)
					{
						if (!in_array($genre_id, $current_suggestions) && !in_array($genre_id, $current_genres))
						{
							$total_added++;
							$dbl->run("INSERT INTO `game_genres_suggestions` SET `game_id` = ?, `genre_id` = ?, `suggested_time` = ?, `suggested_by_id` = ?", array($_POST['id'], $genre_id, core::$date, $_SESSION['user_id']));
						}
					}
				}

				if ($total_added > 0)
				{
					$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'submitted_game_genre_suggestion', core::$date, $_POST['id']));
				}

				$core->message('Your tag suggestions for ' . $game_res['name'] . ' have been submitted! Thank you!');
			}
			else
			{
				$core->message("This is not the page you're looking for!");
			}
		}
	}
	if ($_POST['act'] == 'submit_item')
	{
		$name = trim($_POST['name']);
		$link = trim($_POST['link']);
		$steam_link = trim($_POST['steam_link']);
		$gog_link = trim($_POST['gog_link']);
		$itch_link = trim($_POST['itch_link']);
		
		// make sure its not empty
		$empty_check = core::mempty(compact('name'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: /index.php?module=items_database&view=submit_item");
			die();
		}
		
		if (empty($link) && empty($steam_link) && empty($gog_link) && empty($itch_link))
		{
			$_SESSION['message'] = 'one_link_needed';
			header("Location: /index.php?module=items_database&view=submit_item");
			die();
		}

		$add_res = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($_POST['name']))->fetch();
		if ($add_res)
		{
			$_SESSION['message'] = 'item_submit_exists';
			header("Location: /index.php?module=items_database&view=submit_item");
			exit;
		}

		$dlc = 0;
		if (isset($_POST['dlc']))
		{
			$dlc = 1;
		}

		$free = 0;
		if (isset($_POST['free']))
		{
			$free = 1;
		}

		$application = 0;
		if (isset($_POST['application']))
		{
			$application = 1;
		}

		$emulator = 0;
		if (isset($_POST['emulator']))
		{
			$emulator = 1;
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

		$dbl->run("INSERT INTO `calendar` SET `name` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `approved` = 0, `is_dlc` = ?, `base_game_id` = ?, `free_game` = ?, `is_application` = ?, `is_emulator` = ?, `license` = ?", array($name, $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $dlc, $base_game, $free, $application, $emulator, $license));
		$new_id = $dbl->new_id();

		$core->process_game_genres($new_id);

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = 'item_database_addition', `created_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, $new_id));

		$_SESSION['message'] = 'item_submitted';
		$_SESSION['message_extra'] = $name;
		header("Location: /index.php?module=items_database&view=submit_item");		
	}

	if ($_POST['act'] == 'submit_dev')
	{
		// make sure its not empty
		$name = trim($_POST['name']);
		if (empty($name))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'developer/publisher name';
			header("Location: /index.php?module=items_database&view=submit_dev");
			die();
		}
		
		$link = trim($_POST['link']);

		$add_res = $dbl->run("SELECT `name` FROM `developers` WHERE `name` = ?", array($name))->fetch();
		if ($add_res)
		{
			$_SESSION['message'] = 'dev_submit_exists';
			header("Location: /index.php?module=items_database&view=submit_dev");
			die();
		}

		$dbl->run("INSERT INTO `developers` SET `name` = ?, `website` = ?, `approved` = 0", [$name, $link]);

		$new_id = $dbl->new_id();

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = 'dev_database_addition', `created_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, $new_id));

		$_SESSION['message'] = 'dev_submitted';
		$_SESSION['message_extra'] = $name;
		header("Location: /index.php?module=items_database&view=submit_dev");			
	}
}