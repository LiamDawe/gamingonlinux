<?php
$templating->load('search');
if (!isset($_GET['author_id']))
{
	$templating->set_previous('title', 'Article Search', 1);
	$templating->set_previous('meta_description', 'Search for articles on GamingOnLinux', 1);

	$templating->block('top');
	$templating->set('url', $core->config('website_url'));
}

$search_text = '';
if (isset($_GET['q']))
{
	$search_text = str_replace("+", ' ', $_GET['q']);
	$search_text = strip_tags($_GET['q']);
}
$templating->set('search_text', htmlspecialchars($search_text));

if (!isset($_GET['q']) && !isset($_GET['author_id']))
{
	$templating->load('articles');
	$templating->block('multi', 'articles');

	$templating->block('articles_top', 'articles');
	$options = '';
	$res = $dbl->run("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC")->fetch_all();
	foreach ($res as $get_cats)
	{
		$options .= '<option value="'.$get_cats['category_id'].'">'.$get_cats['category_name'].'</option>';
	}
	$templating->set('options', $options);

	$all_check = '';
	$any_check = 'checked';

	$templating->set('any_check', $any_check);
	$templating->set('all_check', $all_check);
}

$search_array = array(explode(" ", $search_text));
$search_through = '';
foreach ($search_array[0] as $item)
{
	$item = str_replace("%","\%", $item);
	$search_through .= '%'.$item.'%';
}

// check there wasn't none found to prevent loops
if (isset($search_text) && !empty($search_text))
{
	// do the search query
	$found_search = $dbl->run("SELECT a.`article_id`, a.`title`, a.`slug`, a.`author_id`, a.`date` , a.`guest_username`, u.`username`, a.`show_in_menu`
	FROM `articles` a
	LEFT JOIN `users` u ON a.`author_id` = u.`user_id`
	WHERE a.`active` = 1
	AND a.`title` LIKE ?
	ORDER BY a.`date` DESC
	LIMIT 0 , 100", array($search_through))->fetch_all();

	if ($found_search)
	{
		$article_id_array = array();

		foreach ($found_search as $article)
		{
			$article_id_array[] = $article['article_id'];
		}
		$article_id_sql = implode(', ', $article_id_array);

		$get_categories = $article_class->find_article_tags(array('article_ids' => $article_id_sql, 'limit' => 5));

		// loop through results
		foreach ($found_search as $found)
		{
			$date = $core->human_date($found['date']);

			$templating->block('row');

			$templating->set('date', $date);
			$templating->set('title', $found['title']);
			$templating->set('article_link', $article_class->get_link($found['article_id'], $found['slug']));

			if ($found['author_id'] == 0)
			{
				$username = $found['guest_username'];
			}

			else
			{
				$username = "<a href=\"/profiles/{$found['author_id']}\">" . $found['username'] . '</a>';
			}
			$templating->set('username', $username);

			$categories_display = '';
			if ($article['show_in_menu'] == 1)
			{
				$categories_display = '<li><a href="#">Editors Pick</a></li>';
			}

			if (isset($get_categories[$found['article_id']]))
			{
				$categories_display .= $article_class->display_article_tags($get_categories[$found['article_id']]);
			}

			$templating->set('categories_list', $categories_display);
		}
	}
	else
	{
		$core->message('Nothing was found with those search terms.', 1);
	}
}

if (isset($_GET['author_id']) && is_numeric($_GET['author_id']))
{
	$pagination = '';

	// check they actually exist
	$username = $dbl->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_GET['author_id']))->fetchOne();
	if ($username)
	{
		// paging for pagination
		$page = core::give_page();
		
		// count how many there is in total
		$total = $dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE active = 1 AND `author_id` = ?", array($_GET['author_id']))->fetchOne();

		// sort out the pagination link
		$pagination = $core->pagination_link(15, $total, "/index.php?module=search&author_id={$_GET['author_id']}&", $page);

		// do the search query
		$found_search = $dbl->run("SELECT a.article_id, a.`title`, a.`slug`, a.author_id, a.`date`, a.guest_username, a.`show_in_menu`, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.active = 1 and a.`author_id` = ? ORDER BY a.date DESC LIMIT ?, 15", array($_GET['author_id'], $core->start))->fetch_all();

		if ($total > 0)
		{
			$templating->set_previous('title', 'Viewing articles by ' . $username, 1);
			$templating->set_previous('meta_description', 'Viewing articles on GamingOnLinux written by ' . $username, 1);

			$templating->block('author_top');
			$templating->set('username', $username);
			$templating->set('profile_link', $core->config('website_url') . 'profiles/' . $found_search[0]['author_id']);

			$article_id_array = array();

			foreach ($found_search as $article)
			{
				$article_id_array[] = $article['article_id'];
			}
			$article_id_sql = implode(', ', $article_id_array);
	
			$get_categories = $article_class->find_article_tags(array('article_ids' => $article_id_sql, 'limit' => 5));

			// loop through results
			foreach ($found_search as $found)
			{
				$date = $core->human_date($found['date']);

				$templating->block('row');

				$templating->set('date', $date);
				$templating->set('title', $found['title']);
				$templating->set('article_link', $article_class->get_link($found['article_id'], $found['slug']));

				$username_link = "<a href=\"/profiles/{$found['author_id']}\">" . $found['username'] . '</a>';
				
				$templating->set('username', $username_link);

				$categories_display = '';
				if ($found['show_in_menu'] == 1)
				{
					$categories_display = '<li><a href="#">Editors Pick</a></li>';
				}
	
				if (isset($get_categories[$found['article_id']]))
				{
					$categories_display .= $article_class->display_article_tags($get_categories[$found['article_id']]);
				}
	
				$templating->set('categories_list', $categories_display);
			}
		}
		else
		{
			$templating->set_previous('title', 'Viewing articles by ' . $username, 1);
			$templating->set_previous('meta_description', 'Viewing articles on GamingOnLinux written by ' . $username, 1);

			$templating->block('author_top');
			$templating->set('username', $username);
			$templating->set('profile_link', $core->config('website_url') . 'profiles/' . $_GET['author_id']);

			$core->message('They have posted no articles!');
		}
	}
	else
	{
		$templating->set_previous('title', 'No user found!', 1);
		$core->message("That user doesn't exist!");
	}

	$templating->block('bottom', 'search');
	$templating->set('pagination', $pagination);
}
?>
