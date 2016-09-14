<?php
$templating->merge('admin_modules/admin_module_games');

if ($_SESSION['user_id'] != 1)
{
	$core->message('section not open yet');
	die();
}

if (isset($_GET['view']) && $_GET['view'] == 'edit' && !isset($_POST['act']))
{
	if (!isset($_GET['id']) || !is_numeric($_GET['id']))
	{
		$core->message('Not ID set, you shouldn\'t be here!');
	}
	else
	{
		$db->sqlquery("SELECT * FROM `calendar` WHERE `id` = ?", array($_GET['id']));
		$count = $db->num_rows();

		if ($count == 0)
		{
			$core->message('That ID does not exist!');
		}
		else if ($count == 1)
		{
			$game = $db->fetch();

			if (isset($_GET['message']))
			{
				if ($_GET['message'] == 'edited')
				{
					$core->message('Game edit completed!');
				}
				if ($_GET['message'] == 'missing')
				{
					$core->message('Please fill a name, a release date and an official website link at a minimum!', null, 1);
				}
			}

			$templating->set_previous('meta_description', 'Editing: '.$game['name'], 1);
			$templating->set_previous('title', 'Editing: ' . $game['name'], 1);

			$templating->block('edit_top', 'admin_modules/admin_module_games');

			$templating->block('edit_item', 'admin_modules/admin_module_games');
			$templating->set('id', $game['id']);
			$templating->set('name', $game['name']);
			$templating->set('link', $game['link']);
			$templating->set('steam_link', $game['steam_link']);
			$templating->set('gog_link', $game['gog_link']);

			$date = new DateTime($game['date']);
			$templating->set('date', $date->format('d-m-Y'));

			$guess = '';
			if ($game['best_guess'] == 1)
			{
				$guess = 'checked';
			}
			$templating->set('guess_check', $guess);

			$text = $game['description'];

			$core->editor('text', $text);

			$templating->block('edit_bottom', 'admin_modules/admin_module_games');
			$templating->set('id', $game['id']);
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Edit')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=games&view=manage&error=missing_id");
			exit;
		}

		if (empty($_POST['name']) || empty($_POST['date']) || empty($_POST['link']))
		{
			header("Location: /admin.php?module=games&view=edit&error=missing&id=" . $_POST['id']);
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$name = trim($_POST['name']);
		$description = trim($_POST['text']);

		$db->sqlquery("UPDATE `calendar` SET `name` = ?, `description` = ?, `date` = ?, `link` = ?, `steam_link` = ?, `gog_link` = ?, `best_guess` = ?, `edit_date` = ? WHERE `id` = ?", array($name, $description, $date->format('Y-m-d'), $_POST['link'], $_POST['steam_link'], $_POST['gog_link'], $guess, date('Y-m-d'), $_POST['id']));

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 1, `action` = ?, `created` = ?, `completed_date` = ?", array($_SESSION['username'] . ' edited ' . $_POST['name'] . ' in the games database.', core::$date, core::$date));

		header("Location: /admin.php?module=games&view=edit&id=" . $_POST['id'] . '&message=edited');
	}
}
