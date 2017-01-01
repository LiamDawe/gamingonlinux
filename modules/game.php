<?php
$templating->merge('game_database');

if (!isset($_GET['game-id']))
{
	$templating->set_previous('meta_description', 'GamingOnLinux Linux games database', 1);
	$templating->set_previous('title', 'GamingOnLinux Linux games database', 1);

	$templating->block('main_top', 'game_database');
	$db->sqlquery("SELECT count(id) AS `total` FROM `calendar` WHERE `approved` = 1 AND `also_known_as` IS NULL");
	$total_games = $db->fetch();
	$templating->set('total_games', $total_games['total']);

	$templating->merge('game-search');
	$templating->block('search', 'game-search');
	$templating->set('search_text', '');

	// random game spotlight
	$db->sqlquery("SELECT `id`, `name`, `date` FROM `calendar` WHERE `approved` = 1 AND `also_known_as` IS NULL ORDER BY RAND() LIMIT 1");
	$random_item = $db->fetch();
	$templating->block('random', 'game_database');
	$templating->set('id', $random_item['id']);
	$templating->set('name', $random_item['name']);
	$templating->set('release_date', $random_item['date']);

	// latest games in the database
	$templating->block('latest_top', 'game_database');
	$db->sqlquery("SELECT `id`, `name`, `date` FROM `calendar` WHERE `approved` = 1 AND `also_known_as` IS NULL ORDER BY `id` DESC LIMIT 10");
	while ($latest = $db->fetch())
	{
		$templating->block('latest_item', 'game_database');
		$templating->set('id', $latest['id']);
		$templating->set('name', $latest['name']);
		$templating->set('release_date', $latest['date']);
	}

	$templating->block('latest_bottom', 'game_database');

	// games recently updated in the database
	$templating->block('edits_top', 'game_database');
	$db->sqlquery("SELECT `id`, `name`, `date` FROM `calendar` WHERE `approved` = 1 AND `also_known_as` IS NULL AND `edit_date` IS NOT NULL AND `edit_date` != '0000-00-00 00:00:00' ORDER BY UNIX_TIMESTAMP(`edit_date`) DESC LIMIT 10");
	while ($latest = $db->fetch())
	{
		$templating->block('latest_item', 'game_database');
		$templating->set('id', $latest['id']);
		$templating->set('name', $latest['name']);
		$templating->set('release_date', $latest['date']);
	}

	$templating->block('edits_bottom', 'game_database');
}

if (isset($_GET['game-id']))
{
	// make sure it exists
	$get_game = $db->sqlquery("SELECT c.`id`, c.`name`, c.`date`, c.`gog_link`, c.`steam_link`, c.`link`, c.`itch_link`, c.`description`, c.`best_guess`, c.`is_dlc`, b.name as base_game_name, b.id as base_game_id FROM `calendar` c LEFT JOIN `calendar` b ON c.base_game_id = b.id WHERE c.`id` = ? AND c.`approved` = 1", array($_GET['game-id']));
	if ($db->num_rows() == 1)
	{
		$game = $get_game->fetch();

		$templating->set_previous('meta_description', 'GamingOnLinux games database: '.$game['name'], 1);
		$templating->set_previous('title', $game['name'], 1);

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

		$templating->block('top', 'game_database');
		$templating->set('name', $game['name']);

		$dlc = '';
		if ($game['is_dlc'] == 1)
		{
			$dlc = '<span class="badge yellow">DLC</span>';
		}
		$templating->set('dlc', $dlc);

		$edit_link = '';
		if ($user->check_group(1,2) == TRUE || $user->check_group(5) == TRUE)
		{
			$edit_link = '<a class="fright" href="/admin.php?module=games&amp;view=edit&amp;id=' . $game['id'] . '&return=game">Edit</a>';
		}
		$templating->set('edit-link', $edit_link);

		if ($game['base_game_id'] != NULL && $game['base_game_id'] != 0)
		{
			$templating->block('base_game', 'game_database');
			$templating->set('base_game_id', $game['base_game_id']);
			$templating->set('base_game_name', $game['base_game_name']);
		}

		$templating->block('main-info', 'game_database');

		$date = '';
		if (!empty($game['date']))
		{
			$date = $game['date'];
		}
		$templating->set('release-date', $date);

		$unreleased = '';
		if (isset($date) && !empty($date) && $date > date('Y-m-d'))
		{
			$unreleased = '<span class="badge blue">Unreleased!</span>';
		}
		$templating->set('unreleased', $unreleased);

		$best_guess = '';
		if ($game['best_guess'] == 1)
		{
			$best_guess = '<span class="badge blue">Best Guess Date!</span>';
		}
		$templating->set('best_guess', $best_guess);

		$description = '';
		if (!empty($game['description']) && $game['description'] != NULL)
		{
			$description = '<strong>About this game</strong><br />' . $game['description'] . '<br /><br />';
		}
		$templating->set('description', bbcode($description));

		$official_link = '';
		if (!empty($game['link']))
		{
			$official_link = '<li><a href="' . $game['link'] . '">Official Website</a></li>';
		}
		$templating->set('official_link', $official_link);

		$gog_link = '';
		if (!empty($game['gog_link']))
		{
			$gog_link = '<li><a href="' . $game['gog_link'] . '">GOG Store</a></li>';
		}
		$templating->set('gog_link', $gog_link);

		$steam_link = '';
		if (!empty($game['steam_link']))
		{
			$steam_link = '<li><a href="' . $game['steam_link'] . '">Steam Store</a></li>';
		}
		$templating->set('steam_link', $steam_link);

		$itch_link = '';
		if (!empty($game['itch_link']))
		{
			$itch_link = '<li><a href="' . $game['itch_link'] . '">itch.io Store</a></li>';
		}
		$templating->set('itch_link', $itch_link);

		// find any associations
		$get_associations = $db->sqlquery("SELECT `name` FROM `calendar` WHERE `also_known_as` = ?", array($game['id']));
		$count_same = $db->num_rows();
		$same_games = array();
		if ($count_same > 0)
		{
			$templating->block('associations');
			while ($associations = $get_associations->fetch())
			{
				$same_games[] = $associations['name'];
			}
			$templating->set('games', implode(', ', $same_games));
		}

		$game['name'] = trim($game['name']);
		$db->sqlquery("SELECT a.`author_id`, a.`article_id`, a.`title`, a.`slug`, a.`guest_username`, u.`username` FROM `article_game_assoc` g LEFT JOIN `calendar` c ON c.id = g.game_id LEFT JOIN `articles` a ON a.article_id = g.article_id LEFT JOIN `users` u ON u.user_id = a.author_id WHERE c.name = ? AND a.active = 1 ORDER BY a.article_id DESC", array($game['name']));
		if ($db->num_rows() > 0)
		{
			$article_list = '';
			$templating->block('articles', 'game_database');
			while ($articles = $db->fetch())
			{
				if (core::config('pretty_urls') == 1)
				{
					$article_link = "/articles/" . $articles['slug'] . '.' . $articles['article_id'];
				}
				else
				{
					$article_link = url . 'index.php?module=articles_full&amp;aid=' . $articles['article_id'] . '&amp;title=' . $articles['slug'];
				}

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

		if ($user->check_group(1,2) == TRUE || $user->check_group(5) == TRUE)
		{
			$templating->block('main_info_bottom', 'game_database');
			$edit_link = '<a href="/admin.php?module=games&amp;view=edit&amp;id=' . $game['id'] . '&return=game">Edit</a>';
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
?>
