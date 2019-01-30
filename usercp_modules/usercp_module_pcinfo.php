<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'PC Info' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/usercp_module_pcinfo');

// get the lists of everything we need, so we can validate input when saving as well as displaying for picking
$get_distros = $dbl->run("SELECT `name` FROM `distributions` ORDER BY `name` = 'Not Listed' DESC, `name` ASC")->fetch_all(PDO::FETCH_COLUMN);
$get_desktops = $dbl->run("SELECT `name` FROM `desktop_environments` ORDER BY `name` = 'Not Listed' DESC, `name` ASC")->fetch_all(PDO::FETCH_COLUMN);
$bits_allowed = array('32bit', '64bit');
$dual_boot_systems = array("Yes Windows", "Yes Mac", "Yes ChromeOS", "Yes Other", "No");
$steamplay_options = array("I will not use it", "Waiting on a specific game working", "In the last month", "In the last six months", "More than six months ago");
$wine_options = array("In the last month", "In the last three months", "In the last six months", "Over six months ago", "I never use it");
$cpu_vendors = array('Intel', 'AMD');
$gpu_vendors = array('Intel', 'AMD', 'Nvidia');
$gpu_driver_options = array('Open Source', 'Proprietary');
$ram_numbers = array();
for ($i = 1; $i <= 128; $i++)
{
	$ram_numbers[] = $i;
}
$monitor_numbers = array();
for ($i = 1; $i <= 12; $i++)
{
	$monitor_numbers[] = $i;
}
$resolution_options = array(
	"800x600", "1024x600", "1024x768",
	"1152x864", "1280x720", "1280x768",
	"1280x800", "1280x1024", "1360x768",
	"1366x768", "1440x900", "1400x1050",
	"1600x900", "1600x1200", "1680x1050",
	"1920x1080", "1920x1200", "2560x1080",
	"2560x1440", "2560x1600", "3200x1800",
	"3440x1440", "3840x1600", "3840x1080",
	"3840x1200", "3840x2160", "4096x2160",
	"5120x1440", "5120x2160", "5120x2880",
	"7680x4320");
$gaming_machine_types = array('Desktop', 'Laptop', 'Sofa/Console PC');
$gamepads = array("None", "Steam Controller", "Xbox 360", "Xbox One", "PS4", "PS3", "Logitech", "Other");

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

	// display distros
	$distro_list = '';
	foreach ($get_distros as $distros)
	{
		$selected = '';
		if ($usercpcp['distro'] == $distros)
		{
			$selected = 'selected';
		}
		$distro_list .= "<option value=\"{$distros}\" $selected>{$distros}</option>";
	}
	$templating->set('distro_list', $distro_list);

	// Desktop environment
	$desktop_list = '';
	foreach ($get_desktops as $desktops)
	{
		$selected = '';
		if ($additional['desktop_environment'] == $desktops)
		{
			$selected = 'selected';
		}
		$desktop_list .= "<option value=\"{$desktops}\" $selected>{$desktops}</option>";
	}
	$templating->set('desktop_list', $desktop_list);

	// distribution architecture 32/64bit
	$bits_options = '';
	foreach ($bits_allowed as $bitsy)
	{
		$selected = '';
		if ($additional['what_bits'] == $bitsy)
		{
			$selected = 'selected';
		}
		$bits_options .= '<option value="'.$bitsy.'" ' . $selected . '>'.$bitsy.'</option>';
	}
	$templating->set('what_bits_options', $bits_options);

	$dual_boot_options = '';
	foreach ($dual_boot_systems as $system)
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
	foreach ($steamplay_options as $option)
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
	foreach ($wine_options as $option)
	{
		$selected = '';
		if ($additional['wine'] == $option)
		{
			$selected = 'selected';
		}
		$wine_options_output .= '<option value="'.$option.'" '.$selected.'>'.$option.'</option>';
	}
	$templating->set('wine_options', $wine_options_output);

	// CPU vendor
	$cpu_options = '';
	foreach ($cpu_vendors as $vendor)
	{
		$selected = '';
		if ($additional['cpu_vendor'] == $vendor)
		{
			$selected = 'selected';
		}
		$cpu_options .= '<option value="'.$vendor.'" '.$selected.'>'.$vendor.'</option>';
	}
	$templating->set('cpu_options', $cpu_options);

	$templating->set('cpu_model', htmlspecialchars($additional['cpu_model']));

	$gpu_options = '';
	foreach ($gpu_vendors as $vendor)
	{
		$selected = '';
		if ($additional['gpu_vendor'] == $vendor)
		{
			$selected = 'selected';
		}
		$gpu_options .= '<option value="'.$vendor.'" '.$selected.'>'.$vendor.'</option>';
	}
	$templating->set('gpu_options', $gpu_options);

	// GPU MODEL 
	$gpu_model = '';
	if (is_numeric($additional['gpu_id']))
	{
		$gpu_model = "<option value=\"{$additional['gpu_id']}\" selected>{$additional['gpu_model']}</option>";
	}
	$templating->set('gpu_model', $gpu_model);

	// gpu driver type
	$gpu_driver = '';
	foreach ($gpu_driver_options as $type)
	{
		$selected = '';
		if ($additional['gpu_driver'] == $type)
		{
			$selected = 'selected';
		}
		$gpu_driver .= '<option value="'.$type.'" '.$selected.'>'.$type.'</option>';
	}
	$templating->set('gpu_driver', $gpu_driver);

	// RAM
	$ram_options = '';
	foreach ($ram_numbers as $i)
	{
		$ram_selected = '';
		if ($i == $additional['ram_count'])
		{
			$ram_selected = 'selected';
		}
		$ram_options .= '<option value="'.$i.'" '.$ram_selected.'>'.$i.'GB</a>';
	}
	$templating->set('ram_options', $ram_options);

	// Monitors
	$monitor_options = '';
	foreach ($monitor_numbers as $i)
	{
		$monitor_selected = '';
		if ($i == $additional['monitor_count'])
		{
			$monitor_selected = 'selected';
		}
		$monitor_options .= '<option value="'.$i.'" '.$monitor_selected.'>'.$i.'</a>';
	}
	$templating->set('monitor_options', $monitor_options);

	// Resolution
	$resolution_options_html = '';
	foreach ($resolution_options as $res)
	{
		$resolution_selected = '';
		if ($res == $additional['resolution'])
		{
			$resolution_selected = 'selected';
		}
		$resolution_options_html .= '<option value="'.$res.'" '.$resolution_selected.'>'.$res.'</a>';
	}
	$templating->set('resolution_options', $resolution_options_html);

	// Type of machine
	$machine_options = '';
	foreach ($gaming_machine_types as $type)
	{
		$selected = '';
		if ($type == $additional['gaming_machine_type'])
		{
			$selected = 'selected';
		}
		$machine_options .= '<option value="'.$type.'" '.$selected.'>'.$type.'</a>';
	}
	$templating->set('machine_options', $machine_options);

	$gamepad_options = '';
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

		// make sure they match what's actually allowed
		// need to make this prettier one day...
		if (!in_array($_POST['distribution'], $get_distros))
		{
			$_POST['distribution'] = '';
		}
		if (!in_array($_POST['pc_info']['desktop_environment'], $get_desktops))
		{
			$_POST['pc_info']['desktop_environment'] = '';
		}
		if (!in_array($_POST['pc_info']['what_bits'], $bits_allowed))
		{
			$_POST['pc_info']['what_bits'] = '';
		}
		if (!in_array($_POST['pc_info']['dual_boot'], $dual_boot_systems))
		{
			$_POST['pc_info']['dual_boot'] = '';
		}
		if (!in_array($_POST['pc_info']['steamplay'], $steamplay_options))
		{
			$_POST['pc_info']['steamplay'] = '';
		}
		if (!in_array($_POST['pc_info']['wine'], $wine_options))
		{
			$_POST['pc_info']['wine'] = '';
		}
		if (!in_array($_POST['pc_info']['cpu_vendor'], $cpu_vendors))
		{
			$_POST['pc_info']['cpu_vendor'] = '';
		}
		$cpu_model = strip_tags(trim($_POST['pc_info']['cpu_model']));
		if (!in_array($_POST['pc_info']['gpu_vendor'], $gpu_vendors))
		{
			$_POST['pc_info']['gpu_vendor'] = '';
		}
		if (isset($_POST['pc_info']['gpu_model']) && !is_numeric($_POST['pc_info']['gpu_model']))
		{
			$_POST['pc_info']['gpu_model'] = NULL;
		}
		if (!in_array($_POST['pc_info']['gpu_driver'], $gpu_driver_options))
		{
			$_POST['pc_info']['gpu_driver'] = '';
		}
		if (!in_array($_POST['pc_info']['ram_count'], $ram_numbers))
		{
			$_POST['pc_info']['ram_count'] = '';
		}
		if (!in_array($_POST['pc_info']['monitor_count'], $monitor_numbers))
		{
			$_POST['pc_info']['monitor_count'] = '';
		}
		if (!in_array($_POST['pc_info']['resolution'], $resolution_options))
		{
			$_POST['pc_info']['resolution'] = '';
		}
		if (!in_array($_POST['pc_info']['gaming_machine_type'], $gaming_machine_types))
		{
			$_POST['pc_info']['gaming_machine_type'] = '';
		}
		if (!in_array($_POST['pc_info']['gamepad'], $gamepads))
		{
			$_POST['pc_info']['gamepad'] = '';
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
