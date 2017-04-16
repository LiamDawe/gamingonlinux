<?php
$db->sqlquery("SELECT `article_id`, `author_id`, `tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
$grab_author = $db->fetch();
if ($grab_author['author_id'] == $_SESSION['user_id'])
{
	$title = strip_tags($_POST['title']);
	$tagline = trim($_POST['tagline']);
	$text = trim($_POST['text']);
	$slug = core::nice_title($_POST['slug']);

	$article_class->gallery_tagline($grab_author);

	$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = 0 WHERE `article_id` = ?", array($title, $slug, $tagline, $text, $_POST['article_id']));

	article_class::process_categories($_POST['article_id']);

	plugins::do_hooks('article_database_entry', $_POST['article_id']);

	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
	}

	$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));

	unset($_SESSION['atitle']);
	unset($_SESSION['atagline']);
	unset($_SESSION['atext']);
	unset($_SESSION['atext2']);
	unset($_SESSION['atext3']);
	unset($_SESSION['acategories']);
	unset($_SESSION['aactive']);
	unset($_SESSION['uploads']);
	unset($_SESSION['uploads_tagline']);
	unset($_SESSION['image_rand']);
	unset($_SESSION['original_text']);
	unset($_SESSION['gallery_tagline_id']);
	unset($_SESSION['gallery_tagline_rand']);
	unset($_SESSION['gallery_tagline_filename']);

	$_SESSION['message'] = 'edited';
	$_SESSION['message_extra'] = 'draft';
	header("Location: " . core::config('website_url') . "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}");
}
else
{
	header("Location: " . core::config('website_url') . "admin.php?module=articles&view=drafts");
}
