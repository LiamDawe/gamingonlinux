<?php
define('path', '/home/gamingonlinux/public_html/includes/');
//define('path', '/mnt/storage/public_html/includes/');
include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

// DISTRIBUTION CHOICE
$grab_users = $db->sqlquery("SELECT distro, count(*) as 'total' FROM users GROUP BY distro ORDER BY `total` DESC");
$distro_choices = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?", array(1, 'Total Users', 'Linux Distributions'));

$new_chart_id = $db->grab_id();
$graph_counter = 0;
foreach ($distro_choices as $distro)
{
	if ($distro['distro'] != '' && $distro['distro'] != 'Not Listed')
	{
		$graph_counter++;
		if ($graph_counter <= 10)
		{
			$labels[$distro['distro']] = $distro['distro'];
			$data[$distro['distro']] = $distro['total'];
		}
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

// CPU VENDOR CHOICE
$grab_users = $db->sqlquery("SELECT cpu_vendor, count(*) as 'total' FROM user_profile_info GROUP BY cpu_vendor ORDER BY `total` DESC");
$cpu_vendors = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?", array(1, 'Total Users', 'CPU Vendor'));

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

$db->sqlquery("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?", array(1, 'Total Users', 'GPU Vendor'));

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

$db->sqlquery("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?", array(1, 'Total Users', 'GPU Driver'));

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

// RAM
$grab_users = $db->sqlquery("SELECT ram_count, count(*) as 'total' FROM user_profile_info WHERE `ram_count` != '' GROUP BY ram_count ORDER BY `total` DESC LIMIT 10");
$ram_count = $db->fetch_all_rows();
$labels = array();

$db->sqlquery("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?", array(1, 'Total Users', 'RAM'));

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

echo "Generation Done";
