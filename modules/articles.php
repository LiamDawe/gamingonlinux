<?php
$templating->merge('articles');

if (!isset($_GET['view']) && !isset($_POST['act']))
{
	// better than showing a blank page
	$templating->set_previous('title', 'Nothing to see here', 1);
	$core->message("There must have been an error as accessing this page directly doesn't do anything, be sure to report exactly what you did.", NULL, 1);
}

if (isset($_GET['view']) && !isset($_POST['act']))
{
	// paging for pagination
	$page = 1; // start at one, just so it's always set
	if (!isset($_GET['page']) || $_GET['page'] <= 0)
	{
		$page = 1;
	}

	else if (is_numeric($_GET['page']) && $_GET['page'] > 0)
	{
		$page = $_GET['page'];
	}

	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'none')
		{
			$core->message("No articles found, try some different categories!");
		}
	}

	if ($_GET['view'] == 'multiple')
	{
		$templating->set_previous('meta_description', 'GamingOnLinux viewing Linux gaming news from mutiple categories', 1);
		$templating->set_previous('title', 'Searching for articles in multiple categories', 1);

		$templating->block('multi', 'articles');

		$templating->block('articles_top', 'articles');
		$options = '';
		$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
		while ($get_cats = $db->fetch())
		{
			$selected = '';
			if (isset($_GET['catid']))
			{
				if (in_array($get_cats['category_id'], $_GET['catid']))
				{
					$selected = 'selected';
				}
			}
			$options .= '<option value="'.$get_cats['category_id'].'" ' . $selected . '>'.$get_cats['category_name'].'</option>';
		}
		$templating->set('options', $options);

		$all_check = '';
		$any_check = '';
		if (isset($_GET['type']))
		{
			if ($_GET['type'] == 'any')
			{
				$any_check = 'checked';
			}
			if ($_GET['type'] == 'all')
			{
				$all_check = 'checked';
			}
		}
		$templating->set('any_check', $any_check);
		$templating->set('all_check', $all_check);

		if (isset($_GET['catid']))
		{
			if (!isset($_GET['type']))
			{
				$type = 'any';
			}
			else
			{
				$type = $_GET['type'];
			}

			// this is really ugly, but I can't think of a better way to do it
			$cat_sql = ' r.`category_id` IN (';
			$count_array = count($_GET['catid']);
			$counter = 1;
			foreach ($_GET['catid'] as $cat)
			{
				$cat_sql .= '?';
				if ($counter < $count_array)
				{
					$cat_sql .= ',';
				}
				$counter++;
			}
			$cat_sql .= ') ';
			$category_ids = implode(', ', $_GET['catid']);

			$all_sql = '';
			if ($type == 'all')
			{
				$all_sql = ' having count(r.`category_id`) = ' . $count_array;
			}
			// count how many there is in total
			$db->sqlquery("SELECT r.`article_id` FROM `article_category_reference` r JOIN `articles` a ON a.article_id = r.article_id WHERE $cat_sql GROUP BY r.article_id $all_sql", $_GET['catid']);

			$total_items = $db->num_rows();

			$for_url = '';
			foreach ($_GET['catid'] as $cat_url_id)
			{
				$for_url .= 'catid[]=' . $cat_url_id . '&amp;';
			}

			$paging_url = "/index.php?module=articles&view=multiple&{$for_url}";

			// sort out the pagination link
			$pagination = $core->pagination_link($_SESSION['articles-per-page'], $total_items, $paging_url, $page);

			$db->sqlquery("SELECT r.article_id, a.author_id, a.title, a.slug, a.tagline, a.text, a.date, a.comment_count, a.guest_username, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.show_in_menu, a.`gallery_tagline`, t.filename as gallery_tagline_filename, u.username FROM `article_category_reference` r JOIN `articles` a ON a.article_id = r.article_id LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `articles_tagline_gallery` t ON t.id = a.gallery_tagline WHERE $cat_sql AND a.active = 1 GROUP BY r.article_id $all_sql ORDER BY a.`date` DESC LIMIT {$core->start}, {$_SESSION['articles-per-page']}", $_GET['catid']);
			$articles_get = $db->fetch_all_rows();

			if ($db->num_rows() == 0)
			{
				header("Location: /index.php?module=articles&view=multiple&message=none");
			}
			else
			{
				$count_rows = $db->num_rows();
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
					ORDER BY CASE WHEN (r.`category_id` IN (?)) THEN 0 ELSE 1 END, r.`article_id` ASC
				) AS a
				WHERE rank < 5";
				$db->sqlquery($category_tag_sql, array($category_ids));
				$get_categories = $db->fetch_all_rows();

				foreach ($articles_get as $article)
				{
					// make date human readable
					$date = $core->format_date($article['date']);

					// get the article row template
					$templating->block('article_row');

					if ($user->check_group(1) == true)
					{
						$templating->set('edit_link', "<p><a href=\"/admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-pencil\"></span>  <strong>Edit</strong></a>");
						if ($article['show_in_menu'] == 0)
						{
							$templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-heart-empty\"></span> <strong>Make Editors Pick</strong></a></p>");
						}
						else if ($article['show_in_menu'] == 1)
						{
							$templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=removeeditors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-remove-circle\"></span> <strong>Remove Editors Pick</strong></a></p>");
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
					$templating->set('text', bbcode($article['tagline'], 1, 0));

					$templating->set('article_link', $article['slug'] . '.' . $article['article_id']);
					$templating->set('comment_count', $article['comment_count']);
				}

				$templating->block('bottom');
				$templating->set('pagination', $pagination);
			}
		}
	}

	// grab articles from the selected category
	if ($_GET['view'] == 'cat')
	{
		if (isset($_GET['catid']))
		{
			$category_checker = str_replace('-', ' ', $_GET['catid']);
			$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` WHERE `category_name` = ? OR `category_id` = ?", array($category_checker,$category_checker));
			if ($db->num_rows() == 1)
			{
				$get_category = $db->fetch();
				$category_id = $get_category['category_id'];

				$templating->set_previous('meta_description', 'GamingOnLinux viewing Linux gaming news from the '.$get_category['category_name'].' category', 1);
				$templating->set_previous('title', 'Article category: ' . $get_category['category_name'], 1);

				$templating->block('category');
				$templating->set('category', $get_category['category_name']);

				// count how many there is in total
				$db->sqlquery("SELECT `article_id` FROM `article_category_reference` WHERE `category_id` = ?", array($category_id));
				$total_pages = $db->num_rows();

				$category_name_paging = str_replace(' ', '-', $get_category['category_name']);
				if (core::config('pretty_urls') == 1)
				{
					$paging_url = "/articles/category/{$category_name_paging}/";
				}
				else
				{
					$paging_url = "/index.php?module=articles&view=cat&catid={$category_name_paging}&";
				}

				// sort out the pagination link
				$pagination = $core->pagination_link($_SESSION['articles-per-page'], $total_pages, $paging_url, $page);

				$db->sqlquery("SELECT c.article_id, a.author_id, a.title, a.slug, a.tagline, a.text, a.date, a.comment_count, a.guest_username, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.show_in_menu, a.gallery_tagline, t.filename as gallery_tagline_filename, u.`username` FROM `article_category_reference` c JOIN `articles` a ON a.article_id = c.article_id LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN articles_tagline_gallery t ON t.`id` = a.`gallery_tagline` WHERE c.category_id = ? AND a.active = 1 ORDER BY a.`date` DESC LIMIT ?, {$_SESSION['articles-per-page']}", array($category_id, $core->start));
				$articles_get = $db->fetch_all_rows();

				if ($db->num_rows() == 0)
				{
					$core->message("No articles found in that category!");
				}

				else
				{
					$count_rows = $db->num_rows();
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
						ORDER BY CASE WHEN (r.`category_id` = ?) THEN 0 ELSE 1 END, r.`article_id` ASC
					) AS a
					WHERE rank < 5";
					$db->sqlquery($category_tag_sql, array($category_id));
					$get_categories = $db->fetch_all_rows();

					$templating->block('articles_top');
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

					foreach ($articles_get as $article)
					{
						// make date human readable
						$date = $core->format_date($article['date']);

						// get the article row template
						$templating->block('article_row');

						if ($user->check_group(1) == true)
						{
							$templating->set('edit_link', "<p><a href=\"/admin.php?module=articles&amp;view=Edit&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-pencil\"></span>  <strong>Edit</strong></a>");
							if ($article['show_in_menu'] == 0)
							{
								$templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=editors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-heart-empty\"></span> <strong>Make Editors Pick</strong></a></p>");
							}
							else if ($article['show_in_menu'] == 1)
							{
								$templating->set('editors_pick_link', " <a href=\"/index.php?module=home&amp;view=removeeditors&amp;article_id={$article['article_id']}\"><span class=\"glyphicon glyphicon-remove-circle\"></span> <strong>Remove Editors Pick</strong></a></p>");
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
						$templating->set('text', bbcode($article['tagline'], 1, 0));

						$templating->set('article_link', $article['slug'] . '.' . $article['article_id']);
						$templating->set('comment_count', $article['comment_count']);
					}

					$templating->block('bottom');
					$templating->set('pagination', $pagination);
				}
			}
			else
			{
				$templating->set_previous('meta_description', 'GamingOnLinux category error does not exist', 1);
				$templating->set_previous('title', 'Article category does not exist', 1);

				$core->message($_GET['catid'] . ' does not exist', NULL, 1);
			}
		}
	}
}
