<?php
$templating->set_previous('meta_description', 'GamingOnLinux is the home of Linux and SteamOS gaming. Covering Linux Games, SteamOS, Reviews and more.', 1);

if (isset($_GET['user_id']))
{
	if (!isset($_SESSION['activated']) && $_SESSION['user_id'] != 0)
	{
		$db->sqlquery("SELECT `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		$get_active = $db->fetch();
		$_SESSION['activated'] = $get_active['activated'];
	}
}

if (isset($_SESSION['activated']) && $_SESSION['activated'] == 0)
{
	$core->message("Your account isn't activated, to begin posting you need to activate your account via email! <a href=\"/index.php?module=activate_user&redo=1\">Click here to re-send a new activation key</a>!");
}

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'unsubscribed')
	{
		$core->message("You have been unsubscribed!");
	}
	if ($_GET['message'] == 'activated')
	{
		$core->message("Your account has been activated!");
	}
	if ($_GET['message'] == 'cannotunsubscribe')
	{
		$core->message("Sorry your details didn't match up to unsubscribe you!", NULL, 1);
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
		$templating->block('announcement_top', 'home');

		$db->sqlquery("SELECT `text` FROM `announcements` ORDER BY `id` DESC");
		while ($announcement = $db->fetch())
		{
			$templating->block('announcement', 'home');
			$templating->set('text', $announcement['text']);
		}

		$templating->block('announcement_bottom', 'home');
	}

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

	if (core::config('pretty_urls') == 1)
	{
		$pagination_linky = "/home/";
	}
	else {
		$pagination_linky = url . "index.php?module=home&amp;";
	}

	// sort out the pagination link
	$pagination = $core->pagination_link(14, $total, $pagination_linky, $page);

	$db->sqlquery("SELECT count(article_id) as count FROM `articles` WHERE `show_in_menu` = 1");
	$featured_ctotal = $db->fetch();
	// latest news
	$db->sqlquery("SELECT a.article_id, a.author_id, a.guest_username, a.title, a.tagline, a.text, a.date, a.comment_count, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.show_in_menu, a.slug, u.username  FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.active = 1 ORDER BY a.`date` DESC LIMIT ?, 14", array($core->start));
	$articles_get = $db->fetch_all_rows();

	$count_rows = $db->num_rows();
	$seperator_counter = 0;

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
				if ($featured_ctotal['count'] < 5)
				{
					$editor_pick_expiry = $core->format_date($article['date'] + 1209600, 'd/m/y');
					$templating->set('editors_pick_link', " <a class=\"tooltip-top\" title=\"It would expire around now on $editor_pick_expiry\" href=\"".url."index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-heart-empty\"></span> <strong>Make Editors Pick</strong></a></p>");
				}
				else if ($featured_ctotal['count'] == 5)
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

		// sort out the categories (tags)
		$categories_list = $editors_pick;
		$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? LIMIT 4", array($article['article_id']));
		while ($get_categories = $db->fetch())
		{
			if ($get_categories['category_id'] == 60)
			{
				$categories_list .= " <li class=\"ea\"><a href=\"/articles/category/{$get_categories['category_id']}\">{$get_categories['category_name']}</a></li> ";
			}

			else
			{
				$categories_list .= " <li><a href=\"/articles/category/{$get_categories['category_id']}\">{$get_categories['category_name']}</a></li> ";
			}
		}

		$templating->set('categories_list', $categories_list);

		$top_image = '';
		if ($article['article_top_image'] == 1)
		{
			$top_image = "<img alt src=\"".url."uploads/articles/topimages/{$article['article_top_image_filename']}\">";
		}
		if (!empty($article['tagline_image']))
		{
			$top_image = "<img alt src=\"".url."uploads/articles/tagline_images/{$article['tagline_image']}\">";
		}

		$templating->set('top_image', $top_image);

		// set last bit to 0 so we don't parse links in the tagline
		$templating->set('text', $article['tagline']);

		$templating->set('comment_count', $article['comment_count']);
	}

	$templating->block('bottom', 'home');
	$templating->set('pagination', $pagination);
}

if (isset($_GET['view']) && $_GET['view'] == 'editors')
{
	// count how many editors picks we have
	$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");

	$editor_pick_count = $db->num_rows();

	if ($editor_pick_count == core::config('editor_picks_limit'))
	{
		header("Location: ".url."index.php?module=home&error=toomanypicks");
	}

	else
	{
		$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 1 WHERE `article_id` = ?", array($_GET['article_id']));

		header("Location: ".url."admin.php?module=featured&view=add&article_id={$_GET['article_id']}");
	}
}

if (isset($_GET['view']) && $_GET['view'] == 'removeeditors')
{
	$db->sqlquery("SELECT `featured_image` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
	$featured = $db->fetch();

	$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 0, `featured_image` = '' WHERE `article_id` = ?", array($_GET['article_id']));
	unlink($_SERVER['DOCUMENT_ROOT'] . url . 'uploads/carousel/' . $featured['featured_image']);

	header("Location: ".url."index.php?module=home&message=unpicked");
}
