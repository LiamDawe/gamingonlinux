<?php
plugins::register_hook('article_form_top', 'games_tagging_list');
plugins::register_hook('article_database_entry', 'games_list_updating');
plugins::register_hook('display_article_tags_list', 'game_tag_list');
plugins::register_hook('article_deletion', 'remove_game_rows');

function hook_games_tagging_list($article_id)
{
	global $db, $templating, $edit_state;
    
    $templating->merge_plugin('games_database/template');
    $tagging_block = $templating->block_store('game_tagging', 'games_database/template');

    if ($article_id != NULL)
    {
		// get games list
		$games_check_array = array();
		$db->sqlquery("SELECT `game_id` FROM `article_game_assoc` WHERE `article_id` = ?", array($article_id));
		while($games_check = $db->fetch())
		{
			$games_check_array[] = $games_check['game_id'];
		}
	}

	$games_list = '';
	$db->sqlquery("SELECT `id`, `name` FROM `calendar` ORDER BY `name` ASC");
    while ($games = $db->fetch())
    {
		// if there was some sort of error, we use the games set on the error
		if (isset($_GET['error']))
		{
			if (!empty($_SESSION['agames']) && in_array($games['id'], $_SESSION['agames']))
			{
				$games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
			}
		}

		// otherwise if we are submitting a form, like on a preview
		else if (!empty($_POST['games']) && !isset($_GET['error']))
		{
			if (in_array($games['id'], $_POST['games']))
			{
				$games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
			}
		}

		// lastly, if we are viewing an existing article
		else if (($article_id != NULL) && isset($games_check_array) && in_array($games['id'], $games_check_array))
		{
			$games_list .= "<option value=\"{$games['id']}\" selected>{$games['name']}</option>";
		}
    }
    
    $tagging_block = $templating->store_replace($tagging_block, ['edit_state' => $edit_state, 'games_list' => $games_list]);

    return $tagging_block;
}

function hook_games_list_updating($article_id)
{
    global $db;

	if (isset($article_id) && is_numeric($article_id))
    {
		// delete any existing games that aren't in the final list for publishing
		$db->sqlquery("SELECT `id`, `article_id`, `game_id` FROM `article_game_assoc` WHERE `article_id` = ?", array($article_id));
		$current_games = $db->fetch_all_rows();

		if (!empty($current_games))
		{
			foreach ($current_games as $current_game)
			{
				if (!in_array($current_game['game_id'], $_POST['games']))
				{
					$db->sqlquery("DELETE FROM `article_game_assoc` WHERE `id` = ?", array($current_game['id']));
				}
			}
		}

		// get fresh list of games, and insert any that don't exist
		$db->sqlquery("SELECT `game_id`, `id`, `article_id` FROM `article_game_assoc` WHERE `article_id` = ?", array($article_id));
		$current_games = $db->fetch_all_rows(PDO::FETCH_COLUMN, 0);

		if (isset($_POST['games']) && !empty($_POST['games']))
		{
			foreach($_POST['games'] as $game)
			{
				if (!in_array($game, $current_games))
				{
					$db->sqlquery("INSERT INTO `article_game_assoc` SET `article_id` = ?, `game_id` = ?", array($article_id, $game));
				}
			}
		}
	}
	
	unset($_SESSION['agames']);
}

function hook_game_tag_list($article_id)
{
	global $db;
	
	$games_list = '';
	// sort out the games tags
	$db->sqlquery("SELECT c.`name`, c.`id` FROM `calendar` c INNER JOIN `article_game_assoc` r ON c.id = r.game_id WHERE r.article_id = ? ORDER BY c.`name` ASC", array($article_id));
	while ($get_games = $db->fetch())
	{
		$games_list .= ' <li><a href="'.core::config('website_url').'index.php?module=game&game-id=' . $get_games['id'] . '">' . $get_games['name'] . '</a></li> ';
	}
	
	return $games_list;
}

function hook_remove_game_rows($article_id)
{
	global $db;
	
	$db->sqlquery("DELETE FROM `article_game_assoc` WHERE `article_id` = ?", array($article_id));
}
