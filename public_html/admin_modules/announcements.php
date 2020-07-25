<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: announcements');
}

$templating->load('admin_modules/admin_module_announcements');

$group_types = ['' => '', 'in_groups' => 'Only those groups', 'not_in_groups' => 'Not in those groups'];

$editor = new editor($core, $templating, $bbcode);

if (isset($_GET['view']))
{
	if ($_GET['view'] == 'manage')
	{
		$templating->block('add', 'admin_modules/admin_module_announcements');
		$templating->block('row', 'admin_modules/admin_module_announcements');
		
		$editor->editor(['name' => 'text', 'editor_id' => 'announcement']);
		$templating->block('bottom_add', 'admin_modules/admin_module_announcements');
		$types_list = '';
		foreach ($group_types as $value => $text)
		{
			$types_list .= '<option value="'.$value.'">'.$text.'</option>';
		}
		$templating->set('types_list', $types_list);

		$templating->block('manage', 'admin_modules/admin_module_announcements');

		// get existing announcements
		$get_announcements = $dbl->run("SELECT `id`, `text`, `author_id`, `user_groups`, `type`, `modules`, `can_dismiss` FROM `announcements` ORDER BY `id` ASC")->fetch_all();
		if ($get_announcements)
		{
			foreach ($get_announcements as $announce)
			{
				$templating->block('row', 'admin_modules/admin_module_announcements');

				$editor->editor(['name' => 'text', 'content' => $announce['text'], 'editor_id' => 'announcement-' . $announce['id']]);
				
				$templating->block('bottom_edit', 'admin_modules/admin_module_announcements');
				$templating->set('id', $announce['id']);
				
				// get groups
				$group_ids_array = unserialize($announce['user_groups']);
				$groups_list = '';
				$get_groups = $dbl->run("SELECT `group_id`, `group_name` FROM `user_groups` ORDER BY `group_name` ASC")->fetch_all();
				foreach ($get_groups as $groups)
				{
					if (!empty($group_ids_array) && in_array($groups['group_id'], $group_ids_array))
					{
						$groups_list .= "<option value=\"{$groups['group_id']}\" selected>{$groups['group_name']}</option>";
					}
				}
				$templating->set('group_ids', $groups_list);
				
				$types_list = '';
				foreach ($group_types as $value => $text)
				{
					$selected = '';
					if ($announce['type'] == $value)
					{
						$selected = 'selected';
					}
					
					$types_list .= '<option value="'.$value.'" '.$selected.'>'.$text.'</option>';
				}
				$templating->set('types_list', $types_list);
				
				// get modules
				$module_ids_array = unserialize($announce['modules']);
				$modules_list = '';
				$get_modules = $dbl->run("SELECT `nice_title`, `module_id` FROM `modules` ORDER BY `nice_title` ASC")->fetch_all();
				foreach ($get_modules as $modules)
				{
					if (!empty($module_ids_array) && in_array($modules['module_id'], $module_ids_array))
					{
						$modules_list .= "<option value=\"{$modules['module_id']}\" selected>{$modules['nice_title']}</option>";
					}
				}
				$templating->set('module_ids', $modules_list);
				
				// can users dismiss it?
				$dismiss = '';
				if ($announce['can_dismiss'] == 1)
				{
					$dismiss = 'checked';
				}
				$templating->set('can_dismiss', $dismiss);
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
		
		$user_groups = NULL;
		if (!empty($_POST['group_ids']))
		{
			$user_groups = serialize($_POST['group_ids']);
		}
		
		$modules = NULL;
		if (!empty($_POST['module_ids']))
		{
			$modules = serialize($_POST['module_ids']);
		}
		
		$dismiss = 0;
		if (isset($_POST['dismiss']))
		{
			$dismiss = 1;
		}

		$dbl->run("INSERT INTO `announcements` SET `text` = ?, `author_id` = ?, `user_groups` = ?, `type` = ?, `modules` = ?, `can_dismiss` = ?", array($text, $_SESSION['user_id'], $user_groups, $_POST['type'], $modules, $dismiss));

		$core->delete_dbcache('index_announcements');

		// note who did it
		$core->new_admin_note(array('completed' => 1, 'content' => ' added a new website announcement bar.'));

		header("Location: /admin.php?module=announcements&view=manage&message=saved&extra=announcement");
		die();
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
		
		$user_groups = NULL;
		if (!empty($_POST['group_ids']))
		{
			$user_groups = serialize($_POST['group_ids']);
		}
		
		$modules = NULL;
		if (!empty($_POST['module_ids']))
		{
			$modules = serialize($_POST['module_ids']);
		}
		
		$dismiss = 0;
		if (isset($_POST['dismiss']))
		{
			$dismiss = 1;
		}

		$dbl->run("UPDATE `announcements` SET `text` = ?, `user_groups` = ?, `type` = ?, `modules` = ?, `can_dismiss` = ? WHERE `id` = ?", array($text, $user_groups, $_POST['type'], $modules, $dismiss, $_POST['id']));

		$core->delete_dbcache('index_announcements');

		// note who did it
		$core->new_admin_note(array('completed' => 1, 'content' => ' edited a website announcement bar.'));

		$_SESSION['message'] = 'edited';
		$_SESSION['message_extra'] = 'announcement';
		header("Location: /admin.php?module=announcements&view=manage");
		die();
	}

	if ($_POST['act'] == 'delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->confirmation(['title' => 'Are you sure you want to delete that announcement?', 'text' => 'This cannot be undone!', 'action_url' => '/admin.php?module=announcements&id='.$_POST['id'], 'act' => "delete"]);
		}
		else if (isset($_POST['no']))
		{
			header("Location: /admin.php?module=announcements&view=manage");
		}
		else if (isset($_POST['yes']))
		{
			$dbl->run("DELETE FROM `announcements` WHERE `id` = ?", array($_GET['id']));

			$core->delete_dbcache('index_announcements');

			// note who did it
			$core->new_admin_note(array('completed' => 1, 'content' => ' deleted a website announcement bar.'));

			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'announcement';
			header("Location: /admin.php?module=announcements&view=manage");
		}
	}
}
