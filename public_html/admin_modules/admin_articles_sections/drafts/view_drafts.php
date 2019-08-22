<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

$templating->load('admin_modules/admin_articles_sections/drafts');
if (!isset($_GET['aid']))
{
	$templating->block('drafts_top', 'admin_modules/admin_articles_sections/drafts');

	$get_articles = $dbl->run("SELECT a.`article_id`, a.`date`, a.`title`, a.`tagline`, a.`guest_username`, u.`username` FROM `articles` a LEFT JOIN `users` u on a.`author_id` = u.`user_id` WHERE `draft` = 1 AND `user_id` = ?", array($_SESSION['user_id']))->fetch_all();
	if ($get_articles)
	{
		foreach ($get_articles as $article)
		{
			$templating->block('drafts_row', 'admin_modules/admin_articles_sections/drafts');
			$templating->set('url', $core->config('website_url'));
			$templating->set('article_id', $article['article_id']);
			$templating->set('article_title', $article['title']);
			$templating->set('username', $article['username']);

			$templating->set('date_created', $core->human_date($article['date']));
			$templating->set('delete_button', '<button type="submit" name="act" value="delete_draft" formaction="'.$core->config('website_url').'admin.php?module=articles">Delete</button>');
		}
	}
	else
	{
		$templating->block('none', 'admin_modules/admin_articles_sections/drafts');
	}

	$templating->block('others_drafts', 'admin_modules/admin_articles_sections/drafts');

	$get_theirs = $dbl->run("SELECT a.`article_id`, a.`date`, a.`title`, a.`tagline`, a.`guest_username`, u.`username` FROM `articles` a LEFT JOIN `users` u on a.`author_id` = u.`user_id` WHERE `draft` = 1 AND `user_id` != ?", array($_SESSION['user_id']))->fetch_all();
	if ($get_theirs)
	{
		foreach ($get_theirs as $article)
		{
			$templating->block('drafts_row', 'admin_modules/admin_articles_sections/drafts');
			$templating->set('url', $core->config('website_url'));
			$templating->set('article_id', $article['article_id']);
			$templating->set('article_title', $article['title']);
			$templating->set('username', $article['username']);

			$templating->set('date_created', $core->human_date($article['date']));
			$templating->set('delete_button', '');
		}
	}
	else
	{
		$templating->block('none', 'admin_modules/admin_articles_sections/drafts');
	}
}

else
{
	if (!isset($message_map::$error) || $message_map::$error == 0)
	{
		$article_class->reset_sessions();
	}

	$templating->block('single_draft_top', 'admin_modules/admin_articles_sections/drafts');

	$article = $dbl->run("SELECT a.`article_id`, a.`preview_code`, a.`title`, a.`slug`, a.`text`, a.`tagline`, a.`show_in_menu`, a.`active`, a.`tagline_image`, a.`guest_username`, a.`author_id`, a.`gallery_tagline`, t.`filename` as gallery_tagline_filename, u.`username` FROM `articles` a LEFT JOIN `users` u on a.`author_id` = u.`user_id` LEFT JOIN `articles_tagline_gallery` t ON t.`id` = a.gallery_tagline WHERE `article_id` = ?", array($_GET['aid']))->fetch();
	
	$edit_state = '';
	$edit_state_textarea = '';
	$editor_disabled = 0;
	if ($article['author_id'] != $_SESSION['user_id'])
	{
		$edit_state = 'disabled="disabled"';
		$edit_state_textarea = 'disabled';
		$editor_disabled = 1;
	}

	$_SESSION['original_text'] = $article['text'];

	$templating->load('admin_modules/article_form');

	$templating->block('preview_code', 'admin_modules/article_form');
	$templating->set('preview_url', $core->config('website_url') . 'index.php?module=articles_full&aid=' . $article['article_id'] . '&preview_code=' . $article['preview_code']);
	$templating->set('edit_state', $edit_state);
	$templating->set('article_id', $article['article_id']);

	// get the edit row
	$templating->block('full_editor', 'admin_modules/article_form');
	$templating->set('max_filesize', core::readable_bytes($core->config('max_tagline_image_filesize')));

	// remove these, as it's a draft, we don't lock/disable crap here as it's personal to the user
	$templating->set('edit_state', $edit_state);
	$templating->set('edit_state_textarea', $edit_state_textarea);

	$templating->set('url', $core->config('website_url'));
	$templating->set('main_formaction', '<form class="gol-form" id="article_editor" method="post" action="'.$core->config('website_url').'admin.php?module=articles" enctype="multipart/form-data">');

	// get categorys
	$get_cats = $dbl->run("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($article['article_id']))->fetch_all();
	foreach ($get_cats as $categories_check)
	{
		$categories_check_array[] = $categories_check['category_id'];
	}

	$categorys_list = '';
	$get_all_cats = $dbl->run("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC")->fetch_all();
	foreach ($get_all_cats as $categorys)
	{
		if (isset($message_map::$error) && $message_map::$error > 0)
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

	$templating->set('username', $article['username']);

	$previously_uploaded = '';

	// if they have done it before set title, text and tagline
	$text = $article['text'];
	if (isset($message_map::$error) && $message_map::$error > 0)
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

	$tagline_image = $article_class->display_tagline_image($article);
	$templating->set('tagline_image', $tagline_image);

	$templating->set('max_height', $core->config('article_image_max_height'));
	$templating->set('max_width', $core->config('article_image_max_width'));

	$core->article_editor(['content' => $text, 'disabled' => $editor_disabled]);

	$templating->block('drafts_bottom', 'admin_modules/admin_articles_sections/drafts');
	$templating->set('edit_state', $edit_state);
	$templating->set('article_id', $article['article_id']);
	$templating->set('author_id', $article['author_id']);

	// add in uploaded images from database
	$previously_uploaded = $article_class->display_previous_uploads($article['article_id']);

	$templating->block('uploads', 'admin_modules/article_form');
	$templating->set('previously_uploaded', $previously_uploaded['output']);
	$templating->set('article_id', $article['article_id']);

	$grab_subscribe = $dbl->run("SELECT `auto_subscribe_new_article` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

	$auto_subscribe = '';
	if ($grab_subscribe['auto_subscribe_new_article'] == 1)
	{
		$auto_subscribe = 'checked';
	}
	$templating->set('subscribe_check', $auto_subscribe);

	$article_class->article_history($_GET['aid']);
}
