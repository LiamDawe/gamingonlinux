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

		$db->sqlquery("SELECT `username`, `user_id` FROM `users` WHERE `user_group` IN (1,2,5) ORDER BY `username` ASC");
		$users_list = $db->fetch_all_rows();

		$templating->block('add_top', 'admin_modules/livestreams');

		$templating->block('item', 'admin_modules/livestreams');
		$templating->set('title', '');
		$templating->set('date', '');

		$options = '';
		foreach ($users_list as $user_loop)
		{
			$selected = '';
			if ($_SESSION['user_id'] == $user_loop['user_id'])
			{
				$selected = 'selected';
			}
			$options .= '<option value="'.$user_loop['user_id'].'" ' . $selected . '>'.$user_loop['username'].'</option>';
		}
		$templating->set('options', $options);

		$templating->block('add_bottom', 'admin_modules/livestreams');

		$templating->block('edit_top', 'admin_modules/livestreams');

		$db->sqlquery("SELECT l.`row_id`, l.`title`, l.`date`, l.`owner_id` FROM `livestreams` l INNER JOIN `users` u ON l.`owner_id` = u.`user_id` ORDER BY `date` ASC");
		while ($streams = $db->fetch())
		{
			$templating->block('item', 'admin_modules/livestreams');
			$templating->set('title', $streams['title']);
			$templating->set('id', $streams['row_id']);

			$date = new DateTime($streams['date']);
			$templating->set('date', $date->format('Y-m-d H:i:s'));

			$options = '';
			foreach ($users_list as $user_loop)
			{
				$selected = '';
				if ($streams['owner_id'] == $user_loop['user_id'])
				{
					$selected = 'selected';
				}
				$options .= '<option value="'.$user_loop['user_id'].'" ' . $selected . '>'.$user_loop['username'].'</option>';
			}
			$templating->set('options', $options);

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
		$title = trim($_POST['title']);

		$db->sqlquery("INSERT INTO `livestreams` SET `title` = ?, `date` = ?, `owner_id` = ?", array($title, $date->format('Y-m-d H:i:s'), $_POST['user_id']));
		$new_id = $db->grab_id();

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array($_SESSION['username'] . ' added a new livestream event.', core::$date, core::$date));

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
		$title = trim($_POST['title']);

		$db->sqlquery("UPDATE `livestreams` SET `title` = ?, `date` = ?, `owner_id` = ? WHERE `row_id` = ?", array($title, $date->format('Y-m-d H:i:s'), $_POST['user_id'], $_POST['id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array($_SESSION['username'] . ' edited the ' . $_POST['title'] . ' livestream.', core::$date, core::$date));

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

			$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array($_SESSION['username'] . ' removed ' . $title['title'] . ' from the livestream events.', core::$date, core::$date));

				header("Location: /admin.php?module=livestreams&view=manage&message=deleted");
		}
	}
}
