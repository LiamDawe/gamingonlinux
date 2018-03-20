<?php
$games_database = new game_sales($dbl, $templating, $user, $core);

$templating->load('admin_modules/games');

if (isset($_GET['view']) && !isset($_POST['act']))
{
	if ($_GET['view'] == 'search')
	{
		$templating->set_previous('meta_description', 'Search the entire games database', 1);
		$templating->set_previous('title', 'Searching the entire games database', 1);

		$templating->block('quick_links');
		
		$templating->block('search');
		$search_text = '';
		if (isset($_POST['q']) && !empty($_POST['q']))
		{
			$search_text = trim($_POST['q']);
		}
		$templating->set('search_text', $search_text);

		if (isset($_POST['q']) && !empty($_POST['q']))
		{
			$text_sql = '%'.$search_text.'%';
			$find_items = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ?", [$text_sql])->fetch_all();		
			if ($find_items)
			{
				$templating->block('search_result_top');
				foreach ($find_items as $item)
				{
					$templating->block('search_items');
					$templating->set('name', $item['name']);
					$templating->set('id', $item['id']);
				}
				$templating->block('search_result_bottom');
			}
			else
			{
				$core->message('Nothing found matching that name!');
			}		
		}

	}
	if ($_GET['view'] == 'add')
	{
		if (!isset($message_map::$error) || $message_map::$error == 0)
		{
			$_SESSION['gamesdb_image_rand'] = rand();
		}

		$templating->set_previous('meta_description', 'Adding a new game', 1);
		$templating->set_previous('title', 'Adding a game to the database', 1);

		$templating->block('quick_links');

		$templating->block('add_top', 'admin_modules/games');

		$templating->block('item', 'admin_modules/games');

		// all these need to be empty, as it's a new game
		$set_empty = array('id', 'name', 'link', 'steam_link', 'gog_link', 'itch_link', 'date', 'best_guess_check', 'is_dlc_check', 'base_game', 'free_game_check', 'trailer', 'trailer_link','small_pic', 'supports_linux_check', 'is_hidden_steam_check');
		foreach ($set_empty as $make_empty)
		{
			$templating->set($make_empty, '');
		}

		$types = ['is_game' => "Game", 'is_application' => "Misc Software or Application", 'is_emulator' => "Emulator"];
		$type_options = '';
		foreach ($types as $value => $text)
		{
			$type_options .= '<option value="'.$value.'">'.$text.'</option>';
		}
		$templating->set('type_options', $type_options);

		$licenses = $dbl->run("SELECT `license_name` FROM `item_licenses` ORDER BY `license_name` ASC")->fetch_all();
		$license_options = '';
		foreach ($licenses as $license)
		{
			$license_options .= '<option value="'.$license['license_name'].'">'.$license['license_name'].'</option>';
		}
		$templating->set('license_options', $license_options);

		$core->article_editor(['content' => '']);

		$templating->block('add_bottom', 'admin_modules/games');
	}
	if ($_GET['view'] == 'tag_suggestions')
	{
		$templating->block('quick_links');

		$game_res = $dbl->run("SELECT g.id, g.name, c.`category_name`, c.category_id, s.`suggested_by_id` FROM calendar g INNER JOIN `game_genres_suggestions` s ON s.game_id = g.id INNER JOIN `articles_categorys` c ON s.genre_id = c.category_id GROUP BY s.suggested_by_id, g.`id`, c.category_id")->fetch_all(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		if ($game_res)
		{
			foreach ($game_res as $id => $tags)
			{
				$templating->block('suggest_tags');
				$templating->set('name', $tags[0]['name']);
				$templating->set('id', $id);

				$current_genres = 'None!';
				$get_genres = $core->display_game_genres($id, false);
				if (is_array($get_genres))
				{
					$current_genres = implode(', ', $get_genres);
				}
				$templating->set('current_genres', $current_genres);

				$suggested_list = '';
				foreach ($tags as $tag)
				{
					$suggested_list .= '<option value="'.$tag['category_id'].'" selected>'.$tag['category_name'].'</option>';
				}
				$templating->set('suggested_list', $suggested_list);
				$templating->set('time', core::$date);
				$templating->set('suggested_by_id', $tags[0]['suggested_by_id']);
			}
		}
		else
		{
			$core->message('No current suggestions!');
		}
	}
	if ($_GET['view'] == 'edit')
	{
		$templating->block('quick_links');

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

				$checkboxes_names = ['supports_linux', 'is_hidden_steam', 'best_guess', 'is_dlc', 'free_game'];
				foreach ($checkboxes_names as $check)
				{
					$status = '';
					if ($game[$check] == 1)
					{
						$status = 'checked';
					}
					$templating->set($check.'_check', $status);
				}

				$types = ['is_game' => "Game", 'is_application' => "Misc Software or Application", 'is_emulator' => "Emulator"];
				$type_options = '';
				foreach ($types as $value => $text)
				{
					$selected = '';
					if ($game[$value] == 1)
					{
						$selected = 'selected';
					}
					$type_options .= '<option value="'.$value.'" '.$selected.'>'.$text.'</option>';
				}
				$templating->set('type_options', $type_options);

				$licenses = $dbl->run("SELECT `license_name` FROM `item_licenses` ORDER BY `license_name` ASC")->fetch_all();
				$license_options = '';
				foreach ($licenses as $license)
				{
					$selected = '';
					if ($game['license'] == $license)
					{
						$selected = 'selected';
					}
					$license_options .= '<option value="'.$license['license_name'].'" '.$selected.'>'.$license['license_name'].'</option>';
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

				$core->article_editor(['content' => $text]);

				$templating->block('edit_bottom', 'admin_modules/games');
				$templating->set('return', $return);
				$templating->set('id', $game['id']);
			}
		}
	}
	if ($_GET['view'] == 'submitted_list')
	{
		$templating->block('quick_links');

		$templating->block('submitted_list_top');
		$submitted_list = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `approved` = 0")->fetch_all();
		foreach ($submitted_list as $row)
		{
			$templating->block('submitted_row');
			$templating->set('type', 'Game/Software');
			$templating->set('view', 'submitted_item');
			$templating->set('name', $row['name']);
			$templating->set('id', $row['id']);
		}

		$submitted_list = $dbl->run("SELECT `id`, `name` FROM `developers` WHERE `approved` = 0")->fetch_all();
		foreach ($submitted_list as $row)
		{
			$templating->block('submitted_row');
			$templating->set('type', 'Developer/Publisher');
			$templating->set('view', 'submitted_dev');
			$templating->set('name', $row['name']);
			$templating->set('id', $row['id']);
		}
	}
	if ($_GET['view'] == 'submitted_dev')
	{
		$templating->block('quick_links');

		$templating->block('submitted_list_top');

		if (!isset($_GET['id']) || !is_numeric($_GET['id']))
		{
			$core->message('Not ID set, you shouldn\'t be here!');
		}
		else
		{
			$templating->block('submit_developer');
			$info = $dbl->run("SELECT `id`, `name`, `website` FROM `developers` WHERE `id` = ?", [$_GET['id']])->fetch();
			$templating->set('name', $info['name']);
			$templating->set('link', $info['website']);
			$templating->set('id', $info['id']);
		}
	}
	if ($_GET['view'] == 'submitted_item')
	{
		$templating->block('quick_links');

		$templating->block('submitted_list_top');

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

				$templating->block('submitted_top', 'admin_modules/games');
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

				$date = '';
				if (isset($game['date']) && !empty($game['date']))
				{
					$date = new DateTime($game['date']);
					$date = $date->format('d-m-Y');
				}
				$templating->set('date', $date);

				$supports_linux = '';
				if ($game['supports_linux'] == 1)
				{
					$supports_linux = 'checked';
				}
				$templating->set('supports_linux', $supports_linux);

				$hidden_steam = '';
				if ($game['is_hidden_steam'] == 1)
				{
					$hidden_steam = 'checked';
				}
				$templating->set('hidden_steam', $hidden_steam);

				$types = ['is_game' => "Game", 'is_application' => "Misc Software or Application", 'is_emulator' => "Emulator"];
				$type_options = '';
				foreach ($types as $value => $text)
				{
					$selected = '';
					if ($game[$value] == 1)
					{
						$selected = 'selected';
					}
					$type_options .= '<option value="'.$value.'" '.$selected.'>'.$text.'</option>';
				}
				$templating->set('type_options', $type_options);

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

				$licenses = $dbl->run("SELECT `license_name` FROM `item_licenses` ORDER BY `license_name` ASC")->fetch_all();
				$license_options = '';
				foreach ($licenses as $license)
				{
					$selected = '';
					if ($game['license'] == $license)
					{
						$selected = 'selected';
					}
					$license_options .= '<option value="'.$license['license_name'].'" '.$selected.'>'.$license['license_name'].'</option>';
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

				$templating->block('submitted_bottom', 'admin_modules/games');
				$templating->set('id', $game['id']);
			}
		}		
	}
}

if (isset($_POST['act']))
{	
	// for the action of adding, editing and approving new items
	if ($_POST['act'] == 'Add' || $_POST['act'] == 'Edit' || $_POST['act'] == 'Approve')
	{
		$name = trim($_POST['name']);
		$description = trim($_POST['text']);
		$date = trim($_POST['date']);
		$link = trim($_POST['link']);
		$steam_link = trim($_POST['steam_link']);
		$gog_link = trim($_POST['gog_link']);
		$itch_link = trim($_POST['itch_link']);

		if ($_POST['act'] == 'Edit' || $_POST['act'] == 'Approve')
		{
			if (empty($_POST['id']) || !is_numeric($_POST['id']))
			{
				$_SESSION['message'] = 'no_id';
				$_SESSION['message_extra'] = 'game';
				header("Location: /admin.php?module=games&view=manage");
				die();
			}
		}

		if ($_POST['act'] == 'Add')
		{
			$finish_page = '/admin.php?module=games&view=add';
			$error_page = $finish_page;
		}
		if ($_POST['act'] == 'Edit')
		{
			$finish_page = '/admin.php?module=games&view=edit&id=' . $_POST['id'];
			$error_page = $finish_page;
		}
		if ($_POST['act'] == 'Approve')
		{
			$finish_page = '/admin.php?module=games&view=submitted_list';
			$error_page = '/admin.php?module=games&view=submitted_item&id=' . $_POST['id'];
		}

		// make sure its not empty
		$empty_check = core::mempty(compact('name'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: $error_page");
			die();
		}

		if ($_POST['act'] == 'Add')
		{
			$add_res = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($name))->fetch();
			if ($add_res)
			{
				$_SESSION['message'] = 'game_add_exists';
				$_SESSION['message_extra'] = $add_res['id'];
				header("Location: $error_page");
				die();
			}
		}

		if (empty($link) && empty($steam_link) && empty($gog_link) && empty($itch_link))
		{
			$_SESSION['message'] = 'one_link_needed';
			header("Location: $error_page");
			die();
		}

		$sql_date = NULL;
		if (!empty($date))
		{
			$date = new DateTime($date);
			$sql_date = $date->format('Y-m-d');
		}

		$checkboxes_names = ['supports_linux', 'is_hidden_steam', 'best_guess', 'is_dlc', 'free_game'];
		$checkboxes_sql = [];
		foreach ($checkboxes_names as $check)
		{
			if (isset($_POST[$check]))
			{
				$checkboxes_sql[] = ' `'.$check.'` = 1';
			}
			else
			{
				$checkboxes_sql[] = ' `'.$check.'` = 0';
			}
		}
		$checkboxes_sql_insert = implode(', ', $checkboxes_sql);

		$types = ['is_game', 'is_application', 'is_emulator'];
		$sql_type = '';
		if (!in_array($_POST['type'], $types))
		{
			$_SESSION['message'] = 'no_item_type';
			header("Location: $error_page");
			die();
		}
		else
		{
			$sql_type = '`'.$_POST['type'].'` = 1, ';
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

		if ($_POST['act'] == 'Add')
		{
			$dbl->run("INSERT INTO `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `approved` = 1, `base_game_id` = ?, $sql_type `license` = ?, `trailer` = ?, $checkboxes_sql_insert", array($name, $description, $sql_date, $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $base_game, $license, $trailer));
			$new_id = $dbl->new_id();
	
			$core->process_game_genres($new_id);
	
			if (isset($_SESSION['gamesdb_smallpic']) && $_SESSION['gamesdb_smallpic']['image_rand'] == $_SESSION['gamesdb_image_rand'])
			{
				$games_database->move_small($new_id, $_SESSION['gamesdb_smallpic']['image_name']);
			}
	
			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_addition', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $new_id));
	
			$_SESSION['message'] = 'saved';
			$_SESSION['message_extra'] = 'game';
		}

		if ($_POST['act'] == 'Edit')
		{
			$dbl->run("UPDATE `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `base_game_id` = ?, $sql_type `license` = ?, `trailer` = ?, $checkboxes_sql_insert WHERE `id` = ?", array($name, $description, $sql_date, $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $base_game, $license, $trailer, $_POST['id']));
		
			$core->process_game_genres($_POST['id']);
	
			if (isset($_SESSION['gamesdb_smallpic']) && $_SESSION['gamesdb_smallpic']['image_rand'] == $_SESSION['gamesdb_image_rand'])
			{
				$games_database->move_small($_POST['id'], $_SESSION['gamesdb_smallpic']['image_name']);
			}
	
			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_edit', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['id']));

			$_SESSION['message'] = 'edited';
			$_SESSION['message_extra'] = 'game';	
		}

		if ($_POST['act'] == 'Approve')
		{
			$dbl->run("UPDATE `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `base_game_id` = ?, $sql_type `license` = ?, `trailer` = ?, `approved` = 1, $checkboxes_sql_insert WHERE `id` = ?", array($name, $description, $sql_date, $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $base_game, $license, $trailer, $_POST['id']));
		
			$core->process_game_genres($_POST['id']);
	
			if (isset($_SESSION['gamesdb_smallpic']) && $_SESSION['gamesdb_smallpic']['image_rand'] == $_SESSION['gamesdb_image_rand'])
			{
				$games_database->move_small($_POST['id'], $_SESSION['gamesdb_smallpic']['image_name']);
			}
	
			// update the original notification to clear it
			$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'item_database_addition'", [core::$date, $_POST['id']]);
	
			// note who approved this item for the database
			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'item_database_approved', `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['id']));
		
			$_SESSION['message'] = 'submit_approved';	
		}

		header("Location: $finish_page");
		die();
	}
	if ($_POST['act'] == 'approve_tags')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'game';
			header("Location: /admin.php?module=games&view=tag_suggestions");
			die();
		}

		if (isset($_POST['genre_ids']) && !empty($_POST['genre_ids']))
		{
			$to_remove = [];
			// find the difference between the accepted tags and the last submitted items we saw
			$suggestions_seen = $dbl->run("SELECT `genre_id` FROM `game_genres_suggestions` WHERE `suggested_time` <= ?", [$_POST['current_time']])->fetch_all(PDO::FETCH_COLUMN, 0);

			// remove suggested tags we have hit the little x on when approving, to remove them and approve what was left
			foreach ($suggestions_seen as $suggest)
			{
				if (!in_array($suggest, $_POST['genre_ids']))
				{
					$to_remove[] = $suggest;
				}
			}
			if (!empty($to_remove))
			{
				$in  = str_repeat('?,', count($to_remove) - 1) . '?';
				$merged_array = array_merge([$_POST['id']], $to_remove);
				$dbl->run("DELETE FROM `game_genres_suggestions` WHERE `game_id` = ? AND `genre_id` IN ($in)", $merged_array);
			}

			foreach ($_POST['genre_ids'] as $genre_id)
			{
				// ensure it isn't already tagged (perhaps an editor recently added it manually?)
				$check_exists = $dbl->run("SELECT `id` FROM `game_genres_reference` WHERE `game_id` = ? AND `genre_id` = ?", [$_POST['id'], $genre_id])->fetchOne();
				if (!$check_exists)
				{
					$dbl->run("INSERT INTO `game_genres_reference` SET `game_id` = ?, `genre_id` = ?", [$_POST['id'], $genre_id]);
				}
				
				// no matter what, if we actually approved it, or silently ignored it (due to the above) - remove the suggestion
				$dbl->run("DELETE FROM `game_genres_suggestions` WHERE `game_id` = ? AND `genre_id` = ?", [$_POST['id'], $genre_id]);
			}

			// now set the admin notification as read, for anyone who submitted tags up until we saw them on this item
			$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'submitted_game_genre_suggestion' AND `data` = ? AND `created_date` <= ?", array(core::$date, $_POST['id'], $_POST['current_time']));

			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `created_date` = ?, `completed_date` = ?, `completed` = 1, `type` = 'approved_gametag_submission', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['id']));

			$_SESSION['message'] = 'tags_approved';
			header("Location: /admin.php?module=games&view=tag_suggestions");
		}
		else
		{
			header("Location: /admin.php?module=games&view=tag_suggestions");
		}
	}
	if ($_POST['act'] == 'deny_tags')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'game';
			header("Location: /admin.php?module=games&view=tag_suggestions");
			die();
		}

		// delete all suggested tags for this game, that were suggested up until we saw them (so we don't deny submissions we haven't seen since coming here)
		$dbl->run("DELETE FROM `game_genres_suggestions` WHERE `game_id` = ? AND `suggested_time` <= ?", [$_POST['id'], $_POST['current_time']]);

		// now set the admin notification as read, for anyone who submitted tags up until we saw them on this item
		$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'submitted_game_genre_suggestion' AND `data` = ? AND `created_date` <= ?", array(core::$date, $_POST['id'], $_POST['current_time']));

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `created_date` = ?, `completed_date` = ?, `completed` = 1, `type` = 'denied_gametag_submission', `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['id']));

		$_SESSION['message'] = 'tags_denied';
		header("Location: /admin.php?module=games&view=tag_suggestions");
	}
	if ($_POST['act'] == 'Deny')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$name = $dbl->run("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_POST['id']))->fetchOne();

			$core->yes_no('Are you sure you want to deny ' . $name . ' from being included in the games and software database?', "admin.php?module=games&id={$_POST['id']}", "Deny");
		}

		else if (isset($_POST['no']))
		{
			header("Location: /admin.php?module=games&view=submitted_list");
			die();
		}

		else if (isset($_POST['yes']))
		{
			$info = $dbl->run("SELECT `name`, `small_picture` FROM `calendar` WHERE `id` = ?", array($_GET['id']))->fetch();

			// delete any image attached to it
			if (!empty($info['small_picture']))
			{
				$filename_remove = $core->config('path') . "uploads/gamesdb/small/" . $info['small_picture'];
				if (file_exists($filename_remove))
				{
					unlink($filename_remove);
				}
			}

			if (isset($_SESSION['gamesdb_smallpic']))
			{
				$filename_remove = $core->config('path') . "uploads/gamesdb/small/temp/" . $_SESSION['gamesdb_smallpic']['image_name'];
				if (file_exists($filename_remove))
				{
					unlink($filename_remove);
				}
				unset($_SESSION['gamesdb_smallpic']);
			}

			$dbl->run("DELETE FROM `calendar` WHERE `id` = ?", array($_GET['id']));

			// delete any tags attached to it
			$dbl->run("DELETE FROM `game_genres_reference` WHERE `game_id` = ?", [$_GET['id']]);

			// update the original notification to clear it
			$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `data` = ? AND `type` = 'item_database_addition'", [core::$date, $_GET['id']]);

			// note who denied this item for the database
			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'item_database_denied', `created_date` = ?, `completed_date` = ?, `data` = ?, `content` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_GET['id'], $info['name']));

			header("Location: /admin.php?module=games&view=submitted_list");
			die();
		}
	}
	if ($_POST['act'] == 'approve_dev')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'game';
			header("Location: /admin.php?module=games&view=submitted_list");
			die();
		}

		// make sure its not empty
		$name = trim($_POST['name']);
		if (empty($name))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'developer/publisher name';
			header("Location: /admin.php?module=games&view=submitted_list");
			die();
		}
		
		$link = trim($_POST['link']);

		$add_res = $dbl->run("SELECT `name` FROM `developers` WHERE `name` = ? AND `approved` = 1", array($name))->fetch();
		if ($add_res)
		{
			$_SESSION['message'] = 'dev_approve_exists';
			header("Location: /admin.php?module=games&view=submitted_list");
			die();
		}

		$dbl->run("UPDATE `developers` SET `approved` = 1, `name` = ?, `website` = ? WHERE `id` = ?", [$name, $link, $_POST['id']]);

		// update original notification
		$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'dev_database_addition' AND `data` = ?", array(core::$date, $_POST['id']));

		// make new notification for who did this
		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'dev_database_approve', `created_date` = ?, `completed_date` = ?, `data` = ?, `content` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_POST['id'], $name));		

		$_SESSION['message'] = 'submit_approved';
		header("Location: /admin.php?module=games&view=submitted_list");
	}
	if ($_POST['act'] == 'deny_dev')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$name = $dbl->run("SELECT `name` FROM `developers` WHERE `id` = ?", array($_POST['id']))->fetchOne();

			$core->yes_no('Are you sure you want to deny ' . $name . ' from being included in the developer/publisher database?', "admin.php?module=games&id={$_POST['id']}", "deny_dev");
		}

		else if (isset($_POST['no']))
		{
			header("Location: /admin.php?module=games&view=submitted_list");
			die();
		}

		else if (isset($_POST['yes']))
		{
			$info = $dbl->run("SELECT `name` FROM `developers` WHERE `id` = ?", array($_GET['id']))->fetch();
			if ($info)
			{
				$dbl->run("DELETE FROM `developers` WHERE `id` = ?", array($_GET['id']));

				// update original notification
				$dbl->run("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'dev_database_addition' AND `data` = ?", array(core::$date, $_GET['id']));

				// make new notification for who did this
				$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'dev_database_denied', `created_date` = ?, `completed_date` = ?, `data` = ?, `content` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_GET['id'], $info['name']));		

				$_SESSION['message'] = 'dev_denied';
				header("Location: /admin.php?module=games&view=submitted_list");
				die();
			}
			else
			{
				$_SESSION['message'] = 'dev_doesnt_exist';
				header("Location: /admin.php?module=games&view=submitted_list");
				die();				
			}
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
			$info = $dbl->run("SELECT `name`, `small_picture` FROM `calendar` WHERE `id` = ?", array($_GET['id']))->fetch();

			// delete any image attached to it
			if (!empty($info['small_picture']))
			{
				$filename_remove = $core->config('path') . "uploads/gamesdb/small/" . $info['small_picture'];
				if (file_exists($filename_remove))
				{
					unlink($filename_remove);
				}
			}

			if (isset($_SESSION['gamesdb_smallpic']))
			{
				$filename_remove = $core->config('path') . "uploads/gamesdb/small/temp/" . $_SESSION['gamesdb_smallpic']['image_name'];
				if (file_exists($filename_remove))
				{
					unlink($filename_remove);
				}
				unset($_SESSION['gamesdb_smallpic']);
			}

			$dbl->run("DELETE FROM `calendar` WHERE `id` = ?", array($_GET['id']));

			// delete any tags attached to it
			$dbl->run("DELETE FROM `game_genres_reference` WHERE `game_id` = ?", [$_GET['id']]);

			$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'game_database_deletion', `created_date` = ?, `completed_date` = ?, `data` = ?, `content` = ?", array($_SESSION['user_id'], core::$date, core::$date, $_GET['id'], $info['name']));

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
