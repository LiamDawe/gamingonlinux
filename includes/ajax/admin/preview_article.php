<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0 || !$user->check_group([1,2,5]))
{
	die('You shouldn\'t be here.');
}

if(isset($_POST))
{
	// if it's an existing article: draft, submitted, review
	if (isset($_POST['article_id']) && $_POST['article_id'] > 0)
	{
		$check_article_sql = "SELECT
		a.`title`,
		a.`tagline`,
		a.`text`,
		a.`article_id`,
		a.`author_id`,
		a.`guest_username`,
		a.`tagline_image`,
		a.`draft`,
		a.`locked`,
		a.`locked_by`,
		a.`locked_date`,
		a.`gallery_tagline`,
		t.`filename` as `gallery_tagline_filename`,
		u1.`username`,
		u1.`user_id`,
		u2.`username` as username_lock
		FROM `articles` a
		LEFT JOIN
		`users` u1 ON u1.`user_id` = a.`author_id`
		LEFT JOIN
		`users` u2 ON a.`locked_by` = u2.`user_id`
		LEFT JOIN
		`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
		WHERE `article_id` = ?";
		$article = $dbl->run($check_article_sql, array($_POST['article_id']))->fetch();
		
		if ($article['draft'] == 1)
		{
			// if it's a draft and they own it, use the post content
			if ($_SESSION['user_id'] == $article['author_id'])
			{
				$title = strip_tags($_POST['title']);
				$tagline = $_POST['tagline'];
				$text = $_POST['text'];
			}
			// otherwise, you cannot edit, view the saved details
			else
			{
				$title = $article['title'];
				$tagline = $article['tagline'];
				$text = $article['text'];				
			}
		}
		
		else
		{
			// not a draft, check they have it locked to edit
			if ($article['locked'] == 0)
			{
				$title = $article['title'];
				$tagline = $article['tagline'];
				$text = $article['text'];
			}
			// locked for editing, locked by *you*
			else if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
			{
				$title = strip_tags($_POST['title']);
				$tagline = $_POST['tagline'];
				$text = $_POST['text'];
			}
			// locked for editing, by someone else
			else if ($article['locked'] == 1 && $article['locked_by'] != $_SESSION['user_id'])
			{
				$title = $article['title'];
				$tagline = $article['tagline'];
				$text = $article['text'];				
			}
		}
	}
	// unsaved article, always use post content
	else
	{
		$title = strip_tags($_POST['title']);
		$tagline = $_POST['tagline'];
		$text = $_POST['text'];
	}
	
	$templating->load('admin_modules/admin_module_articles');
	
	// make date human readable
	$date = $core->human_date(core::$date);

	// get the article row template
	$templating->block('preview_row', 'admin_modules/admin_module_articles');
	$templating->set('url', $core->config('website_url'));

	$templating->set('categories_list_preview', '<span class="label label-info">Categories Here</span>');

	$templating->set('title', $title);
	
	$top_image = '';
	$top_image_nobbcode='';
	if (isset($article))
	{
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

		// if it's a registered user show their username with a profile link
		else if (isset($article['username']))
		{
			$username = "<a href=\"/profiles/{$article['user_id']}\">{$article['username']}</a>";
		}
		
		if (!empty($article['tagline_image']))
		{
			$top_image_nobbcode = "<img src=\"" . $core->config('website_url') . "uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\">";
		}
		if ($article['gallery_tagline'] > 0 && !empty($article['gallery_tagline_filename']))
		{
			$top_image_nobbcode = "<img src=\"" . $core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" alt=\"[articleimage]\" class=\"imgList\">";
		}
	}

	// else we are probably just previewing a new post by an editor
	else if (!isset($article))
	{
		$username = "<a href=\"/profiles/{$_SESSION['user_id']}\">{$_SESSION['username']}</a>";
	}

	$templating->set('username', $username);
	
	$templating->set('date', $date);
	$templating->set('submitted_date', 'Submitted ' . $date);
	
	if (isset($_SESSION['gallery_tagline_id']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand'])
	{
		$gallery_image = $dbl->run("SELECT `filename` FROM `articles_tagline_gallery` WHERE `id` = ?", array($_SESSION['gallery_tagline_id']))->fetch();
		$top_image_nobbcode = "<img src=\"" . $core->config('website_url') . "uploads/tagline_gallery/{$gallery_image['filename']}\" alt=\"[articleimage]\" class=\"imgList\">";
	}
	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$top_image_nobbcode = '<img src="' . $core->config('website_url') . 'uploads/articles/tagline_images/temp/thumbnails/' . $_SESSION['uploads_tagline']['image_name'] . '" alt="[articleimage]">';
	}
	
	$tagline_bbcode = '';
	$bbcode_tagline_gallery = NULL;
	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$tagline_bbcode = '/temp/' . $_SESSION['uploads_tagline']['image_name'];
	}

	else if (isset($_SESSION['gallery_tagline_rand']) && $_SESSION['gallery_tagline_rand'] == $_SESSION['image_rand'])
	{
		$tagline_bbcode = $_SESSION['gallery_tagline_filename'];
		$bbcode_tagline_gallery = 1;
	}
	
	else if ((!isset($_SESSION['uploads_tagline']) || $_SESSION['uploads_tagline']['image_rand'] != $_SESSION['image_rand']) || (!isset($_SESSION['gallery_tagline_rand']) || $_SESSION['gallery_tagline_rand'] != $_SESSION['image_rand']) && isset($article))
	{
		if (!empty($article['tagline_image']))
		{
			$tagline_bbcode  = $article['tagline_image'];
		}
		if (!empty($article['gallery_tagline']))
		{
			$tagline_bbcode = $article['gallery_tagline_filename'];
			$bbcode_tagline_gallery = 1;
		}
	}
	$templating->set('top_image_nobbcode', $top_image_nobbcode);
	$templating->set('tagline', $tagline);
	$templating->set('text_full', $bbcode->article_bbcode($text));
	
	echo $templating->output();
}
