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
$templating->set('search_text', $search_text);

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
	
		// this is required to properly count up the rank for the tags
		$dbl->run("SET @rank=null, @val=null");

		$category_tag_sql = "SELECT * FROM (
			SELECT r.`article_id`, c.`category_name` , c.`category_id` , @rank := IF( @val = r.`article_id`, @rank +1, 1 ) AS rank, @val := r.`article_id`
			FROM  `article_category_reference` r
			INNER JOIN  `articles_categorys` c ON c.`category_id` = r.`category_id`
			WHERE r.`article_id`
			IN ( $article_id_sql )
			ORDER BY CASE WHEN (r.`category_id` = 60) THEN 0 ELSE 1 END, r.`article_id` ASC
		) AS a
		WHERE rank < 5";
		$get_categories = $dbl->run($category_tag_sql)->fetch_all();

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

			$editors_pick = '';
			if ($found['show_in_menu'] == 1)
			{
				$editors_pick = '<li><a href="#">Editors Pick</a></li>';
			}
			$categories_list = $editors_pick;
			foreach ($get_categories as $k => $category_list)
			{
				if (in_array($found['article_id'], $category_list))
				{
					$tag_link = $article_class->tag_link($category_list['category_name']);

					if ($category_list['category_id'] == 60)
					{
						$categories_list .= " <li class=\"ea\"><a href=\"$tag_link\">{$category_list['category_name']}</a></li> ";
					}

					else
					{
						$categories_list .= " <li><a href=\"$tag_link\">{$category_list['category_name']}</a></li> ";
					}
				}
			}
			$templating->set('categories_list', $categories_list);
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
		$found_search = $dbl->run("SELECT a.article_id, a.`title`, a.`slug`, a.author_id, a.`date`, a.guest_username, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.active = 1 and a.`author_id` = ? ORDER BY a.date DESC LIMIT ?, 15", array($_GET['author_id'], $core->start))->fetch_all();

		if ($total > 0)
		{
			$templating->set_previous('title', 'Viewing articles by ' . $username, 1);
			$templating->set_previous('meta_description', 'Viewing articles on GamingOnLinux written by ' . $username, 1);

			$templating->block('author_top');
			$templating->set('username', $username);

			if ($core->config('pretty_urls') == 1)
			{
				$profile_link = $core->config('website_url') . 'profiles/' . $found_search[0]['author_id'];
			}
			else
			{
				$profile_link = $core->config('website_url') . 'index.php?module=profile&user_id=' . $found_search[0]['author_id'];
			}
			$templating->set('profile_link', $profile_link);

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

				// sort out the categories (tags)
				$categories_list = '';
				$res = $dbl->run("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? ORDER BY r.`category_id` = 60 DESC, r.`category_id` ASC", array($found['article_id']))->fetch_all();
				foreach ($res as $get_categories)
				{
					$tag_link = $article_class->tag_link($get_categories['category_name']);
					
					if ($get_categories['category_id'] == 60)
					{
						$categories_list .= " <li class=\"ea\"><a href=\"$tag_link\">{$get_categories['category_name']}</a></li> ";
					}

					else
					{
						$categories_list .= " <li><a href=\"$tag_link\">{$get_categories['category_name']}</a></li> ";
					}
				}


				$templating->set('categories_list', $categories_list);
			}
		}
		else
		{
			$templating->set_previous('title', 'Viewing articles by ' . $username, 1);
			$templating->set_previous('meta_description', 'Viewing articles on GamingOnLinux written by ' . $username, 1);

			$templating->block('author_top');
			$templating->set('username', $username);

			if ($core->config('pretty_urls') == 1)
			{
				$profile_link = $core->config('website_url') . 'profiles/' . $_GET['author_id'];
			}
			else
			{
				$profile_link = $core->config('website_url') . 'index.php?module=profile&user_id=' . $_GET['author_id'];
			}
			$templating->set('profile_link', $profile_link);

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
