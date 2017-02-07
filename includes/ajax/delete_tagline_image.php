<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_user.php');
$user = new user();
$user->check_session();

if ($user->check_group([1,2,5]) == false)
{
	die('You should not be here.');
}

$check = 0;

if (isset($_POST['article_id']) && $_POST['article_id'] != 0)
{
	$db->sqlquery("SELECT `title`,`tagline_image` FROM `articles` WHERE `article_id` = ?", array($_POST['article_id']));
	$article = $db->fetch();

	// remove old image
	if (!empty($article['tagline_image']))
	{
		if (unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/' . $article['tagline_image']) && unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/articles/tagline_images/thumbnails/' . $article['tagline_image']))
		{
			$check = 1;
		}
	}

	$db->sqlquery("UPDATE `articles` SET `tagline_image` = '', `gallery_tagline` = 0 WHERE `article_id` = ?", array($_POST['article_id']));

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
