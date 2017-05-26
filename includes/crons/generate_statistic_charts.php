<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

// don't count people who haven't logged in for six months, need to test this more before putting it in
// last time i ran it, it already cut off a ton of people even though the system has only been around a few weeks, which wasn't right at all
// I THINK it's because I forgot to even include the class_core which I do now, so it might actually work now
$cutoff = 182*24*3600;
$last_login = core::$date - $cutoff;

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

$users = $dbl->run("SELECT u.`distro`, count(*) as 'total', d.`arch-based`, d.`ubuntu-based` FROM `users` u INNER JOIN `distributions` d ON u.distro = d.name WHERE u.`distro` != '' AND u.`distro` != 'Not Listed' AND u.`distro` IS NOT NULL GROUP BY u.`distro`, d.`arch-based`, d.`ubuntu-based` ORDER BY `total` DESC")->fetch_all();

$dbl->run("INSERT INTO `user_stats_charts` SET `h_label` = ?, `name` = ?, `grouping_id` = $grouping_id", array('Total Users', 'Linux Distributions (Combined)'));
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
array ("name" => "Linux Distributions (Split)", "db_field" => "distro", "table" => 'users'),
array ("name" => "Desktop Environment", "db_field" => "desktop_environment"),
array ("name" => "Distro Architecture", "db_field" => "what_bits"),
array ("name" => "Dual Booting", "db_field" => "dual_boot"),
array ("name" => "CPU Vendor", "db_field" => "cpu_vendor"),
array ("name" => "GPU Vendor", "db_field" => "gpu_vendor"),
array ("name" => "GPU Driver", "db_field" => "gpu_driver"),
array ("name" => "GPU Driver (Nvidia)", "db_field" => "gpu_driver", "gpu_vendor" => "Nvidia"),
array ("name" => "GPU Driver (AMD)", "db_field" => "gpu_driver", "gpu_vendor" => "AMD"),
array ("name" => "RAM", "db_field" => "ram_count"),
array ("name" => "Monitors", "db_field" => "monitor_count"),
array ("name" => "Resolution", "db_field" => "resolution"),
array ("name" => "Main Gaming Machine", "db_field" => "gaming_machine_type"),
array ("name" => "Main Gamepad", "db_field" => "gamepad")
);

foreach ($charts as $chart)
{
	$table = 'user_profile_info';
	if (isset($chart['table']))
	{
		$table = $chart['table'];
	}

	$sql_vendor = '';
	if (isset($chart['gpu_vendor']))
	{
		$sql_vendor = "`gpu_vendor` = '{$chart['gpu_vendor']}' AND";
	}

	$users = $dbl->run("SELECT {$chart['db_field']}, count(*) as 'total' FROM $table WHERE $sql_vendor `{$chart['db_field']}` != '' AND `{$chart['db_field']}` != 'Not Listed' AND `{$chart['db_field']}` IS NOT NULL GROUP BY {$chart['db_field']} ORDER BY `total` DESC")->fetch_all();
	$labels = array();
	$data = array();

	$dbl->run("INSERT INTO `user_stats_charts` SET `h_label` = ?, `name` = ?, `grouping_id` = $grouping_id", array('Total Users', $chart['name']));
	$new_chart_id = $dbl->new_id();
	
	foreach ($users as $user)
	{
			$labels[] = $user[$chart['db_field']];
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
		echo "Data $dat added!<br />";
	}

	$dbl->run("UPDATE `user_stats_charts` SET `total_answers` = $total_dat WHERE `id` = $new_chart_id");

	unset($data);
	unset($labels);
	unset($label);
	unset($users);
	unset($user);
	unset($dat);
}

$dbl->run("INSERT INTO `user_stats_grouping` SET `grouping_id` = ?", array($grouping_id));
