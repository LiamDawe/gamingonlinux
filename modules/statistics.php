<?php
$templating->set_previous('title', 'User stats', 1);
$templating->set_previous('meta_description', 'Statistics generated from the users of the GamingOnLinux website', 1);

include(core::config('path') . '/includes/profile_fields.php');

$templating->load('statistics');

// TOTAL USERS
$templating->block('users');
$templating->set('total_users', core::config('total_users'));

$options = '';
$db->sqlquery("SELECT `grouping_id`, `generated_date` FROM `user_stats_grouping` ORDER BY `grouping_id` DESC LIMIT 12");
while ($get_list = $db->fetch())
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

$charts = array(
  array("name" => "Linux Distributions (Combined)"),
  array("name" => "Linux Distributions (Split)"),
  array("name" => "Desktop Environment"),
  array("name" => "Distro Architecture"),
  array("name" => "Dual Booting"),
  array("name" => "CPU Vendor"),
  array("name" => "GPU Vendor"),
  array("name" => "GPU Driver", "order" => "drivers"),
  array("name" => "GPU Driver (Nvidia)", "order" => "drivers"),
  array("name" => "GPU Driver (AMD)", "order" => "drivers"),
  array("name" => "RAM"),
  array("name" => "Monitors"),
  array("name" => "Resolution"),
  array("name" => "Main Gaming Machine"),
  array("name" => "Main Gamepad")
);

$counter = 0;

foreach($charts as $chart)
{
  if (isset($_POST['picker']) && is_numeric($_POST['picker']))
  {
    $grouping_id = $_POST['picker'];
  }
  else
  {
    $db->sqlquery("SELECT grouping_id FROM user_stats_grouping ORDER BY `grouping_id` DESC LIMIT 1");
    $default_grouping = $db->fetch();
    $grouping_id = $default_grouping['grouping_id'];
  }

  $db->sqlquery("SELECT `id`, `grouping_id`,`name` FROM `user_stats_charts` WHERE `name` = '{$chart['name']}' AND `grouping_id` = ? ORDER BY `id` DESC LIMIT 1", array($grouping_id));
  $get_chart_id = $db->fetch();

  $db->sqlquery("SELECT `grouping_id` FROM `user_stats_charts` WHERE `grouping_id` < ? ORDER BY `id` DESC LIMIT 1", array($get_chart_id['grouping_id']));
  $previous_group = $db->fetch();

  $db->sqlquery("SELECT `id` FROM `user_stats_charts` WHERE `name` = '{$chart['name']}' AND `grouping_id` = ? ORDER BY `id` DESC LIMIT 1", array($previous_group['grouping_id']));
  $get_last_chart_id = $db->fetch();

  $order = '';
  if (isset($chart['order']))
  {
    $order = $chart['order'];
  }

  $grab_chart = $core->stat_chart($get_chart_id['id'], $order, $get_last_chart_id['id']);

  // only do this once
  if ($counter == 0)
  {
    $templating->block('info');
    $templating->set('date', $grab_chart['date']);
  }

  $templating->block('chart_section');
  $templating->set('title', $chart['name']);
  $templating->set('graph', $grab_chart['graph']);
  $download_link = '';
  if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
  {
    $download_link = '<div style="text-align: center;"><em>Download Graph: (<a href="/download-graph.php?id='.$get_chart_id['id'].'">SVG</a>)</em></div>';
  }
  $templating->set('download_link', $download_link);
  $templating->set('total_users', $grab_chart['total_users_answered']);
  $templating->set('full_info', $grab_chart['full_info']);
  $counter++;
}
