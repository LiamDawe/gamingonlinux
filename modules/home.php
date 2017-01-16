<?php
$templating->set_previous('meta_description', 'GamingOnLinux is the home of Linux and SteamOS gaming. Covering Linux Games, SteamOS, Reviews and more.', 1);

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'unsubscribed')
	{
		$core->message("You have been unsubscribed!");
	}
	if ($_GET['message'] == 'unliked')
	{
		$core->message("You have unliked all articles and comments!");
	}
	if ($_GET['message'] == 'activated')
	{
		$core->message("Your account has been activated!");
	}
	if ($_GET['message'] == 'cannotunsubscribe')
	{
		$core->message("Sorry your details didn't match up to unsubscribe you!", NULL, 1);
	}
	if ($_GET['message'] == 'cannotunlike') // this is from the unlike all function
	{
		$core->message("Sorry your details didn't match up to unlike!", NULL, 1);
	}
	if ($_GET['message'] == 'banned')
	{
		$core->message("You were banned, most likely for spamming!", NULL, 1);
	}
	if ($_GET['message'] == 'spam')
	{
		$core->message("You have been sent here to due being flagged as a spammer! Please contact us directly if this is false.");
	}
	if ($_GET['message'] == 'unpicked')
	{
		$core->message("That article is now not an editors pick! What did it do wrong? :(");
	}
	if ($_GET['message'] == 'picked')
	{
		$core->message("That article is now an editors pick! Remember to give it a featured image!");
	}
}

if (isset($_GET['error']) && $_GET['error'] == 'toomanypicks')
{
	$core->message("Sorry there are already " . core::config('editor_picks_limit') . " articles set as editor picks!", NULL, 1);
}

$templating->merge('home');

if (!isset($_GET['view']))
{
	$templating->set_previous('title', 'Linux & SteamOS gaming news', 1);

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

			if ($user->check_group(1,2) == true || $user->check_group(5))
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

			if (core::config('pretty_urls') == 1)
			{
				$article_link = "/articles/" . $article['slug'] . '.' . $article['article_id'];
			}
			else
			{
				$article_link = url . 'index.php?module=articles_full&amp;aid=' . $article['article_id'] . '&amp;title=' . $article['slug'];
			}

			$templating->set('article_link', $article_link);

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
						$category_name = str_replace(' ', '-', $category_list['category_name']);
						if (core::config('pretty_urls') == 1)
						{
							$category_url = "/articles/category/{$category_name}/";
						}
						else
						{
							$category_url = "/index.php?module=articles&view=cat&catid={$category_name}";
						}
						if ($category_list['category_id'] == 60)
						{
							$categories_list .= " <li class=\"ea\"><a href=\"$category_url\">{$category_list['category_name']}</a></li> ";
						}

						else
						{
							$categories_list .= " <li><a href=\"$category_url\">{$category_list['category_name']}</a></li> ";
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
		header("Location: ".url."index.php?module=home&error=toomanypicks");
	}

	else
	{
		header("Location: ".url."admin.php?module=featured&view=add&article_id={$_GET['article_id']}");
	}
}

if (isset($_GET['view']) && $_GET['view'] == 'removeeditors')
{
	$db->sqlquery("SELECT `featured_image` FROM `editor_picks` WHERE `article_id` = ?", array($_GET['article_id']));
	$featured = $db->fetch();

	$db->sqlquery("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($_GET['article_id']));
	unlink(core::config('path') . 'uploads/carousel/' . $featured['featured_image']);

	$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($_GET['article_id']));

	$db->sqlquery("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'total_featured'");

	header("Location: ".url."index.php?module=home&message=unpicked");
}
