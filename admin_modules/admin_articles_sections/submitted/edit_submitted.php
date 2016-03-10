<?php
$title = strip_tags($_POST['title']);
$tagline = trim($_POST['tagline']);
$text = trim($_POST['text']);

$temp_tagline = 0;
if (!empty($_POST['temp_tagline_image']))
{
	$temp_tagline = 1;
}

// make sure its not empty
if (empty($title) || empty($tagline) || empty($_POST['text']) || empty($_POST['article_id']))
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=empty&temp_tagline=$temp_tagline");
}

else if (strlen($_POST['tagline']) < 100)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=shorttagline&temp_tagline=$temp_tagline");
}

else if (strlen($_POST['tagline']) > 400)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=taglinetoolong&temp_tagline=$temp_tagline");
}

else if (strlen($_POST['title']) < 10)
{
	$_SESSION['atitle'] = $_POST['title'];
	$_SESSION['atagline'] = $_POST['tagline'];
	$_SESSION['atext'] = $_POST['text'];
	$_SESSION['acategories'] = $_POST['categories'];

	header("Location: admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&error=shorttitle&temp_tagline=$temp_tagline");
}

else
{
	$block = 0;
	if (isset($_POST['show_block']))
	{
		$block = 1;
	}

	$db->sqlquery("UPDATE `articles` SET `title` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ? WHERE `article_id` = ?", array($title, $tagline, $text, $block, $_POST['article_id']));

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

	// update history
	$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?", array($_POST['article_id'], $_SESSION['user_id'], $core->date));

	// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
	unset($_SESSION['atitle']);
	unset($_SESSION['atagline']);
	unset($_SESSION['atext']);
	unset($_SESSION['acategories']);
	unset($_SESSION['tagerror']);
	unset($_SESSION['aactive']);
	unset($_SESSION['uploads']);
	unset($_SESSION['uploads_tagline']);
	unset($_SESSION['image_rand']);

	header("Location: {$config['website_url']}admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&message=editdone");
}
