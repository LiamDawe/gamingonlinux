<?php
$templating->set_previous('title', 'User stats', 1);
$templating->set_previous('meta_description', 'Statistics generated from the users of the GamingOnLinux website', 1);

include(core::config('path') . '/includes/profile_fields.php');

$templating->load('statistics');

// TOTAL USERS
$templating->block('users');
$templating->set('total_users', core::config('total_users'));

// DISTRIBUTION CHOICE
$db->sqlquery("SELECT `id` FROM `charts` WHERE `name` = 'Linux Distributions' ORDER BY `id` DESC LIMIT 1");
$get_distro_chart = $db->fetch();

$distro_chart = $core->stat_chart($get_distro_chart['id']);

$templating->block('info');
$templating->set('date', $distro_chart['date']);

$templating->block('distribution');
$templating->set('graph', $distro_chart['graph']);

// CPU VENDOR CHOICE
$db->sqlquery("SELECT `id` FROM `charts` WHERE `name` = 'CPU Venfor' ORDER BY `id` DESC LIMIT 1");
$get_cpu_chart = $db->fetch();

$cpu_chart = $core->stat_chart($get_cpu_chart['id']);

$templating->block('cpu_vendor');
$templating->set('graph', $cpu_chart['graph']);

/*
// GPU VENDOR
$gpu_total_not_set = 0;
$gpu_not_set = 0;
$gpu_null = 0;
$gpu_labels = array();
$db->sqlquery("SELECT `gpu_vendor`, count(*) as `total` FROM `user_profile_info` GROUP BY `gpu_vendor` ORDER BY `total` DESC");
while ($gpu_vendor = $db->fetch())
{
	if ($gpu_vendor['gpu_vendor'] == '')
	{
		$gpu_not_set = $gpu_vendor['total'];
	}
	if ($gpu_vendor['gpu_vendor'] === NULL)
	{
		$gpu_null = $gpu_vendor['total'];
	}

	if ($gpu_vendor['gpu_vendor'] != '' && $gpu_vendor['gpu_vendor'] != NULL)
	{
			$gpu_labels[$gpu_vendor['gpu_vendor']] = $gpu_vendor['total'];
	}

	$settings = array('minimum_grid_spacing_h'=> 20, 'bar_width_min'=> 10, 'graph_title' => 'GPU Vendor', 'auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside', 'show_grid_h' => false, 'label_h' => 'Total Users');
	$graph = new SVGGraph(400, 300, $settings);
	$colours = array(array('rgb(151,187,205):0.90','rgb(113,140,153):'), array('rgb(152,125,113):0.90','rgb(114,93,84)'));
	$graph->colours = $colours;
}
$graph->Values($gpu_labels);
$gpu_vendor_graph = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';

$templating->block('gpu_vendor');
$templating->set('gpu_vendor_graph', $gpu_vendor_graph);
$gpu_total_not_set = $gpu_null+$gpu_not_set;
$templating->set('gpu_total_not_set', $gpu_total_not_set);
*/
