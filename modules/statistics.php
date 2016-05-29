<?php
$templating->set_previous('title', 'User stats', 1);
$templating->set_previous('meta_description', 'Statistics generated from the users of the GamingOnLinux website', 1);

include(core::config('path') . '/includes/profile_fields.php');
require_once(core::config('path') . '/includes/SVGGraph/SVGGraph.php');

$templating->load('statistics');

// TOTAL USERS
$templating->block('users');
$templating->set('total_users', core::config('total_users'));

// DISTRIBUTION CHOICE
$grab_users = $db->sqlquery("SELECT distro, count(*) as 'total' FROM users GROUP BY distro ORDER BY `total` DESC");
$distro_choices = $db->fetch_all_rows();
$total_not_set = 0;
$total_not_listed = 0;
$labels = array();
foreach ($distro_choices as $distro)
{
	if ($distro['distro'] == '')
	{
		$total_not_set = $distro['total'];
	}
	if ($distro['distro'] == 'Not Listed')
	{
		$total_not_listed = $distro['total'];
	}

	$settings = array('minimum_grid_spacing_h'=> 20, 'bar_width_min'=> 10, 'graph_title' => 'Linux Distributions', 'auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside', 'show_grid_h' => false, 'label_h' => 'Total Users');
	$graph = new SVGGraph(400, 300, $settings);
	$colours = array(array('rgb(151,187,205):0.90','rgb(113,140,153):'), array('rgb(152,125,113):0.90','rgb(114,93,84)'));
	$graph->colours = $colours;

	$graph_counter == 0;
	if ($distro['distro'] != '' && $distro['distro'] != 'Not Listed')
	{
		$graph_counter++;
		if ($graph_counter <= 10)
		{
			$labels[$distro['distro']] = $distro['total'];
		}
	}
}
$graph->Values($labels);
$get_graph = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';

$templating->block('distribution');

$templating->set('not_set', $total_not_set);
$templating->set('not_listed', $total_not_listed);
$templating->set('graph', $get_graph);

// CPU VENDOR
$cpu_total_not_set = 0;
$cpu_not_set = 0;
$cpu_null = 0;
$cpu_labels = array();
$db->sqlquery("SELECT `cpu_vendor`, count(*) as `total` FROM `user_profile_info` GROUP BY `cpu_vendor` ORDER BY `total` DESC");
while ($cpu_vendor = $db->fetch())
{
	if ($cpu_vendor['cpu_vendor'] == '')
	{
		$cpu_not_set = $cpu_vendor['total'];
	}
	if ($cpu_vendor['cpu_vendor'] == NULL)
	{
		$cpu_null = $cpu_vendor['total'];
	}

	if ($cpu_vendor['cpu_vendor'] != '' && $cpu_vendor['cpu_vendor'] != NULL)
	{
			$cpu_labels[$cpu_vendor['cpu_vendor']] = $cpu_vendor['total'];
	}

	$settings = array('minimum_grid_spacing_h'=> 20, 'bar_width_min'=> 10, 'graph_title' => 'Linux Distributions', 'auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside', 'show_grid_h' => false, 'label_h' => 'Total Users');
	$graph = new SVGGraph(400, 300, $settings);
	$colours = array(array('rgb(151,187,205):0.90','rgb(113,140,153):'), array('rgb(152,125,113):0.90','rgb(114,93,84)'));
	$graph->colours = $colours;
}
$graph->Values($cpu_labels);
$cpu_vendor_graph = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';

$templating->block('cpu_vendor');
$templating->set('cpu_vendor_graph', $cpu_vendor_graph);
$cpu_total_not_set = $cpu_null+$cpu_not_set;
$templating->set('cpu_total_not_set', $cpu_total_not_set);
