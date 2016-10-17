<?php
$templating->merge('search');
if (!isset($_GET['author_id']))
{
	$templating->set_previous('title', 'Search linux gaming articles', 1);
	$templating->set_previous('meta_description', 'Search for Linux gaming articles on GamingOnLinux.com', 1);

	$templating->block('top');
	$templating->set('url', core::config('website_url'));
}

$search_text = '';
if (isset($_GET['q']))
{
	$search_text = str_replace("+", ' ', $_GET['q']);
	$search_text = htmlspecialchars($search_text);
}
$templating->set('search_text', $search_text);

if (!isset($_GET['q']) && !isset($_GET['author_id']))
{
	$templating->merge('articles');
	$templating->block('multi', 'articles');

	$templating->block('articles_top', 'articles');
	$options = '';
	$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
	while ($get_cats = $db->fetch())
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

if (isset($search_text) && !empty($search_text))
{
	// do the search query
	$db->sqlquery("SELECT a.article_id, a.`title` , a.author_id, a.`date` , a.guest_username, u.username
	FROM  `articles` a
	LEFT JOIN  `users` u ON a.author_id = u.user_id
	WHERE a.active = 1
	AND a.title LIKE ?
	ORDER BY a.date DESC
	LIMIT 0 , 100", array($search_through));

	$found_search = $db->fetch_all_rows();

	// loop through results
	foreach ($found_search as $found)
	{
		$date = $core->format_date($found['date']);

		$templating->block('row');

		$templating->set('date', $date);
		$templating->set('title', $found['title']);
		$templating->set('article_link', $core->nice_title($found['title']) . '.' . $found['article_id']);

		if ($found['author_id'] == 0)
		{
			$username = $found['guest_username'];
		}

		else
		{
			$username = "<a href=\"/profiles/{$found['author_id']}\">" . $found['username'] . '</a>';
		}
		$templating->set('username', $username);

		// sort out the categories (tags)
		$categories_list = '';
		$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ?", array($found['article_id']));
		while ($get_categories = $db->fetch())
		{
			$categories_list .= "<li><a href=\"/articles/category/{$get_categories['category_id']}\"><span class=\"label label-info\">{$get_categories['category_name']}</span></a></li>";
		}
		$templating->set('categories_list', $categories_list);
	}
}

if (isset($_GET['author_id']) && is_numeric($_GET['author_id']))
{
	// paging for pagination
	if (!isset($_GET['page']) || $_GET['page'] <= 0)
	{
		$page = 1;
	}

	else if (is_numeric($_GET['page']))
	{
		$page = $_GET['page'];
	}

	// count how many there is in total
	$db->sqlquery("SELECT `article_id` FROM `articles` WHERE active = 1 AND `author_id` = ?", array($_GET['author_id']), 'search.php');
	$total = $db->num_rows();

	// sort out the pagination link
	$pagination = $core->pagination_link(15, $total, "/index.php?module=search&author_id={$_GET['author_id']}&", $page);

	// do the search query
	$db->sqlquery("SELECT a.article_id, a.`title`, a.author_id, a.`date`, a.guest_username, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.active = 1 and a.`author_id` = ? ORDER BY a.date DESC LIMIT ?, 15", array($_GET['author_id'], $core->start), 'search.php');
	$found_search = $db->fetch_all_rows();
	$total_found = $db->num_rows();

	if ($total_found > 0)
	{
		$templating->set_previous('title', 'Viewing articles by ' . $found_search[0]['username'], 1);
		$templating->set_previous('meta_description', 'Viewing articles on GamingOnLinux written by ' . $found_search[0]['username'], 1);

		$templating->block('author_top');
		$templating->set('username', $found_search[0]['username']);

		if (core::config('pretty_urls') == 1)
		{
			$profile_link = core::config('website_url') . 'profiles/' . $found_search[0]['author_id'];
		}
		else
		{
			$profile_link = core::config('website_url') . 'index.php?module=profile&user_id=' . $found_search[0]['author_id'];
		}
		$templating->set('profile_link', $profile_link);

		// loop through results
		foreach ($found_search as $found)
		{
			$date = $core->format_date($found['date']);

			$templating->block('row');

			$templating->set('date', $date);
			$templating->set('title', $found['title']);
			$templating->set('article_link', $core->nice_title($found['title']) . '.' . $found['article_id']);

			if ($found['author_id'] == 0)
			{
				$username = $found['guest_username'];
			}

			else
			{
				$username = "<a href=\"/profiles/{$found['author_id']}\">" . $found['username'] . '</a>';
			}
			$templating->set('username', $username);

			// sort out the categories (tags)
			$categories_list = '';
			$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? ORDER BY r.`category_id` = 60 DESC, r.`category_id` ASC", array($found['article_id']));
			while ($get_categories = $db->fetch())
			{
				if ($get_categories['category_id'] == 60)
				{
					$categories_list .= " <li class=\"ea\"><a href=\"/articles/category/{$get_categories['category_id']}\">{$get_categories['category_name']}</a></li> ";
				}

				else
				{
					$categories_list .= " <li><a href=\"/articles/category/{$get_categories['category_id']}\">{$get_categories['category_name']}</a></li> ";
				}
			}


			$templating->set('categories_list', $categories_list);
		}
	}

	$templating->block('bottom');
	$templating->set('pagination', $pagination);
}
?>
