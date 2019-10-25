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

$charts_list = array(
	array("name" => "Linux Distributions (Combined)", "bundle_outside_top10" => 1),
	array("name" => "Linux Distributions (Split)", "bundle_outside_top10" => 1),
	array("name" => "Desktop Environment", "bundle_outside_top10" => 1),
	array("name" => "Distro Architecture", "bundle_outside_top10" => 0),
	array("name" => "Dual Booting", "bundle_outside_top10" => 0),
	array("name" => "Steam Play Use", "bundle_outside_top10" => 0),
	array("name" => "Wine Use", "bundle_outside_top10" => 0),
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
	array("name" => "PC VR Headset", "bundle_outside_top10" => 1)
);

if (!isset($_GET['view']) || isset($_GET['view']) && $_GET['view'] == 'monthly')
{
	$templating->block('monthly_top');
	$options = '';
	$query_list = $dbl->run("SELECT `grouping_id`, `generated_date` FROM `user_stats_grouping` ORDER BY `grouping_id` DESC LIMIT 24")->fetch_all();
	foreach ($query_list as $get_list)
	{
		$selected = '';
		if (isset($_POST['picker']) && is_numeric($_POST['picker']) && $_POST['picker'] == $get_list['grouping_id'])
		{
			$selected = 'selected';
		}
		$options .= '<option value="' . $get_list['grouping_id'] . '" ' . $selected . '>'.$get_list['generated_date'].'</option>';
	}
	$templating->block('picker');
	$templating->set('options', $options);

	$counter = 0;

	if (isset($_POST['picker']) && is_numeric($_POST['picker']))
	{
		$grouping_id = core::make_safe($_POST['picker']);
	}
	else
	{
		$grouping_id = $dbl->run("SELECT grouping_id FROM user_stats_grouping ORDER BY `grouping_id` DESC LIMIT 1")->fetchOne();
	}

	// Check if the cached file is still fresh. If it is, just pull it in and that's it done
	if ($filecache->check_cache('user_statistics_'.$grouping_id, 86400)) // 1 day cache
	{
		$templating->get_cache('user_statistics_'.$grouping_id);
	}
	else
	{
		echo $templating->output();

		$filecache->init();

		foreach($charts_list as $chart)
		{
			$get_chart_id = $dbl->run("SELECT `id`, `grouping_id`,`name`, `h_label`, `total_answers` FROM `user_stats_charts` WHERE `name` = ? AND `grouping_id` = ? ORDER BY `id` DESC LIMIT 1", array($chart['name'], $grouping_id))->fetch();

			if ($get_chart_id['total_answers'] > 0)
			{
				$previous_group = $dbl->run("SELECT `grouping_id` FROM `user_stats_charts` WHERE `grouping_id` < ? ORDER BY `id` DESC LIMIT 1", array($get_chart_id['grouping_id']))->fetch();

				$get_last_chart_id = $dbl->run("SELECT `id` FROM `user_stats_charts` WHERE `name` = ? AND `grouping_id` = ? ORDER BY `id` DESC LIMIT 1", array($chart['name'], $previous_group['grouping_id']))->fetchOne();

				$charts = new charts($dbl);

				$options = ['padding_right' => 70, 'show_top_10' => 1, 'bundle_outside_top10' => $chart['bundle_outside_top10']];

				if (isset($get_chart_id['id']))
				{
					$grab_chart = $charts->stat_chart($get_chart_id['id'], $get_last_chart_id, $options);

					// only do this once
					if ($counter == 0)
					{
						$templating->block('info', 'statistics');
						$templating->set('date', $grab_chart['date']);
					}

					$templating->block('chart_section', 'statistics');
					$templating->set('title', $chart['name']);
					$templating->set('graph', $grab_chart['graph']);
					$download_link = '';
					if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
					{
						$download_link = '<div style="text-align: center;"><em>Download Graph: (<a href="/render_chart.php?id='.$get_chart_id['id'].'&type=stats&download">SVG</a>)</em> | <a href="/render_chart.php?id='.$get_chart_id['id'].'&type=stats">Graph Link</a></div>';
					}
					$templating->set('download_link', $download_link);
					$templating->set('total_users', $grab_chart['total_users_answered']);
					$templating->set('full_info', $grab_chart['full_info']);
					$counter++;
				}
			}
		}
			$templating->block('monthly_bottom', 'statistics');

		echo $templating->output();
		$filecache->write('user_statistics_'.$grouping_id);
	}
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

		$templating->block('trend_chart');
		$templating->set('title', $chart['name']);
		$templating->set('graph', '<div style="text-align:center; width: 100%;">' . $grab_chart['graph'] . '</div>');
	}
	$templating->block('trends_bottom', 'statistics');
}
