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
}