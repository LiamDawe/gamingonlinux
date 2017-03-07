<?php
$templating->merge('admin_modules/admin_module_announcements');

if (isset($_GET['view']))
{
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
		else 
		{
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
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'announcement text';
			header("Location: /admin.php?module=announcements&view=manage");
			die();
		}

		$db->sqlquery("INSERT INTO `announcements` SET `text` = ?, `author_id` = ?", array($text, $_SESSION['user_id']));
		header("Location: /admin.php?module=announcements&view=manage&message=saved&extra=announcement");
	}
	if ($_POST['act'] == 'edit')
	{
		$text = trim($_POST['text']);
		$id = (int) $_POST['id'];
		
		if (empty($text))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'announcement text';
			header("Location: /admin.php?module=announcements&view=manage");
			die();
		}
		if (empty($id) || !is_numeric($id))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'announcement';
			header("Location: /admin.php?module=announcements&view=manage");
			die();
		}

		$db->sqlquery("UPDATE `announcements` SET `text` = ? WHERE `id` = ?", array($text, $_POST['id']));
		$_SESSION['message'] = 'edited';
		$_SESSION['message_extra'] = 'announcement';
		header("Location: /admin.php?module=announcements&view=manage");
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
			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'announcement';
			header("Location: /admin.php?module=announcements&view=manage");
		}
	}
}
