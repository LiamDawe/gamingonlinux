<?php
$templating->set_previous('title', 'PC Info' . $templating->get('title', 1)  , 1);
$templating->merge('usercp_modules/usercp_module_pcinfo');

if (isset($_GET['updated']))
{
	$core->message('You have updated your profile!');
}

if (!isset($_POST['act']))
{
	$db->sqlquery("SELECT  `pc_info_public`, `distro` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));

	$usercpcp = $db->fetch();

	$templating->block('pcdeets', 'usercp_modules/usercp_module_pcinfo');

	$public_info = '';
	if ($usercpcp['pc_info_public'] == 1)
	{
		$public_info = 'checked';
	}
	$templating->set('public_check', $public_info);

	// grab distros
	$distro_list = '';
	$db->sqlquery("SELECT `name` FROM `distributions` ORDER BY `name` = 'Not Listed' DESC, `name` ASC");
	while ($distros = $db->fetch())
	{
			$selected = '';
			if ($usercpcp['distro'] == $distros['name'])
			{
				$selected = 'selected';
			}
			$distro_list .= "<option value=\"{$distros['name']}\" $selected>{$distros['name']}</option>";
	}
	$templating->set('distro_list', $distro_list);

	$db->sqlquery("SELECT `desktop_environment`, `what_bits`, `dual_boot`, `cpu_vendor`, `cpu_model`, `gpu_vendor`, `gpu_model`, `gpu_driver`, `ram_count`, `monitor_count`, `gaming_machine_type`, `resolution` FROM `user_profile_info` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$additional = $db->fetch();

	// Desktop environment
	$desktop_list = '';
	$db->sqlquery("SELECT `name` FROM `desktop_environments` ORDER BY `name` = 'Not Listed' DESC, `name` ASC");
	while ($desktops = $db->fetch())
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

	$windows = '';
	$mac = '';
	$nope = '';
	if ($additional['dual_boot'] == 'Yes Windows')
	{
		$windows = 'selected';
	}
	if ($additional['dual_boot'] == 'Yes Mac')
	{
		$mac = 'selected';
	}
	if ($additional['dual_boot'] == 'No')
	{
		$nope = 'selected';
	}

	$dual_boot_options = '<option value="Yes Windows" '.$windows.'>Yes With Windows</option><option value="Yes Mac" '.$mac.'>Yes With Mac</option><option value="No" '.$nope.'>No</option>';
	$templating->set('dual_boot_options', $dual_boot_options);

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

	$templating->set('gpu_model', $additional['gpu_model']);

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
	$hybrid = '';
	if ($additional['gpu_driver'] == 'Hybrid Driver')
	{
		$hybrid = 'selected';
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
	for ($i = 1; $i <= 5; $i++)
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
		"3440x1440",
		"3840x2160");
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
}
else if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Update')
	{
		$pc_info_filled = 0;
		$public = 0;

		if (isset($_POST['public']))
		{
			$public = 1;
		}

		// check if the have set any of their pc info
		$pc_info_fields = array(
			$_POST['desktop'],
			$_POST['what_bits'],
			$_POST['dual_boot'],
			$_POST['cpu_vendor'],
			$_POST['cpu_model'],
			$_POST['gpu_vendor'],
			$_POST['gpu_model'],
			$_POST['gpu_driver'],
			$_POST['ram_count'],
			$_POST['monitor_count'],
			$_POST['resolution'],
			$_POST['gaming_machine_type']);
		foreach ($pc_info_fields as $field)
		{
			if (isset($field) && !empty($field))
			{
				$pc_info_filled = 1;
				break;
			}
		}

		// additional profile fields
		$sql_additional = "UPDATE `user_profile_info` SET
		`desktop_environment` = ?,
		`what_bits` = ?,
		`dual_boot` = ?,
		`cpu_vendor` = ?,
		`cpu_model` = ?,
		`gpu_vendor` = ?,
		`gpu_model` = ?,
		`gpu_driver` = ?,
		`ram_count` = ?,
		`monitor_count` = ?,
		`resolution` = ?,
		`gaming_machine_type` = ?,
		`date_updated` = ?
		WHERE
		`user_id` = ?";
		$db->sqlquery($sql_additional, array(
		$_POST['desktop'],
		$_POST['what_bits'],
		$_POST['dual_boot'],
		$_POST['cpu_vendor'],
		$_POST['cpu_model'],
		$_POST['gpu_vendor'],
		$_POST['gpu_model'],
		$_POST['gpu_driver'],
		$_POST['ram_count'],
		$_POST['monitor_count'],
		$_POST['resolution'],
		$_POST['gaming_machine_type'],
		gmdate("Y-n-d H:i:s"),
		$_SESSION['user_id']));

		$user_update_sql = "UPDATE `users` SET `distro` = ?, `pc_info_public` = ?, `pc_info_filled` = ? WHERE `user_id` = ?";
		$user_update_query = $db->sqlquery($user_update_sql, array($_POST['distribution'], $public, $pc_info_filled, $_SESSION['user_id']));

		header("Location: " . core::config('website_url') . "usercp.php?module=pcinfo&updated");
	}
}
