<?php
define('path', '/home/gamingonlinux/public_html/includes/');
//define('path', '/mnt/storage/public_html/includes/');
include(path . 'config.php');

include(path . 'class_core.php');

$core = new core();

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

// don't count people who haven't logged in for six months, need to test this more before putting it in
// last time i ran it, it already cut off a ton of people even though the system has only been around a few weeks, which wasn't right at all
// I THINK it's because I forgot to even include the class_core which I do now, so it might actually work now
$cutoff = 182*24*3600;
$last_login = $core->date - $cutoff;

// get the last grouping_id, this is how we group together each new generation for easy edits and deletions
$db->sqlquery("SELECT `grouping_id` FROM `user_stats_charts` ORDER BY `id` DESC LIMIT 1");
if ($db->num_rows() == 0)
{
  $grouping_id = 1;
}
else
{
    $get_grouping_id = $db->fetch();
    $grouping_id = $get_grouping_id['grouping_id'] + 1;
}

$charts = array (
  array ("name" => "Linux Distributions", "db_field" => "distro", "table" => 'users'),
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
	array ("name" => "Main Gaming Machine", "db_field" => "gaming_machine_type")
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

	$grab_users = $db->sqlquery("SELECT {$chart['db_field']}, count(*) as 'total' FROM $table WHERE $sql_vendor `{$chart['db_field']}` != '' AND `{$chart['db_field']}` != 'Not Listed' AND `{$chart['db_field']}` IS NOT NULL GROUP BY {$chart['db_field']} ORDER BY `total` DESC");
	$users = $db->fetch_all_rows();
	$labels = array();
	$data = array();

	$db->sqlquery("INSERT INTO `user_stats_charts` SET `h_label` = ?, `name` = ?, `grouping_id` = $grouping_id", array('Total Users', $chart['name']));

	$new_chart_id = $db->grab_id();
	foreach ($users as $user)
	{
			$labels[] = $user[$chart['db_field']];
			$data[] = $user['total'];
	}

	$label_counter = 0;
	foreach ($labels as $label)
	{
		$db->sqlquery("INSERT INTO `user_stats_charts_labels` SET `chart_id` = ?, `name` = ?, `grouping_id` = $grouping_id", array($new_chart_id, $label));

		// get the first id
		if ($label_counter == 0)
		{
			$label_counter++;

			$new_label_id = $db->grab_id();
		}

		echo "Label $label added!<br />";
	}

	$set_label_id = $new_label_id;
  $total_dat = 0;
	// put in the data
	foreach ($data as $dat)
	{
		$db->sqlquery("INSERT INTO `user_stats_charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `grouping_id` = $grouping_id", array($new_chart_id, $set_label_id, $dat));
		$set_label_id++;
    $total_dat = $total_dat + $dat;
		echo "Data $dat added!<br />";
	}

  $db->sqlquery("UPDATE `user_stats_charts` SET `total_answers` = $total_dat WHERE `id` = $new_chart_id");

	unset($data);
	unset($labels);
	unset($label);
	unset($users);
	unset($user);
  unset($dat);
}

echo "Generation Done";
