<?php
$templating->merge('admin_modules/admin_module_articles');
$templating->set('article_css', 'articleadmin');

$author_id = '';

// Grab whoevers details we need from a pre-existing article (so not previewing a brand new not-yet-posted article)
if (isset($_POST['check']))
{
	if ($_POST['check'] == 'Edit' || $_POST['check'] == 'Submitted' || $_POST['check'] == 'Draft' || $_POST['check'] == 'Review')
	{
		$check_article_sql = "SELECT
		a.`article_id`,
		a.`author_id`,
		a.`guest_username`,
		a.`tagline_image`,
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
		".$core->db_tables['users']." u1 ON u1.`user_id` = a.`author_id`
		LEFT JOIN
		".$core->db_tables['users']." u2 ON a.`locked_by` = u2.`user_id`
		LEFT JOIN
		`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline`
		WHERE `article_id` = ?";
		$db->sqlquery($check_article_sql, array($_POST['article_id']));
		$article = $db->fetch();

		$author_id = $article['author_id'];
	}
}

if (isset($article))
{
	if ($article['locked'] == 1 && $article['locked_by'] != $_SESSION['user_id'])
	{
		$templating->block('edit_locked');
		$templating->set('locked_username', $article['username_lock']);

		$lock_date = $core->format_date($article['locked_date']);

		$templating->set('locked_date', $lock_date);
	}

	else if ($article['locked'] == 0)
	{
		$db->sqlquery("UPDATE `articles` SET `locked` = 1, `locked_by` = ?, `locked_date` = ? WHERE `article_id` = ?", array($_SESSION['user_id'], core::$date, $article['article_id']));
	}
}

// make date human readable
$date = $core->format_date(core::$date);

// get the article row template
$templating->block('preview_row', 'admin_modules/admin_module_articles');
$templating->set('url', $core->config('website_url'));

$templating->set('categories_list_preview', '<span class="label label-info">Categories Here</span>');

$templating->set('title', strip_tags($_POST['title']));
$templating->set('slug', strip_tags($_POST['slug']));
$templating->set('author_id', $author_id);

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
		$top_image = "<img src=\"" . $core->config('website_url') . "uploads/articles/tagline_images/thumbnails/{$article['tagline_image']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
		BBCode: <input type=\"text\" class=\"form-control input-sm\" value=\"[img]tagline-image[/img]\" /><br />";
	}
	if ($article['gallery_tagline'] > 0 && !empty($article['gallery_tagline_filename']))
	{
		$top_image_nobbcode = "<img src=\"" . $core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" alt=\"[articleimage]\" class=\"imgList\">";
		$top_image = "<div class=\"test\" id=\"{$article['gallery_tagline']}\"><img src=\"" . $core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
		BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /><br />
		Full Image Url: <a href=\"" . $core->config('website_url') . "uploads/tagline_gallery/{$article['gallery_tagline_filename']}\" target=\"_blank\">Click Me</a></div>";
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
	$db->sqlquery("SELECT `filename` FROM `articles_tagline_gallery` WHERE `id` = ?", array($_SESSION['gallery_tagline_id']));
	$gallery_image = $db->fetch();
	$top_image_nobbcode = "<img src=\"" . $core->config('website_url') . "uploads/tagline_gallery/{$gallery_image['filename']}\" alt=\"[articleimage]\" class=\"imgList\">";
	$top_image = "<div class=\"test\" id=\"{$article['gallery_tagline']}\"><img src=\"" . $core->config('website_url') . "uploads/tagline_gallery/{$gallery_image['filename']}\" alt=\"[articleimage]\" class=\"imgList\"><br />
	BBCode: <input type=\"text\" class=\"form-control\" value=\"[img]tagline-image[/img]\" /></div>";
}
if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
{
	$top_image_nobbcode = '<img src="' . $core->config('website_url') . 'uploads/articles/tagline_images/temp/thumbnails/' . $_SESSION['uploads_tagline']['image_name'] . '" alt="[articleimage]">';
	$top_image = '<img src="' . $core->config('website_url') . 'uploads/articles/tagline_images/temp/thumbnails/' . $_SESSION['uploads_tagline']['image_name'] . '" alt="[articleimage]" class="imgList"><br />
	BBCode: <input type="text" class="form-control input-sm" value="[img]tagline-image[/img]" /><br />';
}

if (isset($article))
{
	$templating->set('article_id_field', "<input type=\"hidden\" name=\"article_id\" value=\"{$article['article_id']}\" />");
}
else
{
	$templating->set('article_id_field', "");
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
$templating->set('tagline', $_POST['tagline']);
$templating->set('text_full', $bbcode->parse_bbcode($_POST['text'], 1, 1, $tagline_bbcode, $bbcode_tagline_gallery));
$templating->set('article_link', '#');
$templating->set('comment_count', '0');

// sort out the avatar
// either no avatar (gets no avatar from gravatars redirect) or gravatar set
$db->sqlquery("SELECT `avatar`, `avatar_gravatar`, `gravatar_email`, `article_bio`, `avatar_uploaded` FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($_SESSION['user_id']));
$article_avatar = $db->fetch();

if (empty($article_avatar['avatar']) || $article_avatar['avatar_gravatar'] == 1)
{
	$avatar = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $article_avatar['gravatar_email'] ) ) ) . "?d=https://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
}

// either uploaded or linked an avatar
else
{
	$avatar = $article_avatar['avatar'];
	if ($article_avatar['avatar_uploaded'] == 1)
	{
		$avatar = "/uploads/avatars/{$article_avatar['avatar']}";
	}
}

$templating->set('avatar', $avatar);

if (empty($article['article_bio']))
{
	$bio = 'This user has not filled out their biography!';
}
else
{
	$bio = $bbcode->parse_bbcode($article['article_bio']);
}

$templating->set('article_bio', $bio);

// this bit is for the final form
$templating->merge('admin_modules/article_form');
$templating->block('full_editor', 'admin_modules/article_form');
$templating->set('max_filesize', core::readable_bytes($core->config('max_tagline_image_filesize')));
$templating->set('main_formaction', '<form id="form" method="post" action="'.$core->config('website_url').'admin.php?module=preview" enctype="multipart/form-data">');
$templating->set('tagline', $_POST['tagline']);
$templating->set('tagline_image', $top_image);

// get categories
$categories_list = '';

$db->sqlquery("SELECT * FROM `articles_categorys` ORDER BY `category_name` ASC");
while ($categorys = $db->fetch())
{
	if (!empty($_POST['categories']) && in_array($categorys['category_id'], $_POST['categories']))
	{
		$categories_list .= "<option value=\"{$categorys['category_id']}\" selected>{$categorys['category_name']}</option>";
	}
}

$templating->set('categories_list', $categories_list);

$edit_state = '';
if (isset($article))
{
	if ($article['locked'] == 1 && $article['locked_by'] != $_SESSION['user_id'])
	{
		$edit_state = 'disabled';
	}
	$article_form_top = $plugins->do_hooks('article_form_top', $article['article_id']);
	
}
else
{
	$article_form_top = $plugins->do_hooks('article_form_top', $article['article_id']);
}
$templating->set('article_form_top', $article_form_top);

$templating->set('categories_list', $categories_list);

$templating->set('title', htmlentities($_POST['title'], ENT_QUOTES));
$templating->set('slug', $_POST['slug']);

$templating->set('max_height', $core->config('article_image_max_height'));
$templating->set('max_width', $core->config('article_image_max_width'));

$core->editor(['name' => 'text', 'content' => $_POST['text'], 'editor_id' => 'comment_text', 'article_editor' => 1]);

$templating->block('preview_bottom', 'admin_modules/admin_module_articles');

$pick_check = '';
if (isset($_POST['show_block']))
{
	$pick_check = 'checked';
}
$templating->set('pick_check', $pick_check);

// Setup the correct form buttons
if ($_POST['check'] == 'Edit')
{
	$templating->set('buttons', '<button type="submit" name="act" value="Edit" class="btn btn-primary" '.$edit_state.' formaction="/admin.php?module=articles">Edit Article</button> <button type="submit" name="act" value="Preview" class="btn btn-info" />Preview</button>');
	$templating->set('check', 'Edit');
	$templating->set('enable_article', '<label class="checkbox"><input type="checkbox" name="show_article" checked /> Enable article?</label>');
	$templating->set('submit_as_self', '');
}

else if ($_POST['check'] == 'Draft')
{
	$templating->set('buttons', '<button type="submit" name="act" value="add_draft" formaction="/admin.php?module=articles" class="btn">Publish Now</button> <button type="submit" name="act" value="Move_Draft" class="btn btn-info" formaction="/admin.php?module=articles" />Move to Admin Review Queue</button> <button type="submit" name="act" value="Edit_Draft" class="btn btn-primary" formaction="/admin.php?module=articles">Finish Edit</button> <button type="submit" name="act" value="Preview" class="btn btn-info" />Preview & Edit More</button>');
	$templating->set('check', 'Draft');
	$templating->set('enable_article', '');
	$templating->set('submit_as_self', '');
}

else if ($_POST['check'] == 'Review')
{
	$templating->set('buttons', '<button type="submit" name="act" formaction="/admin.php?module=reviewqueue" value="Approve_Admin" class="btn btn-primary" '.$edit_state.'>Publish Now</button> <button type="submit" name="act" formaction="/admin.php?module=reviewqueue" value="edit" class="btn btn-primary" '.$edit_state.' formaction="/admin.php?module=articles">Finish Edit</button> <button type="submit" name="act" value="Preview" class="btn btn-info" />Preview & Edit More</button>');
	$templating->set('check', 'Review');
	$templating->set('enable_article', '');
	$templating->set('submit_as_self', '');
}

else if ($_POST['check'] == 'Submitted')
{
	$self_check = '';
	if (isset($_POST['submit_as_self']))
	{
		$self_check = 'checked';
	}
	$templating->set('buttons', '<button type="submit" name="act" value="Approve" formaction="/admin.php?module=articles" />Approve & Publish Now</button> <button type="submit" name="act" value="Edit_Submitted" formaction="/admin.php?module=articles">Finish Edit</button> <button type="submit" name="act" value="Deny" formaction="/admin.php?module=articles" />Deny</button> <button type="submit" value="Preview_Submitted" formaction="/admin.php?module=preview" name="act" class="btn btn-info">Preview Submitted Article & Edit More</button>');
	$templating->set('check', 'Submitted');
	$templating->set('enable_article', '');
	$templating->set('submit_as_self', '<label class="checkbox"><input type="checkbox" name="submit_as_self" '.$self_check.'/> Submit article as yourself? <em>Useful if you rewrote an article based on what was submitted. It will add a thank you text to the bottom.</em></label>');
}
else if (isset($_POST['check']) == 'Add')
{
	$templating->set('buttons', '<button type="submit" name="act" value="publish_now" formaction="/admin.php?module=add_article" class="btn">Publish Now</button> <button class="btn" type="submit" name="act" value="review" formaction="/admin.php?module=articles">Submit For Review</button> <button type="submit" name="act" value="Preview" class="btn" />Preview & Edit More</button> <button type="submit" name="act" value="Save_Draft" class="btn btn-info" formaction="/admin.php?module=articles">Save as draft</button>');
	$templating->set('check', 'Add');
	$templating->set('enable_article', '');
	$templating->set('submit_as_self', '');
}

$templating->set('author_id', $author_id);

if (isset($_POST['article_id']))
{
	$templating->set('article_id', $_POST['article_id']);
}

else
{
	$templating->set('article_id', '');
}

// sort out previously uploaded images
$previously_uploaded = '';
$previously_uploaded = $article_class->display_previous_uploads();

if (isset($_POST['check']) && isset($article))
{
	// add in uploaded images from database
	$previously_uploaded = $article_class->display_previous_uploads($article['article_id']);
}

$templating->set('previously_uploaded', $previously_uploaded);

$auto_subscribe = '';
if (isset($_POST['subscribe']))
{
	$auto_subscribe = 'checked';
}
$templating->set('subscribe_check', $auto_subscribe);

$subscribe_check = '';
$subscribe_box = '';
if (isset($article))
{
	if ($article['author_id'] == $_SESSION['user_id'])
	{
		if (isset($_POST['subscribe']))
		{
			$subscribe_check = 'checked';
		}
	}
}
$subscribe_box = '<label class="checkbox"><input type="checkbox" name="subscribe" '.$subscribe_check.' /> Subscribe to article to receive comment replies via email</label>';
$templating->set('subscribe_box', $subscribe_box);
