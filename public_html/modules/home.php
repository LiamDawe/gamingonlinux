<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('meta_description', $core->config('meta_description'), 1);

$templating->load('home');

if (!isset($_GET['view']))
{
	$templating->set_previous('title', $core->config('meta_homepage_title'), 1);

	$templating->block('articles_top', 'home');
	
	$quick_nav = '';
	if ($core->config('quick_nav') == 1)
	{
		$quick_nav = $templating->block_store('quick_nav', 'home');
		
		$quick_tag_hidden = [];
		$quick_tag_normal = [];
		$qn_res = $dbl->run("SELECT `category_name` FROM `articles_categorys` WHERE `quick_nav` = 1")->fetch_all();
		foreach ($qn_res as $get_quick)
		{
			$quick_tag_hidden[] = '<li><a href="'.$article_class->tag_link($get_quick['category_name']).'">'.$get_quick['category_name'].'</a></li>';
			$quick_tag_normal[] = ' <a class="link_button" href="'.$article_class->tag_link($get_quick['category_name']).'">'.$get_quick['category_name'].'</a> ';
		}
		
		$quick_nav = $templating->store_replace($quick_nav, ['quick_tag_hidden' => implode($quick_tag_hidden), 'quick_tag_normal' => implode($quick_tag_normal)]);
	}
	
	$templating->set('quick_nav', $quick_nav);

	// paging for pagination
	$page = core::give_page();

	// count how many there is in total
	if (($total = $core->get_dbcache('total_articles_active')) === false) // there's no cache
	{
		$total = $dbl->run("SELECT COUNT(`article_id`) FROM `articles` WHERE `active` = 1")->fetchOne();
		$core->set_dbcache('total_articles_active', $total);
	}

	if ($total)
	{
		if (!isset($_GET['displayall']))
		{
			$in  = str_repeat('?,', count($user->blocked_tags) - 1) . '?';
			$pagination_target = '/home/';
			if (!empty($user->blocked_tags) && $user->blocked_tags[0] != 0) // can't rely on counter cache if they're blocking tags
			{
				$total_blocked = $dbl->run("SELECT count(*) FROM article_category_reference c LEFT JOIN `articles` a ON a.article_id = c.article_id WHERE c.`category_id` IN ( $in ) AND a.active = 1", $user->blocked_tags)->fetchOne();
				$total = $total - $total_blocked;
			}
		}
		else
		{
			$in = '?';
			$user->blocked_tags = [0 => 0];
			$pagination_target = '/all-articles/';

			$templating->block('view_all');
		}

		$per_page = 15;
		if (isset($_SESSION['articles-per-page']) && is_numeric($_SESSION['articles-per-page']))
		{
			$per_page = $_SESSION['articles-per-page'];
		}
		
		$last_page = ceil($total/$per_page);
		
		if ($page > $last_page)
		{
			$page = $last_page;
		}

		// sort out the pagination link
		$pagination = $core->pagination_link($per_page, $total, $pagination_target, $page);

		// latest news
		$query = "SELECT a.`article_id`, a.`author_id`, a.`guest_username`, a.`title`, a.`tagline`, a.`text`, a.`date`, a.`comment_count`, a.`tagline_image`, a.`show_in_menu`, a.`slug`, a.`gallery_tagline`, t.`filename` as gallery_tagline_filename, u.`username`, u.`profile_address` FROM `articles` a LEFT JOIN `users` u on a.`author_id` = u.`user_id` LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` WHERE a.`active` = 1 AND NOT EXISTS (SELECT 1 FROM article_category_reference c  WHERE a.article_id = c.article_id AND c.`category_id` IN ( $in )) ORDER BY a.`date` DESC LIMIT ?, ?";

		$articles_get = $dbl->run($query, array_merge($user->blocked_tags, [$core->start], [$per_page]))->fetch_all();

		if ($articles_get)
		{
			$article_id_array = array();

			foreach ($articles_get as $article)
			{
				$article_id_array[] = $article['article_id'];
			}
			$article_id_sql = implode(', ', $article_id_array);

			$get_categories = $article_class->find_article_tags(array('article_ids' => $article_id_sql, 'limit' => 5));
			
			$templating->load('articles');

			$article_class->display_article_list($articles_get, $get_categories);

			$templating->block('bottom', 'home');
			$templating->set('pagination', $pagination);
		}
	}
}

if (isset($_GET['view']) && $_GET['view'] == 'editors')
{
	if ($core->config('total_featured') == $core->config('editor_picks_limit'))
	{
		$_SESSION['message'] = 'toomanypicks';
		$_SESSION['message_extra'] = $core->config('editor_picks_limit');
		header("Location: ".$core->config('website_url')."index.php?module=home");
	}

	else
	{
		header("Location: ".$core->config('website_url')."admin.php?module=featured&view=add&article_id={$_GET['article_id']}");
	}
}

if (isset($_GET['view']) && $_GET['view'] == 'removeeditors')
{
	if (isset($_GET['article_id']) && is_numeric($_GET['article_id']))
	{
		$article_class->remove_editor_pick($_GET['article_id']);

		header("Location: ".$core->config('website_url')."index.php?module=home");
	}
}
