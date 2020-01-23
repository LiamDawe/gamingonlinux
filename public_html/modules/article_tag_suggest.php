<?php
$templating->load('article_tag_suggest');

$templating->set_previous('title', 'Article tag suggestion', 1);
$templating->set_previous('meta_description', 'Article tag suggestion GamingOnLinux', 1);

if (isset($_GET['article_id']))
{
	// check exists and grab info
	$get_item_res = $dbl->run("SELECT `article_id`, `title` FROM `articles` WHERE `article_id` = ?", [$_GET['article_id']])->fetch();
	if ($get_item_res)
	{
		$templating->block('suggest_tags');
		$templating->set('title', $get_item_res['title']);
		$templating->set('article_id', $_GET['article_id']);

		// Normal tags
		$current_genres = 'None!';
		$get_categories = $article_class->find_article_tags(array('article_ids' => $get_item_res['article_id']));
		$get_genres = $article_class->display_article_tags($get_categories[$get_item_res['article_id']], 'array_plain');
		if (is_array($get_genres))
		{
			$current_genres = implode(', ', $get_genres);
		}
		$templating->set('current_genre_tags', $current_genres);

		// Game/project tags
		$current_linked_games = $dbl->run("SELECT a.`game_id`, g.`name` FROM `article_item_assoc` a INNER JOIN `calendar` g ON g.id = a.game_id WHERE a.`article_id` = ?", array($get_item_res['article_id']))->fetch_all();
		$current_games = 'None!';
		if ($current_linked_games)
		{
			$current_games = implode(', ', $article_class->display_game_tags($current_linked_games, 'array_plain'));
		}
		$templating->set('current_game_tags', $current_games);
	}
	else
	{
		$core->message("This is not the page you're looking for!");
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'suggest_tags')
	{
		if (isset($_POST['article_id']) && is_numeric($_POST['article_id']) && !empty($_POST['categories']))
		{
			// check exists and grab info
			$get_item_res = $dbl->run("SELECT `title` FROM `articles` WHERE `article_id` = ?", [$_POST['article_id']])->fetch();
			if ($get_item_res)
			{
				// get fresh list of suggestions, and insert any that don't exist
				$current_suggestions = $dbl->run("SELECT `category_id` FROM `article_category_suggestions` WHERE `article_id` = ?", array($_POST['article_id']))->fetch_all(PDO::FETCH_COLUMN, 0);

				// get fresh list of current tags, insert any that don't exist
				$current_genres = $dbl->run("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($_POST['article_id']))->fetch_all(PDO::FETCH_COLUMN, 0);				

				if (isset($_POST['categories']) && !empty($_POST['categories']) && core::is_number($_POST['categories']))
				{
					$total_added = 0;
					foreach($_POST['categories'] as $genre_id)
					{
						if (!in_array($genre_id, $current_suggestions) && !in_array($genre_id, $current_genres))
						{
							$total_added++;
							$dbl->run("INSERT INTO `article_category_suggestions` SET `article_id` = ?, `category_id` = ?, `suggested_time` = ?, `suggested_by_id` = ?", array($_POST['article_id'], $genre_id, core::$sql_date_now, $_SESSION['user_id']));
						}
					}
				}

				if ($total_added > 0)
				{
					$core->new_admin_note(['complete' => 0, 'type' => 'submitted_article_tag_suggestion', 'content' => 'submitted a tag suggestion for an article.', 'data' => $_POST['article_id']]);
				}

				$_SESSION['message'] = 'saved';
				$_SESSION['message_extra'] = 'list of article tag suggestions';
				header("Location: /articles/".$_POST['article_id']);
				die();
			}
			else
			{
				$core->message("This is not the page you're looking for!");
			}
		}
		else
		{
			header("Location: /articles/".$_POST['article_id']);
			die();
		}
	}
}
?>