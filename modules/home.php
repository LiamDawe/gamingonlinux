<?php
$templating->set_previous('meta_description', core::config('meta_description'), 1);

$templating->merge('home');

if (!isset($_GET['view']))
{
	$templating->set_previous('title', core::config('meta_homepage_title'), 1);

	$core->check_old_pc_info($_SESSION['user_id']);

	$templating->block('articles_top', 'home');
	
	$top_of_home_hook = '';
	$top_of_home_hook = plugins::do_hooks('top_of_home_hook');
	$templating->set('top_of_home_hook', $top_of_home_hook);
	
	$quick_nav = '';
	if (core::config('quick_nav') == 1)
	{
		$quick_nav = $templating->block_store('quick_nav', 'home');
		
		$quick_tag_hidden = [];
		$quick_tag_normal = [];
		$db->sqlquery("SELECT `category_name` FROM `articles_categorys` WHERE `quick_nav` = 1");
		while ($get_quick = $db->fetch())
		{
			$quick_tag_hidden[] = '<li><a href="'.article_class::tag_link($get_quick['category_name']).'">'.$get_quick['category_name'].'</a></li>';
			$quick_tag_normal[] = ' <a class="link_button" href="'.article_class::tag_link($get_quick['category_name']).'">'.$get_quick['category_name'].'</a> ';
		}
		
		$quick_nav = $templating->store_replace($quick_nav, ['quick_tag_hidden' => implode($quick_tag_hidden), 'quick_tag_normal' => implode($quick_tag_normal)]);
	}
	
	$templating->set('quick_nav', $quick_nav);

	// paging for pagination
	$page = core::give_page();

	// count how many there is in total
	$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `active` = 1");
	$total = $db->num_rows();

	if ($total > 0)
	{
		if (core::config('pretty_urls') == 1)
		{
			$pagination_linky = "/home/";
		}
		else
		{
			$pagination_linky = url . "index.php?module=home&amp;";
		}

		$per_page = 15;
		if (isset($_SESSION['articles-per-page']) && is_numeric($_SESSION['articles-per-page']))
		{
			$per_page = $_SESSION['articles-per-page'];
		}
		
		$last_page = round($total/$per_page);
		
		if ($page > $last_page)
		{
			$page = $last_page;
		}

		// sort out the pagination link
		$pagination = $core->pagination_link($per_page, $total, $pagination_linky, $page);

		// latest news
		$db->sqlquery("SELECT a.`article_id`, a.`author_id`, a.`guest_username`, a.`title`, a.`tagline`, a.`text`, a.`date`, a.`comment_count`, a.`tagline_image`, a.`show_in_menu`, a.`slug`, a.`gallery_tagline`, t.`filename` as gallery_tagline_filename, u.`username` FROM `articles` a LEFT JOIN `users` u on a.`author_id` = u.`user_id` LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` WHERE a.`active` = 1 ORDER BY a.`date` DESC LIMIT ?, ?", array($core->start, $per_page));
		$articles_get = $db->fetch_all_rows();

		$count_rows = $db->num_rows();
		$seperator_counter = 0;

		$article_id_array = array();

		foreach ($articles_get as $article)
		{
			$article_id_array[] = $article['article_id'];
		}
		$article_id_sql = implode(', ', $article_id_array);

		// this is required to properly count up the rank for the tags
		$db->sqlquery("SET @rank=null, @val=null");

		$category_tag_sql = "SELECT * FROM (
			SELECT r.article_id, c.`category_name` , c.`category_id` , @rank := IF( @val = r.article_id, @rank +1, 1 ) AS rank, @val := r.article_id
			FROM  `article_category_reference` r
			INNER JOIN  `articles_categorys` c ON c.category_id = r.category_id
			WHERE r.article_id
			IN ( $article_id_sql )
			ORDER BY CASE WHEN (r.`category_id` = 60) THEN 0 ELSE 1 END, r.`article_id` ASC
		) AS a
		WHERE rank < 5";
		$db->sqlquery($category_tag_sql);
		$get_categories = $db->fetch_all_rows();
		
		$templating->merge('articles');

		article_class::display_article_list($articles_get, $get_categories);

		$templating->block('bottom', 'home');
		$templating->set('pagination', $pagination);
	}
}

if (isset($_GET['view']) && $_GET['view'] == 'editors')
{
	if (core::config('total_featured') == core::config('editor_picks_limit'))
	{
		$_SESSION['message'] = 'toomanypicks';
		header("Location: ".core::config('website_url')."index.php?module=home");
	}

	else
	{
		header("Location: ".core::config('website_url')."admin.php?module=featured&view=add&article_id={$_GET['article_id']}");
	}
}

if (isset($_GET['view']) && $_GET['view'] == 'removeeditors')
{
	if ($user->check_group([1,2,5]))
	{
		if (isset($_GET['article_id']) && is_numeric($_GET['article_id']))
		{
			$db->sqlquery("SELECT `featured_image` FROM `editor_picks` WHERE `article_id` = ?", array($_GET['article_id']));
			$featured = $db->fetch();

			$db->sqlquery("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($_GET['article_id']));
			unlink(core::config('path') . 'uploads/carousel/' . $featured['featured_image']);

			$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($_GET['article_id']));

			$db->sqlquery("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'total_featured'");

			$_SESSION['message'] = 'unpicked';
			header("Location: ".core::config('website_url')."index.php?module=home");
		}
	}
}
