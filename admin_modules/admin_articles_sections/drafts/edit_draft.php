<?php
$db->sqlquery("SELECT `author_id` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
$grab_author = $db->fetch();
if ($grab_author['author_id'] == $_SESSION['user_id'])
{
	$title = strip_tags($_POST['title']);
	$tagline = trim($_POST['tagline']);
	$text = trim($_POST['text']);
	$slug = $core->nice_title($_POST['slug']);

	$db->sqlquery("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = 0 WHERE `article_id` = ?", array($title, $slug, $tagline, $text, $_POST['article_id']));

	// delete any existing categories that aren't in the final list for publishing
	$db->sqlquery("SELECT `ref_id`, `article_id`, `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($_POST['article_id']));
	$current_categories = $db->fetch_all_rows();

	foreach ($current_categories as $current_category)
	{
		if (!in_array($current_category['category_id'], $_POST['categories']))
		{
			$db->sqlquery("DELETE FROM `article_category_reference` WHERE `ref_id` = ?", array($current_category['ref_id']));
		}
	}

	// get fresh list of categories, and insert any that don't exist
	$db->sqlquery("SELECT `category_id` FROM `article_category_reference` WHERE `article_id` = ?", array($_POST['article_id']));
	$current_categories = $db->fetch_all_rows(PDO::FETCH_COLUMN, 0);

	foreach($_POST['categories'] as $category)
	{
		if (!in_array($category, $current_categories))
		{
			$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = ?", array($_POST['article_id'], $category));
		}
	}

	// process game associations
	$db->sqlquery("DELETE FROM `article_game_assoc` WHERE `article_id` = ?", array($_POST['article_id']));

	if (isset($_POST['games']))
	{
		foreach($_POST['games'] as $game)
		{
			$db->sqlquery("INSERT INTO `article_game_assoc` SET `article_id` = ?, `game_id` = ?", array($_POST['article_id'], $game));
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
	unset($_SESSION['agames']);
	unset($_SESSION['tagerror']);
	unset($_SESSION['aactive']);
	unset($_SESSION['uploads']);
	unset($_SESSION['uploads_tagline']);
	unset($_SESSION['image_rand']);

	header("Location: " . core::config('website_url') . "admin.php?module=articles&view=drafts&aid={$_POST['article_id']}&message=editdone");
}
else
{
		header("Location: " . core::config('website_url') . "admin.php?module=articles&view=drafts");
}
