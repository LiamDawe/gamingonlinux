<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user_id = strip_tags($_GET['user_id']);

if (!is_numeric($user_id))
{
	die('The user ID has to be a number!');
}

if(isset($user_id))
{
	$grab_fields = $dbl->run("SELECT `username`, `pc_info_public`, `distro` FROM `users` WHERE `user_id` = ?", array($user_id))->fetch();
	if (!$grab_fields)
	{
		$core->message('That person does not exist here!');
	}
	else
	{
		if ($grab_fields['pc_info_public'] == 1)
		{
			$templating->load('pc_info_overlay');
			$templating->block('main');
			$templating->set('username', $grab_fields['username']);
			$templating->set('profile_link', '/profiles/' . $user_id);

			$fields_output = '';
			$pc_info = $user->display_pc_info($user_id, $grab_fields['distro']);
			if ($pc_info['counter'] > 0)
			{
				foreach ($pc_info as $k => $info)
				{
					if ($k != 'counter')
					{
						$fields_output .= '<li>' . $info . '</li>';
					}
				}
			}
			else
			{
				$fields_output = '<li><em>This user has not filled out their PC info!</em></li>';
			}
			$templating->set('fields', $fields_output);
			$templating->set('stats_link', "/users/statistics");
			$templating->set('profile_link', '/profiles/' . $user_id);

			$edit_link = '';
			if (isset($user_id))
			{
				if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id)
				{
					$edit_link = ' | <a href="/usercp.php?module=pcinfo">Edit your PC info</a>';
				}
			}
			$templating->set('edit_link', $edit_link);

			echo $templating->output();
		}
	}
}
