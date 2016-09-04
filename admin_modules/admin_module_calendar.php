<?php
$templating->merge('admin_modules/admin_module_calendar');

$years_array = range(2014, 2020);

if (isset($_GET['error']))
{
	if ($_GET['error'] == 'missing')
	{
		$core->message("You have to put at least a name and date in!", NULL, 1);
	}
	if ($_GET['error'] == 'exists')
	{
		$core->message("That game already exists!", NULL, 1);
	}
	if ($_GET['error'] == 'missing_id')
	{
		$core->message("The game id was missing, this is likely a code error or hacking attempt, bug Liam!", NULL, 1);
	}
}

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'added')
	{
		$core->message("You have added the calendar item!");
	}

	if ($_GET['message'] == 'edited')
	{
		$core->message("You have edited that calendar item!");
	}

	if ($_GET['message'] == 'deleted')
	{
		$core->message("You have deleted that calendar item!");
	}
	if ($_GET['message'] == 'approved')
	{
		$core->message("You have approved the calendar item!");
	}
}

if (isset($_GET['view']))
{
	if (!isset($_GET['year']))
	{
		$year = date("Y");
	}
	else if (isset($_GET['year']) && is_numeric($_GET['year']))
	{
		$year = $_GET['year'];
	}

	if ($_GET['view'] == 'search')
	{
		$templating->block('search', 'admin_modules/admin_module_calendar');
		$templating->block('search_result_top', 'admin_modules/admin_module_calendar');

		$db->sqlquery("SELECT `id`, `name` FROM `calendar` WHERE `name` LIKE ?", array('%'.$_POST['name'].'%'));
		$total_found = $db->num_rows();
		if ($total_found > 0)
		{
			while ($items = $db->fetch())
			{
				$templating->block('search_items', 'admin_modules/admin_module_calendar');
				$templating->set('id', $items['id']);
				$templating->set('name', $items['name']);
			}
		}
		else
		{
			$core->message('None found.');
		}

		$templating->block('search_result_bottom', 'admin_modules/admin_module_calendar');
	}

	if ($_GET['view'] == 'edit')
	{
		$templating->block('edit_top', 'admin_modules/admin_module_calendar');

		$db->sqlquery("SELECT `id`, `date`, `name`, `comment`, `link`, `best_guess` FROM `calendar` WHERE `id` = ?", array($_GET['id']));
		$listing = $db->fetch();

		$templating->block('edit_item', 'admin_modules/admin_module_calendar');
		$guess_check = '';
		if ($listing['best_guess'] == 1)
		{
			$guess_check = 'checked';
		}
		$templating->set('guess_check', $guess_check);
		$templating->set('link', $listing['link']);
		$templating->set('name', $listing['name']);
		$templating->set('comment', $listing['comment']);
		$templating->set('id', $listing['id']);

		$date = new DateTime($listing['date']);
		$templating->set('date', $date->format('d-m-Y'));

		$templating->block('edit_bottom', 'admin_modules/admin_module_calendar');
	}

	if ($_GET['view'] == 'submitted')
	{
		$templating->block('submit_main', 'admin_modules/admin_module_calendar');

		$templating->block('submit_top', 'admin_modules/admin_module_calendar');

		$options = '';
		foreach ($years_array as $what_year)
		{
			$selected = '';
			if ($what_year == $year)
			{
				$selected = 'SELECTED';
			}
			$options .= '<option value="'.$what_year.'" '.$selected.'>'.$what_year.'</option>';
		}
		$templating->set('options', $options);

		$months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');

		foreach ($months as $month_key => $month) // loop through each month
		{
			$templating->block('head', 'admin_modules/admin_module_calendar');

			// count how many there is
			$db->sqlquery("SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month_key AND `approved` = 0");
			$counter = $db->fetch();

			$templating->set('month', $month . ' ' . $year . ' (Total: ' . $counter['count'] . ')');

			$db->sqlquery("SELECT `id`, `date`, `name`, `comment`, `link`, `best_guess` FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month_key AND `approved` = 0 ORDER BY `date` ASC");
			while ($listing = $db->fetch()) // loop through the items
			{
				$get_date = date_parse($listing['date']);
				$current_month = $get_date['month'];

				if ($current_month == $month_key)
				{
					$templating->block('submit_item', 'admin_modules/admin_module_calendar');
					$guess_check = '';
					if ($listing['best_guess'] == 1)
					{
						$guess_check = 'checked';
					}
					$templating->set('guess_check', $guess_check);
					$templating->set('link', $listing['link']);
					$templating->set('name', $listing['name']);
					$templating->set('comment', $listing['comment']);
					$templating->set('id', $listing['id']);

					$date = new DateTime($listing['date']);
					$templating->set('date', $date->format('d-m-Y'));
				}
			}

			$templating->block('bottom', 'admin_modules/admin_module_calendar');
		}
	}

	if ($_GET['view'] == 'manage')
	{
		$templating->block('main', 'admin_modules/admin_module_calendar');
		$templating->block('search', 'admin_modules/admin_module_calendar');
		$templating->block('add', 'admin_modules/admin_module_calendar');

		$templating->block('manage_top', 'admin_modules/admin_module_calendar');

		$options = '';
		foreach ($years_array as $what_year)
		{
			$selected = '';
			if ($what_year == $year)
			{
				$selected = 'SELECTED';
			}
			$options .= '<option value="'.$what_year.'" '.$selected.'>'.$what_year.'</option>';
		}
		$templating->set('options', $options);

		$months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');

		foreach ($months as $month_key => $month) // loop through each month
		{
			$templating->block('head', 'admin_modules/admin_module_calendar');

			// count how many there is
			$db->sqlquery("SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month_key AND `approved` = 1");
			$counter = $db->fetch();

			$templating->set('month', $month . ' ' . $year . ' (Total: ' . $counter['count'] . ')');

			$db->sqlquery("SELECT `id`, `date`, `name`, `comment`, `link`, `best_guess` FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month_key AND `approved` = 1 ORDER BY `date` ASC");
			while ($listing = $db->fetch()) // loop through the items
			{
				$get_date = date_parse($listing['date']);
				$current_month = $get_date['month'];

				if ($current_month == $month_key)
				{
					$templating->block('item', 'admin_modules/admin_module_calendar');
					$guess_check = '';
					if ($listing['best_guess'] == 1)
					{
						$guess_check = 'checked';
					}
					$templating->set('guess_check', $guess_check);
					$templating->set('link', $listing['link']);
					$templating->set('name', $listing['name']);
					$templating->set('comment', $listing['comment']);
					$templating->set('id', $listing['id']);

					$date = new DateTime($listing['date']);
					$templating->set('date', $date->format('d-m-Y'));
				}
			}

			$templating->block('bottom', 'admin_modules/admin_module_calendar');
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Add')
	{
		if (empty($_POST['name']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=calendar&view=manage&error=missing");
			exit;
		}

		$db->sqlquery("SELECT `name` FROM `calendar` WHERE `name` = ?", array($_POST['name']));
		if ($db->num_rows() == 1)
		{
			header("Location: /admin.php?module=calendar&view=manage&error=exists");
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$db->sqlquery("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `comment` = ?, `link` = ?, `best_guess` = ?, `approved` = 1, `edit_date` = ?", array($_POST['name'], $date->format('Y-m-d'), $_POST['comment'], $_POST['link'], $guess, date("Y-m-d")));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array("{$_SESSION['username']} added a new game to the release calendar.", core::$date, core::$date));

		header("Location: /admin.php?module=calendar&view=manage&message=added");
	}

	if ($_POST['act'] == 'Edit')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=calendar&view=manage&error=missing_id");
			exit;
		}

		if (empty($_POST['name']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=calendar&view=manage&error=missing");
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$db->sqlquery("UPDATE `calendar` SET `name` = ?, `date` = ?, `comment` = ?, `link` = ?, `best_guess` = ?, `edit_date` = ? WHERE `id` = ?", array($_POST['name'], $date->format('Y-m-d'), $_POST['comment'], $_POST['link'], $guess, date('Y-m-d'), $_POST['id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array($_SESSION['username'] . ' edited ' . $_POST['name'] . ' in the release calendar.', core::$date, core::$date));

		header("Location: /admin.php?module=calendar&view=manage&message=edited");
	}

	if ($_POST['act'] == 'Approve')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=calendar&view=submitted&error=missing_id");
			exit;
		}

		if (empty($_POST['name']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=calendar&view=submitted&error=missing");
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$db->sqlquery("UPDATE `calendar` SET `name` = ?, `date` = ?, `comment` = ?, `link` = ?, `best_guess` = ?, `approved` = 1, `edit_date` = ? WHERE `id` = ?", array($_POST['name'], $date->format('Y-m-d'), $_POST['comment'], $_POST['link'], $guess, date("Y-m-d"), $_POST['id']));

		$db->sqlquery("DELETE FROM `admin_notifications` WHERE `calendar_id` = ?", array($_POST['id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?, `calendar_id` = ?", array($_SESSION['username'] . ' approved ' . $_POST['name'] . ' for the release calendar.', core::$date, core::$date, $_POST['id']));

		header("Location: /admin.php?module=calendar&view=submitted&message=approved");
	}

	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$db->sqlquery("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_POST['id']));
			$name = $db->fetch();

			$core->yes_no('Are you sure you want to delete ' . $name['name'] . ' from the release calendar?', "admin.php?module=calendar&id={$_POST['id']}", "Delete");
		}

		else if (isset($_POST['no']))
		{
			if (isset($_POST['submitted']) && $_POST['submitted'] == 1)
			{
					header("Location: /admin.php?module=calendar&view=submitted");
			}
			else
			{
				header("Location: /admin.php?module=calendar&view=manage");
			}
		}

		else if (isset($_POST['yes']))
		{
			$db->sqlquery("SELECT `name` FROM `calendar` WHERE `id` = ?", array($_GET['id']));
			$name = $db->fetch();

			$db->sqlquery("DELETE FROM `calendar` WHERE `id` = ?", array($_GET['id']));

			$db->sqlquery("DELETE FROM `admin_notifications` WHERE `calendar_id` = ?", array($_GET['id']));

			$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?, `calendar_id` = ?", array($_SESSION['username'] . ' removed ' . $name['name'] . ' from the release calendar.', core::$date, core::$date, $_GET['id']));

			if (isset($_POST['submitted']) && $_POST['submitted'] == 1)
			{
					header("Location: /admin.php?module=calendar&view=submitted&message=deleted");
			}
			else
			{
				header("Location: /admin.php?module=calendar&view=manage&message=deleted");
			}
		}
	}
}
