<?php
$templating->set_previous('meta_description', 'GamingOnLinux is the home of Linux and SteamOS gaming. Covering Linux Games, SteamOS, Reviews and more.', 1);

$templating->merge('home');

if (!isset($_GET['view']))
{
	$templating->set_previous('title', 'Linux & SteamOS gaming community', 1);

	$db->sqlquery("SELECT count(id) as count FROM `announcements`");
	$count_announcements = $db->fetch();
	if ($count_announcements['count'] > 0)
	{
		$templating->merge('announcements');
		$templating->block('announcement_top', 'announcements');

		$get_announcements = $db->sqlquery("SELECT `text` FROM `announcements` ORDER BY `id` DESC");
		while ($announcement = $get_announcements->fetch())
		{
			$templating->block('announcement', 'announcements');
			$templating->set('text', bbcode($announcement['text']));
		}

		$templating->block('announcement_bottom', 'announcements');
	}

	$core->check_old_pc_info($_SESSION['user_id']);

	$templating->block('articles_top', 'home');
	
	$templating->set('editorial_link', article_class::tag_link('Editorial'));
	$templating->set('interview_link', article_class::tag_link('Interview'));
	$templating->set('howto_link', article_class::tag_link('HOWTO'));
	$templating->set('reviews_link', article_class::tag_link('Review'));

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

		foreach ($articles_get as $article)
		{
			// make date human readable
			$date = $core->format_date($article['date']);

			// get the article row template
			$templating->block('article_row', 'home');

			if ($user->check_group([1,2,5]))
			{
				$templating->set('edit_link', "<p><a href=\"" . url ."admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-pencil\"></span> <strong>Edit</strong></a>");

				if ($article['show_in_menu'] == 0)
				{
					if (core::config('total_featured') < 5)
					{
						$editor_pick_expiry = $core->format_date($article['date'] + 1209600, 'd/m/y');
						$templating->set('editors_pick_link', " <a class=\"tooltip-top\" title=\"It would expire around now on $editor_pick_expiry\" href=\"".url."index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-heart-empty\"></span> <strong>Make Editors Pick</strong></a></p>");
					}
					else if (core::config('total_featured') == 5)
					{
						$templating->set('editors_pick_link', "");
					}
				}
				else if ($article['show_in_menu'] == 1)
				{
					$templating->set('editors_pick_link', " <a href=\"".url."index.php?module=home&amp;view=removeeditors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-remove-circle\"></span> <strong>Remove Editors Pick</strong></a></p>");
				}
			}

			else
			{
				$templating->set('edit_link', '');
				$templating->set('editors_pick_link', '');
			}

			$templating->set('title', $article['title']);

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

			$templating->set('article_link', article_class::get_link($article['article_id'], $article['slug']));

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
					$category_link = core::config('website_url') . article_class::tag_link($category_list['category_name']);

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
			$templating->set('text', $article['tagline']);

			$templating->set('comment_count', $article['comment_count']);
		}

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
