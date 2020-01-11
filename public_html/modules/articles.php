<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('articles');

if (!isset($_GET['view']))
{
	// better than showing a blank page
	$templating->set_previous('title', 'Nothing to see here', 1);
	$core->message("There must have been an error as accessing this page directly doesn't do anything, be sure to report exactly what you did.", 1);
}

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

// for pagination, how many per page and actual page number
$articles_per_page = 15;
if (core::is_number($_SESSION['articles-per-page']))
{
	$articles_per_page = $_SESSION['articles-per-page'];
}

$page = core::give_page();

$allowed_views = ['none', 'cat', 'multiple'];
$view = 'none';
if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views))
{
	$view = $_GET['view'];
}

if (isset($view))
{
	if ($view == 'none')
	{
		$_SESSION['message'] = 'none_found';
		$_SESSION['message_extra'] = 'categories';
		header("Location: /index.php?module=search");
		die();
	}
	
	// viewing a single category
	if ($view == 'cat')
	{
		if (!isset($_GET['catid']) || (isset($_GET['catid']) && empty($_GET['catid'])))
		{
			$_SESSION['message'] = 'none_found';
			$_SESSION['message_extra'] = 'categories';
			header("Location: /index.php?module=search");
			die();
		}

		$safe_category = strip_tags($_GET['catid']);
		$get_category = $dbl->run("SELECT `category_id`, `category_name` FROM `articles_categorys` WHERE `category_name` = ?", array($safe_category))->fetch();
		if (!$get_category)
		{
			$_SESSION['message'] = 'none_found';
			$_SESSION['message_extra'] = 'categories';
			header("Location: /index.php?module=search");
			die();
		};

		$templating->set_previous('meta_description', 'GamingOnLinux viewing Linux gaming news from the '.$get_category['category_name'].' category', 1);
		$templating->set_previous('title', 'Article category: ' . $get_category['category_name'], 1);

		$templating->block('category');
		$templating->set('category', $get_category['category_name']);
		
		$article_class->display_category_picker();
		
		$safe_ids[] = $get_category['category_id'];
		
		$all_sql = '';
		if ($type == 'all')
		{
			$safe_ids[] = $get_category['category_id'];
			$all_sql = 'having count(r.`category_id`) = ?';
		}

		$total_items = $dbl->run("SELECT COUNT(r.`article_id`) FROM `article_category_reference` r JOIN `articles` a ON a.`article_id` = r.`article_id` WHERE r.category_id IN (?) AND a.`active` = 1 $all_sql", $safe_ids)->fetchOne();

		if ($total_items > 0)
		{
			$last_page = ceil($total_items/$articles_per_page);
		
			if ($page > $last_page)
			{
				$page = $last_page;
			}

			$paging_url = "/index.php?module=articles&view=cat&catid=" . $get_category['category_name'] . '&amp;';
			
			// sort out the pagination link
			$pagination = $core->pagination_link($articles_per_page, $total_items, $paging_url, $page);
			
			$cat_sql = 'r.`category_id` IN (?)';
		}
	}
	
	// viewing multiple categories
	else if ($view == 'multiple')
	{
		$templating->set_previous('meta_description', 'GamingOnLinux viewing Linux gaming news from mutiple categories', 1);
		$templating->set_previous('title', 'Searching for articles in multiple categories', 1);

		$templating->block('multi', 'articles');

		if (isset($_GET['catid']) || !isset($_GET['catid']))
		{
			if (!is_array($_GET['catid']))
			{
				$_SESSION['message'] = 'none_found';
				$_SESSION['message_extra'] = 'categories';
				header("Location: /index.php?module=search");
				die();
			}
			$categorys_ids = $_GET['catid'];
		}
		
		$article_class->display_category_picker($categorys_ids);
		
		// sanitize, force to int as that's what we require
		foreach ($categorys_ids as $k => $make_safe)
		{
			$safe_ids[$k] = (int) $make_safe;
		}
		
		// this is really ugly, but I can't think of a better way to do it
		$count_array = count($safe_ids);
		// sort placeholders for sql
		$cat_sql  = 'r.`category_id` IN (' . str_repeat('?,', count($safe_ids) - 1) . '?)';
		
		// count how many there is in total
		$all_sql = '';
		if ($type == 'all')
		{
			$all_sql = 'GROUP BY r.`article_id` having count(r.`category_id`) = ' . $count_array;
		}
		
		// otherwise, pick articles that have any of the selected tags
		$total_items = $dbl->run("SELECT COUNT(*) FROM (SELECT COUNT(r.`article_id`) FROM `article_category_reference` r JOIN `articles` a ON a.`article_id` = r.`article_id` WHERE $cat_sql AND a.`active` = 1 $all_sql) AS `total`", $safe_ids)->fetchOne();
		
		if ($total_items > 0)
		{
			$for_url = '';
			foreach ($safe_ids as $cat_url_id)
			{
				$safe_url_id = core::make_safe($cat_url_id);
				$for_url .= 'catid[]=' . $safe_url_id . '&amp;';
			}

			$last_page = ceil($total_items/$articles_per_page);
		
			if ($page > $last_page)
			{
				$page = $last_page;
			}

			$paging_url = "/index.php?module=articles&view=multiple&amp;" . $for_url . '&amp;type=' . $type . '&amp;';
			
			// sort out the pagination link
			$pagination = $core->pagination_link($articles_per_page, $total_items, $paging_url, $page);
		}
	}
	
	if (isset($total_items) && $total_items > 0)
	{
		$articles_get = $dbl->run("SELECT
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
			$all_sql
			ORDER BY a.`date` DESC LIMIT {$core->start}, $articles_per_page", $safe_ids)->fetch_all();

		$article_id_array = array();

		foreach ($articles_get as $article)
		{
			$article_id_array[] = $article['article_id'];
		}

		$article_id_sql = implode(', ', $article_id_array);

		$get_categories = $article_class->find_article_tags(array('article_ids' => $article_id_sql, 'limit' => 5));
		
		$article_class->display_article_list($articles_get, $get_categories);
				
		$templating->block('bottom');
		$templating->set('pagination', $pagination);
	}
	else
	{
		$core->message('No articles found with those options.');
	}
}
