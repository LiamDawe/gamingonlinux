<?php
$templating->set_previous('title', 'PC Info' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/usercp_module_pcinfo');

if (!isset($_POST['act']))
{
	$usercpcp = $dbl->run("SELECT `pc_info_public`, `distro` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();#

	$additional_sql = "SELECT p.`date_updated`, p.`desktop_environment`, p.`what_bits`, p.`dual_boot`, p.`steamplay`, p.`wine`, p.`cpu_vendor`, p.`cpu_model`, p.`gpu_vendor`, g.`id` AS `gpu_id`, g.`name` AS `gpu_model`, p.`gpu_driver`, p.`ram_count`, p.`monitor_count`, p.`gaming_machine_type`, p.`resolution`, p.`gamepad`, p.`include_in_survey` FROM `user_profile_info` p LEFT JOIN `gpu_models` g ON g.id = p.gpu_model WHERE p.`user_id` = ?";
	$additional = $dbl->run($additional_sql, array($_SESSION['user_id']))->fetch();
	
	// if for some reason they don't have a profile info row, give them one
	// they're purged for inactive users
	if (!$additional)
	{
		$dbl->run("INSERT INTO `user_profile_info` SET `user_id` = ?", array($_SESSION['user_id']));
	}

	$templating->block('pcdeets', 'usercp_modules/usercp_module_pcinfo');
	$templating->set('user_id', $_SESSION['user_id']);
	$templating->set('url', $core->config('website_url'));
	$templating->set('this_template', $core->config('website_url') . 'templates/' . $core->config('template'));

	if (!isset($additional['date_updated']))
	{
		$date_updated = 'Never!';
	}
	else
	{
		$date_updated = date('d M, Y', strtotime($additional['date_updated']));
	}
	$templating->set('date_updated', $date_updated);

	$public_info = '';
	if ($usercpcp['pc_info_public'] == 1)
	{
		$public_info = 'checked';
	}
	$templating->set('public_check', $public_info);

	$include_in_survey = '';
	if ($additional['include_in_survey'] == 1)
	{
		$include_in_survey = 'checked';
	}
	$templating->set('include_in_survey_check', $include_in_survey);

	// grab distros
	$distro_list = '';
	$get_distros = $dbl->run("SELECT `name` FROM `distributions` ORDER BY `name` = 'Not Listed' DESC, `name` ASC")->fetch_all();
	foreach ($get_distros as $distros)
	{
		$selected = '';
		if ($usercpcp['distro'] == $distros['name'])
		{
			$selected = 'selected';
		}
		$distro_list .= "<option value=\"{$distros['name']}\" $selected>{$distros['name']}</option>";
	}
	$templating->set('distro_list', $distro_list);

	// Desktop environment
	$desktop_list = '';
	$get_desktops = $dbl->run("SELECT `name` FROM `desktop_environments` ORDER BY `name` = 'Not Listed' DESC, `name` ASC")->fetch_all();
	foreach ($get_desktops as $desktops)
	{
		$selected = '';
		if ($additional['desktop_environment'] == $desktops['name'])
		{
			$selected = 'selected';
		}
		$desktop_list .= "<option value=\"{$desktops['name']}\" $selected>{$desktops['name']}</option>";
	}
	$templating->set('desktop_list', $desktop_list);

	$arc_32 = '';
	if ($additional['what_bits'] == '32bit')
	{
		$arc_32 = 'selected';
	}
	$arc_64 = '';
	if ($additional['what_bits'] == '64bit')
	{
		$arc_64 = 'selected';
	}
	$what_bits_options = '<option value="32bit" ' . $arc_32 . '>32bit</option><option value="64bit" '.$arc_64.'>64bit</option>';
	$templating->set('what_bits_options', $what_bits_options);

	$dual_boot_options = '';
	$systems = array("Yes Windows", "Yes Mac", "Yes ChromeOS", "Yes Other", "No");
	foreach ($systems as $system)
	{
		$selected = '';
		if ($additional['dual_boot'] == $system)
		{
			$selected = 'selected';
		}
		$dual_boot_options .= '<option value="'.$system.'" '.$selected.'>'.$system.'</option>';
	}
	$templating->set('dual_boot_options', $dual_boot_options);

	// Steam Play
	$steamplay_options_output = '';
	$options = array("I will not use it", "Waiting on a specific game working", "In the last month", "In the last six months", "More than six months ago");
	foreach ($options as $option)
	{
		$selected = '';
		if ($additional['steamplay'] == $option)
		{
			$selected = 'selected';
		}
		$steamplay_options_output .= '<option value="'.$option.'" '.$selected.'>'.$option.'</option>';
	}
	$templating->set('steamplay_options', $steamplay_options_output);

	// WINE USE
	$wine_options_output = '';
	$options = array("In the last month", "In the last three months", "In the last six months", "Over six months ago", "I never use it");
	foreach ($options as $option)
	{
		$selected = '';
		if ($additional['wine'] == $option)
		{
			$selected = 'selected';
		}
		$wine_options_output .= '<option value="'.$option.'" '.$selected.'>'.$option.'</option>';
	}
	$templating->set('wine_options', $wine_options_output);

	$intel = '';
	if ($additional['cpu_vendor'] == 'Intel')
	{
		$intel = 'selected';
	}
	$amd = '';
	if ($additional['cpu_vendor'] == 'AMD')
	{
		$amd = 'selected';
	}
	$cpu_options = '<option value="AMD" '.$amd.'>AMD</option><option value="Intel" '.$intel.'>Intel</option>';
	$templating->set('cpu_options', $cpu_options);

	$templating->set('cpu_model', $additional['cpu_model']);

	$intel_gpu = '';
	if ($additional['gpu_vendor'] == 'Intel')
	{
		$intel_gpu = 'selected';
	}
	$amd_gpu = '';
	if ($additional['gpu_vendor'] == 'AMD')
	{
		$amd_gpu = 'selected';
	}
	$nvidia_gpu = '';
	if ($additional['gpu_vendor'] == 'Nvidia')
	{
		$nvidia_gpu = 'selected';
	}
	$gpu_options = '<option value="AMD" '.$amd_gpu.'>AMD</option><option value="Intel" '.$intel_gpu.'>Intel</option><option value="Nvidia" '.$nvidia_gpu.'>Nvidia</option>';
	$templating->set('gpu_options', $gpu_options);

	// GPU MODEL 
	$gpu_model = '';
	if (is_numeric($additional['gpu_id']))
	{
		$gpu_model = "<option value=\"{$additional['gpu_id']}\" selected>{$additional['gpu_model']}</option>";
	}
	$templating->set('gpu_model', $gpu_model);

	$open = '';
	if ($additional['gpu_driver'] == 'Open Source')
	{
		$open = 'selected';
	}
	$prop = '';
	if ($additional['gpu_driver'] == 'Proprietary')
	{
		$prop = 'selected';
	}
	$gpu_driver = '<option value="Open Source" '.$open.'>Open Source</option><option value="Proprietary" '.$prop.'>Proprietary</option>';
	$templating->set('gpu_driver', $gpu_driver);

	// RAM
	$ram_options = '';
	$ram_selected = '';
	for ($i = 1; $i <= 64; $i++)
	{
		if ($i == $additional['ram_count'])
		{
			$ram_selected = 'selected';
		}
		$ram_options .= '<option value="'.$i.'" '.$ram_selected.'>'.$i.'GB</a>';
		$ram_selected = '';
	}
	$templating->set('ram_options', $ram_options);

	// Monitors
	$monitor_options = '';
	$monitor_selected = '';
	for ($i = 1; $i <= 10; $i++)
	{
		if ($i == $additional['monitor_count'])
		{
			$monitor_selected = 'selected';
		}
		$monitor_options .= '<option value="'.$i.'" '.$monitor_selected.'>'.$i.'</a>';
		$monitor_selected = '';
	}
	$templating->set('monitor_options', $monitor_options);

	// Resolution
	$resolution_options_html = '';
	$resolution_selected = '';
	$resolution_options = array(
		"800x600",
		"1024x600",
		"1024x768",
		"1152x864",
		"1280x720",
		"1280x768",
		"1280x800",
		"1280x1024",
		"1360x768",
		"1366x768",
		"1440x900",
		"1400x1050",
		"1600x900",
		"1600x1200",
		"1680x1050",
		"1920x1080",
		"1920x1200",
		"2560x1080",
		"2560x1440",
		"2560x1600",
		"3200x1800",
		"3440x1440",
		"3840x1600",
		"3840x1080",
		"3840x1200",
		"3840x2160",
		"4096x2160",
		"5120x1440",
		"5120x2160",
		"5120x2880",
		"7680x4320");
	foreach ($resolution_options as $res)
	{
		if ($res == $additional['resolution'])
		{
			$resolution_selected = 'selected';
		}
		$resolution_options_html .= '<option value="'.$res.'" '.$resolution_selected.'>'.$res.'</a>';
		$resolution_selected = '';
	}
	$templating->set('resolution_options', $resolution_options_html);

	// Type of machine
	$desktop = '';
	if ($additional['gaming_machine_type'] == 'Desktop')
	{
		$desktop = 'selected';
	}

	$laptop = '';
	if ($additional['gaming_machine_type'] == 'Laptop')
	{
		$laptop = 'selected';
	}

	$sofa = '';
	if ($additional['gaming_machine_type'] == 'Sofa/Console PC')
	{
		$sofa = 'selected';
	}

	$machine_options = '<option value="Desktop" '.$desktop.'>Desktop</option><option value="Laptop" '.$laptop.'>Laptop</option><option value="Sofa/Console PC" '.$sofa.'>Sofa/Console PC</option>';
	$templating->set('machine_options', $machine_options);

	$gamepad_options = '';
	$gamepads = array("None", "Steam Controller", "Xbox 360", "Xbox One", "PS4", "PS3", "Logitech", "Other");
	foreach ($gamepads as $gamepad)
	{
		$selected = '';
		if ($additional['gamepad'] == $gamepad)
		{
			$selected = 'selected';
		}
		$gamepad_options .= '<option value="'.$gamepad.'" '.$selected.'>'.$gamepad.'</option>';
	}
	$templating->set('gamepad_options', $gamepad_options);
}
else if (isset($_POST['act']))
{
	if ($_POST['act'] == 'wipe')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->confirmation(['title' => 'Are you sure you wish to wipe your PC information?', 'text' => 'You can update it again any time.', 'act' => 'wipe', 'action_url' => '/usercp.php?module=pcinfo']);
		}
		else if (isset($_POST['no']))
		{
			header("Location: /usercp.php?module=pcinfo");
		}
		else if (isset($_POST['yes']))
		{
			$empty_sql = [];
			$fields = ['date_updated', 'desktop_environment', 'what_bits', 'dual_boot', 'wine', 'ram_count', 'cpu_vendor', 'cpu_model', 'gpu_vendor', 'gpu_model', 'gpu_driver', 'monitor_count', 'resolution', 'gaming_machine_type', 'gamepad'];
			foreach ($fields as $field)
			{
				$empty_sql[] = ' `'.$field.'` = NULL ';
			}
			$dbl->run('UPDATE `user_profile_info` SET '.implode(', ', $empty_sql).' WHERE `user_id` = ?', [$_SESSION['user_id']]);
			$dbl->run('UPDATE `users` SET `distro` = NULL WHERE `user_id` = ?', [$_SESSION['user_id']]);

			$_SESSION['message'] = 'pc_info_wiped';
			header("Location: /usercp.php?module=pcinfo");
		}
	}

	if ($_POST['act'] == 'Update')
	{
		$pc_info_filled = 0;
		$public = 0;
		$include_in_survey = 0;

		if (isset($_POST['public']))
		{
			$public = 1;
		}

		if (isset($_POST['include_in_survey']))
		{
			$include_in_survey = 1;
		}

		// check if the have set any of their pc info
		foreach ($_POST['pc_info'] as $field)
		{
			if (isset($field) && !empty($field) && $field != 'Not Listed')
			{
				$pc_info_filled = 1;
				break;
			}
		}

		// they have to be a number, no matter what
		$ram_count = NULL;
		if (isset($_POST['pc_info']['ram_count']) && is_numeric($_POST['pc_info']['ram_count']))
		{
			$ram_count = $_POST['pc_info']['ram_count'];
		}

		$monitor_count = NULL;
		if (isset($_POST['pc_info']['monitor_count']) && is_numeric($_POST['pc_info']['monitor_count']))
		{
			$monitor_count = $_POST['pc_info']['monitor_count'];
		}

		// build the query of fields to update
		$update_sql = "UPDATE `user_profile_info` SET ";
		$fields_sql = [];
		$values_sql = [];
		foreach ($_POST['pc_info'] as $key => $value)
		{
			$fields_sql[] = ' `' . $key . '` = ?, ';
			if (!empty($value))
			{
				$values_sql[] = $value;
			}
			else
			{
				$values_sql[] = NULL;
			}
		}

		$update_sql = $update_sql . implode(' ', $fields_sql) . ' `date_updated` = ? WHERE `user_id` = ?';

		$dbl->run($update_sql, array_merge($values_sql, [gmdate("Y-n-d H:i:s")], [$_SESSION['user_id']]));

		$user_update_sql = "UPDATE `users` SET `distro` = ?, `pc_info_public` = ?, `pc_info_filled` = ? WHERE `user_id` = ?";
		$dbl->run($user_update_sql, array($_POST['distribution'], $public, $pc_info_filled, $_SESSION['user_id']));

		$dbl->run("UPDATE `user_profile_info` SET `include_in_survey` = ? WHERE `user_id` = ?", array($include_in_survey, $_SESSION['user_id']));

		$_SESSION['message'] = 'pc_info_updated';
		header("Location: " . $core->config('website_url') . "usercp.php?module=pcinfo");
	}
}
