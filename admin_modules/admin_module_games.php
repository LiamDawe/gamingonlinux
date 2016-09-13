<?php
$templating->merge('admin_modules/admin_module_games');

if ($_SESSION['user_id'] != 1)
{
	$core->message('section not open yet');
	die();
}

if (isset($_GET['view']) && $_GET['view'] == 'edit')
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

			$templating->block('edit_top', 'admin_modules/admin_module_games');

			$templating->block('edit_item', 'admin_modules/admin_module_games');

			$templating->block('edit_bottom', 'admin_modules/admin_module_games');
		}
	}
}
