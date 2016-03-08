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
	// grab articles from the selected category
	if ($_GET['view'] == 'cat')
	{
		$templating->set_previous('meta_description', 'GamingOnLinux viewing Linux gaming news from a specific category', 1);
		$templating->set_previous('title', 'Viewing article category', 1);

		if (isset($_GET['catid']))
		{
			if (is_numeric($_GET['catid']))
			{
				// paging for pagination
				if (!isset($_GET['page']) || $_GET['page'] <= 0)
				{
					$page = 1;
				}

				else if (is_numeric($_GET['page']) && $_GET['page'] > 0)
				{
					$page = $_GET['page'];
				}

				$category_id = $_GET['catid'];

				// count how many there is in total
				$db->sqlquery("SELECT `article_id` FROM `article_category_reference` WHERE `category_id` = ?", array($category_id));
				$total_pages = $db->num_rows();

				// sort out the pagination link
				$pagination = $core->pagination_link(14, $total_pages, "/articles/category/$category_id/", $page);

				$db->sqlquery("SELECT c.article_id, a.author_id, a.title, a.slug, a.tagline, a.text, a.date, a.comment_count, a.guest_username, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.show_in_menu, u.username FROM `article_category_reference` c JOIN `articles` a ON a.article_id = c.article_id LEFT JOIN `users` u on a.author_id = u.user_id WHERE c.category_id = ? AND a.active = 1 ORDER BY a.`date` DESC LIMIT ?, 14", array($category_id, $core->start), 'articles.php');
				$articles_get = $db->fetch_all_rows();

				if ($db->num_rows() == 0)
				{
					$core->message("No articles found in that category!");
				}

				else
				{
					$count_rows = $db->num_rows();
					$seperator_counter = 0;

					$templating->block('articles_top');

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
							$top_image = "<img src=\"/uploads/articles/topimages/{$article['article_top_image_filename']}\" class=\"tagline-img\" alt=\"[articleimage]\">";
						}

						if (!empty($article['tagline_image']))
						{
							$top_image = "<img class=\"img-responsive\" src=\"/uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"article-image\">";
						}

						$templating->set('top_image', $top_image);

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
	}
}
