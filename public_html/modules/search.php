<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
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

$ticked = '';
if (isset($_GET['title_only']) && $_GET['title_only'] == 'on')
{
	$ticked = 'checked';
}
$templating->set('ticked', $ticked);

if (!isset($_GET['q']) && !isset($_GET['author_id']) && !isset($_GET['appid']))
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
$search_sql_text = '';
foreach ($search_array[0] as $item)
{
	$item = str_replace("%","\%", $item);
	$search_through .= '%'.$item.'%';
	$search_sql_text .= '+'.$item;
}

$per_page = 15;
if (isset($_SESSION['articles-per-page']) && is_numeric($_SESSION['articles-per-page']) && $_SESSION['articles-per-page'] > 0)
{
	$per_page = $_SESSION['articles-per-page'];
}

// check there wasn't none found to prevent loops
if (isset($search_text) && !empty($search_text))
{
	$page = core::give_page();
	
	$page_url = '/index.php?module=search&amp;q='.$search_text.'&amp;';

	$search_type = '(`title` LIKE ? OR MATCH(`text`) AGAINST(? IN BOOLEAN MODE))';
	$search_data = array($search_through,'"'.$search_sql_text.'"');
	if (isset($_GET['title_only']) && $_GET['title_only'] == 'on')
	{
		$search_type = '`title` LIKE ?';
		$page_url .= 'title_only=on&amp;';
		$search_data = array($search_through);
	}

	$total = $dbl->run("SELECT count(*) FROM articles WHERE $search_type AND `active` = 1 ",$search_data)->fetchOne();

	$last_page = ceil($total/$per_page);
		
	if ($page > $last_page)
	{
		$page = $last_page;
	}

	$pagination = $core->pagination_link($per_page, $total, $page_url, $page);

	// do the search query
	$found_search = $dbl->run("SELECT a.`article_id`, a.`tagline`, a.`comment_count`, a.`title`, a.`slug`, a.`author_id`, a.`date` , a.`guest_username`, u.`username`, a.`show_in_menu`, a.`tagline_image`, a.`gallery_tagline`, t.`filename` as gallery_tagline_filename
	FROM `articles` a
	LEFT JOIN `users` u ON a.`author_id` = u.`user_id`
	LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
	WHERE a.`active` = 1
	AND $search_type 
	ORDER BY a.`date` DESC
	LIMIT $core->start , $per_page", $search_data)->fetch_all();

	if ($found_search)
	{
		$article_id_array = array();

		foreach ($found_search as $article)
		{
			$article_id_array[] = $article['article_id'];
		}
		$article_id_sql = implode(', ', $article_id_array);

		$get_categories = $article_class->find_article_tags(array('article_ids' => $article_id_sql, 'limit' => 5));

		$templating->load('articles');

		// loop through results
		$article_class->display_article_list($found_search, $get_categories);

		$templating->block('bottom', 'search');
		$start_no = $core->start;
		if ($core->start == 0)
		{
			$start_no = 1;
		}
		$templating->set('search_no_start', $start_no);

		$end_no = $core->start + $per_page;
		if ($end_no > $total)
		{
			$end_no = $total;
		}
		$templating->set('end_no', $end_no);

		$templating->set('total', $total);
		$templating->set('pagination', $pagination);
	}
	else
	{
		$core->message('Nothing was found with those search terms.', 1);
	}
}

if (isset($_GET['author_id']) && is_numeric($_GET['author_id']))
{
	$pagination = '';

	$profile_fields = include dirname ( dirname ( __FILE__ ) ) . '/includes/profile_fields.php';

	$db_grab_fields = '';
	foreach ($profile_fields as $field)
	{
		$db_grab_fields .= "`{$field['db_field']}`,";
	}

	// check they actually exist
	$user_details = $dbl->run("SELECT $db_grab_fields `username`, `user_id`, `article_bio` FROM `users` WHERE `user_id` = ?", array($_GET['author_id']))->fetch();
	if ($user_details)
	{
		// paging for pagination
		$page = core::give_page();
		
		// count how many there is in total
		$total = $dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE active = 1 AND `author_id` = ?", array($_GET['author_id']))->fetchOne();

		//lastpage is = total found / items per page, rounded up.
		if ($total <= 10)
		{
			$lastpage = 1;
		}
		else
		{
			$lastpage = ceil($total/$per_page);
		}

		if ($page > $lastpage)
		{
			$page = $lastpage;
		}

		// sort out the pagination link
		$pagination = $core->pagination_link($per_page, $total, "/index.php?module=search&author_id={$_GET['author_id']}&", $page);

		// do the search query
		$found_search = $dbl->run("SELECT a.`article_id`, a.`title`, a.`slug`, a.`author_id`, a.`tagline`, a.`date`, a.guest_username, a.`show_in_menu`, a.`comment_count`, a.`tagline_image`, a.`gallery_tagline`, t.`filename` as gallery_tagline_filename, u.`username` FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` WHERE a.active = 1 and a.`author_id` = ? ORDER BY a.date DESC LIMIT ?, ?", array($_GET['author_id'], $core->start, $per_page))->fetch_all();
		$templating->set_previous('title', 'Viewing articles by ' . $user_details['username'], 1);
		$templating->set_previous('meta_description', 'Viewing articles on GamingOnLinux written by ' . $user_details['username'], 1);

		// article author information
		$templating->block('author_top');
		$templating->set('username', $user_details['username']);
		$templating->set('profile_link', $core->config('website_url') . 'profiles/' . $user_details['user_id']);

		if (isset($user_details['article_bio']) && !empty($user_details['article_bio']))
		{
			$templating->block('about_author');
			$templating->set('author_bio', $bbcode->parse_bbcode($user_details['article_bio']));

			$profile_fields_output = user::user_profile_icons($profile_fields, $user_details);

			if (!empty($profile_fields_output))
			{
				$profile_fields_output = '<br /><br />Find me in these places: <div class="social_icons_search"><ul>' . $profile_fields_output . '</ul></div>';
			}

			$templating->set('profile_fields', $profile_fields_output);
		}

		$templating->block('author_bottom');

		if ($total > 0)
		{
			$article_id_array = array();

			foreach ($found_search as $article)
			{
				$article_id_array[] = $article['article_id'];
			}
			$article_id_sql = implode(', ', $article_id_array);
	
			$get_categories = $article_class->find_article_tags(array('article_ids' => $article_id_sql, 'limit' => 5));

			$templating->load('articles');

			// loop through results
			$article_class->display_article_list($found_search, $get_categories);
		}
		else
		{
			$core->message('They have posted no articles!');
		}
	}
	else
	{
		$templating->set_previous('title', 'No user found!', 1);
		$core->message("That user doesn't exist!");
	}

	$templating->block('bottom', 'search');
	$start_no = $core->start;
	if ($core->start == 0)
	{
		$start_no = 1;
	}
	$templating->set('search_no_start', $start_no);

	$end_no = $core->start + $per_page;
	if ($end_no > $total)
	{
		$end_no = $total;
	}
	$templating->set('end_no', $end_no);

	$templating->set('total', $total);
	$templating->set('pagination', $pagination);
}

if (isset($_GET['appid']) && is_numeric($_GET['appid']))
{
	$pagination = '';

	// check they actually exist
	$exists = $dbl->run("SELECT `id` FROM `calendar` WHERE `id` = ?", array($_GET['appid']))->fetch();
	if ($exists)
	{
		// paging for pagination
		$page = core::give_page();
		
		// count how many there is in total
		$total = $dbl->run("SELECT COUNT(ia.`article_id`) FROM `article_item_assoc` ia JOIN `articles` a ON ia.article_id = a.article_id WHERE ia.`game_id` = ? AND a.`active` = 1", array($_GET['appid']))->fetchOne();

		//lastpage is = total found / items per page, rounded up.
		if ($total <= 10)
		{
			$lastpage = 1;
		}
		else
		{
			$lastpage = ceil($total/$per_page);
		}

		if ($page > $lastpage)
		{
			$page = $lastpage;
		}

		// sort out the pagination link
		$pagination = $core->pagination_link($per_page, $total, "/index.php?module=search&appid={$_GET['appid']}&", $page);

		// do the search query
		$found_search = $dbl->run("SELECT ia.`article_id`, c.`name`, a.`title`, a.`slug`, a.`author_id`, a.`tagline`, a.`date`, a.guest_username, a.`show_in_menu`, a.`comment_count`, a.`tagline_image`, a.`gallery_tagline`, t.`filename` as gallery_tagline_filename, u.`username` FROM `article_item_assoc` ia JOIN `calendar` c ON ia.`game_id` = c.`id` JOIN `articles` a ON ia.`article_id` = a.`article_id` LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` WHERE a.active = 1 and ia.`game_id` = ? ORDER BY a.date DESC LIMIT ?, ?", array($_GET['appid'], $core->start, $per_page))->fetch_all();
		$templating->set_previous('title', 'Linux gaming articles tagged for ' . $found_search[0]['name'], 1);
		$templating->set_previous('meta_description', 'Linux gaming articles on GamingOnLinux tagged for ' . $found_search[0]['name'], 1);

		$templating->block('game_top');
		$templating->set('appid', $_GET['appid']);
		$templating->set('app_title', $found_search[0]['name']);

		if ($total > 0)
		{
			$article_id_array = array();

			foreach ($found_search as $article)
			{
				$article_id_array[] = $article['article_id'];
			}
			$article_id_sql = implode(', ', $article_id_array);
	
			$get_categories = $article_class->find_article_tags(array('article_ids' => $article_id_sql, 'limit' => 5));

			$templating->load('articles');

			// loop through results
			$article_class->display_article_list($found_search, $get_categories);
		}
		else
		{
			$core->message('They have posted no articles!');
		}
	}
	else
	{
		$templating->set_previous('title', 'App not found!', 1);
		$core->message("Sorry, that app ID doesn't seem to exist.");
	}

	$templating->block('bottom', 'search');
	$start_no = $core->start;
	if ($core->start == 0)
	{
		$start_no = 1;
	}
	$templating->set('search_no_start', $start_no);

	$end_no = $core->start + $per_page;
	if ($end_no > $total)
	{
		$end_no = $total;
	}
	$templating->set('end_no', $end_no);

	$templating->set('total', $total);
	$templating->set('pagination', $pagination);
}
$templating->block('help_links','search');
?>
