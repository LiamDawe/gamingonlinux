<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

$return_page = "admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}";
if ($checked = $article_class->check_article_inputs($return_page))
{
	$block = 0;
	if (isset($_POST['show_block']))
	{
		$block = 1;
	}

	$article_class->gallery_tagline($checked);

	$dbl->run("UPDATE `articles` SET `title` = ?, `slug` = ?, `tagline` = ?, `text`= ?, `show_in_menu` = ? WHERE `article_id` = ?", array($checked['title'], $checked['slug'], $checked['tagline'], $checked['text'], $block, $_POST['article_id']));

	$article_class->process_categories($_POST['article_id']);
	$article_class->process_games($_POST['article_id']);

	if (isset($_SESSION['uploads_tagline']) && $_SESSION['uploads_tagline']['image_rand'] == $_SESSION['image_rand'])
	{
		$core->move_temp_image($_POST['article_id'], $_SESSION['uploads_tagline']['image_name'], $checked['text']);
	}

	if ($_SESSION['original_text'] != $checked['text']) // only update history if text is actually different
	{
		$dbl->run("INSERT INTO `article_history` SET `article_id` = ?, `user_id` = ?, `date` = ?, `text` = ?", array($_POST['article_id'], $_SESSION['user_id'], core::$date, $_SESSION['original_text']));
	}

	// article has been edited, remove any saved info from errors (so the fields don't get populated if you post again)
	$article_class->reset_sessions();
	unset($_SESSION['original_text']);

	$_SESSION['message'] = 'edited';
	$_SESSION['message_extra'] = 'submitted';
	header("Location: " . $core->config('website_url') . "admin.php?module=articles&view=Submitted&aid={$_POST['article_id']}");
}
