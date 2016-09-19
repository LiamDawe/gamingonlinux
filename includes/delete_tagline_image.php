<?php
session_start();

include('config.php');

include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('class_core.php');
$core = new core();

$check = 0;

if (isset($_POST['article_id']) && $_POST['article_id'] != 0)
{
	$db->sqlquery("SELECT `article_top_image`,`article_top_image_filename`,`title`,`tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
	$article = $db->fetch();

	// remove old image
	if ($article['article_top_image'] == 1 && !empty($article['article_top_image_filename']))
	{
		if (unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/topimages/' . $article['article_top_image_filename']))
		{
			$check = 1;
		}
	}
	if (!empty($article['tagline_image']))
	{
		if (unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']) && unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']))
		{
			$check = 1;
		}
	}

	$db->sqlquery("UPDATE `articles` SET `article_top_image` = 0, `article_top_image_filename` = '', `tagline_image` = '' WHERE `article_id` = ?", array($_POST['article_id']));

	if ($check == 1)
	{
		echo "YES";
	}

	else
	{
		echo "NO";
	}
}
else
{
	if (isset($_SESSION['uploads_tagline']['image_name']))
	{
		if(unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $_SESSION['uploads_tagline']['image_name']) && unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $_SESSION['uploads_tagline']['image_name']))
		{
			unset($_SESSION['uploads_tagline']);
			echo "YES";
		}

		else
		{
			echo "NO";
		}
	}
}
?>
