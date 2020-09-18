<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: admin modules config.');
}

$templating->load('admin_modules/admin_module_modules');

if (isset($_GET['page']) && !isset($_POST['action']))
{
	if ($_GET['page'] == 'main')
	{
		$templating->block('main');
		
		$mod_res = $dbl->run("SELECT `module_id`, `module_file_name`, `activated` FROM `modules` ORDER BY `module_file_name` ASC")->fetch_all();
		foreach ($mod_res as $module)
		{
			$templating->block('main_module');
			$templating->set('module_file_name', $module['module_file_name']);
			
			// check for disable/enable options
			$button_action = '';
			$button_text = '';
			if ($module['activated'] == 1)
			{
				$button_action = 'Disable';
				$button_text = 'Disable';
			}
			
			else
			{
				$button_action = 'Enable';
				$button_text = 'Enable';
			}
			$templating->set('button_action', $button_action);
			$templating->set('button_text', $button_text);
			$templating->set('type', 'main');
			$templating->set('id', $module['module_id']);
		}
	}
	
	else if ($_GET['page'] == 'usercp')
	{
		$templating->block('usercp');
		
		$mod_res = $dbl->run("SELECT `module_id`, `module_file_name`, `module_title`, `module_link`, `show_in_sidebar`, `activated` FROM `usercp_modules` ORDER BY `module_file_name` ASC")->fetch_all();
		foreach ($mod_res as $module)
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
			$button_action = '';
			$button_text = '';
			if ($module['activated'] == 1)
			{
				$button_action = 'Disable';
				$button_text = 'Disable';
			}
			
			else
			{
				$button_action = 'Enable';
				$button_text = 'Enable';
			}
			$templating->set('button_action', $button_action);
			$templating->set('button_text', $button_text);
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
			$core->message("You need to enter a filename!", 1);
		}
		
		else
		{
			$active = 0;
			if (isset($_POST['active']))
			{
				$active = 1;
			}
			$dbl->run("INSERT INTO `modules` SET `module_file_name` = ?, `activated` = ?", array($_POST['file'], $active));

			$fetch_modules = $dbl->run('SELECT `module_id`, `module_file_name`, `nice_title`, `nice_link`, `sections_link` FROM `modules` WHERE `activated` = 1 ORDER BY `nice_title` ASC')->fetch_all();
			$core->set_dbcache('active_main_modules', serialize($fetch_modules)); // no expiry as shown blocks hardly ever changes

			$core->new_admin_note(array('completed' => 1, 'content' => ' added a new website module: '.$_POST['file'].'.'));

			$core->message("The module {$_POST['file']} has been added! <a href=\"admin.php?module=modules&page={$_POST['type']}\">Click here to return.</a>");
		}
	}
	
	else if ($_POST['action'] == 'Enable')
	{
		$id = $_POST['id'];
		 
		if (!is_numeric($id))
		{
			$core->message('The module id has to be a number!', 1);
		}
		
		else
		{
			$module_name = $dbl->run("SELECT `module_file_name` FROM `{$type}modules` WHERE `module_id` = ?", array($id))->fetchOne();

			$dbl->run("UPDATE `{$type}modules` SET `activated` = 1 WHERE `module_id` = ?", array($id));

			if ($type == 'main' || empty($type))
			{
				$fetch_modules = $dbl->run('SELECT `module_id`, `module_file_name`, `nice_title`, `nice_link`, `sections_link` FROM `modules` WHERE `activated` = 1 ORDER BY `nice_title` ASC')->fetch_all();
				$core->set_dbcache('active_main_modules', serialize($fetch_modules)); // no expiry as shown blocks hardly ever changes
			}

			$core->new_admin_note(array('completed' => 1, 'content' => ' enabled a website module: '.$module_name.'.'));
			
			$core->message('That module is now active!');
		}
	}
	
	else if ($_POST['action'] == 'Disable')
	{
		$id = $_POST['id'];
		 
		if (!is_numeric($id))
		{
			$core->message('The module id has to be a number!', 1);
		}
		
		else
		{
			$module_name = $dbl->run("SELECT `module_file_name` FROM `{$type}modules` WHERE `module_id` = ?", array($id))->fetchOne();

			$dbl->run("UPDATE `{$type}modules` SET `activated` = 0 WHERE `module_id` = ?", array($id));

			if ($type == 'main' || empty($type))
			{
				$fetch_modules = $dbl->run('SELECT `module_id`, `module_file_name`, `nice_title`, `nice_link`, `sections_link` FROM `modules` WHERE `activated` = 1 ORDER BY `nice_title` ASC')->fetch_all();
				$core->set_dbcache('active_main_modules', serialize($fetch_modules)); // no expiry as shown blocks hardly ever changes
			}

			$core->new_admin_note(array('completed' => 1, 'content' => ' disabled a website module: '.$module_name.'.'));
			
			$core->message('That module is now deactivated! Be sure to delete/disable any block using it or you will encouter errors!');
		}
	}
	
	else if ($_POST['action'] == 'Delete')
	{ 
		if (!is_numeric($_POST['id']))
		{
			$core->message('The module id has to be a number!', 1);
		}
		
		else
		{
			$module_name = $dbl->run("SELECT `module_file_name` FROM `{$type}modules` WHERE `module_id` = ?", array($_POST['id']))->fetchOne();

			$dbl->run("DELETE FROM `{$type}modules` WHERE `module_id` = ?", array($_POST['id']));

			if ($type == 'main' || empty($type))
			{
				$fetch_modules = $dbl->run('SELECT `module_id`, `module_file_name`, `nice_title`, `nice_link`, `sections_link` FROM `modules` WHERE `activated` = 1 ORDER BY `nice_title` ASC')->fetch_all();
				$core->set_dbcache('active_main_modules', serialize($fetch_modules)); // no expiry as shown blocks hardly ever changes
			}

			$core->new_admin_note(array('completed' => 1, 'content' => ' deleted a website module: '.$module_name.'.'));
			
			$core->message('That module is now deleted! Be sure to delete/disable any block using it or you will encouter errors!');
		}
	}
	
	else if ($_POST['action'] == 'Edit')
	{
		if (!is_numeric($_POST['id']))
		{
			$core->message('The module id has to be a number!', 1);
		}
		
		else
		{
			// check for sidebar enable
			$sidebar = 0;
			if (isset($_POST['sidebar']))
			{
				$sidebar = 1;
			}

			$module_name = $dbl->run("SELECT `module_file_name` FROM `{$type}modules` WHERE `module_id` = ?", array($_POST['id']))->fetchOne();
			
			$dbl->run("UPDATE `{$type}modules` SET `module_file_name` = ?, `module_title` = ?, `module_link` = ?, `show_in_sidebar` = ? WHERE `module_id` = ?", array($_POST['file'], $_POST['title'], $_POST['link'], $sidebar, $_POST['id']));

			if ($type == 'main' || empty($type))
			{
				$fetch_modules = $dbl->run('SELECT `module_id`, `module_file_name`, `nice_title`, `nice_link`, `sections_link` FROM `modules` WHERE `activated` = 1 ORDER BY `nice_title` ASC')->fetch_all();
				$core->set_dbcache('active_main_modules', serialize($fetch_modules)); // no expiry as shown blocks hardly ever changes
			}

			$core->new_admin_note(array('completed' => 1, 'content' => ' edited a website module: '.$module_name.'.'));
			
			$core->message("That module has been edited! <a href=\"admin.php?module=modules&page={$_POST['type']}\">Click here to return.</a>");
		}		
	}
}
