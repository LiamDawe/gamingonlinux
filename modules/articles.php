<?php
$templating->merge('articles');

if (!isset($_GET['view']))
{
	// better than showing a blank page
	$templating->set_previous('title', 'Nothing to see here', 1);
	$core->message("There must have been an error as accessing this page directly doesn't do anything, be sure to report exactly what you did.", NULL, 1);
}

if (isset($_GET['view']) && ($_GET['view'] == 'cat' || $_GET['view'] == 'multiple'))
{
	// paging for pagination
	$page = core::give_page();

	// searching multiple categories
	if ($_GET['view'] == 'multiple')
	{
		$templating->set_previous('meta_description', 'GamingOnLinux viewing Linux gaming news from mutiple categories', 1);
		$templating->set_previous('title', 'Searching for articles in multiple categories', 1);

		$templating->block('multi', 'articles');

		if (isset($_GET['catid']))
		{
			if (!is_array($_GET['catid']))
			{
				$_SESSION['message'] = 'none_found';
				$_SESSION['message_extra'] = 'categories';
				header("Location: /index.php?module=articles&view=cat");
				die();
			}
			$categorys_ids = $_GET['catid'];
		}
	}

	// clicked a single category link from somewhere
	if ($_GET['view'] == 'cat')
	{
		if (isset($_GET['catid']) && !is_array($_GET['catid']))
		{
			$safe_category = core::make_safe($_GET['catid']);
			$safe_category = str_replace('-', ' ', $safe_category);
			$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` WHERE `category_name` = ?", array($safe_category));
			if ($db->num_rows() == 0)
			{
				$_SESSION['message'] = 'none_found';
				$_SESSION['message_extra'] = 'categories';
				header("Location: /index.php?module=articles&view=cat");
				die();
			}
			$get_category = $db->fetch();

			$templating->set_previous('meta_description', 'GamingOnLinux viewing Linux gaming news from the '.$get_category['category_name'].' category', 1);
			$templating->set_previous('title', 'Article category: ' . $get_category['category_name'], 1);

			$templating->block('category');
			$templating->set('category', $get_category['category_name']);

			$categorys_ids[] = $get_category['category_id'];
		}
		else
		{
			$templating->set_previous('meta_description', 'No category was picked', 1);
			$templating->set_previous('title', 'No category was picked', 1);
		}
	}

	// show the category selection box
	$templating->block('articles_top', 'articles');
	$options = '';
	$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
	while ($get_cats = $db->fetch())
	{
		$selected = '';
		if (isset($categorys_ids) && in_array($get_cats['category_id'], $categorys_ids))
		{
			$selected = 'selected';
		}
		$options .= '<option value="'.$get_cats['category_id'].'" ' . $selected . '>'.$get_cats['category_name'].'</option>';
	}
	$templating->set('options', $options);

	$all_check = '';
	$any_check = 'checked';
	if (isset($_GET['type']))
	{
		if ($_GET['type'] == 'any')
		{
			$any_check = 'checked';
			$all_check = '';
		}
		if ($_GET['type'] == 'all')
		{
			$all_check = 'checked';
			$any_check = '';
		}
	}
	$templating->set('any_check', $any_check);
	$templating->set('all_check', $all_check);

	if (isset($_GET['catid']) && !empty($_GET['catid']))
	{
		$types_allowed = ['any', 'all'];
		// from clicking a single category tag, it wouldn't be set yet
		if (!isset($_GET['type']))
		{
			$type = 'any';
		}
		else if (in_array($_GET['type'], $types_allowed))
		{
			$type = $_GET['type'];
		}
		else
		{
			$type = 'any';
		}

		// sanitize
		$safe_ids = core::make_safe($categorys_ids);

		foreach ($safe_ids as $k => $safe)
		{
			$safe_ids[$k] = strip_tags($safe);
		}

		// this is really ugly, but I can't think of a better way to do it
		$count_array = count($safe_ids);
		$counter = 1;

		$cat_sql = '';
		foreach ($safe_ids as $cat)
		{
			$cat_sql .= '?';
			if ($counter < $count_array)
			{
				$cat_sql .= ',';
			}
			$counter++;
		}
		$cat_sql = sprintf(" r.`category_id` IN (%s) ", $cat_sql);

		$all_sql = '';
		if ($type == 'all')
		{
			$all_sql = ' having count(r.`category_id`) = ' . $count_array;
		}

		// count how many there is in total
		$db->sqlquery("SELECT r.`article_id` FROM `article_category_reference` r JOIN `articles` a ON a.`article_id` = r.`article_id` WHERE $cat_sql GROUP BY r.`article_id` $all_sql", $safe_ids);
		$total_items = $db->num_rows();

		if ($total_items == 0)
		{
			$_SESSION['message'] = 'none_found';
			$_SESSION['message_extra'] = 'articles';
			header("Location: /index.php?module=articles&view=multiple");
			die();
		}

		// which pagination link to give them
		if ($_GET['view'] == 'multiple')
		{
			$for_url = '';
			foreach ($safe_ids as $cat_url_id)
			{
				$safe_url_id = core::make_safe($cat_url_id);
				$for_url .= 'catid[]=' . $safe_url_id . '&amp;';
			}

			$paging_url = "/index.php?module=articles&view=multiple&" . $for_url;
		}
		else if ($_GET['view'] == 'cat')
		{
			$paging_url = "/index.php?module=articles&view=cat&catid=" . $get_category['category_name'] . '&amp;';
		}

		$articles_per_page = 15;
		if (core::is_number($_SESSION['articles-per-page']))
		{
			$articles_per_page = $_SESSION['articles-per-page'];
		}

		// sort out the pagination link
		$pagination = $core->pagination_link($articles_per_page, $total_items, $paging_url, $page);

		$db->sqlquery("SELECT
			r.`article_id`,
			a.`author_id`,
			a.`title`,
			a.`slug`,
			a.`tagline`,
			a.`text`,
			a.`date`,
			a.`comment_count`,
			a.`guest_username`,
			a.`tagline_image`,
			a.`show_in_menu`,
			a.`gallery_tagline`,
			t.`filename` as gallery_tagline_filename,
			u.`username`
			FROM `article_category_reference` r
			JOIN `articles` a ON a.`article_id` = r.`article_id`
			LEFT JOIN `users` u on a.`author_id` = u.`user_id`
			LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
			WHERE $cat_sql AND a.`active` = 1
			GROUP BY r.`article_id` $all_sql
			ORDER BY a.`date` DESC LIMIT {$core->start}, $articles_per_page", $safe_ids);
		$articles_get = $db->fetch_all_rows();

		$count_rows = $db->num_rows();

		if ($count_rows > 0)
		{
			$article_id_array = array();

			foreach ($articles_get as $article)
			{
				$article_id_array[] = $article['article_id'];
			}
			$article_id_sql = implode(', ', $article_id_array);

			// this is required to properly count up the rank for the tags
			$db->sqlquery("SET @rank=null, @val=null");

			$category_tag_sql = "SELECT * FROM (
				SELECT r.article_id, c.`category_name` , c.`category_id`,
				@rank := IF( @val = r.article_id, @rank +1, 1 ) AS rank, @val := r.article_id
				FROM  `article_category_reference` r
				INNER JOIN  `articles_categorys` c ON c.category_id = r.category_id
				WHERE r.article_id
				IN ( $article_id_sql )
				ORDER BY CASE WHEN ($cat_sql) THEN 0 ELSE 1 END, r.`article_id` ASC
				) AS a
				WHERE rank < 5";
			$db->sqlquery($category_tag_sql, $safe_ids);
			$get_categories = $db->fetch_all_rows();

			foreach ($articles_get as $article)
			{
				// make date human readable
				$date = $core->format_date($article['date']);

				// get the article row template
				$templating->block('article_row');

				if ($user->check_group(1) == true)
				{
					$templating->set('edit_link', "<p><a href=\"/admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"> <strong>Edit</strong></a>");
					if ($article['show_in_menu'] == 0)
					{
						$templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><strong>Make Editors Pick</strong></a></p>");
					}
					else if ($article['show_in_menu'] == 1)
					{
						$templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=removeeditors&amp;article_id={$article['article_id']}\"><strong>Remove Editors Pick</strong></a></p>");
					}
				}

				else
				{
					$templating->set('edit_link', '');
					$templating->set('editors_pick_link', '');
				}

				$templating->set('title', $article['title']);
				$templating->set('user_id', $article['author_id']);

				if ($article['author_id'] == 0)
				{
					if (empty($article['guest_username']))
					{
						$username = 'Guest';
					}

					else
					{
						$username = $article['guest_username'];
					}
				}

				else
				{
					$username = "<a href=\"/profiles/{$article['author_id']}\">" . $article['username'] . '</a>';
				}

				$templating->set('username', $username);

				$templating->set('date', $date);

				$editors_pick = '';
				if ($article['show_in_menu'] == 1)
				{
					$editors_pick = '<li><a href="#">Editors Pick</a></li>';
				}
				$categories_list = $editors_pick;

				foreach ($get_categories as $k => $category_list)
				{
					if (in_array($article['article_id'], $category_list))
					{
						$category_link = article_class::tag_link($category_list['category_name']);

						if ($category_list['category_id'] == 60)
						{
							$categories_list .= " <li class=\"ea\"><a href=\"$category_link\">{$category_list['category_name']}</a></li> ";
						}
						else
						{
							$categories_list .= " <li><a href=\"$category_link\">{$category_list['category_name']}</a></li> ";
						}
					}
				}

				$templating->set('categories_list', $categories_list);

				$tagline_image = $article_class->tagline_image($article);

				$templating->set('top_image', $tagline_image);

				// set last bit to 0 so we don't parse links in the tagline
				$templating->set('text', bbcode($article['tagline'], 1, 0));
				
				$templating->set('article_link', article_class::get_link($article['article_id'], $article['slug']));
				$templating->set('comment_count', $article['comment_count']);
			}

			$templating->block('bottom');
			$templating->set('pagination', $pagination);
		}
	}
}
else 
{
	$_SESSION['message'] = 'empty';
	$_SESSION['message_extra'] = 'category';
	header("Location: /index.php?module=search");
	die();
}
