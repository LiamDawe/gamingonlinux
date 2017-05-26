<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user = new user($dbl, $core);
$user->check_session();

if ($user->check_group([1,2,5]) == false)
{
	die('You should not be here.');
}

$check = 0;

if (isset($_POST['article_id']) && $_POST['article_id'] != 0)
{
	$article = $dbl->run("SELECT `title`,`tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']))->fetch();

	// remove old image
	if (!empty($article['tagline_image']))
	{
		if (unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']) && unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']))
		{
			$check = 1;
		}
	}

	$dbl->run("UPDATE `articles` SET `tagline_image` = '', `gallery_tagline` = 0 WHERE `article_id` = ?", array($_POST['article_id']));

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
		if(unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/temp/' . $_SESSION['uploads_tagline']['image_name']) && unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/temp/thumbnails/' . $_SESSION['uploads_tagline']['image_name']))
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
