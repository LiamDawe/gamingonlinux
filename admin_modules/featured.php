<?php
include($file_dir . '/includes/class_image_uploads.php');
$image_upload = new image_upload();

$templating->merge('admin_modules/admin_module_featured');

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'accepted')
	{
		$core->message('The article has been published! You set it as an editors pick so it needs a featured banner uploaded here.');
	}
	if ($_GET['message'] == 'added')
	{
		$core->message('Added that carousel item!');
	}

	if ($_GET['message'] == 'empty')
	{
		$core->message('You must fill out all fields!', 1, NULL);
	}

	if ($_GET['message'] == 'nofile')
	{
		$core->message("No file was selected to upload!", NULL, 1);
	}

	if ($_GET['message'] == 'filetype')
	{
		$core->message("You can only upload gif jpg and png files for featured images.", NULL, 1);
	}

	if ($_GET['message'] == 'dimensions')
	{
		$core->message('It was not the correct size!', NULL, 1);
	}

	if ($_GET['message'] == 'toobig')
	{
		$core->message('File size too big! The max is 300kb, try to use some more compression on it, or find another image.', NULL, 1);
	}

	if ($_GET['message'] == 'cantmove')
	{
		$core->message('Could not upload file! Upload folders may not have correct permissions.', NULL, 1);
	}
	if ($_GET['message'] == 'edited')
	{
		$core->message('You edited that carousel item!');
	}

	if ($_GET['message'] == 'deleted')
	{
		$core->message('You have deleted that carousel item!');
	}
}

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'add')
	{
		if (isset($_GET['article_id']))
		{
			$db->sqlquery("SELECT `title` FROM `articles`	WHERE `article_id` = ?", array($_GET['article_id']));
			if ($db->num_rows() == 1)
			{
				$title = $db->fetch();

				$templating->block('add', 'admin_modules/admin_module_featured');
				$templating->set('max_width', $core->config('carousel_image_width'));
				$templating->set('max_height', $core->config('carousel_image_height'));

				$templating->set('article_title', $title['title']);
				$templating->set('article_id', $_GET['article_id']);
			}
			else {
				$core->message('Article does not exist!');
			}
		}
		else
		{
			$core->message("You can only add a featured image when setting an article to be an editor's pick!");
		}
	}

	if ($_GET['view'] == 'manage')
	{
		$templating->block('manage_top', 'admin_modules/admin_module_featured');

		$db->sqlquery("SELECT p.`article_id`, p.featured_image, p.hits, a.`title` FROM `editor_picks` p INNER JOIN `articles` a ON p.article_id = a.article_id");
		$count = $db->num_rows();

		while ($items = $db->fetch())
		{
			$templating->block('manage_item', 'admin_modules/admin_module_featured');
			$templating->set('title', $items['title']);
			$templating->set('article_id', $items['article_id']);
			$image = '<strong>This Editors Pick currently has no featured image set!</strong><br />';
			if (!empty($items['featured_image']))
			{
				$image = '<img src="' . $core->config('website_url') . 'uploads/carousel/' . $items['featured_image'] . '" width="100%" class="img-responsive"/>';
			}

			$templating->set('current_image', $image);
			$templating->set('max_width', $core->config('carousel_image_width'));
			$templating->set('max_height', $core->config('carousel_image_height'));
			$templating->set('hits', $items['hits']);
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		$upload = image_upload::featured_image($_POST['article_id'], 1);
		if ($upload === true)
		{
			header("Location: /admin.php?module=featured&view=manage&message=added");
		}
		else
		{
			header("Location: /admin.php?module=featured&view=manage&message=$upload");
		}
	}

	if ($_POST['act'] == 'edit')
	{
		$upload = image_upload::featured_image($_POST['article_id'], 0);
		if ($upload === true)
		{
			header("Location: /admin.php?module=featured&view=manage&message=edited");
		}
		else
		{
			header("Location: /admin.php?module=featured&view=manage&message=$upload");
		}
	}

	if ($_POST['act'] == 'delete')
	{
		$db->sqlquery("SELECT `featured_image` FROM `editor_picks` WHERE `article_id` = ?", array($_POST['article_id']));
		$featured = $db->fetch();

		$db->sqlquery("DELETE FROM `editor_picks` WHERE `article_id` = ?", array($_POST['article_id']));
		unlink($core->config('path') . 'uploads/carousel/' . $featured['featured_image']);

		$db->sqlquery("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'total_featured'");

		$db->sqlquery("UPDATE `articles` SET `show_in_menu` = 0 WHERE `article_id` = ?", array($_POST['article_id']));

		header("Location: /admin.php?module=featured&view=manage&message=deleted");
	}
}
