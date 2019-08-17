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
	$page = core::give_page();
	$per_page = 50;
	$page_url = '/index.php?module=search&q='.$search_text.'&';

	$total = $dbl->run("SELECT COUNT(*) FROM `articles` a LEFT JOIN `users` u ON a.`author_id` = u.`user_id` WHERE a.`active` = 1 AND a.`title` LIKE ? ORDER BY a.`date` DESC", array($search_through))->fetchOne();

	$last_page = ceil($total/$per_page);
		
	if ($page > $last_page)
	{
		$page = $last_page;
	}

	$pagination = $core->pagination_link($per_page, $total, $page_url, $page);

	// do the search query
	$found_search = $dbl->run("SELECT a.`article_id`, a.`title`, a.`slug`, a.`author_id`, a.`date` , a.`guest_username`, u.`username`, a.`show_in_menu`
	FROM `articles` a
	LEFT JOIN `users` u ON a.`author_id` = u.`user_id`
	WHERE a.`active` = 1
	AND a.`title` LIKE ?
	ORDER BY a.`date` DESC
	LIMIT $core->start , $per_page", array($search_through))->fetch_all();

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
	$user_details = $dbl->run("SELECT $db_grab_fields `username`, `article_bio` FROM `users` WHERE `user_id` = ?", array($_GET['author_id']))->fetch();
	if ($user_details)
	{
		// paging for pagination
		$page = core::give_page();
		
		// count how many there is in total
		$total = $dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE active = 1 AND `author_id` = ?", array($_GET['author_id']))->fetchOne();

		$per_page = 15;
		if (isset($_SESSION['per-page']) && is_numeric($_SESSION['per-page']) && $_SESSION['per-page'] > 0)
		{
			$per_page = $_SESSION['per-page'];
		}

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
		$found_search = $dbl->run("SELECT a.article_id, a.`title`, a.`slug`, a.author_id, a.`date`, a.guest_username, a.`show_in_menu`, u.`username` FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.active = 1 and a.`author_id` = ? ORDER BY a.date DESC LIMIT ?, 15", array($_GET['author_id'], $core->start))->fetch_all();

		if ($total > 0)
		{
			$templating->set_previous('title', 'Viewing articles by ' . $user_details['username'], 1);
			$templating->set_previous('meta_description', 'Viewing articles on GamingOnLinux written by ' . $user_details['username'], 1);

			// article author information
			$templating->block('author_top');
			$templating->set('username', $user_details['username']);
			$templating->set('profile_link', $core->config('website_url') . 'profiles/' . $found_search[0]['author_id']);

			if (isset($user_details['article_bio']) && !empty($user_details['article_bio']))
			{
				$templating->block('about_author');
				$templating->set('author_bio', $bbcode->parse_bbcode($user_details['article_bio']));

				$profile_fields_output = user::user_profile_icons($profile_fields, $user_details);

				if (!empty($profile_fields_output))
				{
					$profile_fields_output = '<br /><br />Find me in these places: <ul class="social_icons_search">' . $profile_fields_output . '</ul>';
				}

				$templating->set('profile_fields', $profile_fields_output);
			}

			$templating->block('author_bottom');

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
?>
