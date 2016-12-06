<?php
$templating->merge('admin_modules/livestreams');

if (isset($_GET['view']) && !isset($_POST['act']))
{
	if ($_GET['view'] == 'manage')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'edited')
			{
				$core->message("Livestream edited!");
			}
			if ($_GET['message'] == 'added')
			{
				$core->message("Livestream added");
			}
			if ($_GET['message'] == 'missing')
			{
				$core->message('Please fill a title, and a date!', null, 1);
			}
		}

		$templating->set_previous('meta_description', 'Managing livestreams', 1);
		$templating->set_previous('title', 'Managing livestreams', 1);

		$templating->block('add_top', 'admin_modules/livestreams');

		$templating->block('item', 'admin_modules/livestreams');
		$templating->set('title', '');
		$templating->set('date', '');
		$templating->set('end_date', '');
		$templating->set('community_check', '');
		$templating->set('community_name', '');
		$templating->set('stream_url', '');

		$templating->block('add_bottom', 'admin_modules/livestreams');

		$templating->block('edit_top', 'admin_modules/livestreams');

		$streams_grab = $db->sqlquery("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url` FROM `livestreams` ORDER BY `date` ASC");
		$streams_store = $db->fetch_all_rows();

		foreach ($streams_store as $streams)
		{
			$templating->block('item', 'admin_modules/livestreams');
			$templating->set('title', $streams['title']);
			$templating->set('id', $streams['row_id']);

			$date = new DateTime($streams['date']);
			$templating->set('date', $date->format('Y-m-d H:i:s'));

			$end_date = new DateTime($streams['end_date']);
			$templating->set('end_date', $end_date->format('Y-m-d H:i:s'));

			$streamer_list = '';
			$db->sqlquery("SELECT s.`user_id`, u.username FROM `livestream_presenters` s INNER JOIN users u ON u.user_id = s.user_id WHERE `livestream_id` = ?", array($streams['row_id']));
			while ($grab_streamers = $db->fetch())
			{
				$streamer_list .= '<option value="'.$grab_streamers['user_id'].'" selected>'.$grab_streamers['username'].'</option>';
			}
			$templating->set('users_list', $streamer_list);

			$community_check = '';
			if ($streams['community_stream'] == 1)
			{
				$community_check = 'checked';
			}
			$templating->set('community_check', $community_check);

			$templating->set('stream_url', $streams['stream_url']);

			$streamer_community_name = '';
			if (!empty($streams['streamer_community_name']))
			{
				$streamer_community_name = $streams['streamer_community_name'];
			}
			$templating->set('community_name', $streamer_community_name);

			$templating->block('edit_bottom', 'admin_modules/livestreams');
			$templating->set('id', $streams['row_id']);
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Add')
	{
		if (empty($_POST['title']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=livestreams&view=add&error=missing");
			die();
		}

		$date = new DateTime($_POST['date']);
		$end_date = new DateTime($_POST['end_date']);
		$title = trim($_POST['title']);
		$community_name = trim($_POST['community_name']);
		$stream_url = trim($_POST['stream_url']);

		$date_created = date('Y-m-d H:i:s');

		$community = 0;
		if (isset($_POST['community']))
		{
			$community = 1;
		}

		$db->sqlquery("INSERT INTO `livestreams` SET `title` = ?, `date_created` = ?, `date` = ?, `end_date` = ?, `community_stream` = ?, `streamer_community_name` = ?, `stream_url` = ?", array($title, $date_created, $date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $community, $community_name, $stream_url));
		$new_id = $db->grab_id();

		$core->process_livestream_users($new_id);

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], 'new_livestream_event', core::$date, core::$date));

		header("Location: /admin.php?module=livestreams&view=manage&&message=added");
	}
	if ($_POST['act'] == 'Edit')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=livestreams&view=manage&message=missing_id");
			die();
		}

		if (empty($_POST['title']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=livestreams&view=edit&message=missing&id=" . $_POST['id']);
			die();
		}

		$date = new DateTime($_POST['date']);
		$end_date = new DateTime($_POST['end_date']);
		$title = trim($_POST['title']);
		$community_name = trim($_POST['community_name']);
		$stream_url = trim($_POST['stream_url']);

		$community = 0;
		if (isset($_POST['community']))
		{
			$community = 1;
		}

		$db->sqlquery("UPDATE `livestreams` SET `title` = ?, `date` = ?, `end_date` = ?, `community_stream` = ?, `streamer_community_name` = ?, `stream_url` = ? WHERE `row_id` = ?", array($title, $date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $community, $community_name, $stream_url, $_POST['id']));

		$core->process_livestream_users($_POST['id']);

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `data` = ?, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], 'edit_livestream_event', $_POST['id'], core::$date, core::$date));

		header("Location: /admin.php?module=livestreams&view=manage&message=edited");
	}
	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$db->sqlquery("SELECT `title` FROM `livestreams` WHERE `row_id` = ?", array($_POST['id']));
			$title = $db->fetch();

			$return = '';
			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				$return = $_GET['return'];
			}

			$core->yes_no('Are you sure you want to delete ' . $title['title'] . ' from the livestream event list?', "admin.php?module=livestreams&id={$_POST['id']}", "Delete");
		}

		else if (isset($_POST['no']))
		{
			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				header("Location: /index.php?module=livestreams");
				die();
			}
		}

		else if (isset($_POST['yes']))
		{
			$db->sqlquery("SELECT `title` FROM `livestreams` WHERE `row_id` = ?", array($_GET['id']));
			$title = $db->fetch();

			$db->sqlquery("DELETE FROM `livestreams` WHERE `row_id` = ?", array($_GET['id']));

			$db->sqlquery("DELETE FROM `livestream_presenters` WHERE `id` = ?", array($_GET['id']));

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], 'deleted_livestream_event', core::$date, core::$date));

			header("Location: /admin.php?module=livestreams&view=manage&message=deleted");
		}
	}
}
