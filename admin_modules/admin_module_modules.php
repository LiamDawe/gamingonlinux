<?php
$templating->merge('admin_modules/admin_module_modules');

if (isset($_GET['page']) && !isset($_POST['action']))
{
	if ($_GET['page'] == 'main')
	{
		$templating->block('main');
		
		$db->sqlquery("SELECT `module_id`, `module_file_name`, `activated` FROM `modules` ORDER BY `module_file_name` ASC");
		while ($module = $db->fetch())
		{
			$templating->block('main_module');
			$templating->set('module_file_name', $module['module_file_name']);
			
			// check for disable/enable options
			$disable_check = '';
			$enable_check = '';
			if ($module['activated'] == 1)
			{
				$enable_check = 'disabled="disabled"';
			}
			
			else
			{
				$disable_check = 'disabled="disabled"';
			}
			$templating->set('enabled_check', $enable_check);
			$templating->set('disabled_check', $disable_check);
			$templating->set('type', 'main');
			$templating->set('id', $module['module_id']);
		}
	}
	
	else if ($_GET['page'] == 'usercp')
	{
		$templating->block('usercp');
		
		$db->sqlquery("SELECT `module_id`, `module_file_name`, `module_title`, `module_link`, `show_in_sidebar`, `activated` FROM `usercp_modules` ORDER BY `module_file_name` ASC");
		while ($module = $db->fetch())
		{
			$templating->block('usercp_module');
			$templating->set('module_file_name', $module['module_file_name']);
			
			// check for sidebar enable
			$sidebar = '';
			if ($module['show_in_sidebar'] == 1)
			{
				$sidebar = 'checked';
			}
			
			$templating->set('sidebar_checked', $sidebar);
			
			// check for disable/enable options
			$disable_check = '';
			$enable_check = '';
			if ($module['activated'] == 1)
			{
				$enable_check = 'disabled="disabled"';
			}
			
			else
			{
				$disable_check = 'disabled="disabled"';
			}
			
			$templating->set('enabled_check', $enable_check);
			$templating->set('disabled_check', $disable_check);
			$templating->set('title', $module['module_title']);
			$templating->set('link', $module['module_link']);
			$templating->set('type', 'usercp');
			$templating->set('id', $module['module_id']);
		}
	}
}

else if (isset($_POST['action']))
{
	$type = '';
	if ($_POST['type'] == 'usercp')
	{
		$type = 'usercp_';
	}
	
	if ($_POST['action'] == 'Add')
	{
		if (empty($_POST['file']))
		{
			$core->message("You need to enter a filename!", NULL, 1);
		}
		
		else
		{
			$active = 0;
			if (isset($_POST['active']))
			{
				$active = 1;
			}
			$db->sqlquery("INSERT INTO `modules` SET `module_file_name` = ?, `activated` = ?", array($_POST['file'], $active));
			$core->message("The module {$_POST['file']} has been added! <a href=\"admin.php?module=modules&page={$_POST['type']}\">Click here to return.</a>");
		}
	}
	
	else if ($_POST['action'] == 'Enable')
	{
		$id = $_POST['id'];
		 
		if (!is_numeric($id))
		{
			$core->message('The module id has to be a number!', NULL, 1);
		}
		
		else
		{
			$db->sqlquery("UPDATE `{$type}modules` SET `activated` = 1 WHERE `module_id` = ?", array($id));
			
			$core->message('That module is now active!');
		}
	}
	
	else if ($_POST['action'] == 'Disable')
	{
		$id = $_POST['id'];
		 
		if (!is_numeric($id))
		{
			$core->message('The module id has to be a number!', NULL, 1);
		}
		
		else
		{
			$db->sqlquery("UPDATE `{$type}modules` SET `activated` = 0 WHERE `module_id` = ", array($id));
			
			$core->message('That module is now deactivated! Be sure to delete/disable any block using it or you will encouter errors!');
		}
	}
	
	else if ($_POST['action'] == 'Delete')
	{ 
		if (!is_numeric($_POST['id']))
		{
			$core->message('The module id has to be a number!', NULL, 1);
		}
		
		else
		{
			$db->sqlquery("DELETE FROM `{$type}modules` WHERE `module_id` = ?", array($_POST['id']));
			
			$core->message('That module is now deleted! Be sure to delete/disable any block using it or you will encouter errors!');
		}
	}
	
	else if ($_POST['action'] == 'Edit')
	{
		if (!is_numeric($_POST['id']))
		{
			$core->message('The module id has to be a number!', NULL, 1);
		}
		
		else
		{
			// check for sidebar enable
			$sidebar = 0;
			if (isset($_POST['sidebar']))
			{
				$sidebar = 1;
			}
			
			$db->sqlquery("UPDATE `{$type}modules` SET `module_file_name` = ?, `module_title` = ?, `module_link` = ?, `show_in_sidebar` = ? WHERE `module_id` = ?", array($_POST['file'], $_POST['title'], $_POST['link'], $sidebar, $_POST['id']));
			
			$core->message("That module has been edited! <a href=\"admin.php?module=modules&page={$_POST['type']}\">Click here to return.</a>");
		}		
	}
}
