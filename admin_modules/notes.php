<?php
$templating->load('admin_modules/admin_module_notes');

$templating->block('notes', 'admin_modules/admin_module_notes');

$notes = $dbl->run("SELECT `text` FROM `admin_notes` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

// if they are new, create the notes row
if (!$notes)
{
	$dbl->run("INSERT INTO `admin_notes` SET `user_id` = ?, `text` = ''", array($_SESSION['user_id']));

	$notes = $dbl->run("SELECT `text` FROM `admin_notes` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
}

$templating->set('your_notes', $notes['text']);

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'edit')
	{
		$notes_text = trim($_POST['text']);
		$dbl->run("UPDATE `admin_notes` SET `text` = ? WHERE `user_id` = ?", array($notes_text, $_SESSION['user_id']));
		
		$_SESSION['message'] = 'edited';
		$_SESSION['message_extra'] = 'note';
		header('Location: /admin.php?module=notes');
	}
}
