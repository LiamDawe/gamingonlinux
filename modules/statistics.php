<?php
include(core::config('path') . '/includes/profile_fields.php');

$grab_users = $db->sqlquery("SELECT distro, count(*) as 'total' FROM users GROUP BY distro ORDER BY `total` DESC");
$distro_choices = $db->fetch_all_rows();
$distro_list = '';
$total_not_set = 0;
$total_not_listed = 0;
$labels = array();
foreach ($distro_choices as $distro)
{
	if ($distro['distro'] != '' && $distro['distro'] != 'Not Listed')
	{
		$distro_list .= '<li>' . $distro['distro'] . ': ' . $distro['total'] . '</li>';
	}
	if ($distro['distro'] == '')
	{
		$total_not_set = $distro['total'];
	}
	if ($distro['distro'] == 'Not Listed')
	{
		$total_not_listed = $distro['total'];
	}

	require_once('./includes/SVGGraph/SVGGraph.php');

	$settings = array('graph_title' => 'Linux Distributions', 'auto_fit'=>true, 'pad_left' => 5, 'svg_class' => 'svggraph', 'minimum_units_y' => 1, 'grid_left' => 10, 'axis_text_position_v' => 'inside', 'show_grid_h' => false, 'label_h' => 'Total Users');
	$graph = new SVGGraph(400, 300, $settings);
	$colours = array(array('rgb(151,187,205):0.90','rgb(113,140,153):'), array('rgb(152,125,113):0.90','rgb(114,93,84)'));
	$graph->colours = $colours;

	if ($distro['distro'] != '' && $distro['distro'] != 'Not Listed')
	{
		$labels[$distro['distro']] = $distro['total'];
	}
}
print_r($labels);
$graph->Values($labels);
$get_graph = '<div style="width: 60%; height: 50%; margin: 0 auto; position: relative;">' . $graph->Fetch('HorizontalBarGraph', false) . '</div>';
echo $get_graph;
$templating->load('statistics');
$templating->block('distribution');
$templating->set('distro_list', $distro_list);
$templating->set('not_set', $total_not_set);
$templating->set('not_listed', $total_not_listed);
