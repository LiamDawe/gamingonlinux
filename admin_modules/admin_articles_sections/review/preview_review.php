<?php
$templating->merge('admin_modules/admin_articles_sections/admin_review');

$author_id = '';

$db->sqlquery("SELECT a.`author_id`, a.`guest_username`, a.`article_top_image`, a.`article_top_image_filename`, a.tagline_image, a.locked, a.locked_by, a.locked_date, u.username, u.user_id, u.avatar_uploaded, u.avatar, u.avatar_gravatar, u.gravatar_email, u2.username as username_lock FROM `articles` a LEFT JOIN users u ON u.user_id = a.author_id LEFT JOIN `users` u2 ON a.locked_by = u2.user_id WHERE `article_id` = ?", array($_POST['article_id']));
$article = $db->fetch();

$edit_state = '';
$edit_state_textarea = '';
$editor_disabled = 0;
if ($article['locked'] == 1 && $article['locked_by'] != $_SESSION['user_id'])
{
	$templating->block('edit_locked');
	$templating->set('locked_username', $article['username_lock']);

	$lock_date = $core->format_date($article['locked_date']);
	$templating->set('locked_date', $lock_date);

	$edit_state = 'disabled="disabled"';
	$edit_state_textarea = 'disabled';
	$editor_disabled = 1;
}

else if ($article['locked'] == 0 && $_GET['lock'] == 1)
{
	$db->sqlquery("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], $core->date, $article['article_id']));
	$core->message("This post is now locked by yourself while you edit, please click Edit to unlock it once finished.", NULL, 1);
}

else if ($article['locked'] == 1 && $article['locked_by'] == $_SESSION['user_id'])
{
	$core->message("This post is now locked while you edit, please click Edit to unlock it once finished.", NULL, 1);
}

if (empty($article['avatar']) || $article['avatar_gravatar'] == 1)
{
	$avatar = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $article['gravatar_email'] ) ) ) . "?d=http://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
}
// either uploaded or linked an avatar
else
{
	$avatar = $article['avatar'];
	if ($article['avatar_uploaded'] == 1)
	{
		$avatar = "/uploads/avatars/{$article['avatar']}";
	}
}

$author_id = $article['author_id'];

// make date human readable
$date = $core->format_date($core->date);

$templating->block('review_top', 'admin_modules/admin_articles_sections/admin_review');

// get the article row template
$templating->block('previewadmin_row', 'admin_modules/admin_articles_sections/admin_review');

$templating->set('categories_list_preview', '<span class="label label-info">Categories Here</span>');

$templating->set('title', $_POST['title']);
$templating->set('author_id', $author_id);

$username = "<a href=\"/profiles/{$article['user_id']}\">{$article['username']}</a>";

$templating->set('username', $username);

$templating->set('date', $date);
$templating->set('submitted_date', 'Submitted ' . $date);

$tagline = $_POST['tagline'];
$top_image = '';
if (isset($article) && $article['article_top_image'] == 1)
{
	$top_image = "<img src=\"{$config['website_url']}uploads/articles/topimages/{$article['article_top_image_filename']}\" alt=\"[articleimage]\"><br />";
	$top_image_nobbcode = "<img src=\"{$config['website_url']}uploads/articles/topimages/{$article['article_top_image_filename']}\" alt=\"[articleimage]\">";
}
		if (!empty($article['tagline_image']))
		{
			$top_image = "<img src=\"{$config['website_url']}uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\"><br />
			BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />";
			$top_image_nobbcode = "<img src=\"{$config['website_url']}uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\">";
			$top_image_delete = " <button class=\"red-button\" name=\"act\" value=\"deletetopimage\">Delete Tagline Image</button>";
		}

		$tagline_bbcode = '';
		if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
		{
			$top_image_nobbcode = "<img src=\"{$config['website_url']}uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" alt=\"[articleimage]\">";
			$top_image = "<img src=\"{$config['website_url']}uploads/articles/tagline_images/temp/thumbnails/{$_SESSION['uploads_tagline']['image_name']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
			BBCode: <input type=\"text\" class=\"form-control input-sm\" value=\"[img]tagline-image[/img]\" /><br />";
			$tagline_bbcode = '/temp/' . $_SESSION['uploads_tagline']['image_name'];
		}

		else if ((!isset($_SESSION['uploads_tagline']) || $_SESSION['uploads_tagline']['image_rand'] != $_SESSION['image_rand']) && isset($article))
		{
			if ($article['article_top_image'] == 1)
			{
				$tagline_bbcode = $article['article_top_image_filename'];
			}
			if (!empty($article['tagline_image']))
			{
				$tagline_bbcode  = $article['tagline_image'];
			}
		}

		$templating->set('top_image', $top_image);
		$templating->set('top_image_nobbcode', $top_image_nobbcode);
		$templating->set('text', bbcode($tagline));
		$templating->set('text_full', bbcode($_POST['text'], 1, 1, $tagline_bbcode));
		$templating->set('article_link', '#');
		$templating->set('comment_count', '0');

		$templating->set('avatar', $avatar);

		if (empty($article['article_bio']))
		{
			$bio = 'This user has not filled out their biography!';
		}
		else
		{
			$bio = bbcode($article['article_bio']);
		}

		$templating->set('article_bio', $bio);

		// this bit is for the final form
		$templating->merge('admin_modules/article_form');
		$templating->block('full_editor', 'admin_modules/article_form');
		$templating->set('main_formaction', '<form id="form" method="post" action="'.$config['website_url'].'admin.php?module=articles&edit" enctype="multipart/form-data">');

		// get categorys
		$categories_list = '';
		$db->sqlquery("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC");
		while ($categorys = $db->fetch())
		{
			if (!empty($_POST['categories']) && in_array($categorys['category_id'], $_POST['categories']))
			{
				$categories_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
			}

			else
			{
				$categories_list .= "<option value=\"{$categorys['category_id']}\">{$categorys['category_name']}</option>";
			}
		}

		$templating->set('categories_list', $categories_list);

		$guest_username = '';
		if ($_SESSION['user_id'] == 0)
		{
			$guest_username = "Your Name: <input type=\"text\" name=\"name\" value=\"{$_POST['name']}\" /><br />";
		}
		$templating->set('guest_username', $guest_username);

		$templating->set('edit_title', htmlentities($_POST['title'], ENT_QUOTES));
		$templating->set('edit_tagline', $_POST['tagline']);

		$templating->set('max_height', $config['article_image_max_height']);
		$templating->set('max_width', $config['article_image_max_width']);

		$core->editor('text', $_POST['text'], 1);

		$templating->block('previewadmin_bottom', 'admin_modules/admin_articles_sections/admin_review');

		$edit_state = '';
		if ($article['locked'] == 1 && $article['locked_by'] != $_SESSION['user_id'])
		{
			$edit_state = 'disabled';
		}
		$templating->set('edit_state', $edit_state);

		// sort out previously uploaded images
		$previously_uploaded = '';
		// add in uploaded images from database
		$db->sqlquery("SELECT `filename`,`id` FROM `article_images` WHERE `article_id` = ?", array($_POST['article_id']));
		$article_images = $db->fetch_all_rows();

		foreach($article_images as $value)
		{
			$previously_uploaded .= "<div class=\"col-md-12\" id=\"{$value['id']}\"><img src=\"/uploads/articles/article_images/{$value['filename']}\" class='imgList'><br />
			BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]{$config['website_url']}uploads/articles/article_images/{$value['filename']}[/img]\" />
			<a href=\"#\" id=\"{$value['id']}\" class=\"trash\">Delete Image</a></div>";
		}

		$templating->set('previously_uploaded', $previously_uploaded);
		$templating->set('article_id', $_POST['article_id']);
