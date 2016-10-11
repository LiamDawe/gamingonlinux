<?php
$templating->merge('admin_modules/admin_module_announcements');

if (isset($_GET['view']))
{
	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'added')
		{
			$core->message("That announcement has now been added!");
		}
		if ($_GET['message'] == 'deleted')
		{
			$core->message("That announcement has now been deleted!");
		}
		if ($_GET['message'] == 'empty')
		{
			$core->message("You cannot leave it empty!", NULL, 1);
		}
		if ($_GET['message'] == 'id')
		{
			$core->message("The ID cannot be empty or not a number, this is likely a bug, tell Liam!", NULL, 1);
		}
	}
	if ($_GET['view'] == 'manage')
	{
		$templating->block('add', 'admin_modules/admin_module_announcements');
		$templating->block('row', 'admin_modules/admin_module_announcements');
		$core->editor('text', '', 1);
		$templating->block('bottom_add', 'admin_modules/admin_module_announcements');

		$templating->block('manage', 'admin_modules/admin_module_announcements');

		// get existing announcements
		$db->sqlquery("SELECT `id`, `text`, `author_id` FROM `announcements` ORDER BY `id` ASC");
		if ($db->num_rows() >= 1)
		{
			while ($announce = $db->fetch())
			{
				$templating->block('row', 'admin_modules/admin_module_announcements');
				$core->editor('text', $announce['text'], 1);
				$templating->block('bottom_edit', 'admin_modules/admin_module_announcements');
				$templating->set('id', $announce['id']);
			}
		}
		else {
			$core->message('No announcements found!');
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		$text = trim($_POST['text']);
		if (empty($text))
		{
			header("Location: /admin.php?module=announcements&view=manage&message=empty");
			die();
		}

		$db->sqlquery("INSERT INTO `announcements` SET `text` = ?, `author_id` = ?", array($text, $_SESSION['user_id']));
		header("Location: /admin.php?module=announcements&view=manage&message=added");
	}
	if ($_POST['act'] == 'edit')
	{
		$text = trim($_POST['text']);
		if (empty($text))
		{
			header("Location: /admin.php?module=announcements&view=manage&message=empty");
			die();
		}
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=announcements&view=manage&message=id");
			die();
		}

		$db->sqlquery("UPDATE `announcements` SET `text` = ? WHERE `id` = ?", array($text, $_POST['id']));
	}

	if ($_POST['act'] == 'delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that announcement?', '/admin.php?module=announcements&id='.$_POST['id'], "delete");
		}
		else if (isset($_POST['no']))
		{
			header("Location: /admin.php?module=announcements&view=manage");
		}
		else if (isset($_POST['yes']))
		{
			$db->sqlquery("DELETE FROM `announcements` WHERE `id` = ?", array($_GET['id']));
			header("Location: /admin.php?module=announcements&view=manage&message=deleted");
		}
	}
}
