<?php
$return_page = "admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}";
if ($checked = $article_class->check_article_inputs($return_page))
{
	$block = 0;
	if (isset($_POST['show_block']))
	{
		$block = 1;
	}

	$db->sqlquery("UPDATE `articles` SET `title` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ? WHERE `article_id` = ?", array($checked['title'], $checked['tagline'], $checked['text'], $block, $_POST['article_id']));

	$article_class->process_categories($_POST['article_id']);

	$article_class->article_game_assoc($_POST['article_id']);

	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name']);
	}

	// update history
	$db->sqlquery("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date));

	// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
	unset($_SESSION['atitle']);
	unset($_SESSION['aslug']);
	unset($_SESSION['atagline']);
	unset($_SESSION['atext']);
	unset($_SESSION['acategories']);
	unset($_SESSION['tagerror']);
	unset($_SESSION['aactive']);
	unset($_SESSION['uploads']);
	unset($_SESSION['uploads_tagline']);
	unset($_SESSION['image_rand']);

	header("Location: " . core::config('website_url') . "admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}&message=editdone");
}
