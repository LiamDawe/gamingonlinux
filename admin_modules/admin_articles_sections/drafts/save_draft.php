<?php
$text = trim($_POST['text']);

$title = strip_tags($_POST['title']);

// check its set, if not hard-set it based on the article title
if (isset($_POST['slug']) && !empty($_POST['slug']))
{
	$slug = core::nice_title($_POST['slug']);
}
else
{
	$slug = core::nice_title($_POST['title']);
}

$gallery_tagline_sql = $article_class->gallery_tagline();

$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = 0, `active` = 0, `draft` = 1, `date` = ?, `preview_code` = ? $gallery_tagline_sql", array($_SESSION['user_id'], $title, $slug, $_POST['tagline'], $text, core::$date, core::random_id()));

$article_id = $db->grab_id();

article::process_categories($article_id);

// force subscribe, so they don't lose editors comments
$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?, `emails` = 1, `send_email` = 1", array($_SESSION['user_id'], $article_id));

// update any uploaded images to have this article id, stop any images not being attached to an article
if (isset($_SESSION['uploads']))
{
	foreach($_SESSION['uploads'] as $key)
	{
		$db->sqlquery("UPDATE `article_images` SET `article_id` = ? WHERE `filename` = ?", array($article_id, $key['image_name']));
	}
}

if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
{
	$core->move_temp_image($article_id, $_SESSION['uploads_tagline']['image_name']);
}

// article has been posted, remove any saved info from errors (so the fields don't get populated if you post again)
unset($_SESSION['atitle']);
unset($_SESSION['atagline']);
unset($_SESSION['atext']);
unset($_SESSION['acategories']);
unset($_SESSION['agames']);
unset($_SESSION['uploads_tagline']);
unset($_SESSION['image_rand']);
unset($_SESSION['uploads']);
unset($_SESSION['original_text']);
unset($_SESSION['gallery_tagline_id']);
unset($_SESSION['gallery_tagline_rand']);
unset($_SESSION['gallery_tagline_filename']);

header("Location: admin.php?module=articles&view=drafts&message=saved&extra=draft");
