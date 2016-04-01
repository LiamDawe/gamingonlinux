<?php
$templating->merge('admin_modules/admin_module_articles');
$templating->set('article_css', 'articleadmin');

if (!isset($_GET['view']) && !isset($_POST['act']))
{
	$core->message("Looks like you took a wrong turn!");
}

if (isset($_GET['view']))
{
	// add article
	if ($_GET['view'] == 'add')
	{
		$templating->set_previous('title', 'New article ' . $templating->get('title', 1)  , 1);
		if (!isset($_GET['error']))
		{
			$_SESSION['image_rand'] = rand();
		}

		if (isset ($_GET['error']))
		{
			if ($_GET['error'] == 'empty')
			{
				$core->message('You have to fill in a title, tagline and text!', NULL, 1);
			}

			else if ($_GET['error'] == 'shorttagline')
			{
				$core->message('The tagline was too short, it needs to be at least 100 characters to be informative!', NULL, 1);
			}

			else if ($_GET['error'] == 'taglinetoolong')
			{
				$core->message('The tagline was too long, it needs to be 400 characters or less!', NULL, 1);
			}

			else if ($_GET['error'] == 'shorttitle')
			{
				$core->message('The title was too short, make it informative!', NULL, 1);
			}

			else if ($_GET['error'] == 'toomanypicks')
			{
				$core->message('There are already 3 articles set as editor picks!', NULL, 1);
			}

			else if ($_GET['error'] == 'noimageselected')
			{
				$core->message('You didn\'t select a tagline image to upload with the article, all articles must have one!', NULL, 1);
			}
		}

		$templating->block('add', 'admin_modules/admin_module_articles');

		$templating->merge('admin_modules/article_form');
		$templating->block('full_editor', 'admin_modules/article_form');
		$templating->set('main_formaction', '<form method="post" action="'.core::config('website_url').'admin.php?module=articles" enctype="multipart/form-data">');
		$templating->set('max_filesize', core::readable_bytes(core::config('max_tagline_image_filesize')));

		// get categorys
		$categorys_list = '';
		$db->sqlquery("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC");
		while ($categorys = $db->fetch())
		{
			if (isset($_GET['error']))
			{
				if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
				{
					$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
				}

				else
				{
					$categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
				}
			}

			else
			{
				$categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
			}
		}

		// if they have done it before set text and tagline
		$title = '';
		$text = '';
		$tagline = '';
		$tagline_image = '';
		$temp_tagline_image = '';
		$previously_uploaded = '';

		if (isset($_GET['error']))
		{
			$title = $_SESSION['atitle'];
			$tagline = $_SESSION['atagline'];
			$text = $_SESSION['atext'];

			if ($_GET['temp_tagline'] == 1)
			{
				$types = array('jpg', 'png', 'gif');
				$file = $_SERVER['DOCUMENT_ROOT'] . "/uploads/articles/topimages/temp/{$_SESSION['username']}_article_tagline.";
				$image_load = false;
				foreach ($types as $type)
				{
					if (file_exists($file . $type))
					{
						$image_load = "/uploads/articles/topimages/temp/{$_SESSION['username']}_article_tagline." . $type;
						$temp_tagline_image = "{$_SESSION['username']}_article_tagline." . $type;
						break;
					}
				}
				$tagline_image = "<img src=\"$image_load\">";
			}
			// sort out previously uploaded images
			if (isset($_SESSION['uploads']))
			{
				foreach($_SESSION['uploads'] as $key)
				{
					if ($key['image_rand'] == $_SESSION['image_rand'])
					{
						$previously_uploaded .= "<div class=\"box\"><div class=\"body group\"><div class=\"col-md-12\" id=\"{$key['image_id']}\"><img src=\"/uploads/articles/article_images/{$key['image_name']}\" class='imgList'><br />
						BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]{$config['website_url']}/uploads/articles/article_images/{$key['image_name']}[/img]\" /></div>
						<a href=\"#\" id=\"{$key['image_id']}\" class=\"trash\">Delete Image</a></div></div></div>";
					}
				}
			}
		}

		$templating->set('tagline_image', $tagline_image);
		$templating->set('temp_tagline_image', $temp_tagline_image);

		$templating->set('title', $title);
		$templating->set('tagline', $tagline);
		$templating->set('text', $text);

		$templating->set('categories_list', $categorys_list);
		$templating->set('max_height', $config['article_image_max_height']);
		$templating->set('max_width', $config['article_image_max_width']);

		$db->sqlquery("SELECT `auto_subscribe_new_article` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		$grab_subscribe = $db->fetch();

		$auto_subscribe = '';
		if ($grab_subscribe['auto_subscribe_new_article'] == 1)
		{
			$auto_subscribe = 'checked';
		}

		$core->editor('text', $text, 1);

		$templating->block('add_bottom', 'admin_modules/admin_module_articles');
		$templating->set('previously_uploaded', $previously_uploaded);
		$templating->set('subscribe_check', $auto_subscribe);
	}

	if ($_GET['view'] == 'Edit')
	{
		$templating->set_previous('title', 'Edit article' . $templating->get('title', 1)  , 1);
		if (!isset($_GET['error']))
		{
			$_SESSION['image_rand'] = rand();
		}
		$article_id = $_GET['article_id'];

		// make sure its a number
		if (!is_numeric($article_id))
		{
			$core->message('That is not a correct Article ID!');
		}

		else
		{
			$db->sqlquery("SELECT a.article_id, a.title, a.slug, a.tagline, a.text, a.show_in_menu, a.active, a.guest_username, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.locked, a.locked_by, a.locked_date, u.username, u2.username as username_lock FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `users` u2 ON a.locked_by = u2.user_id WHERE `article_id` = ?", array($article_id));

			$article = $db->fetch();

			if ($article['locked'] == 1 && $_GET['unlock'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$db->sqlquery("UPDATE `articles` SET `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($article['article_id']));

				$core->message("You have unlocked the article for others to edit!");

				// we need to re-catch the article info as we have changed lock status
				$db->sqlquery("SELECT a.article_id, a.title, a.slug, a.text, a.tagline, a.show_in_menu, a.active, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.guest_username, a.author_id, a.locked, a.locked_by, a.locked_date, u.username, u2.username as username_lock FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `users` u2 ON a.locked_by = u2.user_id WHERE `article_id` = ?", array($article_id), 'view_articles.php admin review');

				$article = $db->fetch();
			}

			if ($article['locked'] == 0 && $_GET['lock'] == 1)
			{
				$db->sqlquery("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], core::$date, $article['article_id']));

				// we need to re-catch the article info as we have changed lock status
				$db->sqlquery("SELECT a.article_id, a.title, a.slug, a.text, a.tagline, a.show_in_menu, a.active, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.guest_username, a.author_id, a.locked, a.locked_by, a.locked_date, u.username, u2.username as username_lock FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `users` u2 ON a.locked_by = u2.user_id WHERE `article_id` = ?", array($article_id), 'view_articles.php admin review');

				$article = $db->fetch();
			}

			if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$core->message("This post is now locked while you edit, please click Edit to unlock it once finished.", NULL, 1);

				// we need to re-catch the article info as we have changed lock status
				$db->sqlquery("SELECT a.article_id, a.title, a.slug, a.text, a.tagline, a.show_in_menu, a.active, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.guest_username, a.author_id, a.locked, a.locked_by, a.locked_date, u.username, u2.username as username_lock FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id LEFT JOIN `users` u2 ON a.locked_by = u2.user_id WHERE `article_id` = ?", array($article_id), 'view_articles.php admin review');

				$article = $db->fetch();
			}

			$edit_state = '';
			$edit_state_textarea = '';
			$editor_disabled = 0;
			if ($article['locked'] == 1)
			{
				if ($article['locked_by'] != $_SESSION['user_id'])
				{
					$templating->block('edit_locked');
					$templating->set('locked_username', $article['username_lock']);

					$lock_date = $core->format_date($article['locked_date']);

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

			if (isset ($_GET['error']))
			{
				if ($_GET['error'] == 'empty')
				{
					$core->message('You have to fill in a title, tagline and text!', NULL, 1);
				}

				else if ($_GET['error'] == 'shorttagline')
				{
					$core->message('The tagline was too short, it needs to be at least 100 characters to be informative!', NULL, 1);
				}

				else if ($_GET['error'] == 'taglinetoolong')
				{
					$core->message('The tagline was too long, it needs to be 400 characters or less!', NULL, 1);
				}

				else if ($_GET['error'] == 'shorttitle')
				{
					$core->message('The title was too short, make it informative!', NULL, 1);
				}

				else if ($_GET['error'] == 'toomanypicks')
				{
					$core->message('There are already 3 articles set as editor picks!', NULL, 1);
				}

				else if ($_GET['error'] == 'tagline_image')
				{
					$brandnew = '';
					if (isset($_GET['brandnew']))
					{
						$brandnew = '<br /><strong>NOTE:</strong> The article has been posted, but <strong>it is not yet active</strong> because of this issue!';
					}

					$core->message($_SESSION['tagerror'] . $brandnew, NULL, 1);
				}
			}

			$templating->block('edit_top', 'admin_modules/admin_module_articles');
			$lock_button = '';
			if ($article['locked'] == 0)
			{
				$lock_button = '<a class="button_link" href="/admin.php?module=articles&view=Edit&article_id=' . $article['article_id'] . '&lock=1">Lock For Editing</a><hr />';
			}
			else if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$lock_button = '<a class="button_link" href="/admin.php?module=articles&view=Edit&article_id=' . $article['article_id'] . '&unlock=1">Unlock Article For Others</a><hr />';
			}
			$templating->set('lock_button', $lock_button);

			// get the edit row
			$templating->merge('admin_modules/article_form');
			$templating->block('full_editor', 'admin_modules/article_form');
			$templating->set('max_filesize', core::readable_bytes(core::config('max_tagline_image_filesize')));
			$templating->set('edit_state', $edit_state);
			$templating->set('edit_state_textarea', $edit_state_textarea);

			$brandnew = '';
			if (isset($_GET['brandnew']))
			{
				$brandnew = '&brandnew=1';
			}

			$templating->set('brandnew_check', $brandnew);

			// get categorys
			$db->sqlquery("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']));
			while($categories_check = $db->fetch())
			{
				$categories_check_array[] = $categories_check['category_id'];
			}

			$categorys_list = '';
			$db->sqlquery("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC");
			while ($categorys = $db->fetch())
			{
				if (isset($_GET['error']))
				{
					if (!empty($_SESSION['acategories']) && in_array($categorys['category_id'], $_SESSION['acategories']))
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
					}

					else
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
					}
				}

				else
				{

					if (isset($categories_check_array) && in_array($categorys['category_id'], $categories_check_array))
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
					}

					else
					{
						$categorys_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
					}
				}
			}

			$templating->set('categories_list', $categorys_list);

			// if they have done it before set title, text and tagline
			if (isset($_GET['error']))
			{
				$templating->set('title', htmlentities($_SESSION['atitle'], ENT_QUOTES));
				$templating->set('tagline', $_SESSION['atagline']);
				$templating->set('slug', $_SESSION['aslug']);
			}

			else
			{
				$templating->set('title', htmlentities($article['title'], ENT_QUOTES));
				$templating->set('tagline', $article['tagline']);
				$templating->set('slug', $article['slug']);
			}

			$templating->set('main_formaction', '<form method="post" action="'.$config['website_url'].'admin.php?module=articles" enctype="multipart/form-data">');

			if (empty($article['username']))
			{
				$username = $article['guest_username'];
			}

			else
			{
				$username = $article['username'];
			}

			$templating->set('username', $username);

			$top_image = '';
			$top_image_delete = '';
			if ($article['article_top_image'] == 1)
			{
				$top_image = "<img src=\"/uploads/articles/topimages/{$article['article_top_image_filename']}\" alt=\"[articleimage]\" class=\"imgList\"><br />";
				$top_image_delete = " <button class=\"btn btn-danger\" name=\"act\" value=\"deletetopimage\" $edit_state>Delete Top Image</button>";
			}
			if (!empty($article['tagline_image']))
			{
				$top_image = "<img src=\"/uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
Full Image Url: <a href=\"http://www.gamingonlinux.com/uploads/articles/tagline_images/{$article['tagline_image']}\" target=\"_blank\">Click Me</a><br />";
				$top_image_delete = " <button class=\"btn btn-danger\" name=\"act\" value=\"deletetopimage\" $edit_state>Delete Top Image</button>";
			}
			$templating->set('tagline_image', $top_image);

			$tagline_image = '';
			$temp_tagline_image = '';
			$previously_uploaded = '';

			if (isset($_GET['error']))
			{
				$title = $_SESSION['atitle'];
				$tagline = $_SESSION['atagline'];
				$text = $_SESSION['atext'];

				if ($_GET['temp_tagline'] == 1)
				{
					$types = array('jpg', 'png', 'gif');
					$file = $_SERVER['DOCUMENT_ROOT'] . "/uploads/articles/topimages/temp/{$_SESSION['username']}_article_tagline.";
					$image_load = false;
					foreach ($types as $type)
					{
						if (file_exists($file . $type))
						{
							$image_load = "/uploads/articles/topimages/temp/{$_SESSION['username']}_article_tagline." . $type;
							$temp_tagline_image = "{$_SESSION['username']}_article_tagline." . $type;
							break;
						}
					}
					$tagline_image = "<img src=\"$image_load\">";
					$templating->set('top_image', $tagline_image);
				}

				// sort out previously uploaded images
				if (isset($_SESSION['uploads']))
				{
					foreach($_SESSION['uploads'] as $key)
					{
						$bbcode = "[img]{$config['website_url']}/uploads/articles/article_images/{$key['image_name']}[/img]";
						$previously_uploaded .= "<div class=\"box\"><div class=\"body group\"><div id=\"{$key['image_id']}\"><img src=\"/uploads/articles/article_images/{$key['image_name']}\" class='imgList'><br />
						BBCode: <input type=\"text\" class=\"form-control\" value=\"{$bbcode}\" /></div>
						<button data-bbcode=\"{$bbcode}\" class=\"add_button\">Add to editor</button> <button id=\"{$key['image_id']}\" class=\"trash\">Delete image</button>
						</div></div></div>";
					}
				}
			}

			// add in uploaded images from database
			$db->sqlquery("SELECT `filename`,`id` FROM `article_images` WHERE `article_id` = ?", array($article['article_id']));
			$article_images = $db->fetch_all_rows();

			foreach($article_images as $value)
			{
				$bbcode = "[img]{$config['website_url']}/uploads/articles/article_images/{$value['filename']}[/img]";
				$previously_uploaded .= "<div class=\"box\"><div class=\"body group\"><div id=\"{$value['id']}\"><img src=\"/uploads/articles/article_images/{$value['filename']}\" class='imgList'><br />
				BBCode: <input type=\"text\" class=\"form-control\" value=\"{$bbcode}\" />
				<button data-bbcode=\"{$bbcode}\" class=\"add_button\">Add to editor</button> <button id=\"{$value['id']}\" class=\"trash\">Delete image</button></div></div></div>";
			}

			$templating->set('previously_uploaded', $previously_uploaded);

			$templating->set('temp_tagline_image', $temp_tagline_image);

			$templating->set('max_height', $config['article_image_max_height']);
			$templating->set('max_width', $config['article_image_max_width']);


			// if they have done it before set title, text and tagline
			$text = $article['text'];
			if (isset($_GET['error']))
			{
				$text = $_SESSION['atext'];
			}

			$core->editor('text', $text, 1, $editor_disabled);

			$templating->block('edit_bottom', 'admin_modules/admin_module_articles');
			$templating->set('edit_state', $edit_state);

			$templating->set('previously_uploaded', $previously_uploaded);

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

			$templating->set('top_image_delete', $top_image_delete);
			$templating->set('article_id', $article['article_id']);

			$db->sqlquery("SELECT u.`username`, u.`user_id`, a.`date` FROM `users` u INNER JOIN `article_history` a ON a.user_id = u.user_id WHERE a.article_id = ? ORDER BY a.id DESC LIMIT 10", array($article_id));
			$history = '';
			while ($grab_history = $db->fetch())
			{
				$date = $core->format_date($grab_history['date']);
				$history .= '<li><a href="/profiles/'. $grab_history['user_id'] .'">' . $grab_history['username'] . '</a> - ' . $date . '</li>';
			}

			$templating->block('edit_history', 'admin_modules/admin_module_articles');
			$templating->set('history', $history);
			$templating->block('edit_bottom_history', 'admin_modules/admin_module_articles');
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
			$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
			while ($category = $db->fetch())
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
				$article_query = "SELECT a.article_id, a.title, a.tagline, a.text, a.date, a.comment_count, a.views, a.article_top_image, a.article_top_image_filename, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id  WHERE a.`active` = 0 AND a.`admin_review` = 0 AND a.`draft` = 0 AND a.submitted_unapproved = 0 ORDER BY a.`date` DESC LIMIT ?, 9";
				$count_query = "SELECT `article_id` FROM `articles` WHERE `active` = 0 AND `admin_review` = 0 AND `draft` = 0 AND `submitted_unapproved` = 0";
			}

			else if ($_GET['category'] == 'all')
			{
				$active = 1;
				$paginate_link = "admin.php?module=articles&view=manage&category=all&";
				$article_query = "SELECT a.article_id, a.title, a.tagline, a.text, a.date, a.comment_count, a.views, a.article_top_image, a.article_top_image_filename, u.username FROM `articles` a JOIN `users` u on a.author_id = u.user_id ORDER BY a.`date` DESC LIMIT ?, 9";
				$count_query = "SELECT `article_id` FROM `articles`";
			}

			// count how many there is in total
			$db->sqlquery($count_query);
			$total = $db->num_rows();

			if ($total == 0)
			{
				$core->message('Category empty!');
			}

			else
			{
				// sort out the pagination link
				$pagination = $core->pagination_link(9, $total, $paginate_link, $page);

				$db->sqlquery($article_query, array($core->start));
				$article_manage = $db->fetch_all_rows();

				foreach ($article_manage as $article)
				{
					// make date human readable
					$date = $core->format_date($article['date']);

					// get the article row template
					$templating->block('manage_row');

					// sort out the categories (tags)
					$categories_list = '';
					$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? LIMIT 4", array($article['article_id']));
					while ($get_categories = $db->fetch())
					{
						$categories_list .= " <a href=\"/articles/category/{$get_categories['category_id']}\"><span class=\"label label-info\">{$get_categories['category_name']}</span></a> ";
					}

					if (!empty($categories_list))
					{
						$categories_list = '<p class="small muted">In: ' . $categories_list . '</p>';
					}
					$templating->set('categories_list', $categories_list);

					$templating->set('title', $article['title']);
					$templating->set('username', $article['username']);
					$templating->set('date', $date);
					$templating->set('text', bbcode($article['tagline']));
					$templating->set('article_id', $article['article_id']);
					$templating->set('comment_count', $article['comment_count']);
					$templating->set('views', $article['views']);
					$templating->set('article_link', $core->nice_title($article['title']) . '.' . $article['article_id']);

					$top_image = '';
					if ($article['article_top_image'] == 1)
					{
						$top_image = "<img data-src=\"holder.js/350x220\" alt=\"article-image\" src=\"/uploads/articles/topimages/{$article['article_top_image_filename']}\">";
					}
					$templating->set('top_image', $top_image);
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
			$db->sqlquery("SELECT `article_id` FROM `articles`");
			$total_pages = $db->num_rows();

			// sort out the pagination link
			$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=articles&view=manage&category_id={$_GET['category_id']}&", $page);

			$db->sqlquery("SELECT c.article_id, a.author_id, a.title, a.tagline, a.text, a.date, a.comment_count, a.guest_username, a.article_top_image, a.article_top_image_filename, a.show_in_menu, a.views, u.username FROM `article_category_reference` c JOIN `articles` a ON a.article_id = c.article_id LEFT JOIN `users` u on a.author_id = u.user_id WHERE c.category_id = ? AND a.active = 1 ORDER BY a.`date` DESC LIMIT ?, 9", array($_GET['category_id'], $core->start));
			$article_get = $db->fetch_all_rows();

			foreach ($article_get as $article)
			{
				// make date human readable
				$date = $core->format_date($article['date']);

				// get the article row template
				$templating->block('manage_row');

				// sort out the categories (tags)
				$categories_list = '';
				$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ? LIMIT 4", array($article['article_id']));
				while ($get_categories = $db->fetch())
				{
					$categories_list .= " <a href=\"/articles/category/{$get_categories['category_id']}\"><span class=\"label label-info\">{$get_categories['category_name']}</span></a> ";
				}

				if (!empty($categories_list))
				{
					$categories_list = '<p class="small muted">In: ' . $categories_list . '</p>';
				}
				$templating->set('categories_list', $categories_list);

				$templating->set('title', $article['title']);
				$templating->set('username', $article['username']);
				$templating->set('date', $date);
				$templating->set('text', bbcode($article['tagline']));
				$templating->set('article_id', $article['article_id']);
				$templating->set('comment_count', $article['comment_count']);
				$templating->set('views', $article['views']);
				$templating->set('article_link', $core->nice_title($article['title']) . '.' . $article['article_id']);

				$top_image = '';
				if ($article['article_top_image'] == 1)
				{
					$top_image = "<img data-src=\"holder.js/350x220\" alt=\"article-image\" src=\"/uploads/articles/topimages/{$article['article_top_image_filename']}\">";
				}
				$templating->set('top_image', $top_image);
			}

			$templating->block('manage_bottom');
			$templating->set('pagination', $pagination);
		}
	}

	// View all submitted articles that have not yet been approved
	if ($_GET['view'] == 'Submitted')
	{
		$templating->set_previous('title', 'User submitted articles' . $templating->get('title', 1)  , 1);
		include('admin_articles_sections/submitted/view_articles.php');
	}

	if ($_GET['view'] == 'comments')
	{
		$templating->set_previous('title', 'Article comments' . $templating->get('title', 1)  , 1);
		if (!isset($_GET['ip_id']))
		{
			// paging for pagination
			if (!isset($_GET['page']))
			{
				$page = 1;
			}

			else if (is_numeric($_GET['page']))
			{
				$page = $_GET['page'];
			}

			$templating->block('comments_top', 'admin_modules/admin_module_articles');

			// if we have just deleted one tell us
			if (isset($_GET['deleted']) && $_GET['deleted'] == 1)
			{
				$core->message('That comment report has been deleted (you marked it as not spam).');
			}

			// count how many there is in total
			$db->sqlquery("SELECT `comment_id` FROM `articles_comments` WHERE `spam` = 1");
			$total_pages = $db->num_rows();

			/* get any spam reported comments in a paginated list here */
			$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=article&amp;view=comments", $page);

			$db->sqlquery("SELECT a.*, t.title, u.username, u.user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u2.username as reported_by_username FROM `articles_comments` a INNER JOIN `articles` t ON a.article_id = t.article_id LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` u2 on a.spam_report_by = u2.user_id WHERE a.spam = 1 ORDER BY a.`comment_id` ASC LIMIT ?, 9", array($core->start));
			while ($comments = $db->fetch())
			{
				// make date human readable
				$date = $core->format_date($comments['time_posted']);

				if ($comments['author_id'] == 0)
				{
					$username = $comments['guest_username'];
					$quote_username = $comments['guest_username'];
				}
				else
				{
					$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
					$quote_username = $comments['username'];
				}

				// sort out the avatar
				// either no avatar (gets no avatar from gravatars redirect) or gravatar set
				if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
				{
					$comment_avatar = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=https://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
				}

				// either uploaded or linked an avatar
				else
				{
					$comment_avatar = $comments['avatar'];
					if ($comments['avatar_uploaded'] == 1)
					{
						$comment_avatar = "/uploads/avatars/{$comments['avatar']}";
					}
				}

				$editor_bit = '';
				// check if editor or admin
				if ($comments['user_group'] == 1 || $comments['user_group'] == 2)
				{
					$editor_bit = "<span class=\"comments-editor\">Editor</span>";
				}

				$templating->block('article_comments', 'admin_modules/admin_module_articles');
				$templating->set('user_id', $comments['author_id']);
				$templating->set('username', $username);
				$templating->set('editor', $editor_bit);
				$templating->set('comment_avatar', $comment_avatar);
				$templating->set('date', $date);
				$templating->set('text', bbcode($comments['comment_text']));
				$templating->set('quote_username', $quote_username);
				$templating->set('reported_by', "<a href=\"/profiles/{$comments['spam_report_by']}\">{$comments['reported_by_username']}");
				$templating->set('comment_id', $comments['comment_id']);
				$templating->set('article_title', $comments['title']);
				$templating->set('article_link', $core->nice_title($comments['title']) . '.' . $comments['article_id']);
			}

			$templating->block('comment_reports_bottom', 'admin_modules/admin_module_articles');
			$templating->set('pagination', $pagination);
		}

		else if (isset($_GET['ip_id']))
		{
			// paging for pagination
			if (!isset($_GET['page']))
			{
				$page = 1;
			}

			else if (is_numeric($_GET['page']))
			{
				$page = $_GET['page'];
			}

			// if we have just deleted one tell us
			if (isset($_GET['deleted']) && $_GET['deleted'] == 1)
			{
				$core->message('That comment has been deleted.');
			}

			$templating->block('guest_comments_top', 'admin_modules/admin_module_articles');

			// count how many there is in total
			$db->sqlquery("SELECT `guest_ip` FROM `articles_comments` WHERE `comment_id` = ?", array($_GET['ip_id']));
			$total_pages = $db->num_rows();
			$get_that_ip = $db->fetch();

			$usernames = '';
			// find any users with that IP address
			$db->sqlquery("SELECT `username` FROM `users` WHERE `ip` = ?", array($get_that_ip['guest_ip']));
			$total_users = $db->num_rows();
			if ($total_users > 0)
			{
				while ($set_users = $db->fetch())
				{
					$usernames = $set_users['username'] . ' ';
				}
			}
			else
			{
				$usernames = 'None';
			}

			$templating->set('ip_address', $get_that_ip['guest_ip']);
			$templating->set('username_list', $usernames);

			/* get any spam reported comments in a paginated list here */
			$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=article&amp;view=comments", $page);

			$db->sqlquery("SELECT a.*, t.title, u.username, u.user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u2.username as reported_by_username FROM `articles_comments` a INNER JOIN `articles` t ON a.article_id = t.article_id LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` u2 on a.spam_report_by = u2.user_id WHERE a.guest_ip = ? ORDER BY a.`comment_id` ASC LIMIT ?, 9", array($get_that_ip['guest_ip'],$core->start));
			while ($comments = $db->fetch())
			{
				// make date human readable
				$date = $core->format_date($comments['time_posted']);

				if ($comments['author_id'] == 0)
				{
					$username = $comments['guest_username'];
					$quote_username = $comments['guest_username'];
				}
				else
				{
					$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
					$quote_username = $comments['username'];
				}

				// sort out the avatar
				// either no avatar (gets no avatar from gravatars redirect) or gravatar set
				if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
				{
					$comment_avatar = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=https://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
				}

				// either uploaded or linked an avatar
				else
				{
					$comment_avatar = $comments['avatar'];
					if ($comments['avatar_uploaded'] == 1)
					{
						$comment_avatar = "/uploads/avatars/{$comments['avatar']}";
					}
				}

				$editor_bit = '';
				// check if editor or admin
				if ($comments['user_group'] == 1 || $comments['user_group'] == 2)
				{
					$editor_bit = "<span class=\"comments-editor\">Editor</span>";
				}

				$templating->block('guest_comment_row', 'admin_modules/admin_module_articles');
				$templating->set('user_id', $comments['author_id']);
				$templating->set('username', $username);
				$templating->set('editor', $editor_bit);
				$templating->set('comment_avatar', $comment_avatar);
				$templating->set('date', $date);
				$templating->set('text', bbcode($comments['comment_text']));
				$templating->set('quote_username', $quote_username);
				$templating->set('reported_by', "<a href=\"/profiles/{$comments['spam_report_by']}\">{$comments['reported_by_username']}");
				$templating->set('comment_id', $comments['comment_id']);
				$templating->set('article_title', $comments['title']);
				$templating->set('article_link', $core->nice_title($comments['title']) . '.' . $comments['article_id']);
			}

			$templating->block('comment_reports_bottom', 'admin_modules/admin_module_articles');
			$templating->set('pagination', $pagination);
		}
	}

	// View all submitted articles that have not yet been approved
	if ($_GET['view'] == 'drafts')
	{
		$templating->set_previous('title', 'Article drafts' . $templating->get('title', 1)  , 1);
		include('admin_articles_sections/drafts/view_drafts.php');
	}
}

// this section will load the correct module for what action has been requested
else if (isset($_POST['act']))
{
	/*

	// SUBMITTING AN ARTICLE FOR ADMIN REVIEW

	*/
	if ($_POST['act'] == 'review')
	{
		include('admin_articles_sections/review/new_article.php');
	}

	/*
	// Publishing a fresh article, used by drafts and new article from admin for publishing directly
	*/
	if ($_POST['act'] == 'add')
	{
		include('admin_articles_sections/add_article.php');
	}

	if ($_POST['act'] == 'Add_Review_Comment')
	{
		// make sure news id is a number
		if (!is_numeric($_POST['aid']))
		{
			$core->message('Article id was not a number! Stop trying to do something naughty!');
		}

		else if ($parray['comment_on_articles'] == 0)
		{
			$core->message('You do not have permisions to comment on articles, you may need to be <a href="index.php?module=register">Registered</a> and <a href="index.php?module=login">Logged in</a> to be able to comment! Or else your user group doesn\'t have permissions to comment!');
		}

		else
		{
			// get article name for the email and redirect
			$db->sqlquery("SELECT `title`, `comment_count` FROM `articles` WHERE `article_id` = ?", array($_POST['aid']));
			$title = $db->fetch();
			$title_nice = $core->nice_title($title['title']);

			$page = 1;
			if ($title['comment_count'] > 9)
			{
				$page = ceil($title['comment_count']/9);
			}

			// check empty
			if (empty($_POST['text']))
			{
				header("Location: /admin.php?module=reviewqueue&aid={$_POST['aid']}&error=emptycomment");
			}

			else
			{
				$comment = htmlspecialchars($_POST['text'], ENT_QUOTES);
				$article_id = $_POST['aid'];

				$db->sqlquery("INSERT INTO `articles_comments` SET `article_id` = ?, `author_id` = ?, `time_posted` = ?, `comment_text` = ?", array($_POST['aid'], $_SESSION['user_id'], core::$date, $comment));

				$new_comment_id = $db->grab_id();

				// check if they are subscribing
				if (isset($_POST['subscribe']) && $_SESSION['user_id'] != 0)
				{
					// make sure we don't make lots of doubles
					$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $article_id));

					$emails = 0;
					if (isset($_POST['emails']))
					{
						$emails = 1;
					}

					$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = ?", array($_SESSION['user_id'], $article_id, $emails));
				}

				// email anyone subscribed which isn't you
				$db->sqlquery("SELECT s.`user_id`, s.emails, u.email, u.username FROM `articles_subscriptions` s INNER JOIN `users` u ON s.user_id = u.user_id WHERE `article_id` = ?", array($article_id));
				$users_array = array();
				while ($users = $db->fetch())
				{
					if ($users['user_id'] != $_SESSION['user_id'] && $users['emails'] == 1)
					{
						$users_array[$users['user_id']]['user_id'] = $users['user_id'];
						$users_array[$users['user_id']]['email'] = $users['email'];
						$users_array[$users['user_id']]['username'] = $users['username'];
					}
				}

				// send the emails
				foreach ($users_array as $email_user)
				{
					$to = $email_user['email'];

					// set the title to upper case
					$title_upper = $title['title'];

					// subject
					$subject = "New comment on your unpublished article {$title_upper} on GamingOnLinux.com";

					$username = $_SESSION['username'];

					$comment_email = email_bbcode($comment);

					// message
					$message = "
					<html>
					<head>
					<title>New comment on your unpublished article on GamingOnLinux.com</title>
					<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
					</head>
					<body>
					<img src=\"{$config['website_url']}/templates/default/images/logo.png\" alt=\"Gaming On Linux\">
					<br />
					<p>Hello <strong>{$email_user['username']}</strong>,</p>
					<p><strong>{$username}</strong> has replied to an article you sent for review on titled \"<strong><a href=\"{$config['website_url']}/admin.php?module=reviewqueue&aid={$_POST['aid']}\">{$title_upper}</a></strong>\".</p>
					<div>
				 	<hr>
				 	{$comment_email}
				 	<hr>
				 	You can manage your subscriptions anytime in your <a href=\"{$config['website_url']}/usercp.php\">User Control Panel</a>.
				 	<hr>
				  	<p>If you haven&#39;t registered at <a href=\"{$config['website_url']}\" target=\"_blank\">{$config['website_url']}</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
				  	<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
					</div>
					</body>
					</html>
					";

					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
					$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

					// Mail it
					if ($config['send_emails'] == 1)
					{
						mail($to, $subject, $message, $headers);
					}
				}

				// try to stop double postings, clear text
				unset($_POST['text']);

				// clear any comment or name left from errors
				unset($_SESSION['acomment']);

				header("Location: /admin.php?module=reviewqueue&aid={$_POST['aid']}&message=commentadded");
			}
		}
	}

	if ($_POST['act'] == 'Preview_Submitted')
	{
		include('admin_articles_sections/submitted/preview_submitted.php');
	}

	if ($_POST['act'] == 'Edit')
	{
		$title = strip_tags($_POST['title']);
		$text = trim($_POST['text']);
		$tagline = trim($_POST['tagline']);
		$slug = $core->nice_title($_POST['slug']);

		// count how many editors picks we have
		$editor_picks = array();

		$db->sqlquery("SELECT `article_id` FROM `articles` WHERE `show_in_menu` = 1");
		while($editor_get = $db->fetch())
		{
			$editor_picks[] = $editor_get['article_id'];
		}

		$editor_pick_count = $db->num_rows();

		// make sure its not empty
		if (empty($title) || empty($tagline) || empty($text) || empty($_POST['article_id']))
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['aslug'] = $_POST['slug'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			if (isset($_POST['show_article']))
			{
				$_SESSION['aactive'] = 1;
			}
			else
			{
				$_SESSION['aactive'] = 0;
			}

			$temp_tagline = 0;
			if (!empty($_POST['temp_tagline_image']))
			{
				$temp_tagline = 1;
			}

			header("Location: /admin.php?module=articles&view=Edit&article_id={$_POST['article_id']}&error=empty&temp_tagline=$temp_tagline");
		}

		else if (strlen($_POST['tagline']) < 100)
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['aslug'] = $_POST['slug'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			if (isset($_POST['show_article']))
			{
				$_SESSION['aactive'] = 1;
			}
			else
			{
				$_SESSION['aactive'] = 0;
			}

			$temp_tagline = 0;
			if (!empty($_POST['temp_tagline_image']))
			{
				$temp_tagline = 1;
			}

			header("Location: /admin.php?module=articles&view=Edit&article_id={$_POST['article_id']}&error=shorttagline&temp_tagline=$temp_tagline");
		}

		else if (strlen($_POST['tagline']) > 400)
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['aslug'] = $_POST['slug'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			if (isset($_POST['show_article']))
			{
				$_SESSION['aactive'] = 1;
			}
			else
			{
				$_SESSION['aactive'] = 0;
			}

			$temp_tagline = 0;
			if (!empty($_POST['temp_tagline_image']))
			{
				$temp_tagline = 1;
			}

			header("Location: /admin.php?module=articles&view=Edit&article_id={$_POST['article_id']}&error=taglinetoolong&temp_tagline=$temp_tagline");
		}

		else if (strlen($_POST['title']) < 10)
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['aslug'] = $_POST['slug'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			if (isset($_POST['show_article']))
			{
				$_SESSION['aactive'] = 1;
			}
			else
			{
				$_SESSION['aactive'] = 0;
			}

			$temp_tagline = 0;
			if (!empty($_POST['temp_tagline_image']))
			{
				$temp_tagline = 1;
			}

			header("Location: /admin.php?module=articles&view=Edit&article_id={$_POST['article_id']}&error=shorttitle&temp_tagline=$temp_tagline");
		}

		else if (isset($_POST['show_block']) && $editor_pick_count == 3 && !in_array($_POST['article_id'], $editor_picks))
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['aslug'] = $_POST['slug'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			if (isset($_POST['show_article']))
			{
				$_SESSION['aactive'] = 1;
			}
			else
			{
				$_SESSION['aactive'] = 0;
			}

			$temp_tagline = 0;
			if (!empty($_POST['temp_tagline_image']))
			{
				$temp_tagline = 1;
			}

			header("Location: /admin.php?module=articles&view=Edit&article_id={$_POST['article_id']}&error=toomanypicks");
		}

		else
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

			$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `active` = ?, `locked` = 0, `locked_by` = 0, `locked_date` = 0 WHERE `article_id` = ?", array($title, $slug, $tagline, $text, $block, $show, $_POST['article_id']));

			$db->sqlquery("DELETE FROM `article_category_reference` WHERE `article_id` = ?", array($_POST['article_id']));

			if (isset($_POST['categories']))
			{
				foreach($_POST['categories'] as $category)
				{
					$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($_POST['article_id'], $category));
				}
			}

			if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
			{
				$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
			}

			// update history
			$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date));

			// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
			unset($_SESSION['atitle']);
			unset($_SESSION['aslug']);
			unset($_SESSION['atagline']);
			unset($_SESSION['atext']);
			unset($_SESSION['acategories']);
			unset($_SESSION['tagerror']);
			unset($_SESSION['aactive']);
			unset($_SESSION['uploads']);
			unset($_SESSION['uploads_tagline']);
			unset($_SESSION['image_rand']);

			$nice_title = $core->nice_title($_POST['title']);

			if ($config['pretty_urls'] == 1)
			{
				header("Location: /articles/$slug.{$_POST['article_id']}/");
			}
			else {
				if (!isset($_POST['show_block']))
				{
					header("Location: {$config['website_url']}index.php?module=articles_full&aid={$_POST['article_id']}");
				}
				else {
					header("Location: {$config['website_url']}admin.php?module=featured&view=add&article_id={$_POST['article_id']}");
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
		else
		{
			$post_page = $return_page = "/admin.php?module=articles&amp;article_id={$_POST['article_id']}";
		}

		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$db->sqlquery("SELECT `active` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
			$check = $db->fetch();

			// anti-cheese deleting the wrong article feature
			if ($check['active'] == 1)
			{
				$core->message("WARNING: You are about to delete a live article!", NULL, 1);
			}

			$core->yes_no('Are you sure you want to delete that article?', $post_page, "Delete");
		}

		else if (isset($_POST['no']))
		{
			header("Location: $return_page");
		}

		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_GET['article_id']))
			{
				$core->message('That is not a correct id!');
			}

			else
			{
				// check post exists
				$db->sqlquery("SELECT `article_id`, `date`, `author_id`, `title`, 'tagline_image', `article_top_image`,`article_top_image_filename` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
				$check = $db->fetch();

				if ($db->num_rows() != 1)
				{
					$core->message("That is not a correct id! Options: <a href=\"$return_page\">Go back</a>.");
				}

				// Delete now
				else
				{
					if ($check['author_id'] == 1 && $_SESSION['user_id'] != 1)
					{
						header("Location: $return_page");
					}

					else
					{
						$db->sqlquery("DELETE FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']));
						$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `article_id` = ?", array($_GET['article_id']));
						$db->sqlquery("DELETE FROM `article_category_reference` WHERE `article_id` = ?", array($_GET['article_id']));
						$db->sqlquery("DELETE FROM `articles_comments` WHERE `article_id` = ?", array($_GET['article_id']));
						$db->sqlquery("DELETE FROM `admin_notifications` WHERE `article_id` = ?", array($_GET['article_id']));
						$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `article_id` = ?, `action` = ?, `created` = ?, `completed_date` = ?", array($_GET['article_id'], "{$_SESSION['username']} deleted the article: {$check['title']}", core::$date, core::$date));

						// remove old article's image
						if ($check['article_top_image'] == 1)
						{
							unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/topimages/' . $check['article_top_image_filename']);
						}

						if (!empty($check['tagline_image']))
						{
							unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $check['tagline_image']);
							unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $check['tagline_image']);
						}

						// find any uploaded images, and remove them
						$db->sqlquery("SELECT * FROM `article_images` WHERE `article_id` = ?", array($_GET['article_id']));
						while ($image_search = $db->fetch())
						{
							unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/article_images/' . $image_search['filename']);
						}

						$db->sqlquery("DELETE FROM `article_images` WHERE `article_id` = ?", array($_GET['article_id']));

						$core->message("That article has now been deleted! Options: <a href=\"$return_page\">Go back</a>.");
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
		include('admin_articles_sections/submitted/approve_submitted.php');
	}

	/*
	// APPROVE AN EDITOR SUBMITTED ARTICLE
	*/

	if ($_POST['act'] == 'Approve_Admin')
	{
		include('admin_articles_sections/review/approve_review.php');
	}

	// For editing a post from another admin in the review pool
	if ($_POST['act'] == 'Edit_Admin')
	{
		$title = strip_tags($_POST['title']);
		$tagline = trim($_POST['tagline']);
		$text = trim($_POST['text']);
		$slug = trim($_POST['slug']);

		$temp_tagline = 0;
		if (!empty($_POST['temp_tagline_image']))
		{
			$temp_tagline = 1;
		}

		// make sure its not empty
		if (empty($title) || empty($tagline) || empty($_POST['text']) || empty($_POST['article_id']) || empty($slug))
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['aslug'] = $slug;

			$_SESSION['acategories'] = $_POST['categories'];

			header("Location: /admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=empty&temp_tagline=$temp_tagline");
		}

		else if (strlen($_POST['tagline']) < 100)
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			$_SESSION['aslug'] = $slug;

			header("Location: /admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=shorttagline&temp_tagline=$temp_tagline");
		}

		else if (strlen($_POST['tagline']) > 400)
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			$_SESSION['aslug'] = $slug;

			header("Location: /admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=taglinetoolong&temp_tagline=$temp_tagline");
		}

		else if (strlen($_POST['title']) < 10)
		{
			$_SESSION['atitle'] = $_POST['title'];
			$_SESSION['atagline'] = $_POST['tagline'];
			$_SESSION['atext'] = $_POST['text'];
			$_SESSION['acategories'] = $_POST['categories'];
			$_SESSION['aslug'] = $slug;

			header("Location: /admin.php?module=reviewqueue&aid={$_POST['article_id']}&error=shorttitle&temp_tagline=$temp_tagline");
		}

		else
		{
			$block = 0;
			if (isset($_POST['show_block']))
			{
				$block = 1;
			}

			$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ?, `locked` = 0 WHERE `article_id` = ?", array($title, $slug, $tagline, $text, $block, $_POST['article_id']));

			if (isset($_SESSION['uploads']))
				{
					foreach($_SESSION['uploads'] as $key)
					{
						$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($_POST['article_id'], $key['image_name']));
					}
				}

				$db->sqlquery("DELETE FROM `article_category_reference` WHERE `article_id` = ?", array($_POST['article_id']));

			if (isset($_POST['categories']))
			{
				foreach($_POST['categories'] as $category)
				{
					$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($_POST['article_id'], $category));
				}
			}

			if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
			{
				$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
			}

			// update history
			$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date));

			// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
			unset($_SESSION['atitle']);
			unset($_SESSION['atagline']);
			unset($_SESSION['atext']);
			unset($_SESSION['acategories']);
			unset($_SESSION['tagerror']);
			unset($_SESSION['aactive']);
			unset($_SESSION['uploads']);
			unset($_SESSION['uploads_tagline']);
			unset($_SESSION['image_rand']);
			unset($_SESSION['aslug']);

			if ($_POST['author_id'] != $_SESSION['user_id'])
			{
				// find the authors email
				$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']));
				$author_email = $db->fetch();

					// sort out registration email
					$to = $author_email['email'];

					// subject
					$subject = 'Your article was review and edited on GamingOnLinux.com!';

					$nice_title = $core->nice_title($_POST['title']);

					// message
					$message = "
					<html>
					<head>
					<title>Your article was reviewed and edited GamingOnLinux.com!</title>
					</head>
					<body>
					<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
					<br />
					<p>{$_SESSION['username']} has reviewed and edited your article on <a href=\"http://www.gamingonlinux.com/\" target=\"_blank\">GamingOnLinux.com</a>, here's a link to the article: <a href=\"http://www.gamingonlinux.com/admin.php?module=reviewqueue&aid={$_POST['article_id']}/\">{$_POST['title']}</a></p>
					</body>
					</html>
					";

					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= "From: GamingOnLinux.com Editor Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

					// Mail it
					if ($config['send_emails'] == 1)
					{
						mail($to, $subject, $message, $headers);
					}
				}

				header("Location: /admin.php?module=reviewqueue&aid={$_POST['article_id']}&lock=0");
		}
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
			$core->message("Not a correct article id set!", NULL, 1);
		}

		else
		{
			$db->sqlquery("SELECT `article_top_image`,`article_top_image_filename`,`title`,`tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
			$article = $db->fetch();

			// remove old image
			if ($article['article_top_image'] == 1)
			{
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/topimages/' . $article['article_top_image_filename']);
			}
			if (!empty($article['tagline_image']))
			{
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']);
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']);
			}

			$db->sqlquery("UPDATE `articles` SET `article_top_image` = 0, `article_top_image_filename` = '', `tagline_image` = '' WHERE `article_id` = ?", array($_POST['article_id']));

			$nice_title = $core->nice_title($article['title']);

			$core->message("The articles top image has now been deleted from \"{$article['title']}\"! <a href=\"/articles/$nice_title.{$_POST['article_id']}\">Click here to view the article.</a>");
		}
	}

	if ($_POST['act'] == 'delete_spam_report')
	{
		if (!is_numeric($_GET['comment_id']))
		{
			$core->message("Not a correct id!", NULL, 1);
		}

		else
		{
			$db->sqlquery("SELECT `comment_text` FROM `articles_comments` WHERE `comment_id` = ?", array($_GET['comment_id']));
			$get_comment = $db->fetch();

			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ?, `content` = ? WHERE `comment_id` = ?", array("{$_SESSION['username']} deleted a comment report.", core::$date, $get_comment['comment_text'], $_GET['comment_id']));

			$db->sqlquery("UPDATE `articles_comments` SET `spam` = 0 WHERE `comment_id` = ?", array($_GET['comment_id']));

			header("Location: /admin.php?module=articles&view=comments&deleted=1");
		}
	}
}
