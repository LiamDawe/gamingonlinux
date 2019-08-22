<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
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
	if ($_GET['view'] == 'developer')
	{
		if (!isset($_GET['id']) || !core::is_number($_GET['id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'item id';
			header("Location: /index.php");
			die();
		}
		
		// make sure they exist
		$check_dev = $dbl->run("SELECT `name` FROM `developers` WHERE `id` = ?", array($_GET['id']))->fetchOne();
		if (!$check_dev)
		{
			$_SESSION['message'] = 'none_found';
			$_SESSION['message_extra'] = 'developers matching that ID';
			header("Location: /index.php");
			die();			
		}

		$templating->set_previous('meta_description', 'GamingOnLinux Games & Software database: '.$check_dev, 1);
		$templating->set_previous('title', $check_dev, 1);	

		$templating->block('developer_list_top');
		$templating->set('dev_name', $check_dev);

		// look for some games
		$get_item = $dbl->run("select c.`name` from `calendar` c JOIN `game_developer_reference` d WHERE d.game_id = c.id AND d.developer_id = ?", array($_GET['id']))->fetch_all();
		if ($get_item)
		{
			foreach ($get_item as $game)
			{
				$templating->block('dev_game_row');
				$templating->set('name', $game['name']);
			}
		}	
		else
		{
			$core->message('No games were found in our database from that developer.');
		}
	}
	if ($_GET['view'] == 'item')
	{
		if (!isset($_GET['id']) || !core::is_number($_GET['id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'item id';
			header("Location: /index.php");
			die();
		}
		
		// make sure it exists
		$get_item = $dbl->run("SELECT c.`id`, c.`name`, c.`date`, c.`gog_link`, c.`steam_link`, c.`link`, c.`itch_link`, c.`description`, c.`best_guess`, c.`is_dlc`, c.`free_game`, c.`license`, c.`supports_linux`, c.`is_hidden_steam`, b.`name` as base_game_name, b.`id` as base_game_id FROM `calendar` c LEFT JOIN `calendar` b ON c.`base_game_id` = b.`id` WHERE c.`id` = ? AND c.`approved` = 1", array($_GET['id']))->fetch();
		if ($get_item)
		{
			$templating->set_previous('meta_description', 'GamingOnLinux Games & Software database: '.$get_item['name'], 1);
			$templating->set_previous('title', $get_item['name'], 1);

			if ($get_item['supports_linux'] == 0)
			{
				$core->message("This item does not currently support Linux! It's here in case it ever does, or it may be included in a GOTY Award category for some form of port wishlist (you get the idea). If it shows up in any of our lists, please let us know (it shouldn't!).", 2);
			}

			if ($get_item['is_hidden_steam'] == 1)
			{
				$core->message("This item is not advertised on Steam as supporting Linux, but it does have a Linux version.", 2);
			}

			$templating->block('item_view_top', 'items_database');
			$templating->set('name', $get_item['name']);

			// sort out price
			$free_item = '';
			if ($get_item['free_game'] == 1)
			{
				$free_item = '<span class="badge blue">FREE</span>';
			}
			$templating->set('free_item', $free_item);

			$dlc = '';
			if ($get_item['is_dlc'] == 1)
			{
				$dlc = '<span class="badge yellow">DLC</span>';
			}
			$templating->set('dlc', $dlc);

			$edit_link = '';
			if ($user->check_group([1,2,5]))
			{
				$edit_link = '<a class="fright" href="/admin.php?module=games&amp;view=edit&amp;id=' . $get_item['id'] . '&return=view_item">Edit</a>';
			}
			$templating->set('edit-link', $edit_link);

			if ($get_item['base_game_id'] != NULL && $get_item['base_game_id'] != 0)
			{
				$templating->block('base_game', 'items_database');
				$templating->set('base_game_id', $get_item['base_game_id']);
				$templating->set('base_game_name', $get_item['base_game_name']);
			}

			$templating->block('main-info', 'items_database');

			// parse the release date, with any info tags about it
			$date = '';
			if (!empty($get_item['date']))
			{
				$unreleased = '';
				if ($get_item['date'] > date('Y-m-d'))
				{
					$unreleased = '<span class="badge blue">Unreleased!</span>';
				}
				$best_guess = '';
				if ($get_item['best_guess'] == 1)
				{
					$best_guess = '<span class="badge blue">Best Guess Date!</span>';
				}
				$date = '<li>' . $get_item['date'] . ' ' . $best_guess . $unreleased . '</li>';
			}
			$templating->set('release-date', $date);

			// sort out license
			$license = '';
			if (!empty($get_item['license']) || $get_item['license'] != NULL)
			{
				$license = $get_item['license'];
			}
			if (!empty($license))
			{
				$license = '<li><strong>License</strong></li><li>' . $license . '</li>';
			}
			$templating->set('license', $license);

			// sort out the external links we have for it
			$external_links = '';
			$links_array = [];
			$link_types = ['link' => 'Official Site', 'gog_link' => 'GOG', 'steam_link' => 'Steam', 'itch_link' => 'itch.io'];
			foreach ($link_types as $key => $text)
			{
				if (!empty($get_item[$key]))
				{
					$links_array[] = '<a href="'.$get_item[$key].'">'.$text.'</a>';
				}
			}

			if (!empty($links_array))
			{
				$external_links = implode(', ', $links_array);
			}
			$templating->set('external_links', $external_links);

			// sort out genres
			$genres_output = '';
			$genres_array = [];
			$genres_res = $dbl->run("SELECT g.`category_name`, g.`category_id` FROM `articles_categorys` g INNER JOIN `game_genres_reference` r ON r.genre_id = g.category_id WHERE r.`game_id` = ?", array($get_item['id']))->fetch_all();
			if ($genres_res)
			{
				$genres_output = '<li><strong>Genres</strong></li><li>';
				foreach ($genres_res as $genre)
				{
					$genres_array[] = $genre['category_name'];
				}
				$genres_output .= implode(', ', $genres_array);
				$genres_output .= '</li>';
			}
			$templating->set('genres', $genres_output);			

			$description = '';
			if (!empty($get_item['description']) && $get_item['description'] != NULL)
			{
				$description = '<strong>About this game</strong>:<br />' . $get_item['description'];
			}
			$templating->set('description', $description);

			// find any associations
			$get_associations = $dbl->run("SELECT `name` FROM `calendar` WHERE `also_known_as` = ?", array($get_item['id']))->fetch_all();
			$same_games = array();
			if ($get_associations)
			{
				$templating->block('associations');
				foreach ($get_associations as $associations)
				{
					$same_games[] = $associations['name'];
				}
				$templating->set('games', implode(', ', $same_games));
			}

			// see if it's on sale
			$sales_res = $dbl->run("SELECT s.`link`, s.`sale_dollars`, s.`original_dollars`, s.`sale_pounds`, s.`original_pounds`, s.`sale_euro`, s.`original_euro`, st.`name` as `store_name` FROM `sales` s INNER JOIN `game_stores` st ON s.store_id = st.id WHERE s.accepted = 1 AND s.game_id = ?", [$get_item['id']])->fetch_all();
			if ($sales_res)
			{
				$templating->block('sales', 'items_database');
				$sales_list = '';
				$currencies = ['dollars', 'pounds', 'euro'];
				foreach ($sales_res as $sale)
				{
					$currency_list = [];
					foreach ($currencies as $currency)
					{
						$savings = '';
						if ($sale['sale_'.$currency] != NULL)
						{
							if ($sale['original_'.$currency] != 0)
							{
								$savings = 1 - ($sale['sale_'.$currency] / $sale['original_'.$currency]);
								$savings = round($savings * 100) . '% off';
							}
							$front_sign = NULL;
							$back_sign = NULL;
							if ($currency == 'dollars')
							{
								$front_sign = '&dollar;';
							}
							else if ($currency == 'euro')
							{
								$back_sign = '&euro;';
							}
							else if ($currency == 'pounds')
							{
								$front_sign = '&pound;';
							}
							$currency_list[] = '<span class="badge">'. $front_sign . $sale['sale_'.$currency] . $back_sign . ' ' . $savings . '</span>';
						}
					}
					$sales_list .= '<li><a href="' . $sale['link'] . '">'.$sale['store_name'].'</a> - '.implode(' ', $currency_list).'</li>';
				}
				$templating->set('sales_list', $sales_list);
			}

			$get_item['name'] = trim($get_item['name']);
			$articles_res = $dbl->run("SELECT a.`author_id`, a.`article_id`, a.`title`, a.`slug`, a.`guest_username`, u.`username` FROM `article_item_assoc` g LEFT JOIN `calendar` c ON c.id = g.game_id LEFT JOIN `articles` a ON a.article_id = g.article_id LEFT JOIN `users` u ON u.user_id = a.author_id WHERE c.name = ? AND a.active = 1 ORDER BY a.article_id DESC", array($get_item['name']))->fetch_all();
			if ($articles_res)
			{
				$article_list = '';
				$templating->block('articles', 'items_database');
				foreach ($articles_res as $articles)
				{
					$article_link = $article_class->get_link($articles['article_id'], $articles['slug']);

					if ($articles['author_id'] == 0)
					{
						$username = $articles['guest_username'];
					}

					else
					{
						$username = "<a href=\"/profiles/{$articles['author_id']}\">" . $articles['username'] . '</a>';
					}

					$article_list .= '<li><a href="' . $article_link . '">'.$articles['title'].'</a> by '.$username.'</li>';
				}
				$templating->set('articles', $article_list);
			}

			if ($user->check_group([1,2,5]))
			{
				$templating->block('main_info_bottom', 'items_database');
				$templating->set('edit-link', $edit_link);
			}
		}
		else
		{
			$templating->set_previous('meta_description', 'Game does not exist - GamingOnLinux Linux games database,', 1);
			$templating->set_previous('title', 'Game does not exist - GamingOnLinux Linux games database', 1);
			$core->message("That game id does not exist!", NULL, 1);
		}
	}
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
			$get_item_res = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `id` = ?", [$_GET['id']])->fetch();
			if ($get_item_res)
			{
				$templating->block('suggest_tags');
				$templating->set('name', $get_item_res['name']);
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

		// there was an error on submission, populate fields with previous data
		if (isset($message_map::$error) && $message_map::$error >= 1 && isset($_SESSION['item_post_data']))
		{
			//print_r($_SESSION['item_post_data']);
			foreach ($_SESSION['item_post_data'] as $key => $previous_data)
			{
				if ($previous_data == 'on')
				{
					$previous_data = 'checked';
				}
				$templating->set($key, $previous_data);
			}
		}
		// clear any old info left in session from previous error when submitting, make fields blank
		else
		{
			unset($_SESSION['item_post_data']); 
			$templating->set_many(array('name' => '', 'link' => '', 'steam_link' => '', 'gog_link' => '', 'itch_link' => '', 'supports_linux' => 'checked', 'hidden_steam' => ''));
		}

		$types_options = '';
		$item_types = array('is_game' => 'Game', 'is_application' => 'Misc Software or Application', 'is_emulator' => 'Emulator');
		foreach ($item_types as $key => $value)
		{
			$selected = '';
			if (isset($_SESSION['item_post_data']['type']) && $_SESSION['item_post_data']['type'] == $key)
			{
				$selected = 'selected';
			}
			$types_options .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		$templating->set('types_options', $types_options);

		$licenses = $dbl->run("SELECT `license_name` FROM `item_licenses` ORDER BY `license_name` ASC")->fetch_all();
		$license_options = '';
		foreach ($licenses as $license)
		{
			$selected = '';
			if (isset($_SESSION['item_post_data']['license']) && $_SESSION['item_post_data']['license'] == $license['license_name'])
			{
				$selected = 'selected';
			}
			$license_options .= '<option value="'.$license['license_name'].'" '.$selected.'>'.$license['license_name'].'</option>';
		}
		$templating->set('license_options', $license_options);

		// game name if DLC
		if (isset($_SESSION['item_post_data']['game']))
		{
			$check_exists = $dbl->run("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_SESSION['item_post_data']['game']))->fetch();
			if ($check_exists)
			{
				$game = '<option value="'.$_SESSION['item_post_data']['game'].'">'.$check_exists['name'].'</option>';
			}
		}
		else
		{
			$game = '';
		}
		$templating->set('game', $game);

		// game tags
		$genre_ids = '';
		if (isset($_SESSION['item_post_data']['genre_ids']))
		{
			$in  = str_repeat('?,', count($_SESSION['item_post_data']['genre_ids']) - 1) . '?';
			$grab_genres = $dbl->run("SELECT `category_name`, `category_id` FROM `articles_categorys` WHERE `category_id` IN ($in)", $_SESSION['item_post_data']['genre_ids'])->fetch_all();
			foreach($grab_genres as $genre)
			{
				$genre_ids .= '<option value="'.$genre['category_id'].'" selected>'.$genre['category_name'].'</option>';
			}
		}
		$templating->set('genre_ids', $genre_ids);
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'suggest_tags')
	{
		if (isset($_POST['id']))
		{
			// check exists and grab info
			$get_item_res = $dbl->run("SELECT `name` FROM `calendar` WHERE `id` = ?", [$_POST['id']])->fetch();
			if ($get_item_res)
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
					$core->new_admin_note(['complete' => 0, 'type' => 'submitted_game_genre_suggestion', 'content' => 'submitted a genre suggestion for an item in the database.', 'data' => $_POST['id']]);
				}

				$core->message('Your tag suggestions for ' . $get_item_res['name'] . ' have been submitted! Thank you!');
			}
			else
			{
				$core->message("This is not the page you're looking for!");
			}
		}
	}
	if ($_POST['act'] == 'submit_item')
	{
		// quick helper func for below
		function filter($value) 
		{
			$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}

		// in case of form errors, set it in the session to use it again
		foreach ($_POST as $key => $data)
		{
			if (!is_array($data))
			{
				$_SESSION['item_post_data'][$key] = htmlspecialchars($data);
			}
			else
			{
				array_walk_recursive($data, "filter");
				$_SESSION['item_post_data'][$key] = $data;
			}
		}
		$name = strip_tags(trim($_POST['name']));

		// deal with the links, make sure not empty and validate
		$links = array('link', 'steam_link', 'gog_link', 'itch_link');
		$empty_check = 0;
		foreach ($links as $link)
		{
			$_POST[$link] = trim($_POST[$link]);

			// make doubly sure it's an actual URL, if not make it blank
			if (!filter_var($_POST[$link], FILTER_VALIDATE_URL)) 
			{
				$_POST[$link] = '';
			}

			if (!empty($_POST[$link]))
			{
				$empty_check = 1;
			}
		}
		if ($empty_check == 0)
		{
			$_SESSION['message'] = 'one_link_needed';
			header("Location: /index.php?module=items_database&view=submit_item");
			die();
		}
		
		// make sure its not empty
		$empty_check = core::mempty(compact('name'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: /index.php?module=items_database&view=submit_item");
			die();
		}

		// make sure it doesn't exist already
		$add_res = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($_POST['name']))->fetch();
		if ($add_res)
		{
			$_SESSION['message'] = 'item_submit_exists';
			header("Location: /index.php?module=items_database&view=submit_item");
			die();
		}

		// this needs finishing
		$check_similar = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ?", array('%'.$_POST['name'].'%'))->fetch_all();
		if ($check_similar)
		{
			
		}

		$types = ['is_game', 'is_application', 'is_emulator'];
		$sql_type = '';
		if (!in_array($_POST['type'], $types))
		{
			$_SESSION['message'] = 'no_item_type';
			header("Location: /index.php?module=items_database&view=submit_item");
			die();
		}
		else
		{
			$sql_type = '`'.$_POST['type'].'` = 1, ';
		}

		$supports_linux = 0;
		if (isset($_POST['supports_linux']))
		{
			$supports_linux = 1;
		}

		$hidden_steam = 0;
		if (isset($_POST['hidden_steam']))
		{
			$hidden_steam = 1;
		}

		$free = 0;
		if (isset($_POST['free']))
		{
			$free = 1;
		}

		$dlc = 0;
		if (isset($_POST['dlc']))
		{
			$dlc = 1;
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

		$dbl->run("INSERT INTO `calendar` SET `name` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `itch_link` = ?, `approved` = 0, `is_dlc` = ?, `base_game_id` = ?, `free_game` = ?, $sql_type `license` = ?, `supports_linux` = ?, `is_hidden_steam` = ?", array($name, $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $_POST['itch_link'], $dlc, $base_game, $free, $license, $supports_linux, $hidden_steam));
		$new_id = $dbl->new_id();

		$core->process_game_genres($new_id);

		$core->new_admin_note(['complete' => 0, 'type' => 'item_database_addition', 'content' => 'submitted a new item for the games database.', 'data' => $new_id]);

		unset($_SESSION['item_post_data']);

		$_SESSION['message'] = 'item_submitted';
		$_SESSION['message_extra'] = $name;
		header("Location: /index.php?module=items_database&view=submit_item");		
	}

	if ($_POST['act'] == 'submit_dev')
	{
		// make sure its not empty
		$name = trim(strip_tags($_POST['name']));
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

		$core->new_admin_note(['complete' => 0, 'type' => 'dev_database_addition', 'content' => 'submitted a new developer for the games database.', 'data' => $new_id]);

		$_SESSION['message'] = 'dev_submitted';
		$_SESSION['message_extra'] = $name;
		header("Location: /index.php?module=items_database&view=submit_dev");			
	}
}