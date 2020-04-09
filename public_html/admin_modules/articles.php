<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: admin articles.');
}

$templating->load('admin_modules/admin_module_articles');
$templating->set('article_css', 'articleadmin');

if (!isset($_GET['view']) && !isset($_POST['act']))
{
	$core->message("Looks like you took a wrong turn!");
}

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'Edit')
	{
		$templating->set_previous('title', 'Edit article' . $templating->get('title', 1)  , 1);
		if (!isset($message_map::$error) || $message_map::$error == 0)
		{
			$article_class->reset_sessions();
		}
		$article_id = $_GET['aid'];

		// make sure its a number
		if (!is_numeric($article_id))
		{
			$core->message('That is not a correct Article ID!');
		}

		else
		{
			$article_info_sql = "SELECT
			a.`article_id`,
			a.`title`,
			a.`slug`,
			a.`tagline`,
			a.`text`,
			a.`show_in_menu`,
			a.`active`,
			a.`guest_username`,
			a.`tagline_image`,
			a.`locked`,
			a.`locked_by`,
			a.`locked_date`,
			a.`gallery_tagline`,
			t.`filename` as `gallery_tagline_filename`,
			u.`username`,
			u2.`username` as `username_lock`
			FROM
			`articles` a
			LEFT JOIN
			`users` u on a.author_id = u.user_id
			LEFT JOIN
			`users` u2 ON a.locked_by = u2.user_id
			LEFT JOIN
			`articles_tagline_gallery` t ON t.id = a.gallery_tagline
			WHERE `article_id` = ?";
			$article = $dbl->run($article_info_sql, array($article_id))->fetch();

			if (isset($_GET['unlock']) && $article['locked'] == 1 && $_GET['unlock'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$dbl->run("UPDATE `articles` SET `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($article_id));

				$core->message("You have unlocked the article for others to edit!");

				// we need to re-catch the article info as we have changed lock status
				$article = $dbl->run($article_info_sql, array($article_id))->fetch();
			}

			if (isset($_GET['lock']) && $_GET['lock'] == 1 && $article['locked'] == 0)
			{
				$dbl->run("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], core::$date, $article_id));

				// we need to re-catch the article info as we have changed lock status
				$article = $dbl->run($article_info_sql, array($article_id))->fetch();
			}

			if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$core->message("This post is now locked while you edit, please click Edit to unlock it once finished.", 1);

				// we need to re-catch the article info as we have changed lock status
				$article = $dbl->run($article_info_sql, array($article_id))->fetch();
			}

			$_SESSION['original_text'] = $article['text'];

			$edit_state = '';
			$edit_state_textarea = '';
			$editor_disabled = 0;
			if ($article['locked'] == 1)
			{
				if ($article['locked_by'] != $_SESSION['user_id'])
				{
					$templating->block('edit_locked');
					$templating->set('locked_username', $article['username_lock']);

					$lock_date = $core->human_date($article['locked_date']);

					$templating->set('locked_date', $lock_date);

					$edit_state = 'disabled="disabled"';
					$edit_state_textarea = 'disabled';
					$editor_disabled = 1;
				}
			}
			else if ($article['locked'] == 0)
			{
				$edit_state = 'disabled="disabled"';
				$edit_state_textarea = 'disabled';
				$editor_disabled = 1;
			}

			$templating->block('edit_top', 'admin_modules/admin_module_articles');
			$lock_button = '';
			if ($article['locked'] == 0)
			{
				$lock_button = '<a class="button fleft" href="/admin.php?module=articles&view=Edit&aid=' . $article['article_id'] . '&lock=1">Lock For Editing</a>';
			}
			else if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$lock_button = '<a class="button fleft" href="/admin.php?module=articles&view=Edit&aid=' . $article['article_id'] . '&unlock=1">Unlock Article For Others</a>';
			}
			$templating->set('lock_button', $lock_button);

			// get the edit row
			$templating->load('admin_modules/article_form');
			$templating->block('full_editor', 'admin_modules/article_form');
			$templating->set('max_filesize', core::readable_bytes($core->config('max_tagline_image_filesize')));
			$templating->set('edit_state', $edit_state);
			$templating->set('edit_state_textarea', $edit_state_textarea);

			$brandnew = '';
			if (isset($_GET['brandnew']))
			{
				$brandnew = '&brandnew=1';
			}

			$templating->set('brandnew_check', $brandnew);

			// get categorys
			$cat_res = $dbl->run("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']))->fetch_all();
			foreach ($cat_res as $categories_check)
			{
				$categories_check_array[] = $categories_check['category_id'];
			}

			$categorys_list = '';
			$all_res = $dbl->run("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC")->fetch_all();
			foreach ($all_res as $categorys)
			{
				if (isset($_GET['error']))
				{
					if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
					}
				}

				else
				{
					if (isset($categories_check_array) && in_array($categorys['category_id'], $categories_check_array))
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
					}
				}
			}

			$templating->set('categories_list', $categorys_list);

			$game_tag_list = $article_class->display_previous_games($article['article_id']);
			$templating->set('games_list', $game_tag_list);

			$text = $article['text'];
			$previously_uploaded = '';
			// if they have done it before set title, text and tagline
			if (isset($message_map::$error) && $message_map::$error == 1)
			{
				$templating->set('title', htmlentities($_SESSION['atitle'], ENT_QUOTES));
				$templating->set('tagline', $_SESSION['atagline']);
				$templating->set('slug', $_SESSION['aslug']);

				$text = $_SESSION['atext'];

				// sort out previously uploaded images
				$previously_uploaded	= $article_class->display_previous_uploads();
			}

			else
			{
				$templating->set('title', htmlentities($article['title'], ENT_QUOTES));
				$templating->set('tagline', $article['tagline']);
				$templating->set('slug', $article['slug']);
			}

			$templating->set('main_formaction', '<form class="gol-form" id="article_editor" method="post" action="'.$core->config('website_url').'admin.php?module=articles" enctype="multipart/form-data">');

			if (empty($article['username']))
			{
				$username = $article['guest_username'];
			}

			else
			{
				$username = $article['username'];
			}

			$templating->set('username', $username);

			$tagline_image = $article_class->display_tagline_image($article);
			$templating->set('tagline_image', $tagline_image);

			$tagline_image = '';
			$temp_tagline_image = '';

			// add in uploaded images from database
			$previously_uploaded = $article_class->display_previous_uploads($article['article_id']);

			$templating->set('temp_tagline_image', $temp_tagline_image);

			$templating->set('max_height', $core->config('article_image_max_height'));
			$templating->set('max_width', $core->config('article_image_max_width'));

			$core->article_editor(['content' => $text, 'disabled' => $editor_disabled]);

			$templating->block('edit_bottom', 'admin_modules/admin_module_articles');
			$templating->set('hidden_upload_fields', $previously_uploaded['hidden']);
			$templating->set('edit_state', $edit_state);

			// check if we need to set article appear in the articles block
			if ($article['show_in_menu'] == 1)
			{
				$templating->set('show_block_check', 'checked');
			}

			else
			{
				$templating->set('show_block_check', '');
			}

			if (isset($_GET['error']) && $_GET['error'] == 'tagline_image')
			{
				if ($_SESSION['aactive'] == 1)
				{
					$templating->set('show_article_check', 'checked');
				}

				else
				{
					$templating->set('show_article_check', '');
				}
			}

			else
			{
				if ($article['active'] == 1)
				{
					$templating->set('show_article_check', 'checked');
				}

				else
				{
					$templating->set('show_article_check', '');
				}
			}

			$templating->set('article_id', $article['article_id']);

			$templating->block('uploads', 'admin_modules/article_form');
			$templating->set('previously_uploaded', $previously_uploaded['output']);
			$templating->set('article_id', $article['article_id']);

			$article_class->article_history($article['article_id']);
		}
	}

	// manage articles
	if ($_GET['view'] == 'manage')
	{
		$templating->set_previous('title', 'Articles' . $templating->get('title', 1)  , 1);
		if (!isset($_GET['category_id']) && !isset($_GET['category']))
		{
			$templating->block('manage_cat_top');

			// list categorys and all option
			$cat_res = $dbl->run("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC")->fetch_all();
			foreach ($cat_res as $category)
			{
				$templating->block('manage_cat');
				$templating->set('category_id', $category['category_id']);
				$templating->set('category_name', $category['category_name']);
			}

		}

		// For viewing inactive/all articles
		if (!isset($_GET['category_id']) && isset($_GET['category']))
		{
			// paging for pagination
			if (!isset($_GET['page']) || $_GET['page'] <= 0)
			{
				$page = 1;
			}

			else if (is_numeric($_GET['page']))
			{
				$page = $_GET['page'];
			}

			if ($_GET['category'] == 'inactive')
			{
				$active = 0;
				$paginate_link = "admin.php?module=articles&view=manage&category=inactive&";
				$article_query = "SELECT a.article_id, a.author_id, a.title, a.tagline, a.date, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id  WHERE a.`active` = 0 AND a.`admin_review` = 0 AND a.`draft` = 0 AND a.submitted_unapproved = 0 ORDER BY a.`date` DESC LIMIT ?, 9";
				$count_query = "SELECT COUNT(`article_id`) FROM `articles` WHERE `active` = 0 AND `admin_review` = 0 AND `draft` = 0 AND `submitted_unapproved` = 0";
				$category_name = 'Inactive Articles';
			}

			else if ($_GET['category'] == 'all')
			{
				$active = 1;
				$paginate_link = "admin.php?module=articles&view=manage&category=all&";
				$article_query = "SELECT a.article_id, a.author_id, a.title, a.tagline, a.date, u.username FROM `articles` a JOIN `users` u on a.author_id = u.user_id ORDER BY a.`date` DESC LIMIT ?, 9";
				$count_query = "SELECT COUNT(`article_id`) FROM `articles`";
				$category_name = 'All Articles';
			}

			// count how many there is in total
			$total = $dbl->run($count_query)->fetchOne();

			$templating->block('manage_row_top');
			$templating->set('category_name', $category_name);

			if ($total == 0)
			{
				$core->message('Category empty!');
			}

			else
			{
				// sort out the pagination link
				$pagination = $core->pagination_link(9, $total, $paginate_link, $page);

				$article_manage = $dbl->run($article_query, array($core->start))->fetch_all();

				foreach ($article_manage as $article)
				{
					// make date human readable
					$date = $core->human_date($article['date']);

					// get the article row template
					$templating->block('manage_row');
					$inactive = '';
					if ($_GET['category'] == 'inactive')
					{
						$inactive = '&inactive=1';
					
					}

					$formaction = url.'admin.php?module=articles&view=Edit&aid='.$article['article_id'];
					$delete_action = url.'admin.php?module=articles&article_id='.$article['article_id'].$inactive;

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

					$templating->set('username', $username);
					$templating->set('date', $date);
					$templating->set('article_link', $article_class->get_link($article['article_id']));
					$templating->set('formaction', $formaction);
					$templating->set('delete_button', '<button type="submit" name="act" value="Delete" formaction="'.$delete_action.'">Delete</button>');
				}

				$templating->block('manage_bottom');
				$templating->set('pagination', $pagination);
			}
		}

		// For viewing per-category
		else if (isset($_GET['category_id']))
		{
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
			$total_pages = $dbl->run("SELECT COUNT(c.article_id) FROM `article_category_reference` c JOIN `articles` a ON a.article_id = c.article_id WHERE c.category_id = ? AND a.active = 1", array($_GET['category_id']))->fetchOne();

			$category_name = $dbl->run("SELECT `category_name` FROM `articles_categorys` WHERE `category_id` = ?", array($_GET['category_id']))->fetchOne();

			$templating->block('manage_row_top');
			$templating->set('category_name', $category_name);

			if ($total_pages == 0)
			{
				$core->message('Category empty!');
			}

			else
			{
				// sort out the pagination link
				$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=articles&view=manage&category_id={$_GET['category_id']}&", $page);

				$article_get = $dbl->run("SELECT c.article_id, a.author_id, a.title, a.tagline, a.date, a.guest_username, a.show_in_menu, a.views, u.username FROM `article_category_reference` c JOIN `articles` a ON a.article_id = c.article_id LEFT JOIN `users` u on a.author_id = u.user_id WHERE c.category_id = ? AND a.active = 1 ORDER BY a.`date` DESC LIMIT ?, 9", array($_GET['category_id'], $core->start))->fetch_all();

				foreach ($article_get as $article)
				{
					// make date human readable
					$date = $core->human_date($article['date']);

					// get the article row template
					$templating->block('manage_row');

					$formaction = url.'admin.php?module=articles&view=Edit&aid='.$article['article_id'];
					$delete_action = url.'admin.php?module=articles&article_id='.$article['article_id'];

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

					$templating->set('username', $username);
					$templating->set('date', $date);
					$templating->set('article_link', $article_class->get_link($article['article_id']));
					$templating->set('formaction', $formaction);
					$templating->set('delete_button', '<button type="submit" name="act" value="Delete" formaction="'.$delete_action.'">Delete</button>');
				}

				$templating->block('manage_bottom');
				$templating->set('pagination', $pagination);
			}
		}
	}

	// View all submitted articles that have not yet been approved
	if ($_GET['view'] == 'Submitted')
	{
		$templating->set_previous('title', 'User submitted articles' . $templating->get('title', 1)  , 1);
		include('admin_articles_sections/submitted/view_articles.php');
	}

	// View all submitted articles that have not yet been approved
	if ($_GET['view'] == 'drafts')
	{
		$templating->set_previous('title', 'Article drafts' . $templating->get('title', 1)  , 1);
		include('admin_articles_sections/drafts/view_drafts.php');
	}
}

// this section will load the correct module for what action has been requested
if (isset($_POST['act']))
{
	/*

	// SUBMITTING AN ARTICLE FOR ADMIN REVIEW

	*/
	if ($_POST['act'] == 'review')
	{
		include('admin_articles_sections/review/new_article.php');
	}

	/*
	// Publishing a draft article, used by drafts for publishing directly
	*/
	if ($_POST['act'] == 'add_draft')
	{
		$return_page = '/admin.php?module=articles&view=drafts&aid=' . $_POST['article_id'];
		$article_class->publish_article(['return_page' => $return_page, 'type' => 'draft', 'new_notification_type' => 'new_article_published', 'clear_notification_type' => 'draft']);
	}

	if ($_POST['act'] == 'Edit')
	{
		if ($checked = $article_class->check_article_inputs("/admin.php?module=articles&view=Edit&aid={$_POST['article_id']}"))
		{
			$block = 0;
			if (isset($_POST['show_block']))
			{
				$block = 1;
			}

			$show = 0;
			if (isset($_POST['show_article']))
			{
				$show = 1;
			}

			$article_class->gallery_tagline($checked);

			// first check if it was disabled
			$enabled_check = $dbl->run("SELECT `active` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();

			$dbl->run("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = ?, `locked` = 0, `locked_by` = 0, `locked_date` = 0, `edit_date` = ? WHERE `article_id` = ?", array($checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, $show, core::$sql_date_now, $_POST['article_id']));

			$article_class->process_categories($_POST['article_id']);
			$article_class->process_games($_POST['article_id']);

			if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
			{
				$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name'], $checked['text']);
			}

			// update admin notes if it was disabled
			if (!isset($_POST['show_article']) && $enabled_check['active'] == 1)
			{
				$core->new_admin_note(array('completed' => 1, 'content' => ' disabled an article titled: <a href="/admin.php?module=articles&view=Edit&article_id='.$_POST['article_id'].'">'.$checked['title'].'</a>.'));
			}
			if (isset($_POST['show_article']) && $enabled_check['active'] == 0)
			{
				$core->new_admin_note(array('completed' => 1, 'content' => ' enabled an article titled: <a href="/admin.php?module=articles&view=Edit&article_id='.$_POST['article_id'].'">'.$checked['title'].'</a>.'));
			}

			// update history
			$dbl->run("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));

			// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
			unset($_SESSION['atitle']);
			unset($_SESSION['aslug']);
			unset($_SESSION['atagline']);
			unset($_SESSION['atext']);
			unset($_SESSION['acategories']);
			unset($_SESSION['agames']);
			unset($_SESSION['aactive']);
			unset($_SESSION['uploads']);
			unset($_SESSION['uploads_tagline']);
			unset($_SESSION['image_rand']);
			unset($_SESSION['original_text']);
			unset($_SESSION['gallery_tagline_id']);
			unset($_SESSION['gallery_tagline_rand']);
			
			if (!isset($_POST['show_block']))
			{
				header("Location: /articles/{$checked['slug']}.{$_POST['article_id']}/");
				die();
			}
			else
			{
				$check = $dbl->run("SELECT 1 FROM `editor_picks` WHERE `article_id` = ?", array($_POST['article_id']))->fetchOne();
				if (!$check)
				{
					header("Location: " . $core->config('website_url') . "admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
					die();
				}
				else
				{
					header("Location: /articles/{$checked['slug']}.{$_POST['article_id']}/");
					die();
				}
			}
		}
	}

	if ($_POST['act'] == 'Delete')
	{
		if (isset($_GET['review']) && $_GET['review'] == 1)
		{
			$return_page = "/admin.php?module=reviewqueue";
			$post_page = "/admin.php?module=articles&article_id={$_GET['article_id']}&review=1";
		}
		else if (isset($_GET['inactive']) && $_GET['inactive'] == 1)
		{
			$return_page = "/admin.php?module=articles&view=manage&category=inactive";
			$post_page = "/admin.php?module=articles&article_id={$_GET['article_id']}&inactive=1";			
		}
		else
		{
			$post_page = $return_page = "/admin.php?module=articles&view=manage";
		}

		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$check = $dbl->run("SELECT `active` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']))->fetch();

			// anti-cheese deleting the wrong article feature
			if ($check['active'] == 1)
			{
				$core->message("WARNING: You are about to delete a live article!", 1);
			}

			$core->confirmation(['title' => 'Are you sure you want to delete that article?', 'text' => 'This cannot be undone!', 'action_url' => $post_page, 'act' => 'Delete', 'act_2_name' => 'article_id', 'act_2_value' => $_GET['article_id']]);
		}

		else if (isset($_POST['no']))
		{
			header("Location: $return_page");
			die();
		}

		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_POST['article_id']))
			{
				$core->message('That is not a correct id!');
			}

			else
			{
				// check post exists
				$check = $dbl->run("SELECT `article_id`, `date`, `author_id`, `title`, 'tagline_image' FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();

				if (!$check)
				{
					$core->message("That is not a correct id! Options: <a href=\"$return_page\">Go back</a>.");
				}

				// Delete now
				else
				{
					if ($check['author_id'] == 1 && $_SESSION['user_id'] != 1)
					{
						header("Location: $return_page");
						die();
					}

					else
					{
						$article_class->delete_article($check);
						
						$_SESSION['message'] = 'deleted';
						$_SESSION['message_extra'] = 'article';
						header("Location: $return_page");
						die();
					}
				}
			}
		}
	}

	if ($_POST['act'] == 'Deny')
	{
		include('admin_articles_sections/submitted/deny_submitted.php');
	}

	/*
	// APPROVE A USER SUBMITTED ARTICLE
	*/

	if ($_POST['act'] == 'Approve')
	{
		$return_page = '/admin.php?module=articles&view=Submitted&aid=' . $_POST['article_id'];
		$article_class->publish_article(['return_page' => $return_page, 'type' => 'submitted_article', 'new_notification_type' => 'approve_submitted_article', 'clear_notification_type' => 'submitted_article']);
	}

	// For editing a post from another admin in the review pool
	if ($_POST['act'] == 'Edit_Submitted')
	{
		include('admin_articles_sections/submitted/edit_submitted.php');
	}

	// For editing a post from another admin in the review pool
	if ($_POST['act'] == 'Edit_Draft')
	{
		include('admin_articles_sections/drafts/edit_draft.php');
	}

	if ($_POST['act'] == 'Move_Draft')
	{
		include('admin_articles_sections/drafts/move_draft.php');
	}

	if ($_POST['act'] == 'Save_Draft')
	{
		include('admin_articles_sections/drafts/save_draft.php');
	}

	if ($_POST['act'] == 'delete_draft')
	{
		include('admin_articles_sections/drafts/delete_draft.php');
	}

	if ($_POST['act'] == 'deletetopimage')
	{
		if (!isset($_POST['article_id']))
		{
			$core->message("Not a correct article id set!", 1);
		}

		else
		{
			$article = $dbl->run("SELECT `title`,`tagline_image`, `slug` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();

			// remove old image
			if (!empty($article['tagline_image']))
			{
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']);
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']);
			}

			$dbl->run("UPDATE `articles` SET `tagline_image` = '' WHERE `article_id` = ?", array($_POST['article_id']));

			$core->message("The articles top image has now been deleted from \"{$article['title']}\"! <a href=\"/articles/{$article['slug']}.{$_POST['article_id']}\">Click here to view the article.</a>");
		}
	}
}
