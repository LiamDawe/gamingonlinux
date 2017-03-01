<?php
$templating->merge('game_database');

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'all')
	{
		$licenses = array('', 'Closed Source', 'GPL', 'BSD', 'MIT');

		$templating->set_previous('meta_description', 'GamingOnLinux Linux games database', 1);
		$templating->set_previous('title', 'GamingOnLinux Linux games database - viewing full database', 1);

		$page = core::give_page();

		$templating->block('game_list');
		$license_options = '';
		foreach ($licenses as $license)
		{
			$selected = '';
			if (isset($_GET['license']) && !empty($_GET['license']) && $_GET['license'] == $license)
			{
				$selected = 'selected';
			}
			$license_options .= '<option value="' . $license . '" '.$selected.'>'.$license.'</option>';
		}
		$templating->set('license_options', $license_options);
		
		$genres = $core->display_all_genres();
		$templating->set('genre_options', $genres);
		
		$sql_replace = [];
		$inner_join = '';
		$sql_where = ' WHERE c.`approved` = 1 AND c.`also_known_as` IS NULL ';
		
		if (isset($_GET['license']) && !empty($_GET['license']) && in_array($_GET['license'], $licenses))
		{
			$sql_replace[] = $_GET['license'];
			$sql_where .= ' AND c.`license` = ? ';
		}
		
		$genre = '';
		if (isset($_GET['genre']) && core::is_number($_GET['genre']))
		{
			$sql_replace[] = (int) $_GET['genre'];
			$inner_join = ' INNER JOIN `game_genres_reference` r ON r.game_id = c.id ';
			$sql_where .= ' AND r.`genre_id` = ? ';
		}
		
		$get_total = $db->sqlquery("SELECT count(c.id) AS `total` FROM `calendar` c $inner_join $sql_where", $sql_replace);
		$total_games = $get_total->fetch();

		// sort out the pagination link
		$pagination = $core->pagination_link(18, $total_games['total'], '/index.php?module=game&amp;view=all&', $page, '#comments');
		
		$sql_replace[] = $core->start;

		$grab_games = $db->sqlquery("SELECT c.`name`, c.`id` FROM `calendar` c $inner_join $sql_where ORDER BY c.`name` ASC LIMIT ?, 18", $sql_replace);
		if ($total_games['total'] > 0)
		{
			while ($game = $grab_games->fetch())
			{
				$templating->block('game_list_row');
				$templating->set('name', $game['name']);
				$templating->set('id', $game['id']);
			}
			$templating->block('game_list_bottom');
			$templating->set('pagination', $pagination);
		}
		else
		{
			$templating->block('game_list_bottom');
			$templating->set('pagination', '');
			$core->message("None found with those filters!");
		}
	}
}

if (!isset($_GET['game-id']) && !isset($_GET['view']))
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
	$db->sqlquery("SELECT `id`, `name`, `date` FROM `calendar` WHERE `approved` = 1 AND `also_known_as` IS NULL AND `description` IS NOT NULL AND `description` != '' ORDER BY RAND() LIMIT 1");
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

if (isset($_GET['game-id']) && !isset($_GET['view']))
{
	if (!core::is_number($_GET['game-id']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'game id';
		header("Location: /index.php?module=game");
		die();
	}

	// make sure it exists
	$get_game = $db->sqlquery("SELECT c.`id`, c.`name`, c.`date`, c.`gog_link`, c.`steam_link`, c.`link`, c.`itch_link`, c.`description`, c.`best_guess`, c.`is_dlc`, c.`free_game`, c.`license`, b.`name` as base_game_name, b.`id` as base_game_id FROM `calendar` c LEFT JOIN `calendar` b ON c.`base_game_id` = b.`id` WHERE c.`id` = ? AND c.`approved` = 1", array($_GET['game-id']));
	if ($db->num_rows() == 1)
	{
		$game = $get_game->fetch();

		$templating->set_previous('meta_description', 'GamingOnLinux games database: '.$game['name'], 1);
		$templating->set_previous('title', $game['name'], 1);

		$templating->block('top', 'game_database');
		$templating->set('name', $game['name']);

		$dlc = '';
		if ($game['is_dlc'] == 1)
		{
			$dlc = '<span class="badge yellow">DLC</span>';
		}
		$templating->set('dlc', $dlc);

		$edit_link = '';
		if ($user->check_group([1,2,5]))
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

		// extra info box
		$extra = 0; // don't show it if nothing is filled
		$extra_info = '';

		// sort out price
		$price = '';
		if ($game['free_game'] == 1)
		{
			$price = 'Free';
		}
		if (!empty($price))
		{
			$price = '<li>Price: ' . $price . '</li>';
			$extra++;
		}

		// sort out license
		$license = '';
		if (!empty($game['license']) || $game['license'] != NULL)
		{
			$license = $game['license'];
		}
		if (!empty($license))
		{
			$license = '<li>License: ' . $license . '</li>';
			$extra++;
		}

		// sort out genres
		$genres_output = '';
		$db->sqlquery("SELECT g.`name`, g.`id` FROM `game_genres` g INNER JOIN `game_genres_reference` r ON r.genre_id = g.id WHERE r.`game_id` = ?", array($game['id']));
		while ($genres = $db->fetch())
		{
			$genres_output[] = $genres['name'];
		}
		
		if (!empty($genres_output))
		{
			$genres_output = 'Genres: <ul class="database_extra">' . implode(', ', $genres_output) . '</ul>';
			$extra++;
		}
		
		if ($extra > 0)
		{
			$extra_info = $templating->block_store('extra', 'game_database');
			$extra_info = $templating->store_replace($extra_info, array('price' => $price, 'license' => $license, 'genres' => $genres_output));
		}
		$templating->set('extra_info', $extra_info);

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
			$description = '<br /><strong>About this game</strong>:<br />' . $game['description'] . '<br /><br />';
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

		if ($user->check_group([1,2,5]))
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
