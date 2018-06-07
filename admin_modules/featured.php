<?php
$image_upload = new image_upload($dbl, $core);

$templating->load('admin_modules/admin_module_featured');

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'add')
	{
		if (isset($_GET['article_id']))
		{
			$title = $dbl->run("SELECT `title` FROM `articles` WHERE `article_id` = ?", array($_GET['article_id']))->fetch();
			if ($title)
			{
				$templating->block('add', 'admin_modules/admin_module_featured');
				$templating->set('max_width', $core->config('carousel_image_width'));
				$templating->set('max_height', $core->config('carousel_image_height'));

				$templating->set('article_title', $title['title']);
				$templating->set('article_id', $_GET['article_id']);
			}
			else 
			{
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

		$res = $dbl->run("SELECT p.`article_id`, p.featured_image, p.hits, a.`title` FROM `editor_picks` p INNER JOIN `articles` a ON p.article_id = a.article_id")->fetch_all();

		if ($res)
		{
			foreach ($res as $items)
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
		else
		{
			$core->message("There are no current editor picks :( - you should probably add some!");
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		$upload = $image_upload->featured_image($_POST['article_id'], 1);
		if ($upload === true)
		{
			// update cache
			$new_featured_total = $core->config('total_featured') + 1;
			core::$redis->set('CONFIG_total_featured', $new_featured_total); // no expiry as config hardly ever changes

			$_SESSION['message'] = 'added';
		}
		header("Location: /admin.php?module=featured&view=manage&view=add&article_id=".$_POST['article_id']);
	}

	if ($_POST['act'] == 'edit')
	{
		$upload = $image_upload->featured_image($_POST['article_id'], 0);

		if ($upload === true)
		{
			$_SESSION['message'] = 'edited';	
		}
		header("Location: /admin.php?module=featured&view=manage");
	}

	if ($_POST['act'] == 'delete')
	{
		$article_class->remove_editor_pick($_POST['article_id']);

		header("Location: /admin.php?module=featured&view=manage");
	}
}
