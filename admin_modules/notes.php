<?php
if (isset($_GET['message']))
{
	if ($_GET['message'] == 'updated')
	{
		$core->message('Notes updated!');
	}
}

$templating->merge('admin_modules/admin_module_notes');

$templating->block('notes', 'admin_modules/admin_module_notes');

$db->sqlquery("SELECT `text` FROM `admin_notes` WHERE `user_id` = ?", array($_SESSION['user_id']));
$notes = $db->fetch();

// if they are new, create the notes row
if ($db->num_rows() == 0)
{
	$db->sqlquery("INSERT INTO `admin_notes` SET `user_id` = ?", array($_SESSION['user_id']));

	$db->sqlquery("SELECT `text` FROM `admin_notes` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$notes = $db->fetch();
}

$templating->set('your_notes', $notes['text']);

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'edit')
	{
		$notes_text = trim($_POST['text']);
		$db->sqlquery("UPDATE `admin_notes` SET `text` = ? WHERE `user_id` = ?", array($notes_text, $_SESSION['user_id']));
		
		header('Location: /admin.php?module=notes&message=updated');
	}
}
