<?php
$title = strip_tags($_POST['title']);
$tagline = trim($_POST['tagline']);
$text = trim($_POST['text']);

$db->sqlquery("UPDATE `articles` SET `title` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = 0 WHERE `article_id` = ?", array($title, $tagline, $text, $_POST['article_id']));

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

unset($_SESSION['atitle']);
unset($_SESSION['atagline']);
unset($_SESSION['atext']);
unset($_SESSION['atext2']);
unset($_SESSION['atext3']);
unset($_SESSION['acategories']);
unset($_SESSION['tagerror']);
unset($_SESSION['aactive']);
unset($_SESSION['uploads']);
unset($_SESSION['uploads_tagline']);
unset($_SESSION['image_rand']);

header("Location: " . core::config('website_url') . "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&message=editdone");
