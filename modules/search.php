<?php
$templating->set_previous('title', 'Search linux gaming articles', 1);
$templating->set_previous('meta_description', 'Search for Linux gaming articles on GamingOnLinux.com', 1);

$templating->merge('search');
$templating->block('top');
$templating->set('url', $config['path']);

$search_text = '';
if (isset($_GET['q']))
{
	$search_text = str_replace("+", ' ', $_GET['q']);
	$search_text = htmlspecialchars($search_text);
}
$templating->set('search_text', $search_text);

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
			$categories_list .= " <a href=\"/articles/category/{$get_categories['category_id']}\"><span class=\"label label-info\">{$get_categories['category_name']}</span></a> ";
		}

		if (!empty($categories_list))
		{
			$categories_list = '<div class="small muted">In: ' . $categories_list . '</div>';
		}
		$templating->set('categories_list', $categories_list);
	}

	$templating->block('bottom');
	$templating->set('pagination', $pagination);
}
?>
