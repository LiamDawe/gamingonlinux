<?php
$image_upload = new image_upload($dbl, $core);

$templating->load('admin_modules/admin_module_featured');

if (isset($_GET['view']))
{
	$start_year = date('Y');
	$next_year = $start_year + 1;

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
				$templating->set('start_year', $start_year);
				$templating->set('next_year', $next_year);

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

		$res = $dbl->run("SELECT p.`article_id`, p.featured_image, p.hits, p.end_date, a.`title` FROM `editor_picks` p INNER JOIN `articles` a ON p.article_id = a.article_id")->fetch_all();

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

				$end_date = new DateTime($items['end_date']);
				$templating->set('end_date', $end_date->format('Y-m-d H:i:s'));
				$templating->set('start_year', $start_year);
				$templating->set('next_year', $next_year);

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
		if (!isset($_POST['article_id']) || (isset($_POST['article_id']) && !is_numeric($_POST['article_id'])))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'article id';
			header("Location: /admin.php?module=featured&view=manage");
			die();
		}

		// make sure date is valid
		if (!core::validateDate($_POST['end_date']))
		{
			$_SESSION['message'] = 'invalid_end_date';
			header("Location: /admin.php?module=featured&view=manage&view=add&article_id=181");
			die();
		}

		// make sure end date isn't before today
		$current_time = date('Y-m-d H:i:s');
		if ($_POST['end_date'] < $current_time)
		{
			$_SESSION['message'] = 'end_date_wrong';
			header("Location: /admin.php?module=featured&view=manage&view=add&article_id=181");
			die();
		}

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
		if (!isset($_POST['article_id']) || (isset($_POST['article_id']) && !is_numeric($_POST['article_id'])))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'article id';
			header("Location: /admin.php?module=featured&view=manage");
			die();
		}

		// make sure date is valid
		if (!core::validateDate($_POST['end_date']))
		{
			$_SESSION['message'] = 'invalid_end_date';
			header("Location: /admin.php?module=featured&view=manage");
			die();
		}

		// make sure end date isn't before today
		$current_time = date('Y-m-d H:i:s');
		if ($_POST['end_date'] < $current_time)
		{
			$_SESSION['message'] = 'end_date_wrong';
			header("Location: /admin.php?module=featured&view=manage");
			die();
		}

		$dbl->run("UPDATE `editor_picks` SET `end_date` = ? WHERE `article_id` = ?", array($_POST['end_date'], $_POST['article_id']));

		if (isset($_FILES['new_image']))
		{
			$upload = $image_upload->featured_image($_POST['article_id'], 0);
		}
		if (!isset($_SESSION['message'])) // no error from the upload
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
