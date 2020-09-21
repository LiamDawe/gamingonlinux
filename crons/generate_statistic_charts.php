<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/bootstrap.php";

// get the last grouping_id, this is how we group together each new generation for easy edits and deletions
$get_grouping_id = $dbl->run("SELECT `grouping_id` FROM `user_stats_charts` ORDER BY `id` DESC LIMIT 1")->fetch();
if (!$get_grouping_id)
{
	$grouping_id = 1;
}
else
{
    $grouping_id = $get_grouping_id['grouping_id'] + 1;
}

// this is for the combined linux distribution graph (the one that should be the most accurate)
$labels = array();
$data = array();

$users = $dbl->run("SELECT u.`distro`, count(*) as 'total', d.`arch-based`, d.`ubuntu-based` FROM `users` u INNER JOIN `user_profile_info` p ON u.user_id = p.user_id INNER JOIN `distributions` d ON u.distro = d.name WHERE u.`distro` != '' AND u.`distro` != 'Not Listed' AND u.`distro` IS NOT NULL AND p.`include_in_survey` = 1 GROUP BY u.`distro`, d.`arch-based`, d.`ubuntu-based` ORDER BY `total` DESC")->fetch_all();

$dbl->run("INSERT INTO `user_stats_charts` SET `h_label` = ?, `name` = ?, `grouping_id` = $grouping_id, `bundle_outside_top10` = 1", array('Percentage of users', 'Linux Distributions (Combined)'));
$new_chart_id = $dbl->new_id();

$arch_total = 0;
$ubuntu_total = 0;

foreach ($users as $user)
{
	if ($user['arch-based'] == 1)
	{
		$labels['Arch-based'] = $labels['Arch-based'] + $user['total'];
	}
	else if ($user['ubuntu-based'] == 1)
	{
		$labels['Ubuntu-based'] = $labels['Ubuntu-based'] + $user['total'];
	}
	else
	{
		$labels[$user['distro']] = $labels[$user['distro']] + $user['total'];
	}
}

$total_dat = 0;
foreach ($labels as $key => $label)
{
	$dbl->run("INSERT INTO `user_stats_charts_labels` SET `chart_id` = ?, `name` = ?, `grouping_id` = $grouping_id", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();

	echo 'Label ' . $key . ' added!<br />';

	$dbl->run("INSERT INTO `user_stats_charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `grouping_id` = $grouping_id", array($new_chart_id, $new_label_id, $label));

	$total_dat = $total_dat + $label;
	echo "Data $label added!<br />";
}

$dbl->run("UPDATE `user_stats_charts` SET `total_answers` = $total_dat WHERE `id` = $new_chart_id");

unset($data);
unset($labels);
unset($label);
unset($users);
unset($user);
unset($dat);

// this is for all the others that can be generated automatically
$charts = array (
array ("name" => "Linux Distributions (Split)", "db_field" => "u.distro", "table" => 'users u INNER JOIN user_profile_info p ON u.user_id = p.user_id', 'bundle_outside_top10' => 1),
array ("name" => "Desktop Environment", "db_field" => "p.desktop_environment", 'bundle_outside_top10' => 1),
array ("name" => "Dual Booting", "db_field" => "p.dual_boot"),
array ("name" => "CPU Vendor", "db_field" => "p.cpu_vendor"),
array ("name" => "GPU Vendor", "db_field" => "p.gpu_vendor"),
array ("name" => "GPU Model", "db_field" => "g.name", "table" => "user_profile_info p INNER JOIN `gpu_models` g ON p.gpu_model = g.id"),
array ("name" => "GPU Driver", "db_field" => "p.gpu_driver"),
array ("name" => "GPU Driver (Nvidia)", "db_field" => "p.gpu_driver", "gpu_vendor" => "Nvidia"),
array ("name" => "GPU Driver (AMD)", "db_field" => "p.gpu_driver", "gpu_vendor" => "AMD"),
array ("name" => "RAM", "db_field" => "p.ram_count", 'bundle_outside_top10' => 1),
array ("name" => "Number of monitors", "db_field" => "p.monitor_count"),
array ("name" => "Resolution", "db_field" => "p.resolution", 'bundle_outside_top10' => 1),
array ("name" => "Main Gaming Machine", "db_field" => "p.gaming_machine_type"),
array ("name" => "Main Gamepad", "db_field" => "p.gamepad", 'bundle_outside_top10' => 1),
array ("name" => "PC VR Headset", "db_field" => "p.vrheadset", 'bundle_outside_top10' => 1),
array ("name" => "Session Type", "db_field" => "p.session_type")
);

foreach ($charts as $chart)
{
	echo 'Generating ' . $chart['name'] . PHP_EOL;
	
	$table = 'user_profile_info p';
	if (isset($chart['table']))
	{
		$table = $chart['table'];
	}

	$sql_vendor = '';
	if (isset($chart['gpu_vendor']))
	{
		$sql_vendor = "p.`gpu_vendor` = '{$chart['gpu_vendor']}' AND";
	}

	$users = $dbl->run("SELECT {$chart['db_field']}, count(*) as 'total' FROM $table WHERE $sql_vendor {$chart['db_field']} != '' AND {$chart['db_field']} != 'Not Listed' AND {$chart['db_field']} IS NOT NULL AND p.`include_in_survey` = 1 GROUP BY {$chart['db_field']} ORDER BY `total` DESC")->fetch_all();
	
	$labels = array();
	$data = array();

	$bundle_outside_top10 = 0;
	if (isset($chart['bundle_outside_top10']) && $chart['bundle_outside_top10'] = 1)
	{
		$bundle_outside_top10 = 1;
	}

	$dbl->run("INSERT INTO `user_stats_charts` SET `h_label` = ?, `name` = ?, `grouping_id` = $grouping_id, `bundle_outside_top10` = ?", array('Percentage of users', $chart['name'], $bundle_outside_top10));
	$new_chart_id = $dbl->new_id();
	
	foreach ($users as $user)
	{
		$proper_field_name = ltrim(strstr($chart['db_field'], '.'), '.'); // remove the p. or u.
		echo $proper_field_name;
		$labels[] = $user[$proper_field_name];
		$data[] = $user['total'];
	}

	$label_counter = 0;
	foreach ($labels as $label)
	{
		$dbl->run("INSERT INTO `user_stats_charts_labels` SET `chart_id` = ?, `name` = ?, `grouping_id` = $grouping_id", array($new_chart_id, $label));

		// get the first id
		if ($label_counter == 0)
		{
			$label_counter++;

			$new_label_id = $dbl->new_id();
		}

		echo "Label $label added!<br />";
	}

	$set_label_id = $new_label_id;
	$total_dat = 0;
	// put in the data
	foreach ($data as $dat)
	{
		$dbl->run("INSERT INTO `user_stats_charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `grouping_id` = $grouping_id", array($new_chart_id, $set_label_id, $dat));
		$set_label_id++;
		$total_dat = $total_dat + $dat;
		echo "Data $dat added!<br />" . PHP_EOL;
	}

	$dbl->run("UPDATE `user_stats_charts` SET `total_answers` = $total_dat WHERE `id` = $new_chart_id");

	unset($data);
	unset($labels);
	unset($label);
	unset($users);
	unset($user);
	unset($dat);
}

/* specials */

// x11/wayland desktop environments
echo 'Generating Wayland Desktops' . PHP_EOL;

$desktops_session_wayland = $dbl->run("SELECT COUNT(*) as `data`, `desktop_environment` FROM `user_profile_info` WHERE `desktop_environment` IS NOT NULL AND `session_type` = 'wayland' AND `include_in_survey` = 1 GROUP BY desktop_environment")->fetch_all();

$dbl->run("INSERT INTO `user_stats_charts` SET `h_label` = ?, `name` = ?, `grouping_id` = $grouping_id", array('Percentage of users', 'Wayland Desktops'));
$wayland_chart_id = $dbl->new_id();

$total_wayland_data = 0;
foreach ($desktops_session_wayland as $wayland)
{
	$dbl->run("INSERT INTO `user_stats_charts_labels` SET `chart_id` = ?, `name` = ?, `grouping_id` = $grouping_id", array($wayland_chart_id, $wayland['desktop_environment']));
	$label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `user_stats_charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `grouping_id` = $grouping_id", array($wayland_chart_id, $label_id, $wayland['data']));

	$total_wayland_data = $total_wayland_data + $wayland['data'];
}

$dbl->run("UPDATE `user_stats_charts` SET `total_answers` = $total_wayland_data WHERE `id` = $wayland_chart_id");

echo 'Generating x11 Desktops' . PHP_EOL;
$desktops_session_x11 = $dbl->run("SELECT COUNT(*) as `data`, `desktop_environment` FROM `user_profile_info` WHERE `desktop_environment` IS NOT NULL AND `session_type` = 'x11' AND `include_in_survey` = 1 GROUP BY desktop_environment")->fetch_all();

$dbl->run("INSERT INTO `user_stats_charts` SET `h_label` = ?, `name` = ?, `grouping_id` = $grouping_id", array('Percentage of users', 'x11 Desktops'));
$x11_chart_id = $dbl->new_id();

$total_x11_data = 0;
foreach ($desktops_session_x11 as $x11)
{
	$dbl->run("INSERT INTO `user_stats_charts_labels` SET `chart_id` = ?, `name` = ?, `grouping_id` = $grouping_id", array($x11_chart_id, $x11['desktop_environment']));
	$label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `user_stats_charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `grouping_id` = $grouping_id", array($x11_chart_id, $label_id, $x11['data']));

	$total_x11_data = $total_x11_data + $x11['data'];
}

$dbl->run("UPDATE `user_stats_charts` SET `total_answers` = $total_x11_data WHERE `id` = $x11_chart_id");

$dbl->run("INSERT INTO `user_stats_grouping` SET `grouping_id` = ?", array($grouping_id));

echo PHP_EOL . 'Chart generation done.' . PHP_EOL;
?>