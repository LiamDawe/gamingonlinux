<?php
define('path', '/home/gamingonlinux/public_html/includes/');
//define('path', '/mnt/storage/public_html/includes/');
include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

// don't count people who haven't logged in for six months, need to test this more before putting it in
// last time i ran it, it already cut off a ton of people even though the system has only been around a few weeks, which wasn't right at all
$cutoff = 182*24*3600;
$last_login = $core->date - $cutoff;

// DISTRIBUTION CHOICE
$grab_users = $db->sqlquery("SELECT distro, count(*) as 'total' FROM users WHERE `distro` != '' AND `distro` != 'Not Listed' GROUP BY distro ORDER BY `total` DESC LIMIT 10");
$distro_choices = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'Linux Distributions'));

$new_chart_id = $db->grab_id();
foreach ($distro_choices as $distro)
{
		$labels[$distro['distro']] = $distro['distro'];
		$data[$distro['distro']] = $distro['total'];
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}

$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// CPU VENDOR CHOICE
$grab_users = $db->sqlquery("SELECT cpu_vendor, count(*) as 'total' FROM user_profile_info GROUP BY cpu_vendor ORDER BY `total` DESC");
$cpu_vendors = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'CPU Vendor'));

$new_chart_id = $db->grab_id();
foreach ($cpu_vendors as $cpu_vendor)
{
	if ($cpu_vendor['cpu_vendor'] != '')
	{
			$labels[] = $cpu_vendor['cpu_vendor'];
			$data[] = $cpu_vendor['total'];
	}
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// GPU VENDOR CHOICE
$grab_users = $db->sqlquery("SELECT gpu_vendor, count(*) as 'total' FROM user_profile_info GROUP BY gpu_vendor ORDER BY `total` DESC");
$gpu_vendors = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'GPU Vendor'));

$new_chart_id = $db->grab_id();
foreach ($gpu_vendors as $gpu_vendor)
{
	if ($gpu_vendor['gpu_vendor'] != '')
	{
			$labels[] = $gpu_vendor['gpu_vendor'];
			$data[] = $gpu_vendor['total'];
	}
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// GPU DRIVER CHOICE
$grab_users = $db->sqlquery("SELECT gpu_driver, count(*) as 'total' FROM user_profile_info GROUP BY gpu_driver ORDER BY `total` DESC");
$gpu_drivers = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'GPU Driver'));

$new_chart_id = $db->grab_id();
foreach ($gpu_drivers as $gpu_driver)
{
	if ($gpu_driver['gpu_driver'] != '')
	{
			$labels[] = $gpu_driver['gpu_driver'];
			$data[] = $gpu_driver['total'];
	}
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// GPU DRIVER CHOICE // NVIDIA ONLY
$grab_users = $db->sqlquery("SELECT gpu_driver, count(*) as 'total' FROM user_profile_info WHERE `gpu_vendor` = 'Nvidia' AND `gpu_driver` != '' AND `gpu_driver` IS NOT NULL GROUP BY gpu_driver ORDER BY `total` DESC");
$gpu_drivers_nvidia = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'GPU Driver (Nvidia)'));

$new_chart_id = $db->grab_id();
foreach ($gpu_drivers_nvidia as $gpu_drivern)
{
		$labels[] = $gpu_drivern['gpu_driver'];
		$data[] = $gpu_drivern['total'];
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// GPU DRIVER CHOICE // AMD ONLY
$grab_users = $db->sqlquery("SELECT gpu_driver, count(*) as 'total' FROM user_profile_info WHERE `gpu_vendor` = 'AMD' AND `gpu_driver` != '' AND `gpu_driver` IS NOT NULL GROUP BY gpu_driver ORDER BY `total` DESC");
$gpu_drivers_amd = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'GPU Driver (AMD)'));

$new_chart_id = $db->grab_id();
foreach ($gpu_drivers_amd as $gpu_drivera)
{
		$labels[] = $gpu_drivera['gpu_driver'];
		$data[] = $gpu_drivera['total'];
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// RAM
$grab_users = $db->sqlquery("SELECT ram_count, count(*) as 'total' FROM user_profile_info WHERE `ram_count` != '' GROUP BY ram_count ORDER BY `total` DESC LIMIT 10");
$ram_count = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'RAM'));

$new_chart_id = $db->grab_id();
foreach ($ram_count as $ram)
{
	if ($ram['ram_count'] != '')
	{
			$labels[] = $ram['ram_count'];
			$data[] = $ram['total'];
	}
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// MONITORS
$grab_users = $db->sqlquery("SELECT monitor_count, count(*) as 'total' FROM user_profile_info WHERE `monitor_count` != '' GROUP BY monitor_count ORDER BY `total` DESC LIMIT 10");
$monitor_count = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'Monitors'));

$new_chart_id = $db->grab_id();
foreach ($monitor_count as $monitors)
{
	if ($monitors['monitor_count'] != '')
	{
			$labels[] = $monitors['monitor_count'];
			$data[] = $monitors['total'];
	}
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

unset($data);

// MAIN MACHINE
$grab_users = $db->sqlquery("SELECT gaming_machine_type, count(*) as 'total' FROM user_profile_info WHERE `gaming_machine_type` != '' GROUP BY gaming_machine_type ORDER BY `total` DESC LIMIT 10");
$gaming_machine_type = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `h_label` = ?, `name` = ?, `user_stats_chart` = 1", array('Total Users', 'Main Gaming Machine'));

$new_chart_id = $db->grab_id();
foreach ($gaming_machine_type as $machine)
{
	if ($machine['gaming_machine_type'] != '')
	{
			$labels[] = $machine['gaming_machine_type'];
			$data[] = $machine['total'];
	}
}

$label_counter = 0;
foreach ($labels as $label)
{
	$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

	// get the first id
	if ($label_counter == 0)
	{
		$label_counter++;

		$new_label_id = $db->grab_id();
	}

	echo "Label $label added!<br />";
}
$set_label_id = $new_label_id;
// put in the data
foreach ($data as $dat)
{
	$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
	$set_label_id++;
	echo "Data $dat added!<br />";
}

echo "Generation Done";
