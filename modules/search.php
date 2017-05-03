<?php
$templating->merge('search');
if (!isset($_GET['author_id']))
{
	$templating->set_previous('title', 'Article Search', 1);
	$templating->set_previous('meta_description', 'Search for articles on ' . core::config('site_title'), 1);

	$templating->block('top');
	$templating->set('url', core::config('website_url'));
}

$search_text = '';
if (isset($_GET['q']))
{
	$search_text = str_replace("+", ' ', $_GET['q']);
	$search_text = core::make_safe($search_text);
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

// check there wasn't none found to prevent loops
if (isset($search_text) && !empty($search_text))
{
	// do the search query
	$db->sqlquery("SELECT a.`article_id`, a.`title`, a.`slug`, a.`author_id`, a.`date` , a.`guest_username`, u.`username`, a.`show_in_menu`
	FROM `articles` a
	LEFT JOIN ".$core->db_tables['users']." u ON a.`author_id` = u.`user_id`
	WHERE a.`active` = 1
	AND a.`title` LIKE ?
	ORDER BY a.`date` DESC
	LIMIT 0 , 100", array($search_through));
	$total = $db->num_rows();

	if ($total > 0)
	{
		$found_search = $db->fetch_all_rows();

		$article_id_array = array();

		foreach ($found_search as $article)
		{
			$article_id_array[] = $article['article_id'];
		}
		$article_id_sql = implode(', ', $article_id_array);
	
		// this is required to properly count up the rank for the tags
		$db->sqlquery("SET @rank=null, @val=null");

		$category_tag_sql = "SELECT * FROM (
			SELECT r.`article_id`, c.`category_name` , c.`category_id` , @rank := IF( @val = r.`article_id`, @rank +1, 1 ) AS rank, @val := r.`article_id`
			FROM  `article_category_reference` r
			INNER JOIN  `articles_categorys` c ON c.`category_id` = r.`category_id`
			WHERE r.`article_id`
			IN ( $article_id_sql )
			ORDER BY CASE WHEN (r.`category_id` = 60) THEN 0 ELSE 1 END, r.`article_id` ASC
		) AS a
		WHERE rank < 5";
		$db->sqlquery($category_tag_sql);
		$get_categories = $db->fetch_all_rows();

		// loop through results
		foreach ($found_search as $found)
		{
			$date = $core->format_date($found['date']);

			$templating->block('row');

			$templating->set('date', $date);
			$templating->set('title', $found['title']);
			$templating->set('article_link', article_class::get_link($found['article_id'], $found['slug']));

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
		$core->message('Nothing was found with those search terms.');
	}
}

if (isset($_GET['author_id']) && is_numeric($_GET['author_id']))
{
	// paging for pagination
	$page = core::give_page();
	
	// count how many there is in total
	$db->sqlquery("SELECT `article_id` FROM `articles` WHERE active = 1 AND `author_id` = ?", array($_GET['author_id']), 'search.php');
	$total = $db->num_rows();

	// sort out the pagination link
	$pagination = $core->pagination_link(15, $total, "/index.php?module=search&author_id={$_GET['author_id']}&", $page);

	// do the search query
	$db->sqlquery("SELECT a.article_id, a.`title`, a.`slug`, a.author_id, a.`date`, a.guest_username, u.username FROM `articles` a LEFT JOIN ".$core->db_tables['users']." u on a.author_id = u.user_id WHERE a.active = 1 and a.`author_id` = ? ORDER BY a.date DESC LIMIT ?, 15", array($_GET['author_id'], $core->start));
	$found_search = $db->fetch_all_rows();

	if ($total > 0)
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
			$templating->set('article_link', article_class::get_link($found['article_id'], $found['slug']));

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
				$tag_link = article_class::tag_link($get_categories['category_name']);
				
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

	$templating->block('bottom');
	$templating->set('pagination', $pagination);
}
?>
