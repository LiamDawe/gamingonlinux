<?php
if (isset($_GET['game-id']))
{
	// make sure it exists
	$db->sqlquery("SELECT `id`, `name`, `date`, `gog_link`, `steam_link`, `link` FROM `calendar` WHERE `id` = ? AND `approved` = 1", array($_GET['game-id']));
	if ($db->num_rows() == 1)
	{
		$game = $db->fetch();

		$templating->set_previous('meta_description', 'GamingOnLinux games database: '.$game['name'], 1);
		$templating->set_previous('title', $game['name'], 1);

		$templating->merge('game_database');

		$templating->block('top');
		$templating->set('name', $game['name']);

		$edit_link = '';
		if ($user->check_group(1,2) == TRUE)
		{
			$edit_link = '<a class="fright" href="/admin.php?module=games&amp;view=edit&amp;id=' . $game['id'] . '">Edit</a>';
		}
		$templating->set('edit-link', $edit_link);

		$templating->block('main-info');

		$date = '';
		if (!empty($game['date']))
		{
			$date = $game['date'];
		}
		$templating->set('release-date', $date);

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

		$db->sqlquery("SELECT a.`author_id`, a.`article_id`, a.`title`, a.`slug`, a.`guest_username`, u.`username` FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id WHERE a.`title` LIKE ? ORDER BY a.`article_id` DESC LIMIT 4", array('%'.$game['name'].'%'));
		if ($db->num_rows() > 0)
		{
			$article_list = '';
			$templating->block('articles');
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
	}
	else
	{
		$core->message("That game id does not exist!", NULL, 1);
	}
}
?>
