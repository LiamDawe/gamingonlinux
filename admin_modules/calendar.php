<?php
$templating->merge('admin_modules/admin_module_calendar');

$years_array = range(2014, 2020);

if (isset($_GET['error']))
{
	if ($_GET['error'] == 'missing')
	{
		$core->message("You have to put at least a name and date in!", NULL, 1);
	}
	if ($_GET['error'] == 'missing_id')
	{
		$core->message("The game id was missing, this is likely a code error or hacking attempt, bug Liam!", NULL, 1);
	}
}

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'edited')
	{
		$core->message("You have edited that item!");
	}
	if ($_GET['message'] == 'deleted')
	{
		$core->message("You have deleted that item!");
	}
	if ($_GET['message'] == 'approved')
	{
		$core->message("You have approved the item!");
	}
}

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'submitted')
	{
		$templating->block('submit_main', 'admin_modules/admin_module_calendar');

		$db->sqlquery("SELECT `id`, `date`, `name`, `link`, `best_guess` FROM `calendar` WHERE `approved` = 0 ORDER BY `date` ASC");
		while ($listing = $db->fetch()) // loop through the items
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
			$templating->set('id', $listing['id']);

			$date = new DateTime($listing['date']);
			$templating->set('date', $date->format('d-m-Y'));
		}
		$templating->block('submit_bottom', 'admin_modules/admin_module_calendar');
	}
}

if (isset($_POST['act']))
{
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

		$db->sqlquery("UPDATE `calendar` SET `name` = ?, `date` = ?, `link` = ?, `best_guess` = ?, `approved` = 1, `edit_date` = ? WHERE `id` = ?", array($_POST['name'], $date->format('Y-m-d'), $_POST['link'], $guess, date("Y-m-d"), $_POST['id']));

		$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'calendar_submission' AND `data` = ?", array($_POST['id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `created_date` = ?, `completed_date` = ?, `data` = ?", array($_SESSION['user_id'], 'approved_calendar', core::$date, core::$date, $_POST['id']));

		header("Location: /admin.php?module=calendar&view=submitted&message=approved");
	}
}
