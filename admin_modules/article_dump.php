<?php
$templating->merge('admin_modules/article_dump');
$templating->block('top');
$templating->block('main');

if (isset($_GET['dump']))
{
	$sections = $bbcode->article_dump($_POST['text']);

	$_SESSION['atitle'] = $sections['title'];
	$_SESSION['atagline'] = $sections['tagline'];
	$_SESSION['atext'] = $sections['text'];
	$_SESSION['acategories'] = array();

	// if the tags exist, set them
	$tags_check = explode(",", $sections['tags']);

	foreach ($tags_check as $tag)
	{
		$db->sqlquery("SELECT `category_id` FROM `articles_categorys` WHERE `category_name` = ?", array($tag));
		$get_tag = $db->fetch();
		if (isset($get_tag['category_id']))
		{
			$_SESSION['acategories'][] = $get_tag['category_id'];
		}
	}

	$_SESSION['aslug'] = core::nice_title($sections['title']);
	header("Location: /admin.php?module=add_article&dump");
}
