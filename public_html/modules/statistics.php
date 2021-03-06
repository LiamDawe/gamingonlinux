<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'User stats', 1);
$templating->set_previous('meta_description', 'Statistics generated from the users of the GamingOnLinux website', 1);

//include($core->config('path') . '/includes/profile_fields.php'); not sure why this is in there?

$templating->load('statistics');

// TOTAL USERS
$templating->block('top', 'statistics');
$templating->set('total_users', $core->config('total_users'));

$status_text = '';
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$included = $dbl->run("SELECT `include_in_survey` FROM `user_profile_info` WHERE `user_id` = ?", array((int) $_SESSION['user_id']))->fetchOne();
	if (isset($included))
	{
		if ($included == 0)
		{
			$status_text = '<br /><br />Your profile is currently set to <u>NOT</u> be included. You can <a href="/usercp.php?module=pcinfo">change this here</a> any time.<br />';
		}
	}
}
$templating->set('status', $status_text);

$charts_list = array(
	array("name" => "Linux Distributions (Combined)", "bundle_outside_top10" => 1),
	array("name" => "Linux Distributions (Split)", "bundle_outside_top10" => 1),
	array("name" => "Desktop Environment", "bundle_outside_top10" => 1),
	array("name" => "Dual Booting", "bundle_outside_top10" => 0),
	array("name" => "RAM", "bundle_outside_top10" => 1),
	array("name" => "CPU Vendor", "bundle_outside_top10" => 0),
	array("name" => "GPU Vendor", "bundle_outside_top10" => 0),
	array("name" => "GPU Model", "bundle_outside_top10" => 0),
	array("name" => "GPU Driver", "order" => "drivers", "bundle_outside_top10" => 0),
	array("name" => "GPU Driver (Nvidia)", "order" => "drivers", "bundle_outside_top10" => 0),
	array("name" => "GPU Driver (AMD)", "order" => "drivers", "bundle_outside_top10" => 0),
	array("name" => "Number of monitors", "bundle_outside_top10" => 0),
	array("name" => "Resolution", "bundle_outside_top10" => 1),
	array("name" => "Main Gaming Machine", "bundle_outside_top10" => 0),
	array("name" => "Main Gamepad", "bundle_outside_top10" => 1),
	array("name" => "PC VR Headset", "bundle_outside_top10" => 1),
	array("name" => "Session Type", "bundle_outside_top10" => 0),
	array("name" => "Wayland Desktops", "bundle_outside_top10" => 0),
	array("name" => "x11 Desktops", "bundle_outside_top10" => 0)
);

if (!isset($_GET['view']) || isset($_GET['view']) && $_GET['view'] == 'monthly')
{
	if (isset($_GET['picker']) && is_numeric($_GET['picker']))
	{
		header("Location: ".url."users/statistics/statid=".$_GET['picker']);
		die();
	}
	$templating->block('monthly_top');
	$options = '';
	$query_list = $dbl->run("SELECT `grouping_id`, `generated_date` FROM `user_stats_grouping` ORDER BY `grouping_id` DESC LIMIT 24")->fetch_all();
	foreach ($query_list as $get_list)
	{
		$selected = '';
		if (isset($_GET['statid']) && is_numeric($_GET['statid']) && $_GET['statid'] == $get_list['grouping_id'])
		{
			$selected = 'selected';
		}
		$options .= '<option value="' . $get_list['grouping_id'] . '" ' . $selected . '>'.$get_list['generated_date'].'</option>';
	}
	$templating->block('picker');
	$templating->set('options', $options);

	$counter = 0;

	if (isset($_GET['statid']) && is_numeric($_GET['statid']))
	{
		$grouping_id = core::make_safe($_GET['statid']);
	}
	else
	{
		$grouping_id = $dbl->run("SELECT `grouping_id` FROM user_stats_grouping ORDER BY `grouping_id` DESC LIMIT 1")->fetchOne();
	}

	$get_charts_info = $dbl->run("SELECT `name`, `id`, `grouping_id`, `total_answers`, `bundle_outside_top10` FROM `user_stats_charts` WHERE `grouping_id` = ? ORDER BY `id` ASC", array($grouping_id))->fetch_all(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

	foreach($get_charts_info as $name => $chart)
	{
		if ($chart['total_answers'] > 0)
		{
			$previous_group = $dbl->run("SELECT `grouping_id` FROM `user_stats_charts` WHERE `grouping_id` < ? ORDER BY `id` DESC LIMIT 1", array($chart['grouping_id']))->fetch();

			$get_last_chart_id = $dbl->run("SELECT `id` FROM `user_stats_charts` WHERE `name` = ? AND `grouping_id` = ? ORDER BY `id` DESC LIMIT 1", array($name, $previous_group['grouping_id']))->fetchOne();

			$charts = new charts($dbl);

			$options = ['padding_right' => 70, 'show_top_10' => 1, 'bundle_outside_top10' => $chart['bundle_outside_top10']];

			if (isset($chart['id']))
			{
				$grab_chart = $charts->stat_chart($chart['id'], $get_last_chart_id, $options);

				// only do this once
				if ($counter == 0)
				{
					$templating->block('info', 'statistics');
					$templating->set('date', $grab_chart['date']);
				}

				$templating->block('chart_section', 'statistics');
				$chart_id_link = str_replace(' ', '', $name); // Replaces all spaces with hyphens.
				$chart_id_link = preg_replace('/[^A-Za-z0-9\-]/', '', $chart_id_link); // Removes special chars.
				$templating->set('title_id', $chart_id_link); 
				$templating->set('title', $name);
				$templating->set('graph', $grab_chart['graph']);
				$download_link = '';
				if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
				{
					$download_link = '<div style="text-align: center;"><em>Download Graph: (<a href="/render_chart.php?id='.$chart['id'].'&type=stats&download">SVG</a>)</em> | <a href="/render_chart.php?id='.$chart['id'].'&type=stats">Graph Link</a></div>';
				}
				$templating->set('download_link', $download_link);
				$templating->set('total_users', $grab_chart['total_users_answered']);
				$templating->set('full_info', $grab_chart['full_info']);
				$counter++;
			}
		}
	}
	$templating->block('monthly_bottom', 'statistics');
}
if (isset($_GET['view']) && $_GET['view'] == 'trends')
{
	// trends charts
	$templating->block('trends_top');
	foreach ($charts_list as $chart)
	{
		$order = '';
		if (isset($chart['order']))
		{
			$order = $chart['order'];
		}

		$charts = new charts($dbl);
		$grab_chart = $charts->trends_charts($chart['name'], $order);

		$chart_id_link = str_replace(' ', '', $chart['name']); // Replaces all spaces with hyphens.
		$chart_id_link = preg_replace('/[^A-Za-z0-9\-]/', '', $chart_id_link); // Removes special chars.

		$templating->block('trend_chart');
		if (isset($grab_chart['graph']))
		{
			$templating->set('title', $chart['name']);
			$templating->set('title_id', $chart_id_link); 
			$templating->set('graph', '<div style="text-align:center; width: 100%;">' . $grab_chart['graph'] . '</div>');
		}
		else
		{
			$templating->set('title', $chart['name']);
			$templating->set('title_id', $chart_id_link); 
			$templating->set('graph', 'Chart not generated yet.');
		}	
	}
	$templating->block('trends_bottom', 'statistics');
}
