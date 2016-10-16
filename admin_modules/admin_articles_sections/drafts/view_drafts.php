<?php
$templating->merge('admin_modules/admin_articles_sections/admin_module_articles_drafts');
if (!isset($_GET['aid']))
{
	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'saved')
		{
			$core->message('Draft saved!');
		}

		if ($_GET['message'] == 'moved')
		{
			$core->message('That article has been moved to the admin review queue.');
		}

		if ($_GET['message'] == 'deleted')
		{
			$core->message('That article draft has been deleted!', NULL, 1);
		}
	}

	$templating->block('drafts_top', 'admin_modules/admin_articles_sections/admin_module_articles_drafts');

	$db->sqlquery("SELECT a.article_id, a.date, a.title, a.tagline, a.guest_username, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE `draft` = 1 AND `user_id` = ?", array($_SESSION['user_id']));
	while ($article = $db->fetch())
	{
		$templating->block('drafts_row', 'admin_modules/admin_articles_sections/admin_module_articles_drafts');
		$templating->set('url', core::config('website_url'));
		$templating->set('article_id', $article['article_id']);
		$templating->set('article_title', $article['title']);
		$templating->set('username', $article['username']);

		$templating->set('date_created', $core->format_date($article['date']));
		$templating->set('delete_button', '<button type="submit" name="act" value="delete_draft" formaction="'.core::config('website_url').'admin.php?module=articles">Delete</button>');
		$templating->set('edit_button', '<button type="submit" class="btn btn-primary" formaction="'.core::config('website_url').'admin.php?module=articles&view=drafts&aid='.$article['article_id'].'">Edit</button>');
	}

	$templating->block('others_drafts', 'admin_modules/admin_articles_sections/admin_module_articles_drafts');

	$db->sqlquery("SELECT a.article_id, a.date, a.title, a.tagline, a.guest_username, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE `draft` = 1 AND `user_id` != ?", array($_SESSION['user_id']));
	while ($article = $db->fetch())
	{
		$templating->block('drafts_row', 'admin_modules/admin_articles_sections/admin_module_articles_drafts');
		$templating->set('url', core::config('website_url'));
		$templating->set('article_id', $article['article_id']);
		$templating->set('article_title', $article['title']);
		$templating->set('username', $article['username']);

		$templating->set('date_created', $core->format_date($article['date']));
		$templating->set('delete_button', '');
		$templating->set('edit_button', '');
	}
}

else
{
	if (!isset($_GET['error']))
	{
		$_SESSION['image_rand'] = rand();
	}

	if (isset ($_GET['message']))
	{
		if ($_GET['message'] == 'editdone')
		{
			$core->message('Post Edited!');
		}

		else if ($_GET['message'] == 'tagline_image')
		{
			$core->message($_SESSION['tagerror'], NULL, 1);
		}

		if ($_GET['message'] == 'shorttile')
		{
			$core->message('The title was too short!', NULL, 1);
		}
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
				$brandnew = '<br /><strong>NOTE:</strong> The article has still been saved.';
			}

			$core->message($_SESSION['tagerror'] . $brandnew, NULL, 1);
		}
	}

	$templating->block('single_draft_top', 'admin_modules/admin_articles_sections/admin_module_articles_drafts');

	$db->sqlquery("SELECT a.article_id, a.preview_code, a.title, a.slug, a.text, a.tagline, a.show_in_menu, a.active, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.guest_username, a.author_id, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE `article_id` = ?", array($_GET['aid']));

	$article = $db->fetch();

	$templating->merge('admin_modules/article_form');

	$templating->block('preview_code', 'admin_modules/article_form');
	$templating->set('preview_url', core::config('website_url') . 'index.php?module=articles_full&aid=' . $article['article_id'] . '&preview_code=' . $article['preview_code']);
	$templating->set('edit_state', '');
	$templating->set('article_id', $article['article_id']);

	// get the edit row

	$templating->block('full_editor', 'admin_modules/article_form');
	$templating->set('max_filesize', core::readable_bytes(core::config('max_tagline_image_filesize')));

	// remove these, as it's a draft, we don't lock/disable crap here as it's personal to the user
	$templating->set('edit_state', '');
	$templating->set('edit_state_textarea', '');

	$templating->set('url', core::config('website_url'));
	$templating->set('main_formaction', '<form method="post" action="'.core::config('website_url').'admin.php?module=articles" enctype="multipart/form-data">');

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

	$games_list = $article_class->sort_game_assoc($article['article_id']);

	$templating->set('games_list', $games_list);

	$templating->set('username', $article['username']);

	$previously_uploaded = '';

	// if they have done it before set title, text and tagline
	$text = $article['text'];
	if (isset($_GET['error']))
	{
		$templating->set('title', htmlentities($_SESSION['atitle'], ENT_QUOTES));
		$templating->set('tagline', $_SESSION['atagline']);
		$templating->set('slug', $_SESSION['aslug']);
		$text = $_SESSION['atext'];
	}

	else
	{
		$templating->set('title', htmlentities($article['title'], ENT_QUOTES));
		$templating->set('tagline', $article['tagline']);
		$templating->set('slug', $article['slug']);
	}

	$top_image = '';
	if (!empty($article['tagline_image']))
	{
		$top_image = "<img src=\"" . core::config('website_url') . "uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
		BBCode: <input type=\"text\" class=\"form-control input-sm\" value=\"[img]tagline-image[/img]\" /><br />Full Image Url: <a href=\"http://www.gamingonlinux.com/uploads/articles/tagline_images/{$article['tagline_image']}\" target=\"_blank\">Click Me</a><br />";
	}
	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$top_image = "<img src=\"" . core::config('website_url') . "uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
		BBCode: <input type=\"text\" class=\"form-control input-sm\" value=\"[img]tagline-image[/img]\" /><br />";
	}

	$templating->set('tagline_image', $top_image);

	$templating->set('max_height', core::config('article_image_max_height'));
	$templating->set('max_width', core::config('article_image_max_width'));

	$core->editor('text', $text, 1);

	$templating->block('drafts_bottom', 'admin_modules/admin_articles_sections/admin_module_articles_drafts');
	$templating->set('article_id', $article['article_id']);
	$templating->set('author_id', $article['author_id']);

	// add in uploaded images from database
	$previously_uploaded	= $article_class->previous_uploads($article['article_id']);

	$templating->set('previously_uploaded', $previously_uploaded);

	$db->sqlquery("SELECT `auto_subscribe_new_article` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$grab_subscribe = $db->fetch();

	$auto_subscribe = '';
	if ($grab_subscribe['auto_subscribe_new_article'] == 1)
	{
		$auto_subscribe = 'checked';
	}
	$templating->set('subscribe_check', $auto_subscribe);
}
