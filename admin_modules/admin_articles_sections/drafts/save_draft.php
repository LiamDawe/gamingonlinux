<?php
$text = trim($_POST['text']);

$title = strip_tags($_POST['title']);

$db->sqlquery("INSERT INTO `articles` SET `author_id` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = 0, `active` = 0, `draft` = 1, `date` = ?, `preview_code` = ?", array($_SESSION['user_id'], $title, $_POST['slug'], $_POST['tagline'], $text, core::$date, $core->random_id()));

$article_id = $db->grab_id();

if (isset($_POST['categories']))
{
	foreach($_POST['categories'] as $category)
	{
		$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($article_id, $category));
	}
}

// check if they are subscribing
if (isset($_POST['subscribe']))
{
	$db->sqlquery("INSERT INTO `articles_subscriptions` SET `user_id` = ?, `article_id` = ?", array($_SESSION['user_id'], $article_id));
}

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
unset($_SESSION['uploads_tagline']);
unset($_SESSION['image_rand']);
unset($_SESSION['uploads']);

header("Location: admin.php?module=articles&view=drafts&message=saved");
